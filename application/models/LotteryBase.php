<?php

/**
 * 奖品配置
 * Class LotteryBaseModel
 * @property int $id
 * @property int $icon 图标
 * @property int $exp 金币数/VIP产品ID/随机金币开始数
 * @property int $exp_end 随机金币结束值
 * @property string $title 标题
 * @property string $hint 提示
 * @property int $rate 概率 %
 * @property int $sort 排序越大越前
 * @property int $is_win 是否是参与奖
 * @property int $is_show 是否展示中奖记录
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property int $type 类型 0未中奖 1金币 2VIP 3随机金币
 * @property int $index 下标
 * @mixin \EloquentModel
 */
class LotteryBaseModel extends EloquentModel
{
    protected $table = 'lottery_base';
    public $timestamps = true;
    protected $fillable = [
        'icon',
        'exp',
        'title',
        'hint',
        'rate',
        'sort',
        'is_win',
        'is_show',
        'created_at',
        'updated_at',
        'type',
        'index',
        'exp_end',
    ];

    protected $primaryKey = 'id';
    const LOTTERY_GROUP = 'lottery_base_gp';
    const LOTTERY_KEY = 'lottery_base';
    const LOTTERY_SET = 'lottery_set';

    const WIN_NO = 0;
    const WIN_OK = 1;
    const WIN_TIPS = [
        self::WIN_NO => '否',
        self::WIN_OK => '是',
    ];

    const SHOW_NO = 0;
    const SHOW_OK = 1;
    const SHOW_TIPS = [
        self::SHOW_NO => '不展示',
        self::SHOW_OK => '展示',
    ];

    const TYPE_COINS = 1;
    const TYPE_VIP = 2;
    const TYPE_COINS_RAND = 3;
    const TYPE_TIPS = [
        self::TYPE_COINS => '金币',
        self::TYPE_VIP => 'VIP',
        self::TYPE_COINS_RAND => '随机金币',
    ];

    public static function clearCache()
    {
        cached('')->clearGroup(self::LOTTERY_GROUP);
        redis()->del(self::LOTTERY_SET);
    }

    public function getIconAttribute(): string
    {
        return url_ads($this->attributes['icon'] ?? '');
    }

    public static function info()
    {
        return cached(self::LOTTERY_KEY)
            ->group(self::LOTTERY_GROUP)
            ->chinese('抽奖配置列表')
            ->fetchPhp(function () {
                return self::orderByDesc('sort')
                    ->get()
                    ->map(function ($item){
                        $item->makeHidden(['rate','created_at','updated_at','sort','is_win','is_show','type']);
                        return $item;
                    });
            });
    }

    //参与奖
    public static function ptpAward(){
        return self::selectRaw('id, exp, exp_end, hint, is_win, is_show, type, index')->where('is_win', self::WIN_OK)->first();
    }

    public static function luckyIncrement($id){
        self::where('id', $id)->increment('total_lucky');
    }
}