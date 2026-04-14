<?php


namespace App\console;


use App\console\Queue\QueueOption;
use MvModel;
use tools\RedisService;

class ResetRedisConsole extends AbstractConsole
{
    public $name = 'reset-small-mv';
    public $description = '导入redis，初始化小视频';

    public function process($argc, $argv)
    {
        $hour = date('H');
        if (!in_array($hour, [22,23,0,1])){
            // init redis-data
            self::getRecommendMv();
            self::getCoinsMvIds();
        }
    }

    /**
     * 默认推荐视频 池子
     * @return void
     */
    static function getRecommendMv()
    {
        redis()->del(MvModel::RECOMMEND_FEE_KEY);
        MvModel::queryBase()
            ->where('type', MvModel::TYPE_SHORT)
            ->where('is_aw', MvModel::AW_NO)
            ->select(['id', 'like'])
            ->get()
            ->pluck('id')
            ->chunk(500)
            ->map(function ($_vids){
                $_vids && RedisService::sAddArray(MvModel::RECOMMEND_FEE_KEY, collect($_vids)->values()->toArray());
            });
    }

    //金币视频的池子
    public function getCoinsMvIds(){
        redis()->del(MvModel::COINS_SHORT_MV_ID_LIST_KEY);
        MvModel::queryBase()->where('type', MvModel::TYPE_SHORT)
            ->where('is_aw', MvModel::AW_NO)
            ->where('coins', '>', 0)
            ->orderByDesc('count_pay')
            ->limit(3000)
            ->get()
            ->pluck('id')
            ->chunk(500)
            ->map(function ($_vids){
                $_vids && RedisService::sAddArray(MvModel::COINS_SHORT_MV_ID_LIST_KEY, collect($_vids)->values()->toArray());
            });
    }

}