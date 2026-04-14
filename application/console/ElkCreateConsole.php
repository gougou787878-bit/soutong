<?php


namespace App\console;

use MvModel;

class ElkCreateConsole extends AbstractConsole
{

    /**
     * @var string 定义同步命令
     */
    public $name = 'elk-create-data';
    /**
     * @var string 定义命令描述
     */
    public $description = 'elk数据准备';

    /**
     * @var callable[]
     */
    private $works = [];

    /**
     *  php yaf elk-create-data
     *
     * @param $argc
     * @param $argv
     *
     */
    public function process($argc, $argv)
    {
        echo "start daemonize elk-create-data \r\n";

        self::batchMvInsert();
        //$this->search();

        echo "\r\n over \r\n";
    }

    /**
     * 批量导入数据
     */
    public static function batchMvInsert()
    {
        $hour = date('H');
        if (!in_array($hour, [21,22,23,0,1])){
            MvModel::where('refresh_at', '>', strtotime('-8 hours'))->chunkById(1000,function (\Illuminate\Support\Collection $items){
                collect($items)->each(function (MvModel $item){
                    \service\EsService::syncMv($item);
                    echo "已同步视频:" . $item->id . PHP_EOL;
                });
            });
        }
    }


}