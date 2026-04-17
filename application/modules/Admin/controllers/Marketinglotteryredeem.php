<?php

use service\MarketingLotteryRedemptionService;

class MarketinglotteryredeemController extends BackendBaseController
{
    protected function listAjaxIteration()
    {
        return function (MarketingLotteryRedemptionModel $item) {
            $status = (int) $item->status;
            $item->status_str = MarketingLotteryRedemptionModel::STATUS_TIPS[$status] ?? (string) $item->status;
            $g = $item->grant_snapshot;
            $item->setAttribute('grant_snapshot', is_array($g) ? json_encode($g, JSON_UNESCAPED_UNICODE) : ($g ?? ''));
            $p = $item->prize_snapshot;
            $item->setAttribute('prize_snapshot', is_array($p) ? json_encode($p, JSON_UNESCAPED_UNICODE) : ($p ?? ''));
            return $item;
        };
    }

    public function indexAction()
    {
        $this->display();
    }

    public function saveAction()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->ajaxError('请求错误');
        }
        $post = $this->postArray();
        if (empty($post['_pk'])) {
            return $this->ajaxError('兑奖记录由业务侧生成，后台不支持新增');
        }
        try {
            $adminUid = (int) ($this->getUser()->uid ?? 0);
            test_assert($adminUid > 0, '未登录');
            $svc = new MarketingLotteryRedemptionService();
            $model = $svc->AdminSave((int) $post['_pk'], $adminUid, $post);
            return $this->ajaxSuccessMsg('操作成功', 0, call_user_func($this->listAjaxIteration(), $model));
        } catch (\Throwable $e) {
            return $this->ajaxError($e->getMessage());
        }
    }

    public function delAction()
    {
        return $this->ajaxError('兑奖记录不允许删除');
    }

    public function delAllAction()
    {
        return $this->ajaxError('兑奖记录不允许删除');
    }

    protected function getModelClass(): string
    {
        return MarketingLotteryRedemptionModel::class;
    }

    protected function getPkName(): string
    {
        return 'id';
    }

    protected function getLogDesc(): string
    {
        return '营销抽奖兑奖';
    }
}
