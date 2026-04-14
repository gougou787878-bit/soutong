<?php

namespace App\console\Make;


use App\console\MakeConsole;

class ViewCommand extends \App\console\AbstractConsole
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
            return;
        }
        $this->makeView($this->options['table']);
    }


    protected function makeView($table) {

        $cols = [];
        $pkVal = null;

        $selectArray = [];
        $switchArray = [];
        $searchArray = [];
        foreach ($this->fieldInfo as $item) {
            $col = [
                'key'       => $item['COLUMN_NAME'],
                'name'      => $item['COLUMN_COMMENT'],
                'allowNull' => $item['IS_NULLABLE'] == 'YES'
            ];

            $dateType = trim(strtolower($item['DATA_TYPE']));
            if ($dateType == 'enum') {
                $array = $this->context->enumToArray($item['COLUMN_TYPE']);
                if (count($array) == 2){
                    $switchArray[$item['COLUMN_NAME']] =$array;
                }else{
                    $selectArray[$item['COLUMN_NAME']] = $array;
                }
            }
            $col['name'] = !empty($col['name']) ? $col['name'] : $item['COLUMN_NAME'];
            if ($pkVal == null && $item['COLUMN_KEY'] === 'PRI') {
                $pkVal = $item['COLUMN_NAME'];
            }
            $cols[] = $col;
        }
        $searchArray = array_merge($selectArray , $switchArray);
        defined('STATIC_VER') || define('STATIC_VER', '<?php echo LAY_UI_STATIC ?>');
        $static = STATIC_VER;
        $tableName = '';
        ob_start();
        require __DIR__ . "/stubs/view.phtml";
        $content = ob_get_contents();
        ob_end_clean();
        $moduleName =  $this->options['module'] ??'';
        $moduleName = trim($moduleName);

        if (empty($moduleName)) {
            echo $content;
        } else {
            $file = sprintf("%s/modules/%s/views/%s/index.phtml"
                , config('application.directory')
                , ucfirst($moduleName)
                , strtolower($this->context->camelName($table))
            );
            $_file = str_replace( config('application.directory') , "" , $file);

            $this->context->dirNotExistsCreate($file);

            if (file_exists($file) && !isset($this->options['overlay'])) {
                $this->logWarning("{{$_file}}:视图已存在");
            } else {
                file_put_contents($file, $content);
                $this->logSuccess("{{$_file}}:视图生成成功");
            }
        }
    }
}