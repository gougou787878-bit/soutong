<?php

namespace App\console;

use service\FileService;


class processQueueConsole extends AbstractConsole
{
    public $name = "process-queue";

    public $description = 'process-queue';

    public function process($argc, $argv)
    {
        FileService::processQueue();
    }

}