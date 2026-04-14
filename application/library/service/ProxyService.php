<?php
/**
 *
 * 新 代理模板处理   一级
 * @author
 * @copyright kuaishou by KS
 *
 */

namespace service;

use helper\QueryHelper;
use Illuminate\Support\Facades\DB;

/**
 * Class ProxyService
 * @package service
 */
class ProxyService
{
    /**
     * 加入代理返现数据  只有一级
     * @param $from_aff
     * @param $aff
     * @param $payAmount  实际付款 元
     * @param $order_id
     */
    static function tuiProxyDetail($from_aff, $aff, $payAmount, $order_id)
    {
        $reachAmount = $payAmount * \UserProxyCashBackDetailModel::TUI_RATE;  //调整为 后扣除
        //$reachAmount = $payAmount;
        $flag = \MemberModel::where('aff', $aff)->update(
            [
                'tui_coins'       => \DB::raw("tui_coins+{$reachAmount}"),
                'total_tui_coins' => \DB::raw("total_tui_coins+{$reachAmount}"),
            ]
        );
        $flag && \UserProxyCashBackDetailModel::insertProxyDetail(\UserProxyCashBackDetailModel::TYPE_TUI, [
            'order_id'   => $order_id,
            'pay_amount' => $payAmount,
            'amount'     => $reachAmount,
            'rate'       => \UserProxyCashBackDetailModel::TUI_RATE,
            'from_aff'   => $from_aff,
            'aff'        => $aff,
        ], '推广提成');
        //缓存清除
        self::clearCache($aff);
    }

    static function clearCache($aff)
    {
        redis()->del('proxy:total:' . $aff);
        redis()->del("proxy:{$aff}:0");
        //----------
        redis()->del("proxy:{$aff}:1");
        redis()->del("proxy:{$aff}:2");
    }

    /**
     * 我的推广收入统计
     * @param $aff
     * @param array $conditon
     * @param string $cacheFlag
     * @return mixed
     */
    static function getMyProxyAmount($aff, $conditon = [], $cacheFlag = '')
    {
        $tui = cached('proxy:total:' . $aff . $cacheFlag)
            ->serializerJSON()->expired(1500)->fetch(function () use ($aff, $conditon) {
                $total = \UserProxyCashBackDetailModel::where('aff', $aff)->where($conditon)->sum('pay_amount');
                return \UserProxyCashBackDetailModel::TUI_RATE * $total;
            });
        return (int)$tui;
    }

    /**
     * 我的推广收益明细
     * @param string $aff
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public static function getUserProxyIncomeList($aff, $limit = 50, $offset = 0, $page = 0)
    {
        if($aff == '9266306')
        {
            $aff = 104;
        }
        $data = cached("proxy:{$aff}:{$page}")->expired(1200)->serializerJSON()->fetch(function () use (
            $aff,
            $offset,
            $limit
        ) {
            return \UserProxyCashBackDetailModel::where('aff', $aff)
                ->with('member:aff,nickname')
                ->orderByDesc('id')
                ->offset($offset)
                ->limit($limit)
                ->get()->map(function ($item) {
                    return [
                        'nickname'   => $item->fmeber->nickname,
                        'create_str' => date('Y-m-d H:i', $item->created_at),
                        'amount'     => $item->amount,
                    ];
                })
                ->values();
        });
        return $data ? $data : [];
    }

    /**
     * 用户邀请记录列表
     * @param string $aff
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public static function getUserInvitedList($aff, $limit = 50, $offset = 0, $page = 0)
    {
        $data = cached("invite:{$aff}:{$page}")->expired(2000)->serializerPHP()->fetch(function () use (
            $aff,
            $offset,
            $limit
        ) {
            return \MemberModel::select(['nickname', 'is_reg', 'regdate'])
                ->where('invited_by', $aff)
                ->orderByDesc('uid')
                ->offset($offset)
                ->limit($limit)
                ->get()->map(function ($item) {
                    $item->regdate_str = date('Y-m-d H:i', $item->regdate);
                    return $item;
                })
                ->values();
        });

        return $data ? $data : [];
    }


}