<?php


namespace App\console\Queue;


use App\console\AbstractConsole;

class ClearCommand extends AbstractConsole
{


    /**
     * @var QueueOption
     */
    private $queue;
    /**
     * @var string
     */
    private $command;


    public function __construct($queue) {
        $this->queue = $queue;
    }

    protected function parseArgs($argv) {
        while ($arg = current($argv)) {
            if (in_array($arg, ['all', 'delayed', 'wait', 'reserved'])) {
                $this->command = $arg;
                break;
            }
            next($argv);
        }
    }

    /**
     * @param $argc
     * @param $argv
     * @author xiongba
     * @date 2019-12-02 14:20:00
     */
    public function process($argc, $argv) {
        $this->parseArgs($argv);

        switch ($this->command) {
            case 'all':
                $this->clearAll();
                break;
            case 'delayed':
                $this->clearDelayed();
                break;
            case 'reserved':
                $this->clearReserved();
                break;
            case 'wait':
                $this->clearWait();
                break;
            default:
                $this->help();
                break;
        }
        echo sprintf("[%s] ", date('Y-m-d H:i:s')) . "处理{$this->command}\r\n";
        echo sprintf("[%s] ", date('Y-m-d H:i:s')) . "处理完成\r\n\r\n";
    }


    protected function help() {
        $prefix = join(' ', [$GLOBALS["argv"][0], $GLOBALS["argv"][1]]);
        $space = str_repeat(' ', strlen($prefix));
        echo <<<ARGV
$prefix
$space all        ;清除所有队列
$space delayed    ;清除延迟队列
$space reserved   ;清除已完成的队列
$space wait       ;清除等待执行的队列

示例:
    $prefix all 


ARGV;
        die;
    }

    /**
     * @author xiongba
     * @date 2019-12-02 14:19:57
     */
    protected function clearAll() {
        $this->clearDelayed();
        $this->clearWait();
        $this->clearReserved();
    }

    /**
     * @author xiongba
     * @date 2019-12-02 14:19:55
     */
    protected function clearReserved() {
        $key = $this->queue->getName() . ':reserved';
        while (true) {
            $this->queue->getDriver()->rPop($key);
            if (empty($t)) {
                break;
            }
        }
    }

    /**
     * @author xiongba
     * @date 2019-12-02 14:19:52
     */
    protected function clearDelayed() {
        $key = $this->queue->getName() . ':delayed';
        while (true) {
            $this->queue->getDriver()->rPop($key);
            if (empty($t)) {
                break;
            }
        }
    }

    /**
     * @author xiongba
     * @date 2019-12-02 14:23:34
     */
    protected function clearWait() {
        $key = $this->queue->getName();
        while (true) {
            $this->queue->getDriver()->rPop($key);
            if (empty($t)) {
                break;
            }
        }
    }

}