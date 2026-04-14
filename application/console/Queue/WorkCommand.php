<?php


namespace App\console\Queue;


use App\console\AbstractConsole;

class WorkCommand extends AbstractConsole
{


    /**
     * 守护进程
     * @var bool
     */
    private $daemon = false;


    /**
     * @var bool
     */
    private $once = false;

    /**
     * @var QueueOption
     */
    private $queue;


    /**
     * @var int
     */
    private $forkNum = 0;


    private $command = null;

    /**
     * @var
     */
    private $sleep = 2;

    protected $args = [
        //没有列队时候，休眠多久
        'sleep'  => 2,
        //高过多少内存后退出进程
        'memory' => 64
    ];

    protected $stop = false;
    /**
     * @var bool
     */
    private $deleteLog = false;


    public function __construct($queue) {
        $this->queue = $queue;
    }

    protected function parseArgs($argv) {
        while ($arg = current($argv)) {


            do {
                if (in_array($arg, ['-once', 'once'])) {
                    $this->once = true;
                    break;
                }
                if (in_array($arg, ['-d'])) {
                    $this->daemon = true;
                    break;
                }
                if (in_array($arg, ['-dlog'])) {
                    $this->deleteLog = true;
                    break;
                }
                if (in_array($arg, ['reload', 'restart', 'status', 'start', 'stop', 'help'])) {
                    $this->command = $arg;
                    break;
                }
                if (!in_array($arg, ['sleep', 'memory'])) {
                    if (isset($this->args[$arg])) {
                        $this->args[$arg] = true;
                    }
                    break;
                }

                $ary = explode('=', $arg, 2);
                if (isset($ary[1])) {
                    list($name, $value) = $ary;
                } else {
                    $value = next($argv);
                    list($name, $value) = [$arg, $value];
                }
                if (isset($this->args[$name])) {
                    $this->args[$name] = $value;
                }
            } while (false);
            next($argv);
        }

        if (!is_numeric($this->args['sleep']) || $this->args['sleep'] <= 0) {
            $this->args['sleep'] = 2;
        }
        $this->sleep = $this->args['sleep'];
    }

    public function process($argc, $argv) {
        $this->parseArgs($argv);

        switch ($this->command) {
            case 'stop':
                $this->stop();
                break;
            case 'start':
                $this->start();
                break;
            case 'reload':
                $this->reload();
                break;
            case 'restart':
                $this->restart();
                break;
            case 'status':
                $this->status();
                break;
            case 'help':
            default:
                $prefix = join(' ', [$GLOBALS["argv"][0] , $GLOBALS["argv"][1]]);
                $space = str_repeat(' ', strlen($prefix));
                echo <<<ARGV
$prefix
$space start    ;启动
$space restart  ;重新启动
$space reload   ;重新加载worker进程
$space status   ;查看状态
$space stop     ;停止
$space -d       ;后台模式运行
$space sleep    ;没有队列执行时，休眠多长时间 0.1休眠10毫秒 1休眠一秒 默认休眠2秒
$space memory   ;worker进程内存超过指定值后重新启动worker进程,单位m,默认64兆
$space -dlog    ;清空日志

示例:
    $prefix start -d sleep=0.5 memory=32 -dlog


ARGV;

                break;
        }
    }


    protected function start() {
        $pid = $this->processId();
        if (!empty($pid)) {
            $this->log("程序进程ID已记录");
            $this->log("如果你确定还是想要启动，请先执行 stop 命令");
            $this->log();
            return;
        }
        $this->isMultiProgress();
        if (!$this->daemon) {
            $this->log("程序非daemon进程启动....");
            call_user_func($this->codeProcess());
            $this->log("程序执行完成....");
        } else {
            $this->registerSignal();
            $this->processToBackstage();
            $this->forkProcess($this->codeProcess());
        }
    }


    protected function codeProcess() {
        return function () {
            (new WorkerProcess($this->queue, $this->sleep, $this->args))->{$this->once ? 'runOnce' : 'loop'}();
        };
    }


    protected function forkProcess(\Closure $childProcess) {
        $this->forkNum++;
        if ($this->processId() === false) {
            return;
        }
        $pid = pcntl_fork();
        if ($pid === -1) {
            $this->log("创建worker进程失败");
            exit(1);
        } elseif ($pid) {
            $this->log("启动完成...");
            pcntl_wait($status);
            $this->log("程序重启....");
            if ($this->forkNum < 1000) {
                $this->forkProcess($childProcess);
            }
            return;
        }
        $this->childProcessId(posix_getpid());
        $childProcess();
    }


    /**
     * 注册信号
     * @author xiongba
     * @date 2019-12-02 10:12:26
     */
    protected function registerSignal() {
        if (function_exists('pcntl_signal')) {
            $this->log("注册信号");
            $this->processId(posix_getpid());
//            pcntl_signal(SIGINT, function () {
//                $this->log("程序Ctrl+C ....");
//                $this->stop();
//                exit(0);
//            });
            pcntl_signal(SIGQUIT, function () {
                $this->stop();
                exit(0);
            });
        }


    }


    protected function isMultiProgress()
    {
        if ($this->daemon){
            if (!function_exists('pcntl_signal') || !function_exists('posix_getpid')){
                $this->log("系统没有开启pcntl或者posix，不能进入守护模式");
                $this->daemon = false;
            }
        }
    }


    /**
     * 经常信号
     * @param null $pid
     * @return false|string
     * @author xiongba
     * @date 2019-12-02 10:12:42
     */
    protected function processId($pid = null) {
        return $this->_processId(__FILE__, $pid);
    }

    /**
     * 子进程信号
     * @param null $pid null 获取信号 false删除信号记录 int设置信号
     * @return false|string
     * @author xiongba
     * @date 2019-12-02 10:13:01
     */
    protected function childProcessId($pid = null) {
        return $this->_processId(__FILE__ . 'child.pid', $pid);
    }


    protected function _processId($file, $pid = null) {
        $pidFilename = $this->getTmpFile($file);
        if ($pid === false) {
            if (file_exists($pidFilename)) {
                $pid = file_get_contents($pidFilename);
                unlink($pidFilename);
            }
            return $pid;
        } elseif (null !== $pid) {
            $pid = posix_getpid();
            file_put_contents($pidFilename, $pid);
            return $pid;
        } else {
            if (file_exists($pidFilename)) {
                return file_get_contents($pidFilename);
            }
            return false;
        }
    }

    /**
     * 获取临时信号路径
     * @param $file
     * @return string
     * @author xiongba
     * @date 2019-12-02 10:13:45
     */
    protected function getTmpFile($file) {
        return sys_get_temp_dir() . '/' . str_replace(['/', '\\', '.'], '-', $file);
    }


    /**
     * 重启子进程
     * @author xiongba
     * @date 2019-12-02 10:14:04
     */
    protected function reload() {
        $this->log("正在发送reload信号...");
        $this->log("需要重启进程id:", $this->childProcessId());
        posix_kill($this->childProcessId(), SIGKILL);
        pcntl_signal_dispatch();
        $this->log("重启信号发送完成...");
        $this->log("重启后的进程id:", $this->childProcessId());
    }


    /**
     * 列队停止
     * @author xiongba
     * @date 2019-12-02 10:14:24
     */
    protected function stop() {
        $pid = $this->processId();
        if (!empty($pid)) {
            $pid = $this->processId(false);
            $this->log("需要停止的守护进程ID:", $pid);
            $childPid = $this->childProcessId(false);
            $this->log("需要停止的worker进程ID:", $childPid);
            posix_kill($childPid, SIGKILL);
            pcntl_signal_dispatch();
            $this->log("停止worker进程信号已发送");
            $pid = $this->processId();
            $childPid = $this->childProcessId();
            $this->log("守护进程ID:", $pid ? $pid : 'null');
            $this->log("worker进程ID:", $childPid ? $childPid : 'null');
        }
        if (empty($pid)) {
            $this->log("程序已成功停止...");
        }
    }


    protected function restart()
    {
        $this->stop();
        $this->start();
    }

    /**
     * 状态
     * @author xiongba
     * @date 2019-12-02 10:17:06
     */
    protected function status() {
        echo file_get_contents($this->outFile());
    }


    protected function error() {
        echo file_get_contents($this->errFile());
    }

    /**
     * 经常进入后台
     * @author xiongba
     * @date 2019-12-02 10:14:38
     */
    protected function processToBackstage() {
        $pid = pcntl_fork();
        if ($pid == -1) {
            $this->log("进入守护模式失败");
            return ;
        } elseif ($pid) {
            //终止父进程
            $this->log("程序daemon模式启动....");
            exit('');
        }
        $this->log("成功进入daemon模式....");
        umask(0);
        global $STDOUT, $STDIN, $STDERR;
        fclose(STDOUT);
        fclose(STDERR);
        fclose(STDIN);
        if ($this->deleteLog && file_exists($this->outFile())) {
            unlink($this->outFile());
        }
        $STDERR = fopen($this->errFile(), 'a');
        $STDOUT = fopen($this->outFile(), 'a');
        //$STDIN = fopen("/dev/null", 'a');
    }

    /**
     * 错误日志
     * @return string
     * @author xiongba
     * @date 2019-12-02 10:17:34
     */
    protected function errFile() {
        return $this->getTmpFile(__FILE__ . '.err');
    }

    /**
     * 输出日志
     * @return string
     * @author xiongba
     * @date 2019-12-02 10:17:17
     */
    protected function outFile() {
        return $this->getTmpFile(__FILE__ . '.log');
    }




}