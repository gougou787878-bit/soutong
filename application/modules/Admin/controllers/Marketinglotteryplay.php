<?php

class MarketinglotteryplayController extends BackendBaseController
{
    protected function listAjaxIteration()
    {
        return function (MarketingLotteryPlayModel $item) {
            $item->status_str = MarketingLotteryPlayModel::STATUS_TIPS[$item->status] ?? '';
            $ex = $item->extra;
            $item->setAttribute('extra', is_array($ex) ? json_encode($ex, JSON_UNESCAPED_UNICODE) : ($ex ?? ''));
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
            return $this->ajaxError('参与机会由业务侧生成，后台不支持新增');
        }
        try {
            if ($model = $this->doSave($post)) {
                return $this->ajaxSuccessMsg('操作成功', 0, call_user_func($this->listAjaxIteration(), $model));
            }
            return $this->ajaxError('操作错误');
        } catch (\Throwable $e) {
            return $this->ajaxError($e->getMessage());
        }
    }

    public function delAction()
    {
        return $this->ajaxError('参与机会不允许删除');
    }

    public function delAllAction()
    {
        return $this->ajaxError('参与机会不允许删除');
    }

    protected function getModelClass(): string
    {
        return MarketingLotteryPlayModel::class;
    }

    protected function getPkName(): string
    {
        return 'id';
    }

    protected function getLogDesc(): string
    {
        return '营销抽奖参与机会';
    }
}
