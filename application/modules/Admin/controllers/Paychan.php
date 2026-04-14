<?php

use helper\QueryHelper;


/**
 * Class PaychanController
 */
class PaychanController extends BackendBaseController
{


    public function listAjaxWhere()
    {
        if (isset($_GET['between'])) {
            return [];
        }
        return [
            ['updated_at', '>=', strtotime(date('Y-m-d'))]
        ];
    }

    /**
     * 列表数据过滤
     * @return Closure
     * @author xiongba
     * @date 2019-12-02 17:08:03
     */
    protected function listAjaxIteration()
    {
        /**
         * @param OrdersModel $item
         * @return array
         */
        return function ($item) {
            $item->amount = moneyFormat($item->amount / HT_JE_BEI);
            $item->pay_amount = moneyFormat($item->pay_amount / HT_JE_BEI);
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     * @author xiongba
     * @date 2020-01-01 18:15:51
     */
    public function indexAction()
    {
        $this->display();
    }


    public function listAjaxAction()
    {

        $where = array_merge(
            $this->builderWhereArray(),
            $this->listAjaxWhere()
        );
        //print_r($where);die;
        $query = OrdersModel::where($where)
            ->where('status', '=', OrdersModel::STATUS_SUCCESS)
            ->groupBy('channel')
            ->selectRaw("channel,count(id) as total_number,sum(amount) as amount,sum(pay_amount) as pay_amount");
        //print_r($query);die;
        return $this->ajaxReturn((new QueryHelper())->layuiTable($query, $this->listAjaxIteration()));

    }

    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2020-01-01 18:15:51
     */
    protected function getModelClass(): string
    {
        return OrdersModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2020-01-01 18:15:51
     */
    protected function getPkName(): string
    {
        return 'id';
    }


    public function delAction()
    {
        return $this->ajaxError('不允许操作');
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2019-11-04 17:19:41
     */
    protected function getLogDesc(): string
    {
        return '支付通道统计';
    }
    /**
     *
     * 订单统计
     *
     * @return bool
     */
    public function totalAction()
    {
        /*$data = [
            'count'      => 100,//订单数
            'payedCount' => 100,//成功订单数
            'payedRate'  => 100,//支付成功率
            'orderTotal' => 100,//订单总额
            'payedTotal' => 100,//成交订单总额
        ];
        return $this->ajaxSuccess($data);*/
        $where = array_merge(
            $this->getSearchLikeParam(),
            $this->getSearchWhereParam(),
            $this->getSearchBetweenParam()
        );
        //print_r($where);
        $query = OrdersModel::query()->where($where);
        $payed = $query->where('status', OrdersModel::STATUS_SUCCESS);
       // $count = $query->count('id');
        $payedCount = $payed->count('id');
        $orderTotal = $payed->sum('amount') / 100 / HT_JE_BEI;
        $payedTotal = $payed->sum('pay_amount') / 100 / HT_JE_BEI;
        //$payedRate = $count < 1 ? 0 : number_format(($payedCount / $count)*100, 2, '.', '');
        $data = [
            //'count'      => $count,//订单数
            'payedCount' => $payedCount,//成功订单数
            //'payedRate'  => $payedRate,//支付成功率
            'orderTotal' => $orderTotal,//订单总额
            'payedTotal' => $payedTotal,//成交订单总额
        ];
        return $this->ajaxSuccess($data);
    }

}