<?php

namespace App\console;

use service\AiSdkService;

class AiStripConsole extends AbstractConsole
{

    public $name = 'ai-strip';

    public $description = 'AI脱衣';

    public function process($argc, $argv)
    {
        AiSdkService::start_task_strip();
    }


}