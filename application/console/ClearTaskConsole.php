<?php


namespace App\console;

use tools\HttpCurl;

class ClearTaskConsole extends AbstractConsole
{


    public $name = 'clear-task';

    public $description = '清理任务';


    public function process($argc, $argv) {
        $i = 0;
        $time  = strtotime('-1 month');
        \FeedbackModel::query()->chunkById(10000 , function ($items)use ($time,&$i){
           foreach ($items as $item){
               if($item->addtime > $time){
                    continue;
               }
               \FeedbackReplyModel::query()->where('fid',$item->id)->delete();
               $item->delete();
           }
            $i++;
            echo "\r\n 清理反馈第".$i."次 \r\n";
       });
       echo "\r\n end ############ \r\n";
    }




}