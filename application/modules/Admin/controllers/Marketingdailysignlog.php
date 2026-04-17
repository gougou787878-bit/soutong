<?php

class MarketingdailysignlogController extends BackendBaseController
{
    protected function listAjaxIteration()
    {
        return function (MarketingDailySignLogModel $item) {
            static $memberCache = [];
            $row = $item->toArray();
            $uid = (int) $item->uid;
            if (!array_key_exists($uid, $memberCache)) {
                $member = MemberModel::query()->where('uid', $uid)->first(['uid', 'nickname', 'username']);
                $memberCache[$uid] = $member ? [
                    'nickname' => (string) ($member->nickname ?? ''),
                    'username' => (string) ($member->username ?? ''),
                ] : ['nickname' => '', 'username' => ''];
            }
            $row['nickname'] = $memberCache[$uid]['nickname'];
            $row['username'] = $memberCache[$uid]['username'];
            $row['is_bonus_str'] = ((int) $item->is_bonus === 1) ? '是' : '否';
            return $row;
        };
    }

    public function indexAction()
    {
        $this->display();
    }

    protected function getModelClass(): string
    {
        return MarketingDailySignLogModel::class;
    }

    protected function getPkName(): string
    {
        return 'id';
    }

    protected function getLogDesc(): string
    {
        return '每日签到记录';
    }

    public function saveAction()
    {
        return $this->ajaxError('签到记录不允许后台修改');
    }

    public function delAction()
    {
        return $this->ajaxError('签到记录不允许删除');
    }
}
