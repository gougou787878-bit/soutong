<?php

namespace App\console;
use Illuminate\Database\DetectsLostConnections;
use Throwable;

class Jobs2Console extends AbstractConsole
{
    use DetectsLostConnections;

    public $name = 'jobs2';

    public $description = '监听异步任务2';

    public function process($argc, $argv)
    {
        $rowBuffer = false;
        $i = 1;
        $qname = 'jobs:work:queue:2';
        while (true) {
            try {
                $buffer = $rowBuffer = redis()->lPop($qname);
                if ($buffer === false) {
                    usleep(1024 * ($i >= 1000 ? 1000 : $i *= 10));
                    continue;
                }
                if (!is_string($buffer)) {
                    trigger_log("buffffff -> " . var_export($buffer, 1));
                    continue;
                }
                list($buffer) = json_decode($buffer, true);
                $i = 1;
                $ary = @unserialize($buffer);
                if (empty($ary)) {
                    trigger_log("queue:jobs:" . $buffer);
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
                    trigger_log("Jobs2: 数据链接丢失，进程退出");
                    exit(0);
                }
                trigger_log($e->getMessage());
                if (isset($buffer)) {
                    trigger_log($buffer);
                }
            }
            exit(0);
        }

    }
}