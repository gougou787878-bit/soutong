<?php


namespace App\console;


use SeedFavoritesModel;
use SeedLikeModel;
use SeedPostModel;
use service\AppCenterService;

class SeedPostSortConsole extends AbstractConsole
{


    public $name = 'seed-post-sort';

    public $description = '种子排序字段维护';


    public function process($argc, $argv) {
        set_time_limit(0);
       echo "start 开始\r\n";
       self::defendSeedPost();
      echo PHP_EOL, "结束", PHP_EOL;
    }

    public static function defendSeedPost(){
        $d = (int)date('d');
        $b_three_month = strtotime('-3 month');
        $start_time = date('Y-m-d 00:00:00', $b_three_month);
        $end_time = date('Y-m-d 23:59:59', $b_three_month);
        SeedPostModel::query()
            ->where('status', SeedPostModel::STATUS_ON)
            ->where('is_finished', SeedPostModel::FINISHED_OK)
            ->orderBy('id')
            ->chunkById(500 , function ($items) use ($d, $start_time, $end_time){
                collect($items)->each(function (SeedPostModel $item) use ($d, $start_time, $end_time) {
                    if ($d == 1){
                        $item->hot_sort = 0;
                    }
                    $like_ct = SeedLikeModel::where('related_id', $item->id)
                        ->whereBetween('created_at', [$start_time, $end_time])
                        ->count();
                    $fav_ct = SeedFavoritesModel::where('zy_id', $item->id)
                        ->whereBetween('created_at', [$start_time, $end_time])
                        ->count();
                    $t_ct = $like_ct + $fav_ct;
                    if ($t_ct > 0){
                        $rec_sort = max($item->rec_sort - $t_ct, 0);
                        $item->rec_sort = $rec_sort;
                    }
                    if ($item->isDirty()){
                        $item->save();
                    }
                });
            });
    }
}