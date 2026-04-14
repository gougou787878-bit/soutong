<?php


namespace App\console;


use App\console\Queue\QueueOption;
use service\AppFeedSystemService;

class FeedSendConsole extends AbstractConsole
{

    public $name = 'feed-send';

    public $description = '模拟发送工单';


    public function process($argc, $argv)
    {


        echo "start feed-send \r\n";

        $from = $argv[1] ?? '';
        $to = $argv[2] ?? '';
        //1471179
        if (empty($from)) {
            echo "eg:php yaf feed-send from_id to_id \r\n";
            die;
        }
        if(empty($to)){
            $to = $from;
        }

        $data = \FeedbackModel::where([
            ['id','>=',$from],
            ['id','<=',$to],
        ])->get()->map(function (\FeedbackModel $item){
            return [
                'uuid'=>$item->withMember->uuid,
                'app_type'=>$item->withMember->oauth_type,
                'aff'=>$item->withMember->aff,
                'product'=>$item->platform,
                'type'=>1,
                'nickname'=>$item->withMember->nickname,
                'content'=>$item->content?:'xxx图片',
                'version'=>$item->withMember->app_version,
                'ip'=>USER_IP,
                'vip_level'=>$item->withMember->vip_level,
                'status'=>0,
            ];
        })->filter()->toArray();
     //print_r($data);
        if($data){

            foreach ($data as $d){
                $r = (new AppFeedSystemService())->addFeed($d);
                print_r("req-rep-data:".var_export([$d,$r],true));
                echo PHP_EOL;
            }

        }


        echo PHP_EOL."over".PHP_EOL;

    }



}