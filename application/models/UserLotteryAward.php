<?php

/**
 * 用户抽奖次数奖励表
 * Class UserLotteryAwardModel
 * @property int $id
 * @property int $aff
 * @property int $award_id 奖励配置ID
 * @property string $title 名称
 * @property int $type 1金币 2VIP
 * @property int $val 金币数/VIP天数
 * @property string $created_at 创建时间
 * @mixin \EloquentModel
 */
class UserLotteryAwardModel extends EloquentModel
{
    protected $table = 'user_lottery_award';

    protected $fillable = [
        'aff',
        'type',
        'award_id',
        'title',
        'val',
        'created_at',
    ];

    protected $primaryKey = 'id';

    public static function record($aff, $award_id){
        return self::where('aff', $aff)->where('award_id', $award_id)->first();
    }

    public static function recordByAff($aff){
        return self::where('aff', $aff)->get()->pluck('award_id')->toArray();
    }

    public static function listByAff($aff){
        return self::where('aff', $aff)->get()->map(function (UserLotteryAwardModel $item){
            return [
                'type'  => '领取',
                'bi' => $item->title,
                'date' => $item->created_at
            ];
        })->filter()->values()->toArray();
    }
}