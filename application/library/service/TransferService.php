<?php
/**
 *
 * @date 2020/2/27
 * @author
 * @copyright kuaishou by KS
 * @todo 转账相关
 *
 */

namespace service;

use Yaf\Exception;

/**
 * Class TransferService
 * @package service
 */
class TransferService
{
    static function getTransferRate()
    {
        return (string)setting('trans:rate', 0.1);
    }

    /**
     * @param $member \MemberModel
     * @param $to_uid
     * @param $coin
     * @return array
     */
    static function transferCoin($member, $to_uid, $coin)
    {
        $rate = self::getTransferRate();
        $real_reach_coin = (int)$coin * (1 - $rate);
        $from_uid = $member->uid;

        try {

            \DB::beginTransaction();
            $flag = \MemberModel::where([
                ['uid', '=', $from_uid],
                ['coins', '>=', $coin],

            ])->decrement('coins', $coin);
            if (!$flag) {
                throw new Exception('余额不足', 427);
            }
            $flag2 = \MemberModel::where([
                ['uid', '=', $to_uid]
            ])->increment('coins', $real_reach_coin);
            if (!$flag2) {
                throw new Exception('转账失败，稍后重试', 427);
            }
            \UsersCoinrecordModel::createForExpend('transfer', $from_uid, $to_uid, $coin, 0, 0, 0, 0);
            \MemberModel::clearFor($member);
            \MemberModel::clearFor(\MemberModel::where('uid', $to_uid)->first());
            \DB::commit();

            return [true, $real_reach_coin];
        } catch (\Throwable $e) {
            \DB::rollBack();
            errLog("赠送失败#".$e->getMessage());
            return [false, $e->getMessage()];

        }
    }

}