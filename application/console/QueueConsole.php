<?php


namespace App\console;


use App\console\Queue\QueueOption;

class QueueConsole extends AbstractConsole
{

    public $name = 'queue';

    public $description = '执行队列';


    public function process($argc, $argv) {
        list($targetProcess) = $argv;

        if (strpos($targetProcess , ':') === false){
            $this->help();
        }


        list(, $fn) = explode(':', $targetProcess);
        $class = __NAMESPACE__ . '\\Queue\\' . ucfirst($fn) . 'Command';
        if (!@class_exists($class)) {
            $this->help();
        }
        (new $class(QueueOption::getInstance()))->process($argc, $argv);
    }



    protected function help()
    {
        $prefix = $GLOBALS["argv"][0];
        $space = str_repeat(' ', strlen($prefix));
        echo <<<ARGV
$prefix
$space queue:work   ;执行列队
$space queue:clear  ;清空队列

事例:
    $prefix queue:work


ARGV;
        die;
    }

}