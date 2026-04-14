<?php

namespace App\console;


class Jobs3Console extends AbstractConsole
{
    public $name = 'jobs3';

    public $description = '新上报任务';

    public function pidFile()
    {
        return sys_get_temp_dir() . '/' . str_replace([':', '/'], '_', __FILE__) . '.pid';
    }

    public function process($argc, $argv)
    {
        if ($argv[1] == 'stop') {
            $this->stop();
        } elseif ($argv[1] == 'restart') {
            $this->restart();
        } else {
            $this->start();
        }
    }

    public function stop()
    {
        $pidfile = $this->pidFile();
        if (!file_exists($pidfile)) {
            echo("进程已停止或没有启动\r\n");
            return ;
        }
        $pid = file_get_contents($pidfile);
        if (empty($pid)){
            echo("进程异常，请尝试手动停止\r\n");
            return ;
        }
        posix_kill($pid, SIGINT);
        echo("已成功发送[stop]信号到进程: $pid\r\n");
        echo("等待进程{$pid}停止\r\n");
        $i = 0;
        while ($i++ < 100) {
            sleep(1);
            if (!file_exists($pidfile)) {
                break;
            }
        }
        echo("已经成功处理[stop]信号\r\n");
    }

    public function restart()
    {
        $this->stop();
        $this->start();
    }

    private function processPidFile()
    {
        if (file_exists($this->pidFile())) {
            exit("进程已经启动了。请勿重复启动\r\n");
        } else {
            file_put_contents($this->pidFile(), posix_getppid());
        }
    }

    private function checkPidFile(){
        $file = $this->pidFile();
        if (!file_exists($file) || filesize($file) === 0){
            file_put_contents($this->pidFile(), posix_getppid());
        }
    }

    public function start($num = 5)
    {
        if (file_exists($this->pidFile())) {
            exit("进程已经启动了。请勿重复启动\r\n");
        }
        $processId = posix_getppid();
        echo("正在尝试启动进程，当前进程id：{$processId}\r\n");
        daemonize();
        $processId = posix_getppid();
        echo("master-pid: {$processId}\r\n");
        $this->processPidFile();
        $processIdArray = [];
        $queues = [];
        while (true) {
            if (count($processIdArray) < $num) {
                for ($i = count($processIdArray); $i < $num; $i++) {
                    if (!isset($processIdArray[$i])) {
                        $processId = $this->forkQueue();
                        if (!empty($processId)) {
                            $processIdArray[$i] = $processId;
                        }
                    }
                }
                $this->registerSignal($processIdArray);
            }
            foreach ($processIdArray as $key => $processId) {
                $res = pcntl_waitpid($processId, $status, WNOHANG);
                if ($res == -1 || $res > 0) {
                    unset($processIdArray[$key]);
                }
            }
            pcntl_signal_dispatch();
            usleep(500000);
            $this->checkPidFile();
        }
    }

    protected function registerSignal($pid)
    {
        pcntl_signal(SIGINT, function ($signo) use ($pid) {
            if ($signo == SIGINT) {
                $pids = (array)$pid;
                foreach ($pids as $pid) {
                    $pid && posix_kill($pid, SIGINT);
                }
                if ($pid) {
                    unlink($this->pidFile());
                }
                exit();
            }
        });
    }


    protected function forkQueue($queue = 'jobs:work:queue:3')
    {
        $pid = pcntl_fork();
        if ($pid < 0) {
            return false;
        }
        if ($pid > 0) {
            return $pid;
        }
        $this->registerSignal(0);
        $rowBuffer = false;
        $i = 1;
        while (true) {
            try {
                pcntl_signal_dispatch();
                $buffer = $rowBuffer = redis()->lPop($queue);
                if ($buffer === false) {
                    usleep(1024 * ($i >= 1000 ? 1000 : $i *= 10));
                    continue;
                }
                if (!is_string($buffer)){
                    trigger_log("buffffff -> " . var_export($buffer , 1));
                    continue;
                }
                list($buffer) = json_decode($buffer , true);
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
            } catch (\Throwable $e) {
                if ($this->causedByLostConnection($e)) {
                    if (is_string($rowBuffer)) {
                        redis()->rPush($queue, $rowBuffer);
                    }
                    trigger_log("Jobs: 数据链接丢失，进程退出");
                    exit(0);
                }
                trigger_log($e->getMessage());
                if (isset($buffer)) {
                    trigger_log($buffer);
                }
            }
        }
        exit(0);
    }


}