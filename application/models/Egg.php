<?php

/**
 * class LotteryModel
 *
 * @property int $id
 * @property string $lottery_name 抽奖的名称
 * @property string $lottery_begin 抽奖的开始时间
 * @property string $lottery_end 抽奖的结束时间
 * @property int $lottery_num 被抽奖了多少次
 * @property int $lottery_status 状态
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 *
 *
 * @date 2024-09-21 15:32:39
 *
 * @mixin \Eloquent
 */
class EggModel extends EloquentModel
{
    protected $table = "egg";
    protected $primaryKey = 'id';
    protected $fillable = [
        'lottery_name',
        'lottery_begin',
        'lottery_end',
        'lottery_num',
        'lottery_status',
        'created_at',
        'updated_at'
    ];
    protected $guarded = 'id';
    public $timestamps = true;

    const STATUS_NO = 0;
    const STATUS_OK = 1;
    const STATUS_TIPS = [
        self::STATUS_NO => '关闭',
        self::STATUS_OK => '开启',
    ];

    const WING_NOTICE_LIST = 'lottery_top_list:%d';
    const DRAW_RANK_LIST = 'lottery_draw_rank_list:%d:%s';

    /**
     * @param $id
     * @return EggModel ? null
     */
    public static function info($id){
        return cached('lottery:' . $id)
            ->group('lottery')
            ->clearCached()
            ->fetchPhp(function () use ($id){
                return self::find($id);
            });
    }

    public static function draw($lottery_id, $num)
    {
        $lottery_items = cached('lottery_item_' . $lottery_id)
            ->fetchPhp(function () use ($lottery_id){
                return EggItemModel::where('lottery_id', $lottery_id)
                    ->where('item_status', EggItemModel::STATUS_OK)
                    ->where('is_win', EggItemModel::WIN_OK)
                    ->get()
                    ->keyBy('item_id');
            }, 120);
        $items = [];
        $_self_items = $lottery_items->each(function (EggItemModel $item) use (&$items) {
            for ($i = 0; $i < $item->item_rate; $i++) {
                $items[] = $item->item_id;
            }
        });
        $arr = [];
        for ($i = 1; $i<= $num; $i++){
            $item_id = collect($items)->random();
            $arr[] = $_self_items[$item_id];
        }
        return $arr;
    }
}
