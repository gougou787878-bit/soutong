<?php


namespace App\console;


use App\console\Queue\QueueOption;
use service\AppReportService;

class ReportCenterConsole extends AbstractConsole
{

    /**
     * @var string 定义同步命令
     */
    public $name = 'report-center';
    /**
     * @var string 定义命令描述
     */
    public $description = '数据中心';

    /**
     * @var callable[]
     */
    private $works = [];

    /**
     * debug  模式  php yaf report-data nodaemon
     * daemon 模式  php yaf report-data
     *
     * @param $argc
     * @param $argv
     *
     */
    public function process($argc, $argv)
    {

        $noDaemon = $argv[1] ?? null;
        if ($noDaemon == 'nodaemon') {
            echo "start nodaemon report-center \r\n";
            $service = new AppReportService();
            $service->startDeamonReportData();
            echo "\r\nover\r\n";
            return;
        }
        echo "start daemonize report-center \r\n";
        $this->daemonize();
        $this->fork($this->monitorWorker(), $this->getForkDie());
    }


    /**
     * 工作进程,如果进程退出后，会自动重新启用一个
     *
     * @date 2020-06-02 11:00:17
     */
    protected function workerProcess()
    {
        (new AppReportService())->startDeamonReportData();
    }


    /**
     * 守护进程，子进程一旦挂掉，重新启用一个子进程
     * @return \Closure
     *
     * @date 2020-06-02 10:58:50
     */
    protected function monitorWorker()
    {
        return function ($pid) {
            while (1) {
                pcntl_waitpid($pid, $status, WUNTRACED);
                $this->fork(function ($_pid) use (&$pid) {
                    $pid = $_pid;
                }, function () {
                    cli_set_process_title('report-center worker');
                    $this->workerProcess();
                });
                usleep(50000);
            }
        };
    }


    /**
     * 脱离终端的上下文，使当前进程不受终端退出而退出的影响
     *
     * @date 2020-06-02 10:57:00
     */
    public function daemonize()
    {
        $this->fork($this->getForkDie(), null);
        if (!posix_setsid()) {
            exit('set sid error.');
        }
        $this->fork($this->getForkDie(), null);
        cli_set_process_title('report-center Master');
    }


    /**
     * fork一个进程
     * @param callable $masterProcess 一个回调函数，用来处理主进程要做的事情
     * @param callable $childProcess 一个回调函数，用来处理子进程要做的事情
     *
     * @date 2020-06-02 10:55:51
     */
    protected function fork($masterProcess, $childProcess)
    {
        $pid = pcntl_fork();
        switch ($pid) {
            case -1:
                die('fork process fail');
            default:
                if (is_callable($masterProcess)) {
                    $masterProcess($pid);
                }
                return;
            case 0:
                if (is_callable($childProcess)) {
                    return $childProcess($pid);
                }
        }
    }


    protected function getForkDie($msg = '')
    {
        return function () use ($msg) {
            die($msg);
        };
    }

    private function addWork($param)
    {
        $this->works[] = $param;
    }


}