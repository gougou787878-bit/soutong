<?php


namespace App\console;


class MakeConsole extends AbstractConsole
{
    public $name = "make";

    public $description = '代码成功器';
    protected $options = [
        'table'   => '',
        'module'  => '',
        'overlay' => null
    ];


    public function process($argc, $argv) {
        list($targetProcess) = $argv;

        if (strpos($targetProcess, ':') === false) {
            $this->help();
        }
        list(, $fn) = explode(':', $targetProcess);
        $class = __NAMESPACE__ . '\\Make\\' . ucfirst($fn) . 'Command';
        if (!@class_exists($class)) {
            $this->help();
        }

        $this->parseArgs($argv);

        $this->_process($class);
    }


    protected function _process($class) {
        if (empty($this->options['table'])) {
            (new $class(null,null,null))->help();
            return;
        }
        foreach (explode(',', $this->options['table']) as $item) {
            $field = $this->getFieldInfo($item);
            if (!empty($field)) {
                $data = json_decode(json_encode($field), 1);
                $option = $this->options;
                $option['table'] = $item;
                (new $class($data, $option, $this))->process(0, []);
            }
        }
    }

    protected function parseArgs($argv) {
        while ($arg = current($argv)) {
            do {
                $ary = explode('=', $arg, 2);
                if (!in_array($ary[0], array_keys($this->options))) {
                    continue;
                }
                if (isset($ary[1])) {
                    list($name, $value) = $ary;
                } else {
                    $value = next($argv);
                    list($name, $value) = [$arg, $value];
                }
                if (array_key_exists($name, $this->options)) {
                    $this->options[$name] = $value;
                }
            } while (false);
            next($argv);
        }

    }

    protected function getFieldInfo($table) {
        $sql = sprintf("select COLUMN_NAME, COLUMN_DEFAULT, DATA_TYPE, COLUMN_TYPE, COLUMN_KEY, COLUMN_COMMENT, IS_NULLABLE
from information_schema.COLUMNS
where TABLE_SCHEMA = '%s'
  and TABLE_NAME = '%s%s';", \DB::getDatabaseName(), \DB::getTablePrefix(), $table);
        $data = \DB::select($sql);
        if (empty($data)) {
            $this->logError("{{$table}}数据表不存在");
            return false;
        }
        return $data;
    }

    protected function help() {
        $prefix = $GLOBALS["argv"][0];
        $space = str_repeat(' ', strlen($prefix));
        echo <<<ARGV
$prefix
$space make:view         ;生成试图
$space make:controller   ;生成控制器
$space make:model        ;生成模型
$space make:mvc          ;生成model,controller,view

事例:
    $prefix make:view


ARGV;
        die;
    }


    public function camelName($name) {
        $ary = preg_split('@[-_ ,="\']@', $name, -1, PREG_SPLIT_NO_EMPTY);
        return join('', array_map('ucfirst', $ary));
    }


    public function controllerCamelName($name) {
        $ary = preg_split('@[-_ ,="\']@', $name, -1, PREG_SPLIT_NO_EMPTY);
        return ucfirst(join('', $ary));
    }

    public function dirNotExistsCreate($file) {
        if (!file_exists(dirname($file))) {
            @mkdir(dirname($file), 0755, true);
        }
    }


    /**
     * enum转数组
     * @param $type
     * @return mixed
     * @author xiongba
     * @date 2019-12-02 19:07:19
     */
    public function enumToArray($type)
    {
        $type = ltrim($type, "enum(");
        $type = rtrim($type, ")");
        $type = str_replace("'", '"', $type);
        return json_decode("[$type]", 1);
    }

}