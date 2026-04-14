<?php


namespace App\console;


use DB;

class TicketConsole extends AbstractConsole
{

    public $name = 'create-ticket';

    public $description = '创建并发放观影券';


    public function process($argc, $argv)
    {
        //test 4998241
        echo "\r\n php yaf create-ticket uid number [name] \r\n";
        $flag = false;
        if($argc>=3){
            list($_,$uid,$number,$name) = $argv;

            $name = empty($name)?'免费观影券':$name;
            echo "\r\n php yaf create-ticket {$uid} {$number} {$name} \r\n";
            //print_r($argv);die;
            $flag = \MvTicketModel::sendUserTicket($uid,date('Ymd',strtotime('+30 days')),$number,$name);

        }
        echo  "\r\n exec End : result {$flag} \r\n";


    }


}