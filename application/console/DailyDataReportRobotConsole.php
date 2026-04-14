<?php

namespace App\console;

use DateInterval;
use DatePeriod;
use DateTime;
use service\AiStatService;
use service\ExtraReportService;

class DailyDataReportRobotConsole extends AbstractConsole
{

    public $name = 'daily-data-report-robot';

    public $description = '每日数据机器人上报';

    //上报定时任务
    //16 00 * * * php /home/yaf-gb-xiaolan/yaf daily-data-report-robot

    public function process($argc, $argv)
    {
        //万有引力每日数据上报
        self::yesterday_daily_robot_report();
    }

    public static function yesterday_daily_robot_report(){
        $app_id = config('click.report.app_id');
        //$app_id='DX-042';
        $date = date('Y-m-d', strtotime('yesterday')); // 昨天的日期
        $data2 = AiStatService::dailyDataReportRobot($date);

        ExtraReportService::robot_daily_report(
            $app_id,
            $date,
            $data2['today_active'] ?? 0,
            $data2['upmonth_today_active'] ?? 0,
            //$data2['today_active_rate'] ?? '0.00',

            $data2['charge_self'] ?? '0.00',
            $data2['upmonth_charge_self'] ?? '0.00',
            //$data2['charge_self_rate'] ?? '0.00',

            $data2['channel_users'] ?? 0,
            $data2['upmonth_channel_users'] ?? 0,
            //$data2['channel_users_rate'] ?? '0.00',
            $data2['charge_channel'] ?? '0.00',
            $data2['upmonth_charge_channel'] ?? '0.00'
            //$data2['charge_channel_rate'] ?? '0.00'
        );
    }


}