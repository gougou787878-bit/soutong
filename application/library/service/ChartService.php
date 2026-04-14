<?php
/**
 * @todo 后台曲线统计相关逻辑服务
 * @package service\ChartService
 */
namespace service;

/**
 * Class ChartService
 * @package service
 */
class ChartService
{
    static $baseDir = APP_PATH . '/storage/chart/';
    static $chatDayShow = 29;//real show 10days data

    protected static $dateList;

    static function getDateList() {
        $wgo = self::$chatDayShow;
        if (is_null(self::$dateList)) {
            $week_ago_date = strtotime("-{$wgo} days");
            $dateData = [];
            for ($i = 0; $i <= self::$chatDayShow; $i++) {
                $_t_stamp = $week_ago_date + $i * 86400;
                $dateData[] = date('Ymd', $_t_stamp);
            }
            self::$dateList = $dateData;
        }
        return self::$dateList;
    }

    static function isToday($day) {
        $date = date('Ymd', time());
        return $date == $day;
    }

    /**
     * 因为是被动获取数据 所以每天要check 前天 校验值修复数据
     *
     * @param $day
     * @param $file
     * @param $isSet  是 设置 ；否 验证
     * @return bool
     */
    static function checkBeforeDay($day, $file, $isSet = false) {
        if (self::isToday($day)) {
            return false;
        }
        $fileName = 'check_' . $day . '_' . $file;
        $pathFile = self::$baseDir .'check/'. $fileName . '.json';
        if (!$isSet) {
            return file_exists($pathFile);
        }
        if (!file_exists(dirname($pathFile))) {
            @mkdir(dirname($pathFile), 0755, true);
        }
        file_put_contents($pathFile, 1);
        return true;
    }

    public static function chartNewUser() {
        $file = __FUNCTION__;
        $name = '日新增';
        $dateList = self::getDateList();
        $getData = self::getContentData($file);
        $data = [];
        foreach ($dateList as $day) {
            $start = strtotime($day . ' 00:00:00');
            $end = strtotime($day . ' 24:00:00');
            if (empty($getData) || !isset($getData['data'][$day]) || self::isToday($day) || (!self::isToday($day) && !self::checkBeforeDay($day, $file))) {//不存在 或在当前没有数据 或者没有check前天
                $number = \MemberModel::whereBetween('regdate', [$start, $end])->count('uid');
                $data[$day] = $number;
                self::checkBeforeDay($day, $file, true);
            }
        }
        $jsonData = ['name' => $name, 'data' => $data];
        self::setContentData($file, $jsonData);
        return self::getContentData($file, true);
    }

    public static function chartInviteNewUser() {
        $file = __FUNCTION__;
        $name = '日邀请';
        $dateList = self::getDateList();
        $getData = self::getContentData($file);
        $data = [];
        foreach ($dateList as $day) {
            $start = strtotime($day . ' 00:00:00');
            $end = strtotime($day . ' 24:00:00');
            if (empty($getData) || !isset($getData['data'][$day]) || self::isToday($day) || (!self::isToday($day) && !self::checkBeforeDay($day, $file))) {//不存在 或在当前没有数据
                $number = \MemberModel::whereBetween('regdate', [$start, $end])->where('invited_by', '!=', 0)->count('uid');
                $data[$day] = $number;
                self::checkBeforeDay($day, $file, true);
            }
        }
        $jsonData = ['name' => $name, 'data' => $data];
        self::setContentData($file, $jsonData);
        return self::getContentData($file, true);
    }

    public static function chartActiveUser() {
        $file = __FUNCTION__;
        $name = '日活跃';
        $dateList = self::getDateList();
        $getData = self::getContentData($file);

        $data = [];
        foreach ($dateList as $day) {

            $start = strtotime($day . ' 00:00:00');
            $end = strtotime($day . ' 24:00:00');
            if (empty($getData) || !isset($getData['data'][$day]) || self::isToday($day) || (!self::isToday($day) && !self::checkBeforeDay($day, $file))) {//不存在 或在当前没有数据
                $number = \MemberLogModel::whereBetween('lastactivity', [$start, $end])->count('id');
                $data[$day] = $number;
                self::checkBeforeDay($day, $file, true);
            }
        }
        $jsonData = ['name' => $name, 'data' => $data];
        self::setContentData($file, $jsonData);
        return self::getContentData($file, true);
    }


    public static function chartOrderCharge() {
        $file = __FUNCTION__;
        $name = '订单充值';
        $dateList = self::getDateList();
        $getData = self::getContentData($file);
        $data = [];
        foreach ($dateList as $day) {
            $start = strtotime($day . ' 00:00:00');
            $end = strtotime($day . ' 24:00:00');
            if (empty($getData) || !isset($getData['data'][$day]) || self::isToday($day) || (!self::isToday($day) && !self::checkBeforeDay($day, $file))) {//不存在 或在当前没有数据
                $number = \OrdersModel::where('status', 3)->whereBetween('updated_at', [$start, $end])->sum('pay_amount') / 100;
                $data[$day] = $number;
                self::checkBeforeDay($day, $file, true);
            }
        }
        $jsonData = ['name' => $name, 'data' => $data];
        self::setContentData($file, $jsonData);
        return self::getContentData($file, true);
    }

    public static function chartOrderVipCharge() {
        $file = __FUNCTION__;
        $name = 'VIP充值';
        $dateList = self::getDateList();
        $getData = self::getContentData($file);
        $data = [];
        foreach ($dateList as $day) {
            $start = strtotime($day . ' 00:00:00');
            $end = strtotime($day . ' 24:00:00');
            if (empty($getData) || !isset($getData['data'][$day]) || self::isToday($day) || (!self::isToday($day) && !self::checkBeforeDay($day, $file))) {//不存在 或在当前没有数据
                $number = \OrdersModel::where([
                        'status'=> 3,
                        'order_type'=> \OrdersModel::TYPE_VIP,
                    ])->whereBetween('updated_at', [$start, $end])->sum('pay_amount') / 100;
                $data[$day] = $number;
                self::checkBeforeDay($day, $file, true);
            }
        }
        $jsonData = ['name' => $name, 'data' => $data];
        self::setContentData($file, $jsonData);
        return self::getContentData($file, true);
    }
    public static function chartOrderChargeNum() {
        $file = __FUNCTION__;
        $name = '订单数量';
        $dateList = self::getDateList();
        $getData = self::getContentData($file);
        $data = [];
        foreach ($dateList as $day) {
            $start = strtotime($day . ' 00:00:00');
            $end = strtotime($day . ' 24:00:00');
            if (empty($getData) || !isset($getData['data'][$day]) || self::isToday($day) || (!self::isToday($day) && !self::checkBeforeDay($day, $file))) {//不存在 或在当前没有数据
                $number = \OrdersModel::whereBetween('updated_at', [$start, $end])->count('id');
                $data[$day] = $number;
                self::checkBeforeDay($day, $file, true);
            }
        }
        $jsonData = ['name' => $name, 'data' => $data];

        self::setContentData($file, $jsonData);
        return self::getContentData($file, true);
    }

    /**
     * 用户金币交易额 消耗
     */
    public static function chartUserCoinCharge() {
        $file = __FUNCTION__;
        $name = '金币消耗';
        $dateList = self::getDateList();
        $getData = self::getContentData($file);
        $data = [];
        foreach ($dateList as $day) {
            $start = strtotime($day . ' 00:00:00');
            $end = strtotime($day . ' 24:00:00');
            if (empty($getData) || !isset($getData['data'][$day]) || self::isToday($day) || (!self::isToday($day) && !self::checkBeforeDay($day, $file))) {//不存在 或在当前没有数据
                $number = \UsersCoinrecordModel::where('type', 'expend')->whereBetween('addtime', [$start, $end])->sum('totalcoin');
                $data[$day] = number_format($number, 2, '.', '');
                self::checkBeforeDay($day, $file, true);
            }
        }
        $jsonData = ['name' => $name, 'data' => $data];

        self::setContentData($file, $jsonData);
        return self::getContentData($file, true);
    }

    static function getContentData($fileName, $isOutPut = false) {
        $pathFile = self::$baseDir . $fileName . '.json';
        if (!file_exists($pathFile)) {
            return [];
        }
        $content = file_get_contents($pathFile);
        $data = json_decode($content, true);
        if (json_last_error() == JSON_ERROR_NONE) {
            if (!$isOutPut) {
                return $data;
            }
            $lengData = count($data['data']);
            if ($lengData > self::$chatDayShow + 1) {
                $_top10 = array_slice($data['data'], $lengData - self::$chatDayShow - 1, self::$chatDayShow + 1, true);
                $data['data'] = $_top10;
                return $data;
            }
            return $data;
        }
        return [];
    }

    static function setContentData($fileName, $data) {
        $pathFile = self::$baseDir . $fileName . '.json';
        if (!file_exists(dirname($pathFile))) {
            @mkdir(dirname($pathFile), 0755, true);
        }
        $haveData = self::getContentData($fileName);
        if ($haveData) {
            $haveData['data'] = self::arrayMerge($haveData['data'], $data['data']);
            $haveData['name'] = $data['name'];
        } else {
            $haveData = $data;
        }
        file_put_contents($pathFile, json_encode($haveData));
    }

    protected static function arrayMerge($ary1, $ary2) {
        foreach ($ary2 as $key => $item) {
            $ary1[$key] = $item;
        }
        return $ary1;
    }

    // new daily stat 统计

    static function getDailyData(){

        $result = [
            'user'=>[
                'name'=>'日新增',
                'data'=>[],
            ],
            'tui_user'=>[
                'name'=>'日邀请',
                'data'=>[],
            ],
            'activity'=>[
                'name'=>'日活跃',
                'data'=>[],
            ],
            'charge'=>[
                'name'=>'日充值',
                'data'=>[],
            ],
            'vip_charge'=>[
                'name'=>'VIP充值',
                'data'=>[],
            ]
        ];
        $date = date('Ymd');
        \DailyStatModel::where('day','!=',$date)
            ->where('day','>=',date('Ymd',strtotime('-41 days')))
            ->limit(50)
            ->get()->map(function (\DailyStatModel $item)use(&$result){
                $result['user']['data'][$item->day] = $item->user;
                $result['tui_user']['data'][$item->day] = $item->tui_user;
                $result['activity']['data'][$item->day] = $item->activity;
                $result['charge']['data'][$item->day] = $item->charge;
                $result['vip_charge']['data'][$item->day] = $item->vip_charge;
            })->toArray();
        //print_r($result);
        //errLog("newChart:".var_export($result,true));
        return $result;
    }


}