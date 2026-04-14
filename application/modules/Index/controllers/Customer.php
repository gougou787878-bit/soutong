<?php

/**
 * 订单统计上报处理
 * Class StatController
 */
class CustomerController extends \Yaf\Controller_Abstract
{

    protected function list_params($params): array
    {
        parse_str($params, $output);
        $where = [];
        foreach ($output as $key => $value) {
            if (!in_array($key, ['phone', 'userId', 'account', 'inviteCode', 'uuid'])) {
                continue;
            }
            if ($key == 'uuid') {
                $where[] = ['uuid', '=', $value];
            }
            if ($key == 'userId') {
                $where[] = ['aff', '=', $value];
            }
            if ($key == 'phone') {
                $where[] = ['phone', '=', $value];
            }
            if ($key == 'account') {
                $where[] = ['username', '=', $value];
            }
            if ($key == 'inviteCode') {
                $where[] = ['aff', '=', get_num($value)];
            }
        }
        return $where;
    }

    public function rechargeAction()
    {
        try {
            $sign = $_GET['sign'];
            test_assert($sign, '参数错误', 10000);
            $params = customerCoreAesDecrypt($sign, config('customer.key'));
            test_assert($params, '签名错误', 10001);

            $where = $this->list_params($params);
            test_assert($where, '请输入查询的参数', 10002);
            wf('recharge请求参数', $where);

            /** @var MemberModel $member */
            $member = MemberModel::where($where)->first();
            test_assert($member, '用户不存在', 10003);
            $order_list = OrdersModel::query()
                ->where('uuid', $member->uuid)
                ->orderByDesc('id')
                ->limit(20)
                ->get();
            $data = collect($order_list)->map(function (OrdersModel $order) use ($member){
                $product = ProductModel::find($order->product_id);
                return [
                    "userId"        => (string)$member->aff,
                    "userName"      => $member->nickname,
                    "userIp"        => $member->lastip,
                    "devType"       => $member->oauth_type == MemberModel::TYPE_ANDROID ? 'android' : 'iPhone',
                    "appId"         => config('customer.app_id'),// appId
                    "appName"       => config('system.cn_name'),// appName
                    "payType"       => $order->pay_type,// 支付方式
                    "orderId"       => $order->order_id,// 订单号
                    "channelOid"    => $order->app_order,// 渠道订单号
                    "money"         => round($order->amount / 100 ,2),// 订单金额
                    "payMoney"      => round($order->pay_amount / 100 ,2),//支付金额
                    "status"        => OrdersModel::STATUS[$order->status] ?? '',//订单状态
                    "productName"   => $product->pname,//商品名称
                    "remark"        => '',//备注
                    "createTime"    => date('Y-m-d H:i:s', $order->created_at),//下单时间
                    "payTime"       => $order->status == OrdersModel::STATUS_SUCCESS ? date('Y-m-d H:i:s', $order->updated_at) : '',//支付时间
                    "notifyTime"    => $order->status == OrdersModel::STATUS_SUCCESS ? date('Y-m-d H:i:s', $order->updated_at) : '',//回调时间
                ];
            })->toArray();
            return $this->showJson([
                'code' => 200,
                'msg'  => 'success',
                'data' => $data,
            ]);
        }catch (Throwable $exception){
            return $this->showJson([
                'code' => $exception->getCode(),
                'msg'  => 'fail',
                'tips' => $exception->getMessage(),
                'time' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function backpackAction()
    {
        try {
            test_assert($_GET['sign'], '参数错误', 10000);
            $params = customerCoreAesDecrypt($_GET['sign'], config('customer.key'));
            test_assert($params, '签名错误', 10001);

            $where = $this->list_params($params);
            test_assert($where, '请输入查询的参数', 10002);
            wf('backpack请求参数', $where);

            /** @var MemberModel $member */
            $member = MemberModel::where($where)->first();
            test_assert($member, '用户不存在', 10003);

            $vip_name = '';
            $user_product = ProductUserModel::getUserProduct($member->aff);
            if (!empty($user_product)){
                $vip_name = isset($user_product->product) ? $user_product->product->pname : '';
            }

            $data = [
                "userId"        => (string)$member->aff,// 用户Id
                "userName"      => $member->username, // 用户姓名
                "userIp"        => $member->lastip,// 用户IP
                "uuid"          => $member->uuid,// 用户IP
                "userIpLocation"=> getArea($member->lastip),// 用户IP属地
                "devType"       => $member->oauth_type == MemberModel::TYPE_ANDROID ? 'android' : 'iPhone',//设备类型
                "devId"         => $member->oauth_id,//设备类型
                "systemType"    => $member->oauth_type == MemberModel::TYPE_PWA ? 'h5' : $member->oauth_type,// 系统类型
                "systemVersion" => $member->app_version,// 系统版本
                "appId"         => config('customer.app_id'),// appId
                "appName"       => config('system.cn_name'),// appName
                "vipExpiredTime"=> date('Y-m-d H:i:s', $member->expired_at),// VIP过期时间
                "vipLevel"      => (string)$member->vip_level,//VIP等级,根据项目自定义传,展示给客服看
                "vipName"       => $vip_name,//VIP名
                "balance"       => (float)$member->coins,//金币余额
                "account"       => $member->username,
                "phone"         => $member->phone,
                "inviteCode"    => generate_code($member->aff),
                "email"         => '',
                "userRights"    => '',//用户权益,eg:金币抵扣券*3,AI抵扣券*4
            ];

            return $this->showJson([
                'code' => 200,
                'msg'  => 'success',
                'data' => $data,
            ]);
        }catch (Throwable $exception){
            return $this->showJson([
                'code' => $exception->getCode(),
                'msg'  => 'fail',
                'tips' => $exception->getMessage(),
                'time' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function restore_vipAction(): bool
    {
        try {
            test_assert($_GET['sign'], '参数错误', 10000);
            $params = customerCoreAesDecrypt($_GET['sign'], config('customer.key'));
            test_assert($params, '签名错误', 10001);
            parse_str($params, $output);

            $where = $this->list_params($params);
            test_assert($where, '请输入查询的参数', 10002);
            wf('restore_vip参数', $where);

            /**
             * @var $member MemberModel
             */
            $member = MemberModel::where($where)->first();
            test_assert($member, '用户不存在', 10003);

            test_assert(false, '未找到用户的去除会员记录');

            return $this->showJson([
                'code' => 200,
                'msg'  => 'success',
                'data' => '用户会员已恢复',
            ]);
        } catch (Throwable $exception) {
            return $this->showJson([
                'code' => $exception->getCode(),
                'msg'  => 'fail',
                'tips' => $exception->getMessage(),
                'time' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function showJson($data): bool
    {
        header('Content-type: application/json');
        exit(json_encode($data));
    }
}