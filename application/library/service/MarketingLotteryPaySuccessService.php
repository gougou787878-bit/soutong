<?php

namespace service;

use Illuminate\Database\QueryException;

/**
 * 支付成功触发：为「触发情景=支付成功后」的营销活动写入参与机会（marketing_lottery_play）。
 *
 * activity.config 可选 JSON 字段：
 * - min_amount (number): 最小支付金额（元），不满足则不发放
 * - amount_stat_type (string|int): 计数方式
 *   - multiple: 按倍数计数（floor(pay / min_amount) 条）
 *   - once: 一次计数（pay >= min_amount 则 1 条）
 * - step_amount (number): 当 amount_stat_type=multiple 时，按此金额（元）计算倍数=发放次数；不配置则发放 1 次
 *
 * 活动上 daily_limit / per_user_limit / total_limit：0 表示不限。
 */
class MarketingLotteryPaySuccessService
{
    private const CONFIG_MIN_AMOUNT = 'min_amount';

    private const CONFIG_AMOUNT_STAT_TYPE = 'amount_stat_type';

    private const AMOUNT_STAT_MULTIPLE = 'multiple';

    private const AMOUNT_STAT_ONCE = 'once';

    private const CONFIG_STEP_AMOUNT = 'step_amount';

    public function GrantPlaysForPaySuccess(array $payload): void
    {
        $traceId = (string) ($payload['trace_id'] ?? '');
        if ($traceId === '') {
            $traceId = 'ml_pay_success:' . date('YmdHis') . ':' . substr(bin2hex(random_bytes(8)), 0, 16);
            $payload['trace_id'] = $traceId;
        }

        $orderId = (string) ($payload['order_id'] ?? '');
        $uid = (int) ($payload['uid'] ?? 0);
        if ($orderId === '' || $uid <= 0) {
            $this->DebugLog($payload, 'skip_invalid_payload', [
                'order_id' => $orderId,
                'uid' => $uid,
            ]);
            return;
        }

        $payYuan = $this->ParsePayMoneyYuan($payload['pay_money_yuan'] ?? null);
        $source = (string) ($payload['source'] ?? '');
        $this->DebugLog($payload, 'start', [
            'order_id' => $orderId,
            'uid' => $uid,
            'source' => $source,
            'pay_yuan' => $payYuan,
        ]);

        $activities = \MarketingLotteryActivityModel::queryActiveForTriggerScenario(
            \MarketingLotteryActivityModel::TRIGGER_SCENARIO_PAY_SUCCESS
        );
        if ($activities->isEmpty()) {
            $this->DebugLog($payload, 'skip_no_active_activities', [
                'order_id' => $orderId,
                'uid' => $uid,
            ]);
            return;
        }
        $this->DebugLog($payload, 'activities_loaded', [
            'activities_cnt' => method_exists($activities, 'count') ? (int) $activities->count() : null,
        ]);

        foreach ($activities as $activity) {
            $this->GrantForOneActivity($activity, $payload, $orderId, $uid, $payYuan, $source);
        }

        $this->DebugLog($payload, 'done', [
            'order_id' => $orderId,
            'uid' => $uid,
        ]);
    }

    private function GrantForOneActivity(
        \MarketingLotteryActivityModel $activity,
        array $payload,
        string $orderId,
        int $uid,
        float $payYuan,
        string $source
    ): void {
        try {
            \DB::transaction(function () use ($activity, $uid, $payload, $orderId, $source, $payYuan): void {
                
                $locked = \MarketingLotteryActivityModel::query()
                    ->whereKey($activity->id)
                    ->lockForUpdate()
                    ->first();
                if ($locked === null || (int) $locked->status !== \MarketingLotteryActivityModel::STATUS_ON) {
                    $this->DebugLog($payload, 'skip_activity_locked_or_off', [
                        'activity_id' => (int) ($activity->id ?? 0),
                        'locked_exists' => $locked !== null,
                        'locked_status' => $locked !== null ? (int) $locked->status : null,
                    ]);
                    return;
                }
                $createdDay = date('Y-m-d');

                // 1) 每人每日活动抽奖名额上限
                $userDailyRemaining = $this->ComputeUserDailyRemaining($locked, $uid, $createdDay);
                if ($userDailyRemaining <= 0) {
                    $this->DebugLog($payload, 'skip_user_daily_limit', [
                        'activity_id' => (int) $locked->id,
                        'user_daily_remaining' => $userDailyRemaining,
                        'created_day' => $createdDay,
                    ]);
                    return;
                }

                // 2) 活动每日发送上限
                $activityDailyRemaining = $this->ComputeActivityDailyRemaining($locked, $createdDay);
                if ($activityDailyRemaining <= 0) {
                    $this->DebugLog($payload, 'skip_activity_daily_send_limit', [
                        'activity_id' => (int) $locked->id,
                        'activity_daily_remaining' => $activityDailyRemaining,
                        'created_day' => $createdDay,
                    ]);
                    return;
                }

                // 3) 活动总上限
                $activityTotalRemaining = $this->ComputeActivityTotalRemaining($locked);
                if ($activityTotalRemaining <= 0) {
                    $this->DebugLog($payload, 'skip_activity_total_limit', [
                        'activity_id' => (int) $locked->id,
                        'activity_total_remaining' => $activityTotalRemaining,
                    ]);
                    return;
                }

                // 每人活动期内总上限
                $userTotalRemaining = $this->ComputeUserTotalRemaining($locked, $uid);
                if ($userTotalRemaining <= 0) {
                    $this->DebugLog($payload, 'skip_user_total_limit', [
                        'activity_id' => (int) $locked->id,
                        'user_total_remaining' => $userTotalRemaining,
                    ]);
                    return;
                }

                // 4) config：计算应奖励几次（默认 1；不满足条件返回 0）
                $cfg = $locked->config;
                if (!is_array($cfg)) {
                    $cfg = [];
                }
                $desiredPlays = $this->ComputeDesiredPlays($cfg, $payYuan);
                if ($desiredPlays <= 0) {
                    $this->DebugLog($payload, 'skip_not_meet_min_amount', [
                        'activity_id' => (int) $locked->id,
                        'desired_plays' => $desiredPlays,
                        'pay_yuan' => $payYuan,
                        'config' => $cfg,
                    ]);
                    return;
                }

                $quota = min($desiredPlays, $userDailyRemaining, $activityDailyRemaining, $activityTotalRemaining, $userTotalRemaining);
                if ($quota <= 0) {
                    $this->DebugLog($payload, 'skip_quota_zero', [
                        'activity_id' => (int) $locked->id,
                        'desired_plays' => $desiredPlays,
                        'user_daily_remaining' => $userDailyRemaining,
                        'activity_daily_remaining' => $activityDailyRemaining,
                        'activity_total_remaining' => $activityTotalRemaining,
                        'user_total_remaining' => $userTotalRemaining,
                        'quota' => $quota,
                    ]);
                    return;
                }

                $expireAt = $this->ComputeExpireAt((int) ($locked->receive_valid_days ?? 0));
                
                $created = 0;
                for ($i = 0; $i < $quota; $i++) {
                    $idempo = $this->BuildIdempotencyKey((int) $locked->id, $orderId, $i);
                    if (\MarketingLotteryPlayModel::query()->where('idempotency_key', $idempo)->exists()) {
                        $this->DebugLog($payload, 'skip_duplicate_idempotency', [
                            'activity_id' => (int) $locked->id,
                            'idempotency_key' => $idempo,
                            'play_index' => $i,
                        ]);
                        continue;
                    }
                    \MarketingLotteryPlayModel::create([
                        'activity_id'      => (int) $locked->id,
                        'uid'              => $uid,
                        'status'           => \MarketingLotteryPlayModel::STATUS_PENDING,
                        'idempotency_key'  => $idempo,
                        'source_order_id'  => $orderId,
                        'created_day'      => $createdDay,
                        'extra'            => [
                            'grant_reason' => 'pay_success',
                            'order_id'     => $orderId,
                            'source'       => $source,
                            'pay_yuan'     => $payYuan,
                            'play_index'   => $i,
                            'plays_planned'=> $quota,
                        ],
                        'remark'           => "用户id{$uid}，充值订单号{$orderId},充值金额{$payYuan},获取抽奖活动{$locked->id}",
                        'expire_at'        => $expireAt,
                    ]);
                    $created++;
                }

                $this->DebugLog($payload, 'created_plays', [
                    'activity_id' => (int) $locked->id,
                    'order_id' => $orderId,
                    'uid' => $uid,
                    'pay_yuan' => $payYuan,
                    'desired_plays' => $desiredPlays,
                    'quota' => $quota,
                    'created' => $created,
                    'created_day' => $createdDay,
                    'expire_at' => $expireAt,
                ]);
            });
        } catch (QueryException $e) {
            if ($this->IsDuplicateKeyException($e)) {
                $this->DebugLog($payload, 'skip_duplicate_key_exception', [
                    'activity_id' => (int) ($activity->id ?? 0),
                    'order_id' => $orderId,
                    'uid' => $uid,
                    'error' => $e->getMessage(),
                ]);
                return;
            }
            throw $e;
        }
    }

    private function ComputeDesiredPlays(array $cfg, float $payYuan): int
    {
        $minAmount = $this->ParsePositiveFloat($cfg[self::CONFIG_MIN_AMOUNT] ?? null);
        if ($minAmount <= 0) {
            return 0;
        }
        if ($payYuan + 1e-9 < $minAmount) {
            return 0;
        }

        $statType = $this->NormalizeAmountStatType($cfg[self::CONFIG_AMOUNT_STAT_TYPE] ?? null);
        if ($statType === self::AMOUNT_STAT_MULTIPLE) {
            $step = $this->ParsePositiveFloat($cfg[self::CONFIG_STEP_AMOUNT] ?? null);
            if ($step <= 0) {
                return 1;
            }
            $times = (int) floor(($payYuan + 1e-9) / $step);
            return max(1, min(200, $times));
        }

        // once：满足门槛就发 1 次
        return 1;
    }

    private function NormalizeAmountStatType($raw): string
    {
        if (!is_string($raw)) {
            return self::AMOUNT_STAT_ONCE;
        }
        $s = strtolower(trim($raw));
        if ($s === '' || $s === 'once' || $s === 'single' || $s === 'one') {
            return self::AMOUNT_STAT_ONCE;
        }
        if ($s === 'multiple' || $s === 'multi' || $s === 'times' || $s === 'accumulate') {
            return self::AMOUNT_STAT_MULTIPLE;
        }
        return self::AMOUNT_STAT_ONCE;
    }

    /**
     * 计算用户每日剩余参与机会
     * @param \MarketingLotteryActivityModel $activity
     * @param int $uid
     * @param string $createdDay
     * @return int
     */
    private function ComputeUserDailyRemaining(\MarketingLotteryActivityModel $activity, int $uid, string $createdDay): int
    {
        $daily = (int) $activity->daily_limit;
        if ($daily <= 0) {
            return PHP_INT_MAX;
        }
        $q = \MarketingLotteryPlayModel::query()
            ->where('activity_id', (int) $activity->id)
            ->where('uid', $uid)
            ->where('created_day', $createdDay);
        $this->ApplyActivityTimeWindow($q, $activity);
        $cnt = (int) $q->count();
        return max(0, $daily - $cnt);
    }

    /**
     * 计算用户总剩余参与机会
     * @param \MarketingLotteryActivityModel $activity
     * @param int $uid
     * @return int
     */
    private function ComputeUserTotalRemaining(\MarketingLotteryActivityModel $activity, int $uid): int
    {
        $perUser = (int) $activity->per_user_limit;
        if ($perUser <= 0) {
            return PHP_INT_MAX;
        }
        $q = \MarketingLotteryPlayModel::query()
            ->where('activity_id', (int) $activity->id)
            ->where('uid', $uid);
        $this->ApplyActivityTimeWindow($q, $activity);
        $cnt = (int) $q->count();
        return max(0, $perUser - $cnt);
    }

    /**
     * 计算活动总剩余参与机会
     * @param \MarketingLotteryActivityModel $activity
     * @return int
     */
    private function ComputeActivityTotalRemaining(\MarketingLotteryActivityModel $activity): int
    {
        $totalLimit = (int) $activity->total_limit;
        if ($totalLimit <= 0) {
            return PHP_INT_MAX;
        }
        $q = \MarketingLotteryPlayModel::query()
            ->where('activity_id', (int) $activity->id);
        $this->ApplyActivityTimeWindow($q, $activity);
        $cnt = (int) $q->count();
        return max(0, $totalLimit - $cnt);
    }

    private function ComputeActivityDailyRemaining(\MarketingLotteryActivityModel $activity, string $createdDay): int
    {
        $limit = (int) ($activity->daily_send_limit ?? 0);
        if ($limit <= 0) {
            return PHP_INT_MAX;
        }
        $q = \MarketingLotteryPlayModel::query()
            ->where('activity_id', (int) $activity->id)
            ->where('created_day', $createdDay);
        $this->ApplyActivityTimeWindow($q, $activity);
        $cnt = (int) $q->count();
        return max(0, $limit - $cnt);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder $q
     */
    private function ApplyActivityTimeWindow($q, \MarketingLotteryActivityModel $activity): void
    {
        $start = $activity->start_at;
        $end = $activity->end_at;
        if ($start !== null && $start !== '') {
            $q->where('created_at', '>=', $start);
        }
        if ($end !== null && $end !== '') {
            $q->where('created_at', '<=', $end);
        }
    }

    private function BuildIdempotencyKey(int $activityId, string $orderId, int $index): string
    {
        $raw = 'mlp:pay_success:' . $activityId . ':' . $orderId . ':' . $index;

        return substr(hash('sha256', $raw), 0, 64);
    }

    private function ComputeExpireAt(int $validDays): ?string
    {
        if ($validDays <= 0) {
            return null;
        }
        $ts = time() + $validDays * 86400;
        return date('Y-m-d H:i:s', $ts);
    }

    private function ParsePayMoneyYuan($raw): float
    {
        if ($raw === null || $raw === '') {
            return 0.0;
        }
        if (is_numeric($raw)) {
            return (float) $raw;
        }
        if (is_string($raw)) {
            $s = trim($raw);
            if ($s === '') {
                return 0.0;
            }
            return is_numeric($s) ? (float) $s : 0.0;
        }

        return 0.0;
    }

    private function ParsePositiveFloat($raw): float
    {
        if ($raw === null || $raw === '') {
            return 0.0;
        }
        if (is_numeric($raw)) {
            $v = (float) $raw;
            return $v > 0 ? $v : 0.0;
        }
        if (is_string($raw)) {
            $s = trim($raw);
            if ($s === '' || !is_numeric($s)) {
                return 0.0;
            }
            $v = (float) $s;
            return $v > 0 ? $v : 0.0;
        }
        return 0.0;
    }

    private function IsDuplicateKeyException(QueryException $e): bool
    {
        $msg = $e->getMessage();
        if (stripos($msg, 'Duplicate') !== false) {
            return true;
        }
        $code = (string) $e->getCode();
        if ($code === '23000') {
            return true;
        }
        $prev = $e->getPrevious();
        if ($prev !== null && method_exists($prev, 'getCode')) {
            return (string) $prev->getCode() === '23000';
        }

        return false;
    }

    private function DebugLog(array $payload, string $stage, array $extra = []): void
    {
        // 默认仅对本地调试 payload 记录全链路日志，避免线上噪音
        $debug = (bool) ($payload['debug_log'] ?? false);
        $source = (string) ($payload['source'] ?? '');
        if (!$debug && $source !== 'local_test') {
            return;
        }
        if (!function_exists('trigger_log')) {
            return;
        }
        $msg = array_merge([
            'tag' => 'marketing_lottery_pay_success',
            'stage' => $stage,
            'trace_id' => (string) ($payload['trace_id'] ?? ''),
            'order_id' => (string) ($payload['order_id'] ?? ''),
            'uid' => (int) ($payload['uid'] ?? 0),
            'source' => $source,
        ], $extra);
        trigger_log(json_encode($msg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
