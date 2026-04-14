<?php

namespace App\console;

use DateTime;
use service\AiStatService;
use service\ExtraReportService;

class AiReportConsole extends AbstractConsole
{

    public $name = 'ai-report';

    public $description = 'AI数据上报';

    //上报定时任务
    //21 03 * * * php /home/yaf-gb-soutong/yaf ai-report

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
            $start_date = '2025-06-01';
            $end_date = date('Y-m-d', strtotime('yesterday')); // 昨天的日期

            $start = new DateTime($start_date);
            $end = new DateTime($end_date);
            $dates = [];

            while ($start <= $end) {
                $dates[] = $start->format('Y-m-d');
                $start->modify('+1 day');
            }

            foreach ($dates as $date) {
                $start = $date . ' 00:00:00';
                $end = $date . ' 23:59:59';
                $data2 = AiStatService::data($start, $end);
                // $app_id, $date, $ai_draw, $ai_draw_ct, $ai_image_to_video, $ai_image_to_video_ct, $ai_strip, $ai_strip_ct, $ai_novel, $ai_novel_ct, $ai_image_change_face, $ai_image_change_face_ct, $ai_video_change_face, $ai_video_change_face_ct, $ai_girlfriend, $ai_girlfriend_ct
                ExtraReportService::ai_report(
                    $app_id,
                    $date,
                    $data2['ai_draw'] ?? '0.00',
                    $data2['ai_draw_ct'] ?? 0,
                    $data2['ai_image_to_video'] ?? '0.00',
                    $data2['ai_image_to_video_ct'] ?? 0,
                    $data2['ai_strip'] ?? '0.00',
                    $data2['ai_strip_ct'] ?? 0,
                    $data2['ai_novel'] ?? '0.00',
                    $data2['ai_novel_ct'] ?? 0,
                    $data2['ai_image_change_face'] ?? '0.00',
                    $data2['ai_image_change_face_ct'] ?? 0,
                    $data2['ai_video_change_face'] ?? '0.00',
                    $data2['ai_video_change_face_ct'] ?? 0,
                    $data2['ai_girlfriend'] ?? '0.00',
                    $data2['ai_girlfriend_ct'] ?? 0,
                    $data2['ai_voice'] ?? '0.00',
                    $data2['ai_voice_ct'] ?? 0
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
        $start = $date . ' 00:00:00';
        $end = $date . ' 23:59:59';
        $data2 = AiStatService::data($start, $end);
        ExtraReportService::ai_report(
            $app_id,
            $date,
            $data2['ai_draw'] ?? '0.00',
            $data2['ai_draw_ct'] ?? 0,
            $data2['ai_image_to_video'] ?? '0.00',
            $data2['ai_image_to_video_ct'] ?? 0,
            $data2['ai_strip'] ?? '0.00',
            $data2['ai_strip_ct'] ?? 0,
            $data2['ai_novel'] ?? '0.00',
            $data2['ai_novel_ct'] ?? 0,
            $data2['ai_image_change_face'] ?? '0.00',
            $data2['ai_image_change_face_ct'] ?? 0,
            $data2['ai_video_change_face'] ?? '0.00',
            $data2['ai_video_change_face_ct'] ?? 0,
            $data2['ai_girlfriend'] ?? '0.00',
            $data2['ai_girlfriend_ct'] ?? 0,
            $data2['ai_voice'] ?? '0.00',
            $data2['ai_voice_ct'] ?? 0
        );
    }


}