<?php


namespace App\console;

use service\LiveService;

class LiveModelConsole extends AbstractConsole
{

    public $name = 'live-model';

    public $description = '直播模块更新;eg:php yaf live-model';

    public function process($argc, $argv)
    {
        $start = date("Y-m-d H:i:s");
        echo "#################  start [ {$start} ]##############".PHP_EOL;

        LiveService::replenish_models();
        LiveService::list_models();
        sleep(1800);

        $end = date("Y-m-d H:i:s");
        echo "##################  over [ {$end} ]##############".PHP_EOL;
    }

}