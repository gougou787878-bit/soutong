<?php


namespace App\console;


use Carbon\Carbon;
use MemberMakerModel;
use MemberModel;
use NavigationModel;
use service\LiveService;
use TabModel;
use MvModel;

class LiveOnlineConsole extends AbstractConsole
{

    public $name = 'live-online';

    public $description = '直播在线状态更新;eg:php yaf live-online';

    public function process($argc, $argv)
    {
        $start = date("Y-m-d H:i:s");
        echo "#################  start [ {$start} ]##############".PHP_EOL;

        LiveService::live_online();
        sleep(100);

        $end = date("Y-m-d H:i:s");
        echo "##################  over [ {$end} ]##############".PHP_EOL;
    }

}