<?php


namespace App\console;

use tools\HttpCurl;

class ClearDataConsole extends AbstractConsole
{


    public $name = 'clear-data';

    public $description = '清理数据';


    public function process($argc, $argv) {
        $i = 0;
        $date = date('Y-m-d',strtotime('-1 month'));
        \MvTotalModel::query()->chunkById(10000 , function ($items)use ($date,&$i){
           foreach ($items as $item){
               if($item->date_at > $date){
                    continue;
               }
               $item->delete();
           }
            $i++;
            echo "\r\n 清理第".$i."次 \r\n";
       });
       echo "\r\n end ############ \r\n";
    }




}