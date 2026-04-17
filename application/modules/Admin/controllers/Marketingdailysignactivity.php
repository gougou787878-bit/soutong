<?php

class MarketingdailysignactivityController extends BackendBaseController
{
    protected function listAjaxIteration()
    {
        return function (MarketingDailySignActivityModel $item) {
            $row = $item->toArray();
            $row['status'] = (int) $item->status;
            $row['status_str'] = MarketingDailySignActivityModel::STATUS_TIPS[(int) $item->status] ?? '';
            $row['bonus_vip_level'] = (int) ($item->bonus_vip_level ?: MemberModel::VIP_LEVEL_MOON);
            $row['bonus_vip_level_str'] = MemberModel::USER_VIP_TYPE[$row['bonus_vip_level']] ?? '';
            return $row;
        };
    }

    public function indexAction()
    {
        $this->display();
    }

    protected function getModelClass(): string
    {
        return MarketingDailySignActivityModel::class;
    }

    protected function getPkName(): string
    {
        return 'id';
    }

    protected function getLogDesc(): string
    {
        return '每日签到活动';
    }

    public function setdaily_coins($value): int
    {
        return max(0, (int) $value);
    }

    public function setcycle_days($value): int
    {
        return max(1, (int) $value);
    }

    public function setbonus_vip_days($value): int
    {
        return max(0, (int) $value);
    }

    public function setbonus_vip_level($value): int
    {
        $level = (int) $value;
        return isset(MemberModel::USER_VIP_TYPE[$level]) && $level > MemberModel::VIP_LEVEL_NO
            ? $level
            : MemberModel::VIP_LEVEL_MOON;
    }
}
