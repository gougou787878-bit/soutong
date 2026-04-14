<?php

class LotteryfrqawardController extends BackendBaseController
{

    /**
     * 列表数据过滤
     * @return Closure
     */
    protected function listAjaxIteration()
    {
        return function (LotteryFrqAwardModel $item) {
            $item->type_str = LotteryBaseModel::TYPE_TIPS[$item->type] ?? '';
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     */
    public function indexAction()
    {
        $this->display();
    }

    /**
     * 获取本控制器和哪个model绑定
     * @return string
     */
    protected function getModelClass(): string
    {
        return LotteryFrqAwardModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     */
    protected function getPkName(): string
    {
        return 'id';
    }

    /**
     * 定义数据操作日志
     * @return string
     * @author fei
     */
    protected function getLogDesc(): string
    {
        return '';
    }
}