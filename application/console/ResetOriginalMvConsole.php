<?php
namespace App\console;

use OriginalModel;

class ResetOriginalMvConsole extends AbstractConsole
{
    //每月一号跑一次
    public $name = 'reset-original-mv';

    public $description = '初始化片库';

    public function process($argc, $argv)
    {
        echo "#################  start ############## \r\n ";

        $this->dealData();

        echo "#################  over ############## \r\n ";
    }

    public function dealData()
    {
        OriginalModel::queryBase()
            ->chunkById(20, function ($items) {
                $ids = collect($items)->pluck('id')->toArray();
                $data = [
                    'hot_c_month' => 0
                ];
                OriginalModel::whereIn('id', $ids)->update($data);
            });
    }

}