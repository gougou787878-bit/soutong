<?php

namespace App\console\Make;

use App\console\AbstractConsole;
use App\console\MakeConsole;

/**
 * Class ValidatorCommand
 *
 *
 *
 * @package App\console\Make
 * @author xiongba
 * @date 2019-12-04 17:24:32
 */
class ValidatorCommand extends AbstractConsole
{


    private $fieldInfo;
    /**
     * @var MakeConsole;
     */
    private $context;

    public function __construct($fieldInfo, $options, $context)
    {
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


    protected function help()
    {
        $prefix = join(' ', [$GLOBALS["argv"][0], $GLOBALS["argv"][1]]);
        $space = str_repeat(' ', strlen($prefix));
        echo <<<ARGV
$prefix
$space table    ;指定表
$space overlay  ;指定是否要覆盖

示例:
    $prefix module=admin table=members overlay
    
ARGV;
    }


    protected function dbTypeToPhpType($type)
    {
        if (strpos($type, 'int') !== false) {
            return 'int';
        }
        return 'string';
    }


    public function process($argc, $argv)
    {
        if (empty($this->options['table'])) {
            $this->help();
            return;
        }
        $this->makeController($this->options['table']);
    }


    protected function makeController($table)
    {
        $fields = [];
        $pkVal = null;
        $propertyList = [];
        $constList = [];

        $rules = [];

        foreach ($this->fieldInfo as $item) {

            $name = $item['COLUMN_NAME'];

            $allowNull = $item['IS_NULLABLE'] == 'YES';


            $rule = [];
            if (!$allowNull) {
                $rule[] = 'required';
            }
            $phpType = $this->dbTypeToPhpType($item['DATA_TYPE']);

            if ($phpType === 'int') {
                $rule[] = 'numeric';
            } else {
                $dateType = trim(strtolower($item['DATA_TYPE']));
                if ($dateType == 'enum') {
                    $array = $this->context->enumToArray($item['COLUMN_TYPE']);
                    $rule[] = 'enum:' . join(',', $array);
                } else {
                    if (strpos($item['COLUMN_TYPE'], '(') !== false) {
                        list(, $d) = explode('(', $item['COLUMN_TYPE']);
                        $rule[] = 'min:1';
                        $rule[] = 'max:' . (int)($d);
                    }
                }
            }
            $rules[$name] = join('|', $rule);
        }
        var_export($rules);
        echo "\r\n";
    }

    protected function getTplContent()
    {
        $file = __DIR__ . "/stubs/model.stub";
        $content = file_get_contents($file);
        return $content;
    }

}