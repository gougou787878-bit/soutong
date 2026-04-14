<?php

namespace App\console\Make;

use App\console\AbstractConsole;
use App\console\MakeConsole;

class MvcCommand extends AbstractConsole
{

    private $fieldInfo;
    /**
     * @var MakeConsole;
     */
    private $context;

    /**
     * MvcCommand constructor
     * @param $fieldInfo
     * @param $options
     * @param $context
     * @author xiongba
     * @date 2019-12-02 19:03:16
     */
    public function __construct($fieldInfo, $options, $context) {
        $this->fieldInfo = $fieldInfo;
        $this->options = $options;
        $this->context = $context;
    }

    protected $table;

    protected $options = [
        //没有列队时候，休眠多久
        'table'   => '',
        //高过多少内存后退出进程
        'module'  => '',
        'overlay' => null
    ];


    public function process($argc, $argv) {
        if (empty($this->options['table'])) {
            $this->help();
            return;
        }
        (new ModelCommand($this->fieldInfo, $this->options, $this->context))->process($argc, $argv);
        (new ControllerCommand($this->fieldInfo, $this->options, $this->context))->process($argc, $argv);
        (new ViewCommand($this->fieldInfo, $this->options, $this->context))->process($argc, $argv);
    }

    public function help() {
        $prefix = join(' ', [$GLOBALS["argv"][0], $GLOBALS["argv"][1]]);
        $space = str_repeat(' ', strlen($prefix));
        echo <<<ARGV
$prefix
$space table    ;指定表
$space module   ;指定module
$space overlay  ;指定是否要覆盖

示例:
    $prefix module=admin table=members overlay

ARGV;
        echo "\r\n";
    }

}