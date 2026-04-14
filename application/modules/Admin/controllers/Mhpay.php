<?php

/**
 * Class MhpayController
 * @author xiongba
 * @date 2022-05-17 17:35:31
 */
class MhpayController extends BackendBaseController
{
    /**
     * 列表数据过滤
     * @return Closure
     * @author xiongba
     * @date 2019-12-02 17:08:03
     */
    protected function listAjaxIteration()
    {
        return function ($item) {
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     * @author xiongba
     * @date 2022-05-17 17:35:31
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2022-05-17 17:35:31
     */
    protected function getModelClass(): string
    {
       return MhPayModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2022-05-17 17:35:31
     */
    protected function getPkName(): string
    {
        return 'id';
    }

    /**
     * 定义数据操作日志
     * @return string
     * @author xiongba
     * @date 2019-11-04 17:19:41
     */
    protected function getLogDesc(): string {
        // TODO: Implement getLogDesc() method.
        return '';
    }

    /**
     *
     * 订单统计
     *
     * @return bool
     */
    public function totalAction()
    {
        $where = array_merge(
            $this->getSearchLikeParam(),
            $this->getSearchWhereParam(),
            $this->getSearchBetweenParam()
        );
        $query = MhPayModel::query()->where($where);
        $payed = clone $query;
        $count = $query->count('id');
        $payedTotal = $payed->sum('coins')  / HT_JE_BEI;
        $data = [
            'count'      => $count,//订单数
            'payedTotal' => $payedTotal,//成交订单总额
        ];
        return $this->ajaxSuccess($data);
    }
}