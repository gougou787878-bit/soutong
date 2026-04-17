<?php

class MarketingdailysignactivityController extends BackendBaseController
{
    protected function listAjaxIteration()
    {
        return function (MarketingDailySignActivityModel $item) {
            $row = $item->toArray();
            $row['status'] = (int) $item->status;
            $row['status_str'] = MarketingDailySignActivityModel::STATUS_TIPS[(int) $item->status] ?? '';
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
}
