<?php

/**
 * class EggItemModel
 *
 * @property int $item_id
 * @property string $item_title 奖品标题
 * @property string $item_name 奖品名称
 * @property int $item_rate 奖品概率
 * @property string $item_icon 奖品图片
 * @property int $item_sort 排序
 * @property int $item_status 状态
 * @property int $lottery_id 抽奖id
 * @property string $giveaway_type 赠品类型
 * @property string $giveaway_id 赠品索引
 * @property int $giveaway_num 赠品数量
 * @property int $is_show 是否展示
 * @property int $is_win 是否中奖
 *
 *
 * @date 2024-09-21 15:32:59
 *
 * @mixin \Eloquent
 */
class EggItemModel extends EloquentModel
{
    protected $table = "egg_item";
    protected $primaryKey = 'item_id';
    protected $fillable = [
        'item_title',
        'item_name',
        'item_rate',
        'item_icon',
        'item_sort',
        'item_status',
        'lottery_id',
        'giveaway_type',
        'giveaway_id',
        'giveaway_num',
        'is_show',
        'is_win',
    ];

    protected $guarded = 'item_id';
    public $timestamps = false;

    const STATUS_NO = 0;
    const STATUS_OK = 1;
    const STATUS_TIPS = [
        self::STATUS_NO => '关闭',
        self::STATUS_OK => '开启',
    ];

    const SHOW_NO = 0;
    const SHOW_OK = 1;
    const SHOW_TIPS = [
        self::SHOW_NO => '否',
        self::SHOW_OK => '是',
    ];

    const WIN_NO = 0;
    const WIN_OK = 1;
    const WIN_TIPS = [
        self::WIN_NO => '否',
        self::WIN_OK => '是',
    ];

    const GIVEAWAY_TYPE_NOTHING = 'nothing';
    const GIVEAWAY_TYPE_COIN = 'coin';
    const GIVEAWAY_TYPE_VIP_COINS_EVER = 'vip_coins_ever';
    const GIVEAWAY_TYPE_VIP_AW_EVER = 'vip_aw_ever';
    const GIVEAWAY_TYPE_VIP_EVER = 'vip_ever';
    const GIVEAWAY_TYPE_VIP_SEVEN_COINS = 'vip_seven_coins';
    const GIVEAWAY_TYPE_VIP_THIRTY = 'vip_thirty';
    const GIVEAWAY_TYPE_VIP_FIFTEEN = 'vip_fifteen';
    const GIVEAWAY_TYPE_VIP_SEVEN = 'vip_seven';
    const GIVEAWAY_TYPE_MANUAL = 'manual';
    const GIVEAWAY_TYPE_VIP_WZ_EVER = 'vip_wz_ever';
    const GIVEAWAY_TYPE = [
        self::GIVEAWAY_TYPE_NOTHING             => '无',
        self::GIVEAWAY_TYPE_COIN                => '金币',
        self::GIVEAWAY_TYPE_VIP_COINS_EVER      => '金币永久卡',
        self::GIVEAWAY_TYPE_VIP_AW_EVER         => '暗网永久卡',
        self::GIVEAWAY_TYPE_VIP_EVER            => '永久会员卡',
        self::GIVEAWAY_TYPE_VIP_SEVEN_COINS     => '7天金币卡',
        self::GIVEAWAY_TYPE_VIP_THIRTY          => '30天VIP',
        self::GIVEAWAY_TYPE_VIP_FIFTEEN         => '15天VIP',
        self::GIVEAWAY_TYPE_VIP_SEVEN           => '7天VIP',
        self::GIVEAWAY_TYPE_MANUAL              => '人工处理',
        self::GIVEAWAY_TYPE_VIP_WZ_EVER         => '王者永久通卡',

    ];

    public function lottery(){
        return $this->hasOne(EggModel:: class, 'id', 'lottery_id');
    }
}
