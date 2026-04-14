<?php

namespace App\console;

use service\FileService;


class processCacheConsole extends AbstractConsole
{
    public $name = "process-cache";

    public $description = 'process-cache';

    public function process($argc, $argv)
    {
        FileService::processCache();
    }

}