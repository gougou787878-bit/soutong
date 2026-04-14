<?php


namespace App\console;


use DB;
use FreeMemberModel;
use MemberModel;
use MvTicketModel;
use OrdersModel;
use ProductModel;
use service\QingMingService;
use UsersCoinrecordModel;

class FixtestConsole extends AbstractConsole
{

    public $name = 'fix-test';

    public $description = '修复没有回调的数据';


    public function process($argc, $argv)
    {
        $s = [
            'xy4_20220202152151071181',
        ];

        OrdersModel::whereIn('order_id' , $s)->each(function (OrdersModel $item){
            $this->notifyOrder($item , $item->amount , time() , 'tmp-' . date('YmdHis_') . rand(1000000,9999999));
        });


    }


    protected function notifyOrder(\OrdersModel $orderObject, $real_pay_amount, $pay_time,$third_id)
    {
        try{
            $order = $orderObject->toArray();
            if ($order['status'] == OrdersModel::STATUS_SUCCESS) {
                echo 'success';
                return;
            }

            $product = $orderObject->product;
            if (is_null($product)) {
                echo '产品不存在';
                return;
            }
            $product = $product->toArray();
            $isActiveProduct = ProductModel::isActiveProduct($product['id']);
            \DB::beginTransaction();
            $order_amount = $order['amount'];
            $updateMember = 0;//用户更新信息标识 默认
            //如果误差范围4元之内,都视为正常
            if ($order_amount > 0 && ($real_pay_amount >= ($order_amount - 400))) {
                //实际支付金额
                $updateOrder = [
                    'updated_at' => $pay_time,
                    'pay_amount' => $real_pay_amount,
                    'app_order'  => $third_id,
                    'status'     => OrdersModel::STATUS_SUCCESS,
                ];
                $resultOrder = $orderObject->update($updateOrder);
                /** @var MemberModel $memberInfo */
                $memberInfo = $orderObject->withMember;
                $user_expired_at = $memberInfo->expired_at;
                $user_aff = $memberInfo->aff;

                if ($product['type'] == OrdersModel::TYPE_VIP) {//冲天数
                    $period_at = ($product['valid_date']) * 86400 + max($user_expired_at, TIMESTAMP);
                    $updateMemberData = [
                        'expired_at' => $period_at,
                        'vip_level'  => $product['vip_level'],
                    ];
                    $presetGold = $product['free_coins'] ?? 0;
                    if ($presetGold) {//冲vip 送金币
                        $updateMemberData['coins'] = $memberInfo->coins
                            + $presetGold;
                        $updateMemberData['coins_total'] = $memberInfo->coins_total
                            + $presetGold;
                    }
                    if ($product['message']) {
                        $updateMemberData['exp'] = $memberInfo->exp
                            + $product['message'];
                    }
                   \MemberModel::query()->where('uuid', $order['uuid'])->update($updateMemberData);

                }
                elseif ($product['type'] == OrdersModel::TYPE_GLOD) {//充金币
                    $toSend = $product['coins'] + $product['free_coins'];
                    $present = QingMingService::getSendPresentGold($toSend,
                        $order['uuid']);//冲钻送冲钻
                    $presentVip = QingMingService::getGoldSendPresentVIP($toSend,
                        $order['uuid']);//冲钻送vip
                    $totalSend = $present + $toSend;
                    $addCoins = $totalSend + $memberInfo->coins;
                    $addTotalCoins = $totalSend + $memberInfo->coins_total;
                    $updateData = [
                        'coins'       => $addCoins,
                        'coins_total' => $addTotalCoins,
                    ];
                    if ($presentVip) {
                        $period_at = $presentVip * 86400 + max($user_expired_at,
                                TIMESTAMP);
                        $updateData['expired_at'] = $period_at;
                        $updateData['vip_level'] = MemberModel::VIP_LEVEL_MOON;
                    }
                    $updateMember = \MemberModel::query()
                        ->where('uuid', $order['uuid'])->update($updateData);
                    $log = UsersCoinrecordModel::addIncome(
                        'recharge', $memberInfo->uid, null, $toSend, $product['id'],
                        0, "充值金币"
                    );
                }
                //观影券
                if ($product['ticket']) {
                    MvTicketModel::sendUserTicket($memberInfo->uid, null,
                        $product['ticket']);
                }
                // 收费视频免费看
                if ($product['free_day'] > 0) {
                    FreeMemberModel::createInit($memberInfo->uid, $product['free_day'], $product['free_day_type']);
                }
            }
            \DB::commit();
            MemberModel::clearFor($memberInfo);
            OrdersModel::clearFor($memberInfo);
            echo "success";
        }catch (\Throwable $e){
            \DB::rollBack();
            echo $e."\r\n";
            echo "failed";
        }
    }

}