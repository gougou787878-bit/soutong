<?php
/**
 *
 * @date 2020/3/31
 * @author
 * @copyright kuaishou by KS
 *
 * 通用 冲x就送x活动处理
 * @example
 *  QingMingService::getSendPresentVip($vip, $uuid);         //冲vip 送vip
 *  QingMingService::getSendPresentGold($gold, $uuid);       //冲金币 送金币
 *  QingMingService::getVIPSendPresentGold($vip, $uuid);     //冲vip 送金币
 *  QingMingService::getGoldSendPresentVIP($gold, $uuid);    //冲金币 送vip
 */


namespace service;


use helper\OperateHelper;

class QingMingService
{

    const DATE_TIME = [
        //'20200401',
        '20200521',
        '20200522',
    ];//活动日期
    const DATE_HOUR = [
       'start'=>'09',
       'end'=>'15',
    ];//活动日期

    // 冲金币送 金币结构
    const GOLD_RANG = [
        375   => [
            'min'   => 1,//最小
            'max'   => 10,//最大
            'step'  => 1,//步长
            'limit' => 5000,//上限
            'key'   => 'qmj350',//标识
        ],
        780   => [
            'min'   => 10,
            'max'   => 20,
            'step'  => 2,
            'limit' => 5000,
            'key'   => 'qmj780',
        ],
        1620  => [
            'min'   => 20,
            'max'   => 40,
            'step'  => 5,
            'limit' => 5000,
            'key'   => 'qmj1620',
        ],
        4350  => [
            'min'   => 50,
            'max'   => 100,
            'step'  => 5,
            'limit' => 5000,
            'key'   => 'qmj4350',
        ],
        9000  => [
            'min'   => 100,
            'max'   => 200,
            'step'  => 10,
            'limit' => 10000,
            'key'   => 'qmj9000',
        ],
        18600 => [
            'min'   => 220,
            'max'   => 500,
            'step'  => 20,
            'limit' => 20000,
            'key'   => 'qmj18600',
        ],
        46500 => [
            'min'   => 500,
            'max'   => 1000,
            'step'  => 50,
            'limit' => 10000,
            'key'   => 'qmj46500',
        ],
        93000 => [
            'min'   => 1200,
            'max'   => 2000,
            'step'  => 100,
            'limit' => 20000,
            'key'   => 'qmj93000',
        ],
    ];

    // 冲vip 送vip 结构
    const VIP_RANG = [
        36=>[
            'min'   => 1,
            'max'   => 3,
            'step'  => 1,
            'limit' => 0,//0 不限制
            'key'   => 'qmjvip36',
        ],
        90=>[
            'min'   => 4,
            'max'   => 8,
            'step'  => 1,
            'limit' => 0,//0 不限制
            'key'   => 'qmjvip90',
        ],
        365=>[
            'min'   => 8,
            'max'   => 14,
            'step'  => 1,
            'limit' => 0,//0 不限制
            'key'   => 'qmjvip365',
        ],
    ];

    // 冲vip 送砖石 结构
    const VIP_RANG_GOLD = [
        36=>[
            'min'   => 5,
            'max'   => 10,
            'step'  => 1,
            'limit' => 0,//0 不限制
            'key'   => 'actvip36',
        ],
        90=>[
            'min'   => 11,
            'max'   => 20,
            'step'  => 1,
            'limit' => 0,//0 不限制
            'key'   => 'actvip90',
        ],
        365=>[
            'min'   => 21,
            'max'   => 30,
            'step'  => 1,
            'limit' => 0,//0 不限制
            'key'   => 'actvip365',
        ],
    ];
    // 冲砖石 送vip 结构
    const GOLD_RANG_VIP = [
        375   => [
            'min'   => 1,//最小
            'max'   => 3,//最大
            'step'  => 1,//步长
            'limit' => 0,//上限
            'key'   => 'acg375',//标识
        ],
        780   => [
            'min'   => 2,
            'max'   => 4,
            'step'  => 1,
            'limit' => 0,
            'key'   => 'acg780',
        ],
        1620  => [
            'min'   => 3,
            'max'   => 6,
            'step'  => 1,
            'limit' => 0,
            'key'   => 'acg1620',
        ],
        4350  => [
            'min'   => 4,
            'max'   => 7,
            'step'  => 1,
            'limit' => 0,
            'key'   => 'acg4350',
        ],
        9000  => [
            'min'   => 5,
            'max'   => 8,
            'step'  => 1,
            'limit' => 0,
            'key'   => 'acg9000',
        ],
        18600 => [
            'min'   => 8,
            'max'   => 15,
            'step'  => 2,
            'limit' => 0,
            'key'   => 'acg18600',
        ],
        46500 => [
            'min'   => 10,
            'max'   => 20,
            'step'  => 4,
            'limit' => 0,
            'key'   => 'acg46500',
        ],
        93000 => [
            'min'   => 15,
            'max'   => 30,
            'step'  => 5,
            'limit' => 0,
            'key'   => 'acg93000',
        ],
    ];

    /**
     * 活动是否可进行
     * @param bool $checkHour  是否验证小时
     * @return bool
     */
    protected static function check($checkHour = false)
    {
        //充值活动开关
        if(setting('active.recharge',0) == '0'){
            return false;
        }
        if($checkHour){
            $now_date_hour = date('H', TIMESTAMP);
            return OperateHelper::betweenIn($now_date_hour,self::DATE_HOUR['start'],self::DATE_HOUR['end']);
        }
        $now_date = date('Ymd', TIMESTAMP);
        if (in_array($now_date,self::DATE_TIME)) {
            return true;
        }
        return false;
    }

    /**冲金币 送金币 入口
     * @param $goldKey
     * @param $uuid
     * @return int
     */
    static function getSendPresentGold($goldKey, $uuid='xxx')
    {
        return 0;
        if (!self::check()) {//非活动时间
            return 0;
        }
        $goldKey = (int)$goldKey;
        $list = self::GOLD_RANG;
        $goldInfo = isset($list[$goldKey]) ? $list[$goldKey] : [];
        if (!$goldInfo) {
            return 0;
        }
        $flagKey = $goldInfo['key'];
        $number = (int)redis()->get($flagKey);
        $limit = $goldInfo['limit'];
        if ($limit && $number > $limit) {
            return 0;
        }
        $range = range($goldInfo['min'], $goldInfo['max'], $goldInfo['step']);
        shuffle($range);
        $present = $range[array_rand($range)];
        redis()->incrBy($flagKey, $present);
        //redis()->incrBy('qmjtotal', $present);//统计赠送
        $hua = $number + $present;
        errLog("冲钻送钻: {$uuid} 冲#{$goldKey} 送#{$present} 累计#{$hua} 限制#{$limit}" . PHP_EOL);
        return $present;
    }

    /**
     * 冲vip 送vip 入口
     * @param $vipDate
     * @param $uuid
     * @return int
     */
    static function getSendPresentVip($vipDate, $uuid='xxx')
    {
        return 0;
        if (!self::check()) {
            return 0;
        }
        $goldKey = (int)$vipDate;
        $list = self::VIP_RANG;
        $goldInfo = isset($list[$goldKey]) ? $list[$goldKey] : [];
        if (!$goldInfo) {
            return 0;
        }
        $date = date('Ymd',TIMESTAMP);
        $flagKey = $goldInfo['key'].$date;
        $number = (int)redis()->get($flagKey);
        $limit = $goldInfo['limit'];
        if ($limit && $number > $limit) {
            return 0;
        }
        $range = range($goldInfo['min'], $goldInfo['max'], $goldInfo['step']);
        shuffle($range);
        $present = $range[array_rand($range)];
        redis()->incrBy($flagKey, $present);
        //redis()->incrBy('qmjviptotal', $present);//统计赠送
        $hua = $number + $present;
        errLog("冲vip送vip: {$uuid} 冲#{$goldKey} 送#{$present} 天  累计#{$hua}  限制#{$limit}" . PHP_EOL);
        return $present;
    }

    /**
     * 冲vip 送金币入口
     * @param $goldKey
     * @param $uuid
     * @return int
     */
    static function getVIPSendPresentGold($vipDate, $uuid='xxx')
    {
        return 0;
        if (!self::check()) {//非活动时间
            return 0;
        }
        $goldKey = (int)$vipDate;
        $list = self::VIP_RANG_GOLD;
        $goldInfo = isset($list[$goldKey]) ? $list[$goldKey] : [];
        if (!$goldInfo) {
            return 0;
        }
        $flagKey = $goldInfo['key'];
        $number = (int)redis()->get($flagKey);
        $limit = $goldInfo['limit'];
        if ($limit && $number > $limit) {
            return 0;
        }
        $range = range($goldInfo['min'], $goldInfo['max'], $goldInfo['step']);
        shuffle($range);
        $present = $range[array_rand($range)];
        redis()->incrBy($flagKey, $present);
        $hua = $number + $present;
        errLog("冲vip送金币: {$uuid} 冲#{$goldKey} 天 送#{$present} 钻 累计#{$hua} 钻" . PHP_EOL);
        return $present;
    }
    /**
     * 冲金币 送VIP入口
     * @param $goldKey
     * @param $uuid
     * @return int
     */
    static function getGoldSendPresentVIP($goldKey, $uuid='xxx')
    {
        if (!self::check(true)) {//非活动时间
            return 0;
        }
        $goldKey = (int)$goldKey;
        $list = self::GOLD_RANG_VIP;
        $goldInfo = isset($list[$goldKey]) ? $list[$goldKey] : [];
        if (!$goldInfo) {
            return 0;
        }
        $flagKey = $goldInfo['key'];
        $number = (int)redis()->get($flagKey);
        $limit = $goldInfo['limit'];
        if ($limit && $number > $limit) {
            return 0;
        }
        $range = range($goldInfo['min'], $goldInfo['max'], $goldInfo['step']);
        shuffle($range);
        $present = $range[array_rand($range)];
        redis()->incrBy($flagKey, $present);
        $hua = $number + $present;
        errLog("冲钻送vip: {$uuid} 冲#{$goldKey} 钻 送#{$present} 天 累计#{$hua} 天" . PHP_EOL);
        return $present;
    }


    static function qixiGameOrderCheck(
        $startDate = '2021-08-12',
        $endDate = '2021-08-25',
        $limit = 8,
        $product = [60, 61, 62, 63,64,65,66]
    ) {
        //\DB::enableQueryLog();
        $key = "qx:{$startDate}:{$endDate}:{$limit}";
        $uuidArray = cached('user:' . $key)->expired(300)
            ->serializerJSON()
            ->fetch(function () use ($product, $startDate, $endDate) {

                return \OrdersModel::where([
                    ['status', '=', \OrdersModel::STATUS_SUCCESS],
                    ['created_at', '>=', strtotime($startDate . ' 00:00:00')],
                    ['created_at', '<', strtotime($endDate . ' 23:59:59')],
                ])->whereIn('product_id', $product)->select(['uuid'])
                    ->groupBy('uuid')
                    ->get()
                    ->pluck('uuid')->toArray();
            });
        if (empty($uuidArray)) {
            return [];
        }
        //errLog("qixiuuid:".var_export($product,1));
        $order = \OrdersModel::where([
            ['status', '=', \OrdersModel::STATUS_SUCCESS],
            ['order_type', '!=', \OrdersModel::TYPE_GAME],
            ['created_at', '>=', strtotime($startDate . ' 00:00:00')],
            ['created_at', '<', strtotime($endDate . ' 23:59:59')],
        ])->selectRaw("sum(pay_amount) as pay_amount_total,uuid")->groupBy('uuid')
            ->whereIn('uuid', $uuidArray)
            ->orderByDesc('pay_amount_total')
            ->limit($limit)
            ->get()->map(function ($item, $_ct) {
                $_t = $_ct + 1;
                return [
                    'nk'=>$item->withMember->nickname,
                    //'nk' => "神秘用户 {$_t}",
                    'cz' => number_format($item->pay_amount_total / 100, 2, '.', ''),
                    'bs' => "x:{$item->uuid}:" . rand(10, 99)
                ];
            })->filter()->toArray();
        //$p = \DB::getQueryLog();
        //print_r(var_export($p,1));
        if(true){
            $order[] = [
                'nk'=>'鼎ge啪',
                'cz'=>"26100.00",
                'bs'=>'x:'.md5($endDate).'25'
            ];$order[] = [
                'nk'=>'大熊',
                'cz'=>"18100.00",
                'bs'=>'x:'.md5($endDate).'48'
            ];

            $order = collect($order)->sortByDesc('cz')->values()->toArray();

            array_pop($order);

        }

        return $order;

    }

}