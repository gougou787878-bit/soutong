<?php


namespace App\console;


use Carbon\Carbon;
use LiveModel;
use MemberMakerModel;
use MemberModel;
use NavigationModel;
use service\LiveService;
use TabModel;
use MvModel;

class LiveModel2Console extends AbstractConsole
{

    public $name = 'live-model2';

    public $description = '直播模块更新;eg:php yaf live-model';

    public function process($argc, $argv)
    {
        $start = date("Y-m-d H:i:s");
        echo "#################  start [ {$start} ]##############".PHP_EOL;

        LiveModel::where('sort', '!=', 0)->update(['sort' => 0]);
        foreach (liveService::CATEGORIES as $v) {
            list($tag, $sort) = $v;
            LiveService::list_models2($tag, $sort);
        }
        sleep(60);

        $end = date("Y-m-d H:i:s");
        echo "##################  over [ {$end} ]##############".PHP_EOL;
    }

}