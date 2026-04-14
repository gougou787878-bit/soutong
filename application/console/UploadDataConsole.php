<?php


namespace App\console;


use App\console\Queue\QueueOption;
use service\AppCenterService;
use service\FileUploadService;

class UploadDataConsole extends AbstractConsole
{


    public $name = 'upload-batch';

    public $description = '上传视频数据';


    public function process($argc, $argv) {
        set_time_limit(0);
       echo "start 上传视频数据\r\n";
       FileUploadService::uploadMP4File();
       echo "\r\n end ############ \r\n";
    }





}