<?php

namespace App\console;

use Illuminate\Database\DetectsLostConnections;
use service\PaySuccessDispatcher;
use Throwable;

class PaySuccessJobsConsole extends AbstractConsole
{
    use DetectsLostConnections;

    public $name = 'pay-success';

    public $description = 'pay success async queue';

    public function process($argc, $argv)
    {
        $rowBuffer = false;
        $i = 1;
        $qname = PaySuccessDispatcher::REDIS_QUEUE;
        while (true) {
            try {
                $buffer = $rowBuffer = redis()->lPop($qname);
                if ($buffer === false) {
                    usleep(1024 * ($i >= 1000 ? 1000 : $i *= 10));
                    continue;
                }
                if (!is_string($buffer)) {
                    trigger_log('buffffff -> ' . var_export($buffer, true));
                    continue;
                }
                list($buffer) = json_decode($buffer, true);
                $i = 1;
                $ary = @unserialize($buffer);
                if (empty($ary)) {
                    trigger_log('queue:pay-success:' . $buffer);
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
                    trigger_log('PaySuccessJobs: lost db connection, exit');
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
