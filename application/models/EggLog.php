<?php

/**
 * class EggLogModel
 *
 * @property int $log_id 日志ID
 * @property int $uid 用户uid
 * @property int $item_id 奖品奖项id
 * @property string $item_name 奖品名称
 * @property string $item_icon 奖品图片
 * @property int $lottery_id 抽奖id
 * @property string $giveaway_type 赠品类型
 * @property string $giveaway_id 赠品类型
 * @property int $giveaway_num 赠品数量
 * @property string $created_at 创建时间
 * @property string $nickname 用户昵称
 * @property int $pay_type 付费类型 0 免费次数 1 金币
 * @property int $coins 金币数
 *
 *
 * @date 2024-09-21 15:33:08
 *
 * @mixin \Eloquent
 */
class EggLogModel extends EloquentModel
{

    protected $table = "egg_log";
    protected $primaryKey = 'log_id';
    protected $fillable = [
        'uid',
        'item_id',
        'item_name',
        'item_icon',
        'lottery_id',
        'giveaway_type',
        'giveaway_id',
        'giveaway_num',
        'created_at',
        'nickname',
        'pay_type',
        'coins'
    ];

    protected $guarded = 'log_id';
    public $timestamps = false;

    const PAY_FREE_NUM = 0;
    const PAY_FREE_COINS = 1;
    const PAY_TYPE_TIPS = [
        self::PAY_FREE_NUM => '免费次数',
        self::PAY_FREE_COINS => '金币',
    ];

    public function lotitem(){
        return $this->hasOne(EggItemModel::class, 'item_id', 'item_id');
    }

    public function lottery(){
        return $this->hasOne(EggModel::class, 'id', 'lottery_id');
    }

    public static function createBy(MemberModel $member, EggItemModel $item)
    {
        return self::create(
            [
                'uid'           => $member->uid,
                'nickname'      => $member->nickname,
                'item_id'       => $item->item_id,
                'item_name'     => $item->item_name,
                'item_icon'     => '',
                'lottery_id'    => $item->lottery_id,
                'giveaway_type' => $item->giveaway_type,
                'giveaway_id'   => $item->giveaway_id,
                'giveaway_num'  => $item->giveaway_num,
                'created_at'    => \Carbon\Carbon::now(),
            ]
        );
    }

    public static function list($uid, $lottery_id){
        return self::selectRaw('item_name as text,created_at as time')
            ->where('uid', $uid)
            ->where('lottery_id', $lottery_id)
            ->orderByDesc('log_id')
            ->limit(50)
            ->get();
    }
}
