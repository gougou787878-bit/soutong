<?php


use App\console\AbstractConsole;

class ConsoleController extends \Yaf\Controller_Abstract
{


    /**
     * @var AbstractConsole[]
     */
    protected $_process;

    public function init() {
        if (PHP_SAPI != "cli") {
            exit;
        }
    }

    public function mainAction() {
        $argv = $this->getRequest()->getParams();
        $cli = array_shift($argv);
        $argc = count($argv);
        $targetProcess = null;
        if ($argc) {
            $targetProcess = array_shift($argv);
            array_unshift($argv, $targetProcess);
        }

        $filename = glob(config('application.directory') . '/console/*.php');
        $ok = false;
        foreach ($filename as $item) {
            $basename = basename(substr($item, 0, -4));
            if ($basename === 'AbstractConsole') {
                continue;
            }
            $this->importProcess($basename);
            if (isset($targetProcess)) {
                $ok = $this->executeProcess($targetProcess, $argv);
            }
            if ($ok){
                break;
            }
        }
        if (!$ok) {
            $this->help($cli);
        }
        die(0);
    }

    /**
     * 倒入进程
     * @param $console
     * @author xiongba
     * @date 2019-11-29 20:30:54
     */
    protected function importProcess($console) {
        $class = "\\App\\console\\$console";
        $object = new $class;
        if ($object instanceof AbstractConsole) {
            $this->_process[] = $object;
        }
    }

    /**
     * 执行进程
     * @param $targetProcess
     * @param $argv
     * @return bool
     * @author xiongba
     * @date 2019-11-29 20:31:08
     */
    protected function executeProcess($targetProcess, $argv) {
        foreach ($this->_process as $process) {
            list($targetProcess) = explode(':', $targetProcess);

            if ($process->name === $targetProcess) {
                $process->process(count($argv), $argv);
                return true;
            }
        }
        return false;
    }

    protected function help($cli) {
        foreach ($this->_process as $process) {
            echo "$cli ", $process->name, '  ;', $process->description, "\r\n";
        }
        echo "\r\n";
    }

}