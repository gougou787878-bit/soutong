<?php

namespace App\console;

use service\ApiAiMagicService;

class AiMagicConsole extends AbstractConsole
{

    public $name = 'ai-magic';

    public $description = 'AI魔法';

    public function process($argc, $argv)
    {
        ApiAiMagicService::start_task();
    }


}