<?php

namespace App\console;

use Illuminate\Database\DetectsLostConnections;
use service\MarketingLotteryTriggerDispatcher;
use Throwable;

class MarketingLotteryTriggerJobsConsole extends AbstractConsole
{
    use DetectsLostConnections;

    public $name = 'marketing-lottery-trigger';

    public $description = 'marketing lottery trigger async queue';

    public function process($argc, $argv)
    {
        $rowBuffer = false;
        $i = 1;
        $qname = MarketingLotteryTriggerDispatcher::REDIS_QUEUE;
        while (true) {
            try {
                $buffer = $rowBuffer = redis()->lPop($qname);
                if ($buffer === false) {
                    usleep(1024 * ($i >= 1000 ? 1000 : $i *= 10));
                    continue;
                }
                if (!is_string($buffer)) {
                    trigger_log('marketing_lottery_trigger_buffer -> ' . var_export($buffer, true));
                    continue;
                }
                list($buffer) = json_decode($buffer, true);
                $i = 1;
                $ary = @unserialize($buffer);
                if (empty($ary)) {
                    trigger_log('queue:marketing-lottery-trigger:' . $buffer);
                    continue;
                }
                list($process, $args) = $ary;
                if (is_callable($process)) {
                    call_user_func_array($process, $args);
                }
            } catch (Throwable $e) {
                if ($this->causedByLostConnection($e)) {
                    if (is_string($rowBuffer)) {
                        redis()->rPush($qname, $rowBuffer);
                    }
                    trigger_log('MarketingLotteryTriggerJobs: lost db connection, exit');
                    exit(0);
                }
                trigger_log($e->getMessage());
                if (isset($buffer)) {
                    trigger_log($buffer);
                }
            }
        }
    }
}
