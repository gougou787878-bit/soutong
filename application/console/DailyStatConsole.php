<?php


namespace App\console;


use DB;

class DailyStatConsole extends AbstractConsole
{

    public $name = 'daily-stat';

    public $description = '每日系统统计;eg:php yaf daily-stat 2050-08-10';




    public function process($argc, $argv)
    {
        //默认是 前一天
        $day = null;
        if($argc>=2) {
            list($_, $date) = $argv;
            $date && $day = $date;
        }
        echo "#################  start [ {$day} ]##############".PHP_EOL;

        if (date('Y-m-d') < '2023-06-18'){
            \DailyStatModel::countSystem($day);
        }else{
            \DailyStatModel::countSystemNew($day);
        }

        echo "##################  over [ {$day} ]##############".PHP_EOL;
    }


}