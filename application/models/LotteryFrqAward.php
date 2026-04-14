<?php

/**
 * 抽奖次数奖励表
 * Class LotteryFrqAwardModel
 * @property int $id
 * @property int $icon 图标
 * @property int $lottery_frq 抽奖次数
 * @property int $type 1金币 2VIP
 * @property int $val 金币数/VIP天数
 * @property string $title 奖品名称
 * @property int $sort 排序
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @mixin \EloquentModel
 */
class LotteryFrqAwardModel extends EloquentModel
{
    protected $table = 'lottery_frq_award';
    public $timestamps = true;
    protected $fillable = [
        'lottery_frq',
        'type',
        'val',
        'title',
        'icon',
        'sort',
        'created_at',
        'updated_at',
    ];

    protected $primaryKey = 'id';

    const TYPE_COINS = 1;
    const TYPE_VIP = 2;
    const TYPE_TIPS = [
        self::TYPE_COINS => '金币',
        self::TYPE_VIP => 'VIP',
    ];

    const CK_LOTTERY_FRQ_AWARD = 'ck:lottery:frq:award';
    const GP_LOTTERY_FRQ_AWARD = 'gp:lottery:frq:award';
    const CN_LOTTERY_FRQ_AWARD = '抽奖次数奖励列表';

    public static function info()
    {
        return cached(self::CK_LOTTERY_FRQ_AWARD)
            ->group(self::GP_LOTTERY_FRQ_AWARD)
            ->chinese(self::CN_LOTTERY_FRQ_AWARD)
            ->fetchPhp(function () {
                return self::orderByDesc('sort')
                    ->get()
                    ->map(function ($item){
                        $item->makeHidden(['created_at','updated_at','sort','type']);
                        return $item;
                    });
            });
    }
}