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

        return transaction(function () use ($activity, $member, $uid, $today, $yesterday) {
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
            $dailyCoins = max(0, (int) $activity->daily_coins);
            $bonusVipDays = max(0, (int) $activity->bonus_vip_days);
            $bonusVipLevel = $this->normalizeBonusVipLevel((int) ($activity->bonus_vip_level ?: \MemberModel::VIP_LEVEL_MOON));
            $isBonus = ($bonusVipDays > 0 && $continuousDay % $cycleDays === 0) ? 1 : 0;

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

            return [
                'activity' => $this->formatActivity($activity),
                'sign' => [
                    'id' => (int) $log->id,
                    'sign_date' => $today,
                    'continuous_day' => $continuousDay,
                    'daily_coins' => $dailyCoins,
                    'is_bonus' => $isBonus,
                    'bonus_vip_days' => $isBonus ? $bonusVipDays : 0,
                ],
            ];
        });
    }

    private function formatActivity(\MarketingDailySignActivityModel $activity): array
    {
        return [
            'id' => (int) $activity->id,
            'name' => (string) $activity->name,
            'start_at' => $activity->start_at,
            'end_at' => $activity->end_at,
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
}
