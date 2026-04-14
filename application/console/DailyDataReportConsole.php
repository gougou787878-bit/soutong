<?php

namespace App\console;

use DateInterval;
use DatePeriod;
use DateTime;
use service\AiStatService;
use service\ExtraReportService;

class DailyDataReportConsole extends AbstractConsole
{

    public $name = 'daily-data-report';

    public $description = '每日数据上报';

    //上报定时任务
    //21 00 * * * php /home/yaf-gb-soutong/yaf daily-data-report

    public function process($argc, $argv)
    {
        if ($argc == 2 && $argv[1] == 'history'){
            self::history_report();
        }else{
            self::yesterday_report();
        }
    }

    public static function history_report()
    {
        try {
            $app_id = config('extra.report.app_id');
            $start     = new DateTime('2025-07-01');
            $yesterday = (new DateTime('yesterday'))->setTime(0, 0, 0);
            // 如果昨天早于起始日，就不输出
            if ($yesterday < $start) {
                exit;
            }
            // DatePeriod 默认不包含结束日，所以把结束日 +1 天
            $period = new DatePeriod($start, new DateInterval('P1D'), (clone $yesterday)->modify('+1 day'));

            foreach ($period as $date) {
                $search_date = $date->format('Ymd');
                $data2 = AiStatService::dailyDataHistory($search_date);
                //$app_id, $date, $charge_and, $charge_ios, $charge_pwa, $charge_self, $charge_channel, $charge_total, $charge_ct, $reg_and, $reg_ios, $reg_pwa, $reg_total, $activity
                ExtraReportService::daily_report(
                    $app_id,
                    $date->format('Y-m-d'),
                    $data2['charge_and'] ?? '0.00',
                    $data2['charge_ios'] ?? '0.00',
                    $data2['charge_pwa'] ?? '0.00',
                    $data2['charge_self'] ?? '0.00',
                    $data2['charge_channel'] ?? '0.00',
                    $data2['charge_total'] ?? '0.00',
                    $data2['charge_ct'] ?? 0,
                    $data2['reg_and'] ?? 0,
                    $data2['reg_ios'] ?? 0,
                    $data2['reg_pwa'] ?? 0,
                    $data2['reg_total'] ?? 0,
                    $data2['activity'] ?? 0
                );
            }
        }catch (\Throwable $exception){
            trigger_log($exception->getMessage());
        }
    }

    public static function yesterday_report()
    {
        $app_id = config('extra.report.app_id');
        $date = date('Y-m-d', strtotime('yesterday')); // 昨天的日期
        $data2 = AiStatService::dailyData($date);
        ExtraReportService::daily_report(
            $app_id,
            $date,
            $data2['charge_and'] ?? '0.00',
            $data2['charge_ios'] ?? '0.00',
            $data2['charge_pwa'] ?? '0.00',
            $data2['charge_self'] ?? '0.00',
            $data2['charge_channel'] ?? '0.00',
            $data2['charge_total'] ?? '0.00',
            $data2['charge_ct'] ?? 0,
            $data2['reg_and'] ?? 0,
            $data2['reg_ios'] ?? 0,
            $data2['reg_pwa'] ?? 0,
            $data2['reg_total'] ?? 0,
            $data2['activity'] ?? 0,
            $data2['user_ct'],
            $data2['old_user_ct'],
            $data2['new_user_ct'],
            $data2['old_active_ct'],
            $data2['add_promotion_ct'],
            $data2['add_nature_ct'],
            $data2['add_internal_ct'],
            $data2['add_charge'],
            $data2['internal_charge'],
            $data2['external_charge'],
            $data2['old_charge'],
            $data2['charge_user_ct'],
            $data2['new_charge_user_ct'],
            $data2['internal_new_charge_ct'],
            $data2['external_new_charge_ct'],
            $data2['old_charge_user_ct'],
            $data2['charge_order_ct'],
            $data2['new_charge_order_ct'],
            $data2['internal_charge_order_ct'],
            $data2['external_charge_order_ct'],
            $data2['old_charge_order_ct'],
            $data2['charge_success_rate'],
            $data2['retain_1'],
            $data2['retain_3'],
            $data2['retain_7'],
            $data2['visit_ct'],
            $data2['down_ct'],
            $data2['internal_ct'],
            $data2['external_ct']
        );
    }


}