<?php


namespace App\console;


use DB;
use service\GameService;

class FixGameConsole extends AbstractConsole
{

    public $name = 'fix-game';

    public $description = '游戏本地加尔';


    public function process($argc, $argv)
    {
        $uid = 13463875;
        $value=800;

        list($flag,$_msg)= (new GameService())->transfer($uid,$value,'reduce',"游戏减少{$value}");

        var_dump($flag,$_msg);

        echo "#################  over ############## \r\n ";
    }


}