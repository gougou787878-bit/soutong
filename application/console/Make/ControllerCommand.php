<?php

namespace App\console\Make;

use App\console\AbstractConsole;
use App\console\MakeConsole;

class ControllerCommand extends AbstractConsole
{
    private $fieldInfo;
    /**
     * @var MakeConsole;
     */
    private $context ;

    public function __construct($fieldInfo, $options , $context) {
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


    public function process($argc, $argv) {
        if (empty($this->options['table'])) {
            $this->help();
            return ;
        }
        $this->makeController($this->options['table']);
    }


    protected function makeController($table) {
        $pkVal = null;
        foreach ($this->fieldInfo as $item) {
            if ($pkVal == null && $item['COLUMN_KEY'] === 'PRI') {
                $pkVal = $item['COLUMN_NAME'];
                break;
            }
        }

        $content = $this->getTplContent();

        $className = $this->context->controllerCamelName($table);
        $moduleName = $this->context->camelName($this->options['module']);
        $modelName = $this->context->camelName($table);
        $content = str_replace(
            [
                '{{className}}',
                '{{date}}',
                '{{pkName}}',
                '{{ModelName}}',
            ],
            [
                $className,
                date('Y-m-d H:i:s'),
                $pkVal,
                $modelName . 'Model'
            ], $content);


        if (empty($this->options['module'])) {
            echo $content;
        } else {
            $file = sprintf("%s/modules/%s/controllers/%s.php"
                , config('application.directory')
                , $moduleName
                , $className
            );
            $_file = str_replace(config('application.directory'), '', $file);

            if (!file_exists(dirname($file))) {
                mkdir(dirname($file));
            }
            if (file_exists($file) && !isset($this->options['overlay'])) {
                $this->logWarning("{{$_file}}控制器已存在");
            } else {
                file_put_contents($file, $content);
                $this->logSuccess("{{$_file}}控制器生成成功");
            }
        }
    }


    public function getTplContent()
    {
        $file = __DIR__ . "/stubs/controller.stub";
        $content = file_get_contents($file);
        return $content;
    }

}