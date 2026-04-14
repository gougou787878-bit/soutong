<?php

namespace App\console\Make;

use App\console\AbstractConsole;
use App\console\MakeConsole;

/**
 * Class ModelCommand
 *
 * @package App\console\View
 * @author xiongba
 * @date 2019-12-02 17:42:48
 */
class ModelCommand extends AbstractConsole
{


    private $fieldInfo;
    /**
     * @var MakeConsole;
     */
    private $context;

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


    public function help() {
        $prefix = join(' ', [$GLOBALS["argv"][0], $GLOBALS["argv"][1]]);
        $space = str_repeat(' ', strlen($prefix));
        echo <<<ARGV
$prefix
$space table    ;指定表
$space overlay  ;指定是否要覆盖

示例:
    $prefix module=admin table=members overlay
    
ARGV;
        echo "\r\n";
    }


    protected function dbTypeToPhpType($type) {
        if (strpos($type, 'int') !== false) {
            return 'int';
        }
        return 'string';
    }


    public function process($argc, $argv) {
        if (empty($this->options['table'])) {
            $this->help();
            return;
        }
        $this->makeController($this->options['table']);
    }


    protected function makeController($table) {
        $fields = [];
        $pkVal = null;
        $propertyList = [];
        $constList = [];


        foreach ($this->fieldInfo as $item) {
            $name = $item['COLUMN_NAME'];
            $propertyList[] = sprintf(' * @property %s $%s %s'
                , $this->dbTypeToPhpType($item['DATA_TYPE'])
                , $name
                , empty($item['COLUMN_COMMENT']) ? '' : $item['COLUMN_COMMENT']
            );

            $dateType = trim(strtolower($item['DATA_TYPE']));

            if ($dateType == 'enum') {
                $array = $this->context->enumToArray($item['COLUMN_TYPE']);
                $array = array_map(function ($item) use ($name) {
                    return sprintf('    const %s = \'%s\';', strtoupper($name . "_" . $item), $item);
                }, $array);
                $constList = array_merge($constList, $array);
            }

            if ($pkVal == null && $item['COLUMN_KEY'] === 'PRI') {
                $pkVal = $item['COLUMN_NAME'];
                continue;
            }
            $fields[] = "'$name'";
        }

        $content = $this->getTplContent();
        $modelName = $this->context->camelName($table);
        $search = [
            '{{className}}',
            '{{date}}',
            '{{pkName}}',
            '{{ModelName}}',
            '{{table}}',
            '{{fields}}',
            '{{propertyList}}',
            '{{constList}}',
        ];
        $replace = [
            $modelName,
            date('Y-m-d H:i:s'),
            $pkVal,
            $modelName . 'Model',
            $table,
            sprintf('[%s]', join(', ', $fields)),
            join("\r\n", $propertyList),
            join("\r\n", $constList),
        ];
        $content = str_replace($search, $replace, $content);

        if (empty($this->options['module'])) {
            echo $content;
        } else {
            $file = sprintf("%s/models/%s.php"
                , config('application.directory')
                , $modelName
            );
            $_file = str_replace(config('application.directory'), '', $file);

            $this->context->dirNotExistsCreate($file);

            if (file_exists($file) && !isset($this->options['overlay'])) {
                $this->logWarning("{{$_file}}模型已存在");
            } else {
                file_put_contents($file, $content);
                $this->logSuccess("{{$_file}}模型生成成功");
            }
        }
    }

    protected function getTplContent() {
        $file = __DIR__ . "/stubs/model.stub";
        $content = file_get_contents($file);
        return $content;
    }

}