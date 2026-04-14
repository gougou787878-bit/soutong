<?php
namespace App\console;

use AdsModel;

class ReportAdsClickConsole extends AbstractConsole
{


    public $name = 'report-ads-click';

    public $description = '上报广告点击';

    public function process($argc, $argv) {
        set_time_limit(0);
        echo "start 上报广告点击\r\n";
        $data = [];
        $list = [];
        // 循环弹出前 100 条数据
        for ($i = 0; $i < 100; $i++) {
            $value = redis()->rPop(AdsModel::ADS_APP_REPORT_KEY);
            if ($value === false) {
                break; // 队列为空时停止
            }
            $list[] = json_decode($value, true);
            $data[] = $value;
        }
        if (empty($list)){
            echo "空的",PHP_EOL;
            return;
        }
        $http = new \tools\HttpCurl();
        $params = ['hash' => config('ads.key'), 'list' => $list];
        $header = ['Content-Type:application/json'];
        $result = $http->post(config('ads.app_report_url'), json_encode($params), $header);
        $result = json_decode($result, true);
        if ($result['status'] != 1){
            collect($data)->map(function ($item){
                redis()->lPush(AdsModel::ADS_APP_REPORT_KEY, $item);
            });
        }
       echo "\r\n end ############ \r\n";
    }





}