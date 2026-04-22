<?php

namespace service;

use Illuminate\Database\Capsule\Manager as DB;

class MarketingDailySignService
{
    public function info(?int $uid = null): array
    {
        $activity = \MarketingDailySignActivityModel::current();
        if (!$activity) {
            return [
                'activity' => null,
                'today_signed' => 0,
                'continuous_day' => 0,
            ];
        }

        $today = date('Y-m-d');
        $todaySigned = 0;
        $continuousDay = 0;
        if ($uid) {
            $todaySigned = \MarketingDailySignLogModel::query()
                ->where('activity_id', (int) $activity->id)
                ->where('uid', $uid)
                ->where('sign_date', $today)
                ->exists() ? 1 : 0;

            $lastLog = \MarketingDailySignLogModel::query()
                ->where('activity_id', (int) $activity->id)
                ->where('uid', $uid)
                ->orderByDesc('sign_date')
                ->orderByDesc('id')
                ->first();
            $continuousDay = $lastLog ? (int) $lastLog->continuous_day : 0;
        }

        return [
            'activity' => $this->formatActivity($activity),
            'today_signed' => $todaySigned,
            'continuous_day' => $continuousDay,
        ];
    }

    public function sign(\MemberModel $member): array
    {
        $activity = \MarketingDailySignActivityModel::current();
        test_assert($activity, '签到活动未开始');

        $uid = (int) $member->uid;
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $rewardActive = $this->isRewardActive($activity);

        return transaction(function () use ($activity, $member, $uid, $today, $yesterday, $rewardActive) {
            $exists = \MarketingDailySignLogModel::query()
                ->where('activity_id', (int) $activity->id)
                ->where('uid', $uid)
                ->where('sign_date', $today)
                ->lockForUpdate()
                ->first();
            test_assert(!$exists, '今日已签到');

            $yesterdayLog = \MarketingDailySignLogModel::query()
                ->where('activity_id', (int) $activity->id)
                ->where('uid', $uid)
                ->where('sign_date', $yesterday)
                ->orderByDesc('id')
                ->first();

            $continuousDay = $yesterdayLog ? ((int) $yesterdayLog->continuous_day + 1) : 1;
            $cycleDays = max(1, (int) $activity->cycle_days);
            $dailyCoins = $rewardActive ? max(0, (int) $activity->daily_coins) : 0;
            $bonusVipDays = max(0, (int) $activity->bonus_vip_days);
            $bonusVipLevel = $this->normalizeBonusVipLevel((int) ($activity->bonus_vip_level ?: \MemberModel::VIP_LEVEL_MOON));
            $isBonus = ($rewardActive && $bonusVipDays > 0 && $continuousDay % $cycleDays === 0) ? 1 : 0;

            if ($dailyCoins > 0) {
                $ok = \MemberModel::useWritePdo()->where('uid', $uid)->update([
                    'coins' => DB::raw("coins+{$dailyCoins}"),
                    'coins_total' => DB::raw("coins_total+{$dailyCoins}"),
                ]);
                test_assert($ok, '发放金币失败');
                \UsersCoinrecordModel::addIncome('daily_sign', $uid, null, $dailyCoins, 0, 0, "每日签到奖励金币:{$dailyCoins}");
            }

            if ($isBonus) {
                $now = time();
                $seconds = $bonusVipDays * 86400;
                $memberExpiredAt = (int) $member->expired_at;
                $periodAt = max($memberExpiredAt, $now) + $seconds;
                $vipLevel = $memberExpiredAt > $now
                    ? max((int) $member->vip_level, $bonusVipLevel)
                    : $bonusVipLevel;
                $ok = \MemberModel::useWritePdo()->where('uid', $uid)->update([
                    'expired_at' => $periodAt,
                    'vip_level' => $vipLevel,
                    'order_at' => $now,
                ]);
                test_assert($ok, '发放会员失败');
            }

            $log = \MarketingDailySignLogModel::create([
                'activity_id' => (int) $activity->id,
                'uid' => $uid,
                'sign_date' => $today,
                'continuous_day' => $continuousDay,
                'daily_coins' => $dailyCoins,
                'bonus_vip_days' => $isBonus ? $bonusVipDays : 0,
                'is_bonus' => $isBonus,
                'remark' => $isBonus ? "连续{$cycleDays}天奖励{$bonusVipDays}天会员" : '',
            ]);

            \MemberModel::clearFor($member);

            try {
                (new MarketingLotteryTriggerService())->trigger([
                    'trigger' => 'daily_sign',
                    'uid' => $uid,
                    'uuid' => (string) ($member->uuid ?? ''),
                ]);
            } catch (\Throwable $e) {
                errLog('MarketingDailySignService::trigger: ' . $e->getMessage());
            }

            return [
                'activity' => $this->formatActivity($activity),
                'sign' => [
                    'id' => (int) $log->id,
                    'sign_date' => $today,
                    'continuous_day' => $continuousDay,
                    'daily_coins' => $dailyCoins,
                    'is_bonus' => $isBonus,
                    'bonus_vip_days' => $isBonus ? $bonusVipDays : 0,
                    'is_warm_up' => $rewardActive ? 0 : 1,
                    'reward_active' => $rewardActive ? 1 : 0,
                ],
            ];
        });
    }

    private function formatActivity(\MarketingDailySignActivityModel $activity): array
    {
        return [
            'id' => (int) $activity->id,
            'name' => (string) $activity->name,
            'show_start_at' => $activity->show_start_at ?: $activity->start_at,
            'start_at' => $activity->start_at,
            'end_at' => $activity->end_at,
            'is_warm_up' => $this->isWarmUp($activity) ? 1 : 0,
            'reward_active' => $this->isRewardActive($activity) ? 1 : 0,
            'daily_coins' => (int) $activity->daily_coins,
            'cycle_days' => (int) $activity->cycle_days,
            'bonus_vip_days' => (int) $activity->bonus_vip_days,
            'bonus_vip_level' => $this->normalizeBonusVipLevel((int) ($activity->bonus_vip_level ?: \MemberModel::VIP_LEVEL_MOON)),
            'bonus_vip_level_str' => \MemberModel::USER_VIP_TYPE[
                $this->normalizeBonusVipLevel((int) ($activity->bonus_vip_level ?: \MemberModel::VIP_LEVEL_MOON))
            ] ?? '',
            'rule_text' => (string) ($activity->rule_text ?? ''),
        ];
    }

    private function normalizeBonusVipLevel(int $level): int
    {
        return isset(\MemberModel::USER_VIP_TYPE[$level]) && $level > \MemberModel::VIP_LEVEL_NO
            ? $level
            : \MemberModel::VIP_LEVEL_MOON;
    }

    private function isWarmUp(\MarketingDailySignActivityModel $activity): bool
    {
        $now = date('Y-m-d H:i:s');
        return $activity->start_at !== null && $activity->start_at !== '' && $activity->start_at > $now;
    }

    private function isRewardActive(\MarketingDailySignActivityModel $activity): bool
    {
        $now = date('Y-m-d H:i:s');
        if ($activity->start_at !== null && $activity->start_at !== '' && $activity->start_at > $now) {
            return false;
        }
        if ($activity->end_at !== null && $activity->end_at !== '' && $activity->end_at < $now) {
            return false;
        }
        return true;
    }
}
