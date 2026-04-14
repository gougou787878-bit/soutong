<?php
/**
 * @todo 后台渠道统计曲线相关逻辑服务
 * @package service\AgentChartService
 */
namespace service;

/**
 * Class AgentChartService
 * @package service
 */
class AgentChartService
{
    static $chatDayShow = 14;//real show 10days data
    protected static $dateList;

    static function getDateList()
    {
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

    static function isToday($day)
    {
        $date = date('Ymd', time());
        return $date == $day;
    }

    public static function chartNewUser($channel)
    {
        $type = \AgentChartModel::TYPE_REG;
        $name = \AgentChartModel::TYPES[$type];
        $dateList = self::getDateList();
        $getData = self::getContentData($channel, $type, $dateList);
        $data = [];
        $opt = $channel ? '=' : '!=';
        foreach ($dateList as $k => $day) {
            $start = strtotime($day . ' 00:00:00');
            $end = strtotime($day . ' 24:00:00');
            //不是今天的day
            if (self::isToday($day)) {
                $number = \MemberModel::whereBetween('regdate', [$start, $end])->where('build_id',$opt, $channel)->count('uid');
                $data[$day] = $number;
            } elseif ($getData && isset($getData[$day])) {
                $data[$day] = $getData[$day]['value'];
            } elseif (!self::isToday($day)) {
                $number = \MemberModel::whereBetween('regdate', [$start, $end])->where('build_id',$opt,$channel)->count('uid');
                $data[$day] = $number;
                $channel && \AgentChartModel::setOrGetChannelDate([
                    'channel' => $channel,
                    'type'    => $type,
                    'date'    => $day
                ], ['is_check' => 1, 'value' => $number]);
            }
        }
        $jsonData = ['name' => $name, 'data' => $data];
        return $jsonData;
    }


    /**
     * 汤币交易单
     * @param $channel
     * @return array
     */
    public static function chartOrderCharge($channel)
    {

        $type = \AgentChartModel::TYPE_GOLD;
        $name = \AgentChartModel::TYPES[$type];
        $opt = $channel ? '=' : '!=';
        $dateList = self::getDateList();
        $getData = self::getContentData($channel, $type, $dateList);
        $data = [];
        foreach ($dateList as $day) {
            $start = strtotime($day . ' 00:00:00');
            $end = strtotime($day . ' 24:00:00');
            //不是今天的day
            if (self::isToday($day)) {
                $number = \OrdersModel::where('status', \OrdersModel::STATUS_SUCCESS)
                        ->where('build_id',$opt, $channel)
                        ->where('order_type', \OrdersModel::TYPE_GLOD)
                        ->whereBetween('updated_at', [$start, $end])
                        ->sum('pay_amount') / 100;
                $data[$day] = $number;
            } elseif ($getData && isset($getData[$day])) {
                $data[$day] = $getData[$day]['value'];
            } elseif (empty($getData) || !isset($getData[$day])) {
                $number = \OrdersModel::where('status', \OrdersModel::STATUS_SUCCESS)
                        ->where('build_id',$opt, $channel)
                        ->where('order_type', \OrdersModel::TYPE_GLOD)
                        ->whereBetween('updated_at', [$start, $end])
                        ->sum('pay_amount') / 100;
                $data[$day] = $number;
                $channel && \AgentChartModel::setOrGetChannelDate([
                    'channel' => $channel,
                    'type'    => $type,
                    'date'    => $day
                ], ['is_check' => 1, 'value' => $number]);
            }
        }
        $jsonData = ['name' => $name, 'data' => $data];
        return $jsonData;
    }


    /**
     * 用户VIP交易额 充值
     */
    public static function chartOrderVIPCharge($channel)
    {
        $type = \AgentChartModel::TYPE_VIP;
        $name = \AgentChartModel::TYPES[$type];
        $opt = $channel ? '=' : '!=';
        $dateList = self::getDateList();
        $getData = self::getContentData($channel, $type, $dateList);
        $data = [];
        foreach ($dateList as $day) {
            $start = strtotime($day . ' 00:00:00');
            $end = strtotime($day . ' 24:00:00');
            //不是今天的day
            if (self::isToday($day)) {
                $number = \OrdersModel::where('status', \OrdersModel::STATUS_SUCCESS)
                        ->where('build_id', $opt,$channel)
                        ->where('order_type', \OrdersModel::TYPE_VIP)
                        ->whereBetween('updated_at', [$start, $end])
                        ->sum('pay_amount') / 100;
                $data[$day] = $number;
            } elseif ($getData && isset($getData[$day])) {
                $data[$day] = $getData[$day]['value'];
            } elseif (empty($getData) || !isset($getData[$day]) || !self::isToday($day)) {
                $number = \OrdersModel::where('status', \OrdersModel::STATUS_SUCCESS)
                        ->where('build_id', $opt,$channel)
                        ->where('order_type', \OrdersModel::TYPE_VIP)
                        ->whereBetween('updated_at', [$start, $end])
                        ->sum('pay_amount') / 100;
                $data[$day] = $number;
                $channel && \AgentChartModel::setOrGetChannelDate([
                    'channel' => $channel,
                    'type'    => $type,
                    'date'    => $day,
                ], ['is_check' => 1, 'value' => $number]);
            }
        }
        $jsonData = ['name' => $name, 'data' => $data];
        return $jsonData;
    }

    static function getContentData($channel, $type, $dateList)
    {
        $data = \AgentChartModel::getChannelDataList($channel, $type, $dateList);
        if (!$data) {
            return false;
        }
        return array_column($data, null, 'date');
    }

    protected static function arrayMerge($ary1, $ary2)
    {
        foreach ($ary2 as $key => $item) {
            $ary1[$key] = $item;
        }
        return $ary1;
    }



}