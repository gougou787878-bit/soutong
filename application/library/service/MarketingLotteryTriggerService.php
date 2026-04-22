<?php

namespace service;

use Illuminate\Database\QueryException;

class MarketingLotteryTriggerService
{
    private const CONFIG_MIN_AMOUNT = 'min_amount';
    private const AMOUNT_STAT_MULTIPLE = 'multiple';
    private const AMOUNT_STAT_ONCE = 'once';
    private const AMOUNT_STAT_PRODUCT_TIER = 'product_tier';
    private const CONFIG_STEP_AMOUNT = 'step_amount';
    private const CONFIG_PRODUCTS = 'products';
    private const AMOUNT_STAT_CONDITION_GROUP = 'condition_group';

    private const INVITEE_REGISTER_MONTHS = 2;
    private const INVITER_VALID_INVITE_LIMIT = 10;

    public function trigger(array $payload): void
    {
        $trigger = (string) ($payload['trigger'] ?? '');
        if ($trigger !== 'pay_success') {
            $this->triggerCondition($payload);
            return;
        }

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
        $triggerFrom = (string) ($payload['trigger_from'] ?? $payload['trigger'] ?? '');
        $this->DebugLog($payload, 'start', [
            'order_id' => $orderId,
            'uid' => $uid,
            'trigger_from' => $triggerFrom,
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

        foreach ($activities as $activity) {
            $this->GrantForOneActivity($activity, $payload, $orderId, $uid, $payYuan, $triggerFrom);
        }
    }

    private function triggerCondition(array $payload): void
    {
        $uid = (int) ($payload['uid'] ?? 0);
        if ($uid <= 0) {
            return;
        }
        if (!isset($payload['trace_id']) || !is_string($payload['trace_id']) || $payload['trace_id'] === '') {
            $payload['trace_id'] = 'ml_condition:' . date('YmdHis') . ':' . substr(bin2hex(random_bytes(8)), 0, 16);
        }
        $activities = \MarketingLotteryActivityModel::queryActiveForTriggerScenario(
            \MarketingLotteryActivityModel::TRIGGER_SCENARIO_PAY_SUCCESS
        );
        foreach ($activities as $activity) {
            $cfg = $activity->config;
            if (!is_array($cfg) || !$this->ConfigHasRule($cfg, self::AMOUNT_STAT_CONDITION_GROUP)) {
                continue;
            }
            $this->GrantForOneActivity($activity, $payload, 'condition:' . $uid, $uid, 0.0, (string) ($payload['trigger_from'] ?? $payload['trigger'] ?? ''));
        }
    }

    private function GrantForOneActivity(
        \MarketingLotteryActivityModel $activity,
        array $payload,
        string $orderId,
        int $uid,
        float $payYuan,
        string $triggerFrom
    ): void {
        try {
            \DB::transaction(function () use ($activity, $uid, $payload, $orderId, $triggerFrom, $payYuan): void {
                $locked = \MarketingLotteryActivityModel::query()
                    ->whereKey($activity->id)
                    ->lockForUpdate()
                    ->first();
                if ($locked === null || (int) $locked->status !== \MarketingLotteryActivityModel::STATUS_ON) {
                    return;
                }

                $createdDay = date('Y-m-d');
                $cfg = $locked->config;
                if (!is_array($cfg)) {
                    $cfg = [];
                }

                $conditionCreated = $this->GrantInvitationConditionPlays($locked, $cfg, $payload, $uid, $triggerFrom, $createdDay);
                $desiredPlays = $this->ComputeDesiredPlays($cfg, $payYuan, $payload);
                if ($desiredPlays <= 0) {
                    if ($conditionCreated > 0) {
                        return;
                    }
                    $this->DebugLog($payload, 'skip_no_matching_rule', [
                        'activity_id' => (int) $locked->id,
                        'pay_yuan' => $payYuan,
                    ]);
                    return;
                }

                $quota = min(
                    $desiredPlays,
                    $this->ComputeUserDailyRemaining($locked, $uid, $createdDay),
                    $this->ComputeActivityDailyRemaining($locked, $createdDay),
                    $this->ComputeActivityTotalRemaining($locked),
                    $this->ComputeUserTotalRemaining($locked, $uid)
                );
                $expireAt = $this->ComputeExpireAt((int) ($locked->receive_valid_days ?? 0));
                $this->CreatePlays($locked, $uid, $quota, $orderId, $createdDay, $expireAt, 'pay_success', $triggerFrom, $payload);
            });
        } catch (QueryException $e) {
            if ($this->IsDuplicateKeyException($e)) {
                return;
            }
            throw $e;
        }
    }

    private function ComputeDesiredPlays(array $cfg, float $payYuan, array $payload): int
    {
        $total = 0;
        foreach ($this->NormalizeGrantRules($cfg) as $rule) {
            $ruleCfg = $this->GetRuleConfig($cfg, $rule);
            if ($rule === self::AMOUNT_STAT_PRODUCT_TIER) {
                $total += $this->ComputeProductTierPlays($ruleCfg, $payload);
            } elseif ($rule === self::AMOUNT_STAT_ONCE) {
                $total += $this->ComputeOncePlays($ruleCfg, $payYuan);
            } elseif ($rule === self::AMOUNT_STAT_MULTIPLE) {
                $total += $this->ComputeMultiplePlays($ruleCfg, $payYuan);
            }
        }

        return max(0, min(200, $total));
    }

    private function ComputeProductTierPlays(array $cfg, array $payload): int
    {
        $productId = (string) ($payload['product_id'] ?? '');
        if ($productId === '') {
            return 0;
        }
        $products = $cfg[self::CONFIG_PRODUCTS] ?? [];
        if (!is_array($products) || empty($products)) {
            return 0;
        }
        $plays = 0;
        if (array_key_exists($productId, $products)) {
            $plays = (int) $products[$productId];
        } elseif (array_key_exists((int) $productId, $products)) {
            $plays = (int) $products[(int) $productId];
        }
        return max(0, min(200, $plays));
    }

    private function ComputeOncePlays(array $cfg, float $payYuan): int
    {
        $minAmount = $this->ParsePositiveFloat($cfg[self::CONFIG_MIN_AMOUNT] ?? null);
        if ($minAmount <= 0 || $payYuan + 1e-9 < $minAmount) {
            return 0;
        }
        return 1;
    }

    private function ComputeMultiplePlays(array $cfg, float $payYuan): int
    {
        $minAmount = $this->ParsePositiveFloat($cfg[self::CONFIG_MIN_AMOUNT] ?? null);
        if ($minAmount <= 0 || $payYuan + 1e-9 < $minAmount) {
            return 0;
        }
        $step = $this->ParsePositiveFloat($cfg[self::CONFIG_STEP_AMOUNT] ?? null);
        if ($step <= 0) {
            return 1;
        }
        $times = (int) floor(($payYuan + 1e-9) / $step);
        return max(1, min(200, $times));
    }

    private function GrantInvitationConditionPlays(
        \MarketingLotteryActivityModel $activity,
        array $cfg,
        array $payload,
        int $inviteeUid,
        string $triggerFrom,
        string $createdDay
    ): int {
        if (!$this->ConfigHasRule($cfg, self::AMOUNT_STAT_CONDITION_GROUP)) {
            return 0;
        }

        $ruleCfg = $this->GetRuleConfig($cfg, self::AMOUNT_STAT_CONDITION_GROUP);
        if (!$this->InviteeConditionMatched($ruleCfg, $payload, $activity)) {
            return 0;
        }

        $invitee = \MemberModel::query()->where('uid', $inviteeUid)->first(['uid', 'aff', 'invited_by']);
        if (!$invitee || (int) $invitee->invited_by <= 0) {
            return 0;
        }

        $inviter = \MemberModel::query()->where('aff', (int) $invitee->invited_by)->first(['uid', 'aff']);
        if (!$inviter) {
            return 0;
        }

        if ($this->CountInviterValidInvitees((int) $activity->id, (int) $inviter->uid) >= self::INVITER_VALID_INVITE_LIMIT) {
            return 0;
        }

        $expireAt = $this->ComputeExpireAt((int) ($activity->receive_valid_days ?? 0));
        $sourceOrderId = 'invite_condition:' . (int) $activity->id . ':' . $inviteeUid;
        $created = 0;

        $inviteePlays = max(0, min(200, (int) ($ruleCfg['invitee_plays'] ?? 0)));
        if ($inviteePlays > 0) {
            $quota = min(
                $inviteePlays,
                $this->ComputeUserDailyRemaining($activity, $inviteeUid, $createdDay),
                $this->ComputeUserTotalRemaining($activity, $inviteeUid),
                $this->ComputeActivityDailyRemaining($activity, $createdDay),
                $this->ComputeActivityTotalRemaining($activity)
            );
            $created += $this->CreatePlays($activity, $inviteeUid, $quota, $sourceOrderId, $createdDay, $expireAt, 'invite_condition_invitee', $triggerFrom, $payload);
        }

        $inviterPlays = max(0, min(200, (int) ($ruleCfg['inviter_plays'] ?? 0)));
        if ($inviterPlays > 0) {
            $quota = min(
                $inviterPlays,
                $this->ComputeUserDailyRemaining($activity, (int) $inviter->uid, $createdDay),
                $this->ComputeUserTotalRemaining($activity, (int) $inviter->uid),
                $this->ComputeActivityDailyRemaining($activity, $createdDay),
                $this->ComputeActivityTotalRemaining($activity)
            );
            $created += $this->CreatePlays($activity, (int) $inviter->uid, $quota, $sourceOrderId, $createdDay, $expireAt, 'invite_condition_inviter', $triggerFrom, $payload);
        }

        return $created;
    }

    private function InviteeConditionMatched(array $cfg, array $payload, \MarketingLotteryActivityModel $activity): bool
    {
        $uid = (int) ($payload['uid'] ?? 0);
        $member = $uid > 0 ? \MemberModel::query()->where('uid', $uid)->first(['uid', 'regdate', 'invited_by']) : null;
        if (!$member || (int) $member->invited_by <= 0) {
            return false;
        }
        if ((int) $member->regdate < strtotime('-' . self::INVITEE_REGISTER_MONTHS . ' months')) {
            return false;
        }
        return $this->ComputeConditionGroupPlays($cfg, $payload, $activity) > 0;
    }

    private function CountInviterValidInvitees(int $activityId, int $inviterUid): int
    {
        return (int) \MarketingLotteryPlayModel::query()
            ->where('activity_id', $activityId)
            ->where('uid', $inviterUid)
            ->where('source_order_id', 'like', 'invite_condition:' . $activityId . ':%')
            ->distinct()
            ->count('source_order_id');
    }

    private function CreatePlays(
        \MarketingLotteryActivityModel $activity,
        int $uid,
        int $count,
        string $sourceOrderId,
        string $createdDay,
        ?string $expireAt,
        string $reason,
        string $triggerFrom,
        array $payload
    ): int {
        $created = 0;
        for ($i = 0; $i < $count; $i++) {
            $idempo = $this->BuildIdempotencyKey((int) $activity->id, $sourceOrderId . ':' . $uid . ':' . $reason, $i);
            if (\MarketingLotteryPlayModel::query()->where('idempotency_key', $idempo)->exists()) {
                continue;
            }
            \MarketingLotteryPlayModel::create([
                'activity_id' => (int) $activity->id,
                'uid' => $uid,
                'status' => \MarketingLotteryPlayModel::STATUS_PENDING,
                'idempotency_key' => $idempo,
                'source_order_id' => $sourceOrderId,
                'created_day' => $createdDay,
                'extra' => [
                    'grant_reason' => $reason,
                    'trigger_from' => $triggerFrom,
                    'trigger_uid' => (int) ($payload['uid'] ?? 0),
                    'pay_yuan' => $this->ParsePayMoneyYuan($payload['pay_money_yuan'] ?? null),
                    'play_index' => $i,
                    'plays_planned' => $count,
                ],
                'remark' => 'marketing lottery trigger',
                'expire_at' => $expireAt,
            ]);
            $created++;
        }
        return $created;
    }

    private function ComputeConditionGroupPlays(array $cfg, array $payload, \MarketingLotteryActivityModel $activity): int
    {
        $conditions = $cfg['conditions'] ?? [];
        if (!is_array($conditions) || empty($conditions)) {
            return 0;
        }
        $logic = strtolower((string) ($cfg['condition_logic'] ?? 'or'));
        $matched = $logic === 'and';
        foreach ($conditions as $condition) {
            if (!is_array($condition)) {
                continue;
            }
            $ok = $this->ConditionMatched($condition, $payload, $activity);
            if ($logic === 'and' && !$ok) {
                $matched = false;
                break;
            }
            if ($logic !== 'and' && $ok) {
                $matched = true;
                break;
            }
        }
        if (!$matched) {
            return 0;
        }
        $plays = (int) ($cfg['grant_plays'] ?? 1);
        return max(1, min(200, $plays));
    }

    private function ConditionMatched(array $condition, array $payload, \MarketingLotteryActivityModel $activity): bool
    {
        $type = (string) ($condition['type'] ?? '');
        $uid = (int) ($payload['uid'] ?? 0);
        if ($uid <= 0) {
            return false;
        }
        if ($type === 'continuous_sign') {
            $days = (int) ($condition['days'] ?? 0);
            return $days > 0 && $this->GetUserContinuousSignDays($uid) >= $days;
        }
        if ($type === 'recharge_total') {
            $amount = $this->ParsePositiveFloat($condition['amount'] ?? null);
            return $amount > 0 && $this->GetUserRechargeTotalYuan($payload, $activity) + 1e-9 >= $amount;
        }
        return false;
    }

    private function GetUserContinuousSignDays(int $uid): int
    {
        $log = \MarketingDailySignLogModel::query()
            ->where('uid', $uid)
            ->orderByDesc('sign_date')
            ->orderByDesc('id')
            ->first();
        return $log ? (int) $log->continuous_day : 0;
    }

    private function GetUserRechargeTotalYuan(array $payload, \MarketingLotteryActivityModel $activity): float
    {
        $uuid = (string) ($payload['uuid'] ?? '');
        if ($uuid === '') {
            $uid = (int) ($payload['uid'] ?? 0);
            $member = $uid > 0 ? \MemberModel::query()->where('uid', $uid)->first(['uuid']) : null;
            $uuid = $member ? (string) $member->uuid : '';
        }
        if ($uuid === '') {
            return 0.0;
        }
        $q = \OrdersModel::query()
            ->where('uuid', $uuid)
            ->where('status', \OrdersModel::STATUS_SUCCESS);
        $startTs = $this->DatetimeToTimestamp($activity->start_at ?? null);
        $endTs = $this->DatetimeToTimestamp($activity->end_at ?? null);
        if ($startTs > 0) {
            $q->where('updated_at', '>=', $startTs);
        }
        if ($endTs > 0) {
            $q->where('updated_at', '<=', $endTs);
        }
        return ((float) $q->sum('pay_amount')) / 100;
    }

    private function NormalizeGrantRules(array $cfg): array
    {
        $raw = $cfg['grant_modes'] ?? [];
        if (!is_array($raw) || empty($raw)) {
            $legacy = $this->NormalizeGrantRule($cfg['amount_stat_type'] ?? '');
            return $legacy !== '' ? [$legacy] : [];
        }
        $rules = [];
        foreach ($raw as $item) {
            $rule = $this->NormalizeGrantRule($item);
            if ($rule !== '' && !in_array($rule, $rules, true)) {
                $rules[] = $rule;
            }
        }
        return $rules;
    }

    private function ConfigHasRule(array $cfg, string $rule): bool
    {
        return in_array($rule, $this->NormalizeGrantRules($cfg), true);
    }

    private function GetRuleConfig(array $cfg, string $rule): array
    {
        $rules = $cfg['rules'] ?? [];
        if (is_array($rules) && isset($rules[$rule]) && is_array($rules[$rule])) {
            return $rules[$rule];
        }
        if (!isset($cfg['rules']) && $this->NormalizeGrantRule($cfg['amount_stat_type'] ?? '') === $rule) {
            return $cfg;
        }
        return [];
    }

    private function NormalizeGrantRule($raw): string
    {
        if (!is_string($raw)) {
            return '';
        }
        $s = strtolower(trim($raw));
        if ($s === '' || $s === 'once' || $s === 'single' || $s === 'one') {
            return self::AMOUNT_STAT_ONCE;
        }
        if ($s === 'multiple' || $s === 'multi' || $s === 'times' || $s === 'accumulate') {
            return self::AMOUNT_STAT_MULTIPLE;
        }
        if ($s === 'product_tier' || $s === 'product' || $s === 'products') {
            return self::AMOUNT_STAT_PRODUCT_TIER;
        }
        if ($s === 'condition_group') {
            return self::AMOUNT_STAT_CONDITION_GROUP;
        }
        return '';
    }

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
        return max(0, $daily - (int) $q->count());
    }

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
        return max(0, $perUser - (int) $q->count());
    }

    private function ComputeActivityTotalRemaining(\MarketingLotteryActivityModel $activity): int
    {
        $totalLimit = (int) $activity->total_limit;
        if ($totalLimit <= 0) {
            return PHP_INT_MAX;
        }
        $q = \MarketingLotteryPlayModel::query()->where('activity_id', (int) $activity->id);
        $this->ApplyActivityTimeWindow($q, $activity);
        return max(0, $totalLimit - (int) $q->count());
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
        return max(0, $limit - (int) $q->count());
    }

    private function ApplyActivityTimeWindow($q, \MarketingLotteryActivityModel $activity): void
    {
        if ($activity->start_at !== null && $activity->start_at !== '') {
            $q->where('created_at', '>=', $activity->start_at);
        }
        if ($activity->end_at !== null && $activity->end_at !== '') {
            $q->where('created_at', '<=', $activity->end_at);
        }
    }

    private function BuildIdempotencyKey(int $activityId, string $orderId, int $index): string
    {
        return substr(hash('sha256', 'mlp:trigger:' . $activityId . ':' . $orderId . ':' . $index), 0, 64);
    }

    private function ComputeExpireAt(int $validDays): ?string
    {
        return $validDays <= 0 ? null : date('Y-m-d H:i:s', time() + $validDays * 86400);
    }

    private function DatetimeToTimestamp($value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }
        $ts = strtotime((string) $value);
        return $ts === false ? 0 : (int) $ts;
    }

    private function ParsePayMoneyYuan($raw): float
    {
        if ($raw === null || $raw === '') {
            return 0.0;
        }
        return is_numeric($raw) ? (float) $raw : 0.0;
    }

    private function ParsePositiveFloat($raw): float
    {
        if ($raw === null || $raw === '' || !is_numeric($raw)) {
            return 0.0;
        }
        $v = (float) $raw;
        return $v > 0 ? $v : 0.0;
    }

    private function IsDuplicateKeyException(QueryException $e): bool
    {
        if (stripos($e->getMessage(), 'Duplicate') !== false) {
            return true;
        }
        return (string) $e->getCode() === '23000';
    }

    private function DebugLog(array $payload, string $stage, array $extra = []): void
    {
        $debug = (bool) ($payload['debug_log'] ?? false);
        $triggerFrom = (string) ($payload['trigger_from'] ?? $payload['trigger'] ?? '');
        if (!$debug && $triggerFrom !== 'local_test') {
            return;
        }
        if (!function_exists('trigger_log')) {
            return;
        }
        trigger_log(json_encode(array_merge([
            'tag' => 'marketing_lottery_trigger',
            'stage' => $stage,
            'trace_id' => (string) ($payload['trace_id'] ?? ''),
            'uid' => (int) ($payload['uid'] ?? 0),
            'trigger_from' => $triggerFrom,
        ], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
