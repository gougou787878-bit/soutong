<?php

use service\AppReportService;
use service\ProxyService;

/**
 * Class OrdersController
 * @author xiongba
 * @date 2020-03-12 20:25:10
 */
class OrdersController extends BackendBaseController
{
    use \repositories\ProxyRepository;


    protected $payWay = [
        'payway_wechat' => '微信',
        'payway_bank'   => '银联',
        'payway_alipay' => '支付宝',
        'payway_visa'   => 'visa',
        'payway_huabei' => '花呗',
        'payway_agent' => '新代理',
    ];

    /**
     * 列表数据过滤
     * @return Closure
     * @author xiongba
     * @date 2019-12-02 17:08:03
     */
    protected function listAjaxIteration()
    {
        return function ($item) {
            /** @var OrdersModel $item */
            $item->amount_yuan = sprintf("%.02f", $item->amount / 100 );
            $item->pay_amount_yuan = sprintf("%.02f", $item->pay_amount / 100 );
            $item->updated_str = date('Y-m-d H:i:s', $item->updated_at);
            $item->created_str = date('Y-m-d H:i:s', $item->created_at);
            //$item->product_name = $item->product->pname;
            return $item;
        };
    }

    public function delAllAction()
    {
    }

    public function delAction()
    {
    }
    public function saveAction() {
        if (!$this->getRequest()->isPost()) {
            return $this->ajaxError('请求错误');
        }
        $data = $this->postArray();
        /**  Array
         * (
         * [amount] => 20000
         * [pay_amount] => 20000
         * [app_order] => 20201210-test-liu
         * [msg] => [后台处理][后台处理]测试修复订单数据
         * [payway] => payway_wechat
         * [status] => 3
         * [channel] => DBSPayfixedWechat
         * [_pk] => 1263936
         * )*/

        $order_id = $data['_pk'] ?? '0';
        /** @var OrdersModel $order */
        $order = OrdersModel::where(['id' => $order_id])->first();
        if (is_null($order)) {
            return $this->ajaxError('订单不存在');
        }
        /** @var ProductModel $product */
        $product = $order->product;
        $old_status = $order->status;

        if ($order->status != OrdersModel::STATUS_WAIT) {
            return $this->ajaxError('订单不可修改~');
        }
        /** @var MemberModel $member */
        $member = MemberModel::where('uuid', $order->uuid)->first();
        if (is_null($member)) {
            return $this->ajaxError('未找到用户信息~');
        }

        $updateData = $data;
        unset($updateData['_pk']);

        try {

            \DB::beginTransaction();
            $updateData['updated_at'] = TIMESTAMP;
            $f = $order->update($updateData);
            if (!$f) {
                throw new \Exception('更新数据失败');
            }
            if ($updateData['status'] != OrdersModel::STATUS_SUCCESS) {
                DB::commit();
                return $this->ajaxSuccess('操作成功');
            }

            if ($product->type == ProductModel::TYPE_VIP) {
                $validAt = $product->valid_date * 86400;
                //订单支付成功
                if ($member->invited_by && $member->aff) {
                    //邀请推广 非渠道用户
                    if (empty($member->build_id)) {
                        ProxyService::tuiProxyDetail($member->aff, $member->invited_by, $updateData['pay_amount'] / 100,
                            $order->order_id);
                    }

                }
                $member->expired_at = max($member->expired_at, TIMESTAMP) + $validAt;
                $member->vip_level = max($product->vip_level, $member->vip_level);
                $totalCoins = $product->coins + $product->free_coins;
                $member->coins = (int)($member->coins + $totalCoins);
                $member->coins_total = (int)($member->coins_total + $totalCoins);
                //买金币
            } elseif ($product->type == ProductModel::TYPE_DIAMOND) {
                $totalCoins = $product->coins + $product->free_coins;
                $member->coins = (int)($member->coins + $totalCoins);
                $member->coins_total = (int)($member->coins_total + $totalCoins);
            }
            $v = $member->saveOrFail();
            //errLog("updateOrderStat:{$v}");
            DB::commit();
            MemberModel::clearFor($member);
            //数据中心  订单更新 上报控制
            if ($updateData['status'] == OrdersModel::STATUS_SUCCESS) {
                (new AppReportService())->updateOrder([
                    'order_id'   => $order->order_id,
                    'third_id'   => $order->app_order,
                    'pay_amount' => $updateData['pay_amount'] / 100,//支付金额（单位元）
                    'payed_at'   => time()
                ]);
            }
            return $this->ajaxSuccessMsg('操作成功' , 0 , call_user_func($this->listAjaxIteration() , $order));
        } catch (\Throwable $e) {
            DB::rollBack();
            errLog("updateOrderError:{$e->getMessage()}");
            return $this->ajaxError('操作失败');
        }
    }

    /**
     * 试图渲染
     * @return string
     * @author xiongba
     * @date 2020-03-12 20:25:10
     */
    public function indexAction()
    {
        $this->assign('channelAll', SettingModel::getOrderChannelData());
        $this->assign('payWay', $this->payWay);
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2020-03-12 20:25:10
     */
    protected function getModelClass(): string
    {
        return OrdersModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2020-03-12 20:25:10
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
    protected function getLogDesc(): string
    {
        // TODO: Implement getLogDesc() method.
        return '';
    }


    public function listAjaxWhere()
    {
        if (isset($_GET['between']) || isset($_GET['is_user'])) {
            return [];
        }
        return [
            ['updated_at', '>=', strtotime(date('Y-m-d'))]
        ];
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
        $query = OrdersModel::query()->where($where);
        $payed = clone $query;
        $payed = $payed->where('status', OrdersModel::STATUS_SUCCESS);
        $count = $query->count('id');
        $payedCount = $payed->count('id');
        $orderTotal = $query->sum('amount') / 100 / HT_JE_BEI;
        $payedTotal = $payed->sum('pay_amount') / 100 / HT_JE_BEI;
        $payedRate = $count < 1 ? 0 : number_format(($payedCount / $count) * 100, 2, '.', '');
        $data = [
            'count'      => $count,//订单数
            'payedCount' => $payedCount,//成功订单数
            'payedRate'  => $payedRate,//支付成功率
            'orderTotal' => $orderTotal,//订单总额
            'payedTotal' => $payedTotal,//成交订单总额
        ];
        return $this->ajaxSuccess($data);
    }

}