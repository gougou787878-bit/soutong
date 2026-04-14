<?php

namespace App\console;

use service\AiSdkService;
use service\ApiAiDrawService;

class AiManualConsole extends AbstractConsole
{

    public $name = 'ai-manual-callback';

    public $description = 'AI手动回调';

    public function process($argc, $argv)
    {
        global $argv;
        $index = (int)($argv[2] ?? '');
        AiSdkService::manual_callback($index);
    }

}