<?php

use service\AppCenterService;
use service\AppReportService;
use service\EventTrackerService;
use service\GameService;
use service\MarketingLotteryTriggerDispatcher;
use service\ProxyService;
use service\QingMingService;

/**
 * 回调
 */
class PayController extends SiteController
{
    use \repositories\PayRepository,
        \repositories\ProxyRepository;

    /**
     * 支付回调 正在使用
     */
    public function notifyAction()
    {
        $date = date("Ymd");
        $logFile = APP_PATH . "/storage/logs/error-{$date}.log";
        $this->config = \Yaf\Registry::get('config');
        $signdata = $data = JAddSlashes($_POST);
        NotifyLogModel::addByPay($data['order_id'],json_encode($_POST));
        /*$signdata = $data = array(
            "order_id" => "3484a342-e797-3078-a113-4d1187af9bef",
            "third_id" => "3484a342-e797-3078-a113-4d1187af9bef",
            "pay_money" => "52",
            "pay_time" => "1556972314",
            "success" => "200",
            "sign" => "9030e149a0b49cd0e7a6a0436aa303f9"
        );*/
        error_log('订单回调数据\r\n' . print_r($data, true), 3, $this->logFile);
        if (isset($data['success']) && $data['success'] == 200) { //付款成功
            unset($signdata['sign']);
            $sign = $this->make_sign_callbak($signdata, config('pay.callback_key'));
            if ($sign != $data['sign']) {
                error_log('签名验证失败' . print_r($data, true), 3, $this->logFile);
                return;
            }
            if (isset($data['channel']) && $data['channel'] == 'AgentPay' && isset($data['aff']) && $data['aff']) {//game agentPay

                    $this->gameAgentPayDone($data);
                    return;

            }

            $order = \OrdersModel::useWritePdo()->where('order_id', $data['order_id'])->first();
            if (is_null($order)) {
                error_log('订单不存在', 3, $this->logFile);
                echo 'success';
                return;
            }
            $order = $order->toArray();
            if ($order['status'] == OrdersModel::STATUS_SUCCESS) {
                error_log('订单已是成功付款状态', 3, $this->logFile);
                echo 'success';
                return;
            }
            //游戏订单处理
            if($order['product_id'] == '9999' || $order['order_type'] == OrdersModel::TYPE_GAME){
                return $this->gameProduct($data,$order);
            }

            /** @var ProductModel $product */
            $product = \ProductModel::query()->where('id', $order['product_id'])->first();

            if (is_null($product)) {
                error_log('产品不存在', 3, $this->logFile);
                return;
            }

            $isActiveProduct = ProductModel::isActiveProduct($product->id);
            \DB::beginTransaction();
            $order_amount = $order['amount'];
            $real_pay_amount = $data['pay_money'] * 100;
            $updateMember = 0;//用户更新信息标识 默认
            //如果误差范围4元之内,都视为正常
            if ($order_amount > 0 && ($real_pay_amount >= ($order_amount - 400))) {
                //实际支付金额
                $updateOrder = [
                    'updated_at' => $data['pay_time'],
                    'pay_amount' => $real_pay_amount,
                    'app_order'  => $data['third_id'],
                    'status'     => OrdersModel::STATUS_SUCCESS,
                ];
                $resultOrder = \OrdersModel::query()->where('order_id', $data['order_id'])->update($updateOrder);
                /** @var MemberModel $memberInfo */
                $memberInfo = \MemberModel::query()->where('uuid', $order['uuid'])->first();
                $user_expired_at = $memberInfo->expired_at;
                $user_aff = $memberInfo->aff;

                if ($product->type == OrdersModel::TYPE_VIP) {//冲天数
                    $log = true;
                    $period_at = ($product->valid_date) * 86400 + max($user_expired_at, TIMESTAMP);
                    $updateMemberData = ['expired_at' => $period_at, 'vip_level' => max($product->vip_level,$memberInfo->vip_level),'birthday'=>time()];
                    $presetGold = $product->free_coins ?? 0;
                    if ($presetGold) {//冲vip 送金币
                        $updateMemberData['coins'] = $memberInfo->coins + $presetGold;
                        $updateMemberData['coins_total'] = $memberInfo->coins_total + $presetGold;
                    }
                    if ($product->message) {
                        $updateMemberData['exp'] = $memberInfo->exp + $product->message;
                    }
                    $updateMember = \MemberModel::useWritePdo()->where('uid',$memberInfo->uid)->update($updateMemberData);
                    $_mgs = "updateMember:" . var_export([$data['order_id'], $updateMemberData, $updateMember], true);
                    trigger_log($_mgs);
                    if (!$updateMember) {
                        error_log($_mgs, 3, $logFile);
                    }
                    //全新代理模式 1级 简单直接 非渠道用户
                    $weChannel = ['', 'gw'];
                    if ($memberInfo->invited_by && in_array($memberInfo->build_id , $weChannel) && $isActiveProduct === false) {

                        ActiveInviteModel::addData($memberInfo->invited_by,$product->toArray());
                        ProxyService::tuiProxyDetail($user_aff, $memberInfo->invited_by, $data['pay_money'],
                            $order['order_id']);
                    }
                    $presetGold && UsersCoinrecordModel::insert([
                        "type"      => 'income',
                        "action"    => 'buyvipsend',
                        "uid"       => $memberInfo->uid,
                        "touid"     => $memberInfo->uid,
                        "giftid"    => $product->id,
                        "giftcount" => 1,
                        "totalcoin" => $presetGold,
                        "showid"    => 0,
                        "addtime"   => TIMESTAMP
                    ]);

                    //vip赠送 ,游戏上分
                    //(new GameService())->transfer($memberInfo->aff,10,'add','充值任意VIP一送10元游戏卷#order_id:'.$data['order_id']);

                    //VIP 赠送视频下载次数
//                    if ($product['download_num'] > 0){
//                        $aff = $memberInfo->aff;
//                        $download_num = $product['download_num'];
//                        bg_run(function () use ($aff, $download_num, $order_amount){
//                            UserDownloadModel::addDownloadNum($aff, $download_num, $order_amount);
//                        });
//                    }
                    //vip 商品卡片
                    ProductUserModel::buyVIPProduct($memberInfo, $product);

                    async_task_cgi(function () use ($memberInfo) {
                        // 异步执行，错误了不影响整体
                        MessageModel::createSendAppVIPMessage($memberInfo->uuid);
                    });

                } elseif ($product->type == OrdersModel::TYPE_GLOD) {//充金币
                    $toSend = $product->coins + $product->free_coins;
                    $present = QingMingService::getSendPresentGold($toSend, $order['uuid']);//冲钻送冲钻
                    //$presentVip = QingMingService::getGoldSendPresentVIP($toSend, $order['uuid']);//冲钻送vip
                    $totalSend = $present + $toSend;
                    $addCoins = $totalSend + $memberInfo->coins;
                    $addTotalCoins = $totalSend + $memberInfo->coins_total;
                    $updateData = [
                        'coins'=>$addCoins,
                        'coins_total'=>$addTotalCoins,
                    ];
//                    if ($presentVip) {
//                        $period_at = $presentVip * 86400 + max($user_expired_at, TIMESTAMP);
//                        $updateData['expired_at'] = $period_at;
//                        $updateData['vip_level'] = MemberModel::VIP_LEVEL_MOON;
//                    }
                    $updateMember = \MemberModel::query()->where('uuid', $order['uuid'])->update($updateData);
                    $log = UsersCoinrecordModel::addIncome(
                        'recharge', $memberInfo->uid, null, $toSend, $product->id, 0, "充值金币"
                    );
                }

                //观影券
                if ($product->ticket) {
                    MvTicketModel::sendUserTicket($memberInfo->uid, null, $product->ticket);
                }
                // 收费视频免费看
//                if ($product['free_day'] > 0) {
//                    FreeMemberModel::createInit($memberInfo->uid, $product['free_day'], $product['free_day_type']);
//                }

                //抽奖活动
                /** @var EggModel $egg */
//                $egg = cached('egg:key')
//                    ->fetchPhp(function (){
//                        return EggModel::where('lottery_status', EggModel::STATUS_OK)
//                            ->orderByDesc('id')
//                            ->first();
//                    }, 60);
//                if (!empty($egg) && strtotime($egg->lottery_begin) < TIMESTAMP && strtotime($egg->lottery_end) > TIMESTAMP){
//                    //展示关闭
//                    EggUserModel::addUserLotteryByPay($memberInfo, $order_amount);
//                }
            }
            if ($updateMember && $resultOrder) {
                \DB::commit();
                MarketingLotteryTriggerDispatcher::triggerPaySuccess('notify', $data, $order, $product->toArray(), $memberInfo);
                MemberModel::clearFor($memberInfo);
                OrdersModel::clearFor($memberInfo);
                //上报 只vip 类型单子
                if ($order['build_id'] && $order['build_id'] != 'gw' && ProductModel::TYPE_GAME != $order['order_type']) {
                    if (!$isActiveProduct) {
                        (new AppCenterService())->updateOrder($order['order_id'], $data['pay_money'],1,$data['pay_time'],$order);
                    }
                }
                //数据中心 订单更新上报
                (new AppReportService())->updateOrder([
                    'order_id'   => $order['order_id'],
                    'third_id'   => $data['third_id'],
                    'pay_amount' => $data['pay_money'],//支付金额（单位元）
                    'payed_at'   => $data['pay_time']
                ]);

                //公司上报
                (new EventTrackerService(
                    $memberInfo->oauth_type,
                    $memberInfo->invited_by,
                    $memberInfo->uid,
                    $memberInfo->oauth_id
                ))->addTask([
                    'event'                 => EventTrackerService::EVENT_ORDER_PAID,
                    'order_id'              => $order['order_id'],
                    'order_type'            => $product['type'] == ProductModel::TYPE_VIP ? 'vip_subscription' : 'coin_purchase',
                    'product_id'            => (string)$product['id'],
                    'product_name'          => $product['pname'],
                    'amount'                => (int)$product['promo_price'],
                    'currency'              => 'CNY',
                    'coin_quantity'         => $product['type'] == ProductModel::TYPE_DIAMOND ? (int)$product['coins'] : 0,
                    'vip_expiration_time'   => $product['type'] == ProductModel::TYPE_VIP ? max($memberInfo->expired_at, TIMESTAMP) + $product->valid_date * 86400 : 0,
                    'pay_type'              => $order['payway'],
                    'pay_channel'           => $order['channel'],
                    'transaction_id'        => $data['third_id'],
                    'create_time'           => to_timestamp($data['pay_time'])
                ]);
                
                //统计
                self::payStatBuried($memberInfo,$order,$data['pay_money'],$product);

                echo "success";
            } else {
                error_log('回调失败', 3, $this->logFile);
                \DB::rollBack();
                echo "failed";
            }
        }
    }

    //没有使用
    public function notify_newAction()
    {
        $date = date("Ymd");
        $logFile = APP_PATH . "/storage/logs/error-{$date}.log";
        $this->config = \Yaf\Registry::get('config');
        $signdata = $data = JAddSlashes($_POST);
        NotifyLogModel::addByPay($data['order_id'], json_encode($_POST));
        error_log('订单回调数据\r\n' . print_r($data, true), 3, $this->logFile);
        if (isset($data['success']) && $data['success'] == 200) { //付款成功
        } else {
            return;
        }
        unset($signdata['sign']);
        $sign = $this->make_sign_callbak($signdata, config('pay.callback_key'));
        if ($sign != $data['sign']) {
            error_log('签名验证失败' . print_r($data, true), 3, $this->logFile);
            return;
        }
        if (isset($data['channel']) && $data['channel'] == 'AgentPay' && isset($data['aff']) && $data['aff']) {//game agentPay
            if ($data['product'] == 'game' || $data['product'] == 'coins') {
                $this->gameAgentPayDone($data);
                return;
            }
        }

        $order = \OrdersModel::useWritePdo()->where('order_id', $data['order_id'])->first();
        if (is_null($order)) {
            error_log("订单不存在:{$data['order_id']} ", 3, $this->logFile);
            echo 'success';
            return;
        }
        $order = $order->toArray();
        if ($order['status'] == OrdersModel::STATUS_SUCCESS) {
            error_log("订单已是成功付款状态:{$data['order_id']}", 3, $this->logFile);
            echo 'success';
            return;
        }
        //游戏订单处理
        if ($order['product_id'] == '9999' || $order['order_type'] == OrdersModel::TYPE_GAME) {
            return $this->gameProduct($data, $order);
        }

        $product = \ProductModel::query()->where('id', $order['product_id'])->first();
        if (is_null($product)) {
            error_log("产品不存在: {$data['order_id']}订单：{$order['product_id']}", 3, $this->logFile);
            return;
        }
        /** @var MemberModel $memberInfo */
        $memberInfo = \MemberModel::query()->where('uuid', $order['uuid'])->first();
        if (is_null($memberInfo)) {
            error_log("查无用户uuid: {$data['uuid']} 订单：{$order['order_id']}", 3, $this->logFile);
        }
        $product = $product->toArray();
        $isActiveProduct = ProductModel::isActiveProduct($product['id']);
        $order_amount = $order['amount'];
        $real_pay_amount = $data['pay_money'] * 100;
        //如果误差范围4元之内,都视为正常
        if ($order_amount > 0 && ($real_pay_amount >= ($order_amount - 400))) {
        } else {
            error_log("付款差额太大" . var_export($data, true), 3, $this->logFile);
            return;
        }
        //实际支付金额
        $updateOrder = [
            'updated_at' => $data['pay_time'],
            'pay_amount' => $real_pay_amount,
            'app_order'  => $data['third_id'],
            'status'     => OrdersModel::STATUS_SUCCESS,
        ];
        $user_expired_at = $memberInfo->expired_at;
        $user_aff = $memberInfo->aff;
        if ($product['type'] == OrdersModel::TYPE_VIP) {//冲天数
            try {
                \DB::beginTransaction();
                $resultOrder = \OrdersModel::useWritePdo()->where('order_id',
                    $data['order_id'])->update($updateOrder);
                if (!$resultOrder) {
                    throw new Exception("vip订单更新失败");
                }
                $period_at = ($product['valid_date']) * 86400 + max($user_expired_at, TIMESTAMP);
                $updateMemberData = ['expired_at' => $period_at, 'vip_level' => $product['vip_level']];
                $presetGold = $product['free_coins'] ?? 0;
                if ($presetGold) {//冲vip 送金币
                    $updateMemberData['coins'] = $memberInfo->coins + $presetGold;
                    $updateMemberData['coins_total'] = $memberInfo->coins_total + $presetGold;
                }
                if ($product['message']) {
                    $updateMemberData['exp'] = $memberInfo->exp + $product['message'];
                }
                $updateMember = \MemberModel::useWritePdo()->where('uid', $memberInfo->uid)->update($updateMemberData);
                if (!$updateMember) {
                    $_msg = "vip更新失败" . var_export([$updateMemberData, $data['order_id']], true);
                    throw new Exception($_msg);
                    error_log($_msg, 3, $logFile);

                }
                \DB::commit();
                MarketingLotteryTriggerDispatcher::triggerPaySuccess('notify_new_vip', $data, $order, $product, $memberInfo);
                //全新代理模式 1级 简单直接 非渠道用户
                if (($memberInfo->invited_by && empty($memberInfo->build_id) && $isActiveProduct === false)
                    || ($memberInfo->invited_by && $memberInfo->build_id && !isChannel($memberInfo->build_id) && $isActiveProduct === false)
                ) {
                    ActiveInviteModel::addData($memberInfo->invited_by, $product);
                    ProxyService::tuiProxyDetail($user_aff, $memberInfo->invited_by, $data['pay_money'],
                        $order['order_id']);
                }
                $presetGold && UsersCoinrecordModel::insert([
                    "type"      => 'income',
                    "action"    => 'buyvipsend',
                    "uid"       => $memberInfo->uid,
                    "touid"     => $memberInfo->uid,
                    "giftid"    => $product['id'],
                    "giftcount" => 1,
                    "totalcoin" => $presetGold,
                    "showid"    => 0,
                    "addtime"   => TIMESTAMP
                ]);
                async_task_cgi(function () use ($memberInfo) {
                    // 异步执行，错误了不影响整体
                    MessageModel::createSendAppVIPMessage($memberInfo->uuid);
                });

            } catch (Throwable $exception) {
                \DB::rollBack();
                errLog("失败:{$exception->getMessage()}");
                return;
            }

        } elseif ($product['type'] == OrdersModel::TYPE_GLOD) {//充金币
            try {
                \DB::beginTransaction();
                $resultOrder = \OrdersModel::useWritePdo()->where('order_id',
                    $data['order_id'])->update($updateOrder);
                if (!$resultOrder) {
                    throw new Exception("gold订单更新失败");
                }

                $toSend = $product['coins'] + $product['free_coins'];
                $presentVip = 0;//冲钻送vip
                $addCoins = $toSend + $memberInfo->coins;
                $addTotalCoins = $toSend + $memberInfo->coins_total;
                $updateData = [
                    'coins'       => $addCoins,
                    'coins_total' => $addTotalCoins,
                ];
                if ($presentVip) {
                    $period_at = $presentVip * 86400 + max($user_expired_at, TIMESTAMP);
                    $updateData['expired_at'] = $period_at;
                    $updateData['vip_level'] = MemberModel::VIP_LEVEL_MOON;
                }
                $updateMember = \MemberModel::useWritePdo()->where('uuid', $order['uuid'])->update($updateData);
                if (!$updateMember) {
                    $_msg = "gold更新失败" . var_export([$updateData, $data['order_id']], true);
                    error_log($_msg, 3, $logFile);
                    throw new Exception($_msg);
                }
                UsersCoinrecordModel::addIncome(
                    'recharge', $memberInfo->uid, null, $toSend, $product['id'], 0, "充值金币"
                );
                \DB::commit();
                MarketingLotteryTriggerDispatcher::triggerPaySuccess('notify_new_gold', $data, $order, $product, $memberInfo);

            } catch (Throwable $exception) {
                \DB::rollBack();
                errLog("gold失败:{$exception->getMessage()}");
                echo "failed";
                return;
            }
        }
        //观影券
        if ($product['ticket']) {
            MvTicketModel::sendUserTicket($memberInfo->uid, null, $product['ticket']);
        }
        // 收费视频免费看
        if ($product['free_day'] > 0) {
            FreeMemberModel::createInit($memberInfo->uid, $product['free_day'], $product['free_day_type']);
        }
        MemberModel::clearFor($memberInfo);
        OrdersModel::clearFor($memberInfo);
        //上报 只vip 类型单子
        if ($order['build_id'] && ProductModel::TYPE_GAME != $order['order_type']) {
            if (!$isActiveProduct) {
                (new AppCenterService())->updateOrder($order['order_id'], $data['pay_money'],1,$data['pay_time'],$order);
            }
        }

        //抽奖活动
        $begin_time = setting('lottery_begin_time', '2023-12-22');
        $end_time =  setting('lottery_end_time', '2024-01-04');
        if(strtotime($begin_time) < TIMESTAMP && strtotime($end_time) > TIMESTAMP){
            UserLotteryModel::addUserLotteryByPay($memberInfo, $order_amount);
        }

        //数据中心 订单更新上报
        (new AppReportService())->updateOrder([
            'order_id'   => $order['order_id'],
            'third_id'   => $data['third_id'],
            'pay_amount' => $data['pay_money'],//支付金额（单位元）
            'payed_at'   => $data['pay_time']
        ]);

        //统计
        self::payStatBuried($memberInfo,$order,$data['pay_money'],$product);

        echo "success";
    }

    /**
     * 统计
     */
    public static function payStatBuried(\MemberModel $member,$order,$pay_money,$product){
        \SysTotalModel::incrBy('notify-order');
        //交易总额
        \SysTotalModel::incrBy('order-amount', $pay_money);
        //邀请充值总额
        if ($order['build_id']) {
            \SysTotalModel::incrBy('invite-order-amount', $pay_money);
        }
        //and充值
        if ($order['oauth_type'] == 'android'){
            \SysTotalModel::incrBy('order-amount-and');
        }
        if ($product['type'] == \OrdersModel::TYPE_VIP) {
            \SysTotalModel::incrBy('pay-vip', $pay_money);
        } elseif ($product['type'] == \OrdersModel::TYPE_GLOD) {
            \SysTotalModel::incrBy('pay-coin', $pay_money);
        }

        //新用户订单数、订单金额统计
        if ($member->regdate >= strtotime(date('Y-m-d'))){
            \SysTotalModel::incrBy('pay-amount-new', $pay_money);
            \SysTotalModel::incrBy('pay-account-new');
        }
    }

    /**
     * 游戏 agentPay回调处理
     * @param $data
     *
     * order_id    string    2d9457ff-90dc-3eb3-8ee8-f40c3bb76405    唯一订单号
     * third_id    string    20190603185141baccd0    第三方支付ID
     * pay_money    string    20    实际支付金额（元）
     * pay_time    int    1558454400    回调时间戳（支付时间）
     * success    int    200    200为成功
     * channel    string    200    AgentPay
     * aff    string    200    123
     * sign    string    18d9bca232d47a80702e85c6632b7dc1    签名
     * callback_url
     * notify_count
     */
    private function gameAgentPayDone($data)
    {
        $product = null;
        $isGame = $data['product'] == 'game';
        if($isGame){
            $aff = $data['aff'];
        }else{
            list($aff, $product_id) = explode(':', $data['aff']);
            if (empty($aff) || empty($product_id)) {
                return;
            }
            /** @var ProductModel $product */
            $product = ProductModel::where('id',$product_id)->first();
        }



        /** @var MemberModel $memberModel */
        $memberModel = MemberModel::where('aff', $aff)->first();
        if (is_null($memberModel)) {
            return;
        }
        $member = $memberModel->toArray();
        $payMoney = $data['pay_money'];

        $order = array(
            'uuid'       => $member['uuid'],
            'product_id' => $isGame?9999:$product_id,
            'amount'     => $payMoney * 100,
            'status'     => OrdersModel::STATUS_SUCCESS,
            'order_id'   => $data['order_id'],
            'order_type' => $isGame?ProductModel::TYPE_GAME:ProductModel::TYPE_DIAMOND,
            'channel'    => $data['channel'],
            'descp'      => $isGame?"游戏充值{$payMoney}":$product->pname,
            'payway'     => $isGame?'alipay':'agent',
            'updated_at' => $data['pay_time'],
            'created_at' => TIMESTAMP,
            'expired_at' => $isGame?($payMoney >= 1000 ? 0 : 0):0,//没用 游戏送vip
            'pay_type'   => 'agent',
            'oauth_type' => $member['oauth_type'],
            'build_id'   => $memberModel->build_id,
            'pay_amount' => $payMoney * 100,
            'app_order'  => $data['third_id'],
            'pay_url'    => $member['aff'] . '-' . $member['uuid'],
        );
        if (OrdersModel::insert($order)) {
            if ($isGame) {
                (new GameService())->transfer($member['aff'], $data['pay_money'], 'add',
                    '支付直充值#order_id:' . $data['order_id'], null, $order['expired_at']);
            } elseif($data['product'] == 'coins') {
                $toSend = $payMoney;
                $productData = $product->toArray();
                if ($productData){
                    //金币和赠送金币
                    $toSend = $productData['coins'] + $productData['free_coins'];
                }
                MemberModel::where('uid', $member['uid'])->update([
                    'coins'       => \DB::raw("coins+{$toSend}"),
                    'coins_total' => \DB::raw("coins_total+{$toSend}"),
                ]);
                UsersCoinrecordModel::addIncome('recharge', $member['uid'], $member['uid'], $toSend,
                    $order['product_id'], 0, "充值金币");

                //jt report
                //数据中心上报 订单创建
                (new AppReportService())->addOrder([
                    'order_id'   => $order['order_id'],//order_sn 全局唯一
                    'uid'        => $member['aff'] ? $member['aff'] : $member['uid'],
                    'oauth_type' => $order['oauth_type'],
                    'amount'     => $payMoney,//订单金额 （单位元）
                    'product'    => $order['order_type'],
                    'way'        => $order['payway'],
                    'created_at' => $order['created_at'],
                    'payed_at'   => $order['updated_at'],
                    'channel'    => $order['channel'],
                    'status'     => 1,
                    'third_id'   => $order['app_order'],
                    'pay_amount' => $data['pay_money'],//支付金额（单位元）
                ]);
            } elseif ($data['type'] == 'vip') {//冲天数
                $productData = $product->toArray();
                $period_at = ($productData['valid_date']) * 86400 + max($memberModel->expired_at, TIMESTAMP);
                $updateMemberData = ['expired_at' => $period_at, 'vip_level' => max($memberModel->vip_level,$productData['vip_level'])];
                $updateMember = \MemberModel::useWritePdo()->where('uid', $memberModel->uid)->update($updateMemberData);
                if (!$updateMember) {
                    $_msg = "agent|vip更新失败" . var_export([$updateMemberData, $data['order_id']], true);
                    errLog($_msg);
                }
                //观影券
                if ($productData['ticket']) {
                    MvTicketModel::sendUserTicket($memberModel->uid, null, $productData['ticket']);
                }

                // 收费视频免费看
//                if ($productData['free_day'] > 0) {
//                    FreeMemberModel::createInit($memberModel->uid, $productData['free_day'], $productData['free_day_type']);
//                }
                //vip 商品卡片
                ProductUserModel::buyVIPProduct($memberModel, $product);
            }

            //统计
            if (!$isGame){
                //上报联盟
                if ($order['build_id'] && $order['build_id'] != 'gw' && ProductModel::TYPE_GAME != $order['order_type']) {
                    $service = new AppCenterService();
                    //第一步add
                    $_type = (ProductModel::TYPE_VIP == $order['order_type']) ? 0 : 1;
                    $service->addOrder($order['order_type']
                        , $order['uuid']
                        , $payMoney
                        , $order['oauth_type']
                        , $_type
                        , $order['build_id']
                        , $memberModel->invited_by
                        , 0
                        , $order['created_at']
                        ,$memberModel->phone);
                    //第二部update
                    if($order['status'] == OrdersModel::STATUS_SUCCESS){
                        $service->updateOrder($order['order_id'], $payMoney,1,$data['pay_time'],$order);
                    }
                }
                self::payStatBuried($memberModel,$order,$payMoney,$product->toArray());
            }

            //公司上报
            (new EventTrackerService(
                $memberModel->oauth_type,
                $memberModel->invited_by,
                $memberModel->uid,
                $memberModel->oauth_id
            ))->addTask([
                'event'                 => EventTrackerService::EVENT_ORDER_PAID,
                'order_id'              => $order['order_id'],
                'order_type'            => $product['type'] == ProductModel::TYPE_VIP ? 'vip_subscription' : 'coin_purchase',
                'product_id'            => (string)$product['id'],
                'product_name'          => $product['pname'],
                'amount'                => (int)$product['promo_price'],
                'currency'              => 'CNY',
                'coin_quantity'         => $product['type'] == ProductModel::TYPE_DIAMOND ? (int)$product['coins'] : 0,
                'vip_expiration_time'   => $product['type'] == ProductModel::TYPE_VIP ?($product['valid_date'] * 86400 + max($memberModel->expired_at,TIMESTAMP)) : 0,
                'pay_type'              => $order['payway'],
                'pay_channel'           => $order['channel'],
                'transaction_id'        => $data['third_id'],
                'create_time'           => to_timestamp($data['pay_time'])
            ]);

            MemberModel::clearFor($member);
            OrdersModel::clearFor($member);
            MarketingLotteryTriggerDispatcher::triggerPaySuccess('game_agent', $data, $order, $product ? $product->toArray() : null, $memberModel);
            die("success");
        }
        errLog("createAgentOrderFailed:" . var_export($order, 1));
        die("failed");
    }
    private function gameProduct($data,$order){
        $order_amount = $order['amount'];
        $real_pay_amount = $data['pay_money'] * 100;
        //如果误差范围4元之内,都视为正常
        if ($order_amount > 0 && ($real_pay_amount >= ($order_amount - 400))) {
            //实际支付金额
            $updateOrder = [
                'updated_at' => $data['pay_time'],
                'pay_amount' => $real_pay_amount,
                'app_order'  => $data['third_id'],
                'status'     => OrdersModel::STATUS_SUCCESS,
            ];
            if(OrdersModel::where('order_id', $data['order_id'])->update($updateOrder)){
                /** @var MemberModel $memberInfo */
                $memberInfo = \MemberModel::query()->where('uuid', $order['uuid'])->first();
                if($memberInfo){
                    (new GameService())->transfer($memberInfo->aff,$data['pay_money'],'add','支付充值#order_id:'.$data['order_id'],null,$order['expired_at']);
                }
                if($memberInfo){
                    MarketingLotteryTriggerDispatcher::triggerPaySuccess('game_product', $data, $order, null, $memberInfo);
                }
                die("success");
            }
        }
        die("failed");
    }


    private function testNotifyActiontest()
    {
        //测试订单
    $this->config = \Yaf\Registry::get('config');
    $signdata = $data = JAddSlashes($_POST);
    if ($data['test'] != 'xxxx111'){
        return ;
    }
    /*$signdata = $data = array(
        "order_id" => "3484a342-e797-3078-a113-4d1187af9bef",
        "third_id" => "3484a342-e797-3078-a113-4d1187af9bef",
        "pay_money" => "52",
        "pay_time" => "1556972314",
        "success" => "200",
        "sign" => "9030e149a0b49cd0e7a6a0436aa303f9"
    );*/
        $data['pay_time'] = time();
        $data['third_id'] = str_shuffle("3484a342-e797-3078-a113-4d1187af9bef");
    error_log('订单回调数据\r\n' . print_r($data, true), 3, $this->logFile);
    if (isset($data['success']) && $data['success'] == 200) { //付款成功
        unset($signdata['sign']);
        $order = \OrdersModel::query()->where('order_id', $data['order_id'])->first();
        if (is_null($order)) {
            error_log('订单不存在', 3, $this->logFile);
            echo 'success';
            return;
        }
        $order = $order->toArray();
        if ($order['status'] == OrdersModel::STATUS_SUCCESS) {
            error_log('订单已是成功付款状态', 3, $this->logFile);
            echo 'success';
            return;
        }

        $product = \ProductModel::query()->where('id', $order['product_id'])->first();
        if (is_null($product)) {
            error_log('产品不存在', 3, $this->logFile);
            return;
        }
        $product = $product->toArray();

        \DB::beginTransaction();

        $order_amount = $order['amount'];
        $real_pay_amount = $data['pay_money'] * 100;
        $updateMember = 0;//用户更新信息标识 默认
        //如果误差范围4元之内,都视为正常
        if ($order_amount > 0 && ($real_pay_amount >= ($order_amount - 400))) {
            //实际支付金额
            $updateOrder = [
                'updated_at' => $data['pay_time'],
                'pay_amount' => $real_pay_amount,
                'app_order'  => $data['third_id'],
                'status'     => OrdersModel::STATUS_SUCCESS,
            ];
            $resultOrder = \OrdersModel::query()->where('order_id', $data['order_id'])->update($updateOrder);
            /** @var MemberModel $memberInfo */
            $memberInfo = \MemberModel::query()->where('uuid', $order['uuid'])->first();
            $user_expired_at = $memberInfo->expired_at;
            $user_aff = $memberInfo->aff;

            if ($product['type'] == OrdersModel::TYPE_VIP) {//冲天数
                $log = true;
                $present = QingMingService::getSendPresentVip($product['valid_date'], $order['uuid']);
                $period_at = ($product['valid_date'] + $present) * 86400 + max($user_expired_at, TIMESTAMP);
                $updateMemberData = ['expired_at' => $period_at, 'vip_level' => $product['vip_level']];
                $presetGold = QingMingService::getVIPSendPresentGold($product['valid_date'], $order['uuid']);
                $freeCoins = $product['free_coins'] ?? 0;
                $presetGold = $presetGold+$freeCoins;
                if ($presetGold) {//冲vip 送金币
                    $updateMemberData['coins'] = $memberInfo->coins + $presetGold;
                    $updateMemberData['coins_total'] = $memberInfo->coins_total + $presetGold;
                }
                //errLog("updateMember:".var_export($updateMemberData,true));
                $updateMember = \MemberModel::query()->where('uuid', $order['uuid'])->update($updateMemberData);
                //$memberInfo->invited_by && $log = $this->addAmountToPreLevels($user_aff, $order_amount, '充值成功');
                //全新代理模式 1级 简单直接 非渠道用户
                if ($memberInfo->invited_by && empty($memberInfo->build_id)) {
                    ProxyService::tuiProxyDetail($user_aff, $memberInfo->invited_by, $data['pay_money'], $order['order_id']);
                }
                $presetGold && UsersCoinrecordModel::insert([
                    "type"      => 'income',
                    "action"    => 'buyvipsend',
                    "uid"       => $memberInfo->uid,
                    "touid"     => $memberInfo->uid,
                    "giftid"    => $product['id'],
                    "giftcount" => 1,
                    "totalcoin" => $presetGold,
                    "showid"    => 0,
                    "addtime"   => TIMESTAMP
                ]);
            } elseif ($product['type'] == OrdersModel::TYPE_GLOD) {//充金币
                $toSend = $product['coins'] + $product['free_coins'];
                $present = QingMingService::getSendPresentGold($toSend, $order['uuid']);//冲钻送冲钻
                $presentVip = QingMingService::getGoldSendPresentVIP($toSend, $order['uuid']);//冲钻送vip
                $totalSend = $present + $toSend;
                $addCoins = $totalSend + $memberInfo->coins;
                $addTotalCoins = $totalSend + $memberInfo->coins_total;
                $updateData = [
                    'coins'=>$addCoins,
                    'coins_total'=>$addTotalCoins,
                ];
                if ($presentVip) {
                    $period_at = $presentVip * 86400 + max($user_expired_at, TIMESTAMP);
                    $updateData['expired_at'] = $period_at;
                    $updateData['vip_level'] = MemberModel::VIP_LEVEL_MOON;
                }
                $updateMember = \MemberModel::query()->where('uuid', $order['uuid'])->update($updateData);
                $log = UsersCoinrecordModel::addIncome(
                    'recharge', $memberInfo->uid, null, $toSend, $product['id'], 0, "充值金币"
                );
            }
        }
        if ($updateMember && $log && $resultOrder) {
            \DB::commit();
            MemberModel::clearFor($memberInfo);
            OrdersModel::clearFor($memberInfo);
            //上报 只vip 类型单子
            if ($order['build_id'] && ProductModel::TYPE_VIP == $order['order_type']) {
                (new AppCenterService())->updateOrder($order['order_id'], $data['pay_money'],$data['pay_time'],$order);
            }
            //数据中心 订单更新上报
            (new AppReportService())->updateOrder([
                'order_id'   => $order['order_id'],
                'third_id'   => $data['third_id'],
                'pay_amount' => $data['pay_money'],//支付金额（单位元）
                'payed_at'   => $data['pay_time']
            ]);
            echo "success";
        } else {
            error_log('回调失败', 3, $this->logFile);
            \DB::rollBack();
            echo "failed";
        }
    }
}


    /**
     * 提现回调
     */
    public function notifywithrawAction()
    {
        if (empty($_POST) || !isset($_POST['sign']) || !isset($_POST['order_id'])) {
            return;
        }
        $this->config = \Yaf\Registry::get('config');
        $signdata = $data = JAddSlashes($_POST);
        NotifyLogModel::addByExchange($data['order_id'], json_encode($data));
        error_log('提现回调参数' . print_r($signdata, true), 3, $this->logFile);

        //签名验证
        unset($signdata['sign']);
        $sign = $this->make_sign_callbak($signdata, config('withdraw.key'));

        if (in_array($data['order_id'] , ['3ef7b9ee-a7c2-384d-b0a9-1b0dfb37b4da'
                                          , '325e98b3-2101-30a2-934a-020c0e461eb5'
                                          , 'b9f579e3-8175-3944-9bcb-03229afcf779'
                                          , 'a0a7b9c7-e207-3695-96a7-8c8dd49a7f96'
                                          , 'b9f65b8d-49e2-3291-95b0-5c0005c11197'
        ])) {
            $data['success'] = 100;
            $data['mark'] = '提现失败';
            $data['sign'] = $sign = 1;
        }

        if (mb_strlen($data['mark']) > 50) {
            $mark = $data['mark'];
            if (strpos($mark, '&amp;quot;') !== false) {
                $mark = htmlspecialchars_decode(htmlspecialchars_decode($mark));
            }
            @json_decode($mark, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data['sign'] = $sign = 1;
                $data['mark'] = '提现失败';
            }
        }


        if ($sign != $data['sign']) {
            error_log('提现签名错误' . $signdata['order_id'], 3, $this->logFile);
            echo "failed";
            return;
        }

        /** @var UserWithdrawModel $withdraw */
        $withdraw = \UserWithdrawModel::onWriteConnection()->where('cash_id', $data['order_id'])->first();
        if(is_null($withdraw)){
            echo 'success';
            return;
        }

        if ($withdraw->status != UserWithdrawModel::STATUS_SUCCESS) {
            echo 'success';
            return;
        }

        //提现成功修改提现订单状态
        if (isset($data['success']) && $data['success'] == 200) {
            if ($withdraw) {
                try {
                    \DB::beginTransaction();
                    /** @var MemberModel $user */
                    $user = MemberModel::onWriteConnection()->where('uuid', $withdraw->uuid)->first();
                    // 金币提现

                    $withdraw->status = UserWithdrawModel::STATUS_POST;
                    $withdraw->trueto_amount = $data['exchange_money'];
                    $withdraw->order_desc = json_encode($data);
                    $withdraw->payed_at = $data['pay_time'] ?? time();
                    $withdraw->updated_at = $data['pay_time'] ?? time();
                    $withdraw->save();
                    \DB::commit();
                    SystemAccountModel::addWithDrawAccount($withdraw);
                    //messageCenter
                    MessageModel::createSystemMessage($withdraw->uuid,MessageModel::SYSTEM_MSG_TPL_WITHDRAW_YES,['money'=>$data['exchange_money']]);
                    $isGame = ($withdraw->withdraw_from == UserWithdrawModel::DRAW_TYPE_GAME);
                    if(!$isGame){
                        //数据中心 提现成功上报控制  成功回调后上报 只限成功的提现
                        (new AppReportService())->exchangeReport([
                            'order_id'    => $withdraw->cash_id,
                            'third_id'    => $data['third_id'] ?? $withdraw->third_id,
                            'uid'         => $user->uid,
                            'oauth_type'  => $user->oauth_type,
                            'name'        => $withdraw->name,
                            'card_number' => $withdraw->account,
                            'amount'      => $withdraw->amount,
                            'pay_amount'  => $data['exchange_money'],
                            'product'     => ($withdraw->from == UserWithdrawModel::DRAW_TYPE_PROXY) ? 1 : 2,
                            'way'         => 'bankcard',
                            'created_at'  => $withdraw->created_at,
                            'payed_at'    => $data['pay_time'] ?? time(),
                            'status'      => 1,
                        ]);
                    }
                    echo 'success';
                } catch (Exception $exception) {
                    echo 'fail';
                    return;
                }
            }
            return;
        }

        // 提现失败退回用户金币
        if (isset($data['success']) && $data['success'] == 100) {
            \DB::beginTransaction();
            try {
                if ($withdraw) {
                    /** @var MemberModel $memberinfo */
                    $memberinfo = \MemberModel::onWriteConnection()->where('uuid', $withdraw->uuid)->first();

                    if ($withdraw->withdraw_from == UserWithdrawModel::DRAW_TYPE_MV) { //退回金币
                        $origin = $memberinfo->score;
                        $memberinfo->score = $origin + $withdraw->coins;
                        $flag = $memberinfo->save();
                        if (empty($flag)) {
                            throw new \Exception('退回用户金币失败');
                        }
                        $itOk = \UserVoterecordModel::addIncome($memberinfo->uid, 'in-votes', $withdraw->coins);
                        if (empty($itOk)) {
                            throw new \Exception('记录收益日志失败');
                        }
                        error_log("mv提现退回#状态：{$flag} 原账户：{$origin} 退回：{$withdraw->coins} ID:{$withdraw->id}", 3, $this->logFile);
                    } elseif ($withdraw->withdraw_from == UserWithdrawModel::DRAW_TYPE_PROXY) {  //退回用户提现的
                        $origin = $memberinfo->tui_coins;
                        $memberinfo->tui_coins = $origin + $withdraw->coins;
                        if (empty($flag = $memberinfo->save())) {
                            throw new \Exception('提现退回金币失败');
                        }
                        error_log("推广提现退回#状态：{$flag} 原账户：{$origin} 退回：{$withdraw->coins} ID:{$withdraw->id}", 3, $this->logFile);
                    }elseif($withdraw->withdraw_from == UserWithdrawModel::DRAW_TYPE_GAME){
                        list($flag,$_msg)= (new GameService())->transfer($memberinfo->uid,$withdraw->amount,'add',"游戏提现退回({$data['order_id']}),加额 {$withdraw->amount}");
                        $data['mark'] = $data['mark'] . ($flag ? '退款#Y' : '');
                        error_log("游戏推广提现退回#状态：false ； ID:{$withdraw->id}", 3, APP_PATH . '/storage/logs/logwithdrawgame.log');
                    }elseif ($withdraw->withdraw_from == UserWithdrawModel::DRAW_TYPE_POST) { //退回金币
                        $origin = $memberinfo->post_coins;
                        $memberinfo->post_coins = $origin + $withdraw->coins;
                        $flag = $memberinfo->save();
                        if (empty($flag)) {
                            throw new \Exception('退回用户社区收益失败');
                        }
                        error_log("社区提现退回#状态：{$flag} 原账户：{$origin} 退回：{$withdraw->coins} ID:{$withdraw->id}", 3, $this->logFile);
                    }
                    if (isset($data['mark']) && strlen($data['mark']) > 255) {
                        $data['mark'] = '提现错误,err-json';
                    }
                    $updatedataWithdraw = ['status' => 4, 'order_desc' => '提现失败#'.$data['mark']];
                    $itOk = \UserWithdrawModel::query()->where('cash_id', $data['order_id'])->update($updatedataWithdraw);
                    if (empty($itOk)){
                        throw new \Exception('修改提现数据失败');
                    }
                    \DB::commit();
                    //messageCenter
                    MessageModel::createSystemMessage($withdraw->uuid, MessageModel::SYSTEM_MSG_TPL_WITHDRAW_NO, ['reason' => '提现失败#' . $data['mark']]);
                    echo 'success';
                }
            } catch (Exception $exception) {
                \DB::rollBack();
                echo 'failed';
                error_log($exception->getMessage(), 3, $this->logFile);
            }
        }
    }
}
