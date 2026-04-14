<?php

/**
 * Class UserLotteryLogModel
 * @property int $id
 * @property int $aff 用户AFF
 * @property int $lottery_id 奖励ID
 * @property int $val 奖励的金币数/VIP产品ID
 * @property string $snapshot 快照
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property int $type 类型
 * @mixin \Eloquent
 */
class UserLotteryLogModel extends EloquentModel
{
    protected $table = 'user_lottery_log';

    protected $fillable = [
        'aff',
        'lottery_id',
        'val',
        'snapshot',
        'created_at',
        'updated_at',
        'type',
    ];

    protected $primaryKey = 'id';

    public static function list($aff){
        return self::where('aff', $aff)->where('created_at', '>', '2024-01-01')->orderByDesc('id')->get()->map(function (UserLotteryLogModel $item){
            $snap = json_decode($item->snapshot, true);
            if ($snap['type'] == LotteryBaseModel::TYPE_COINS_RAND){
                $snap['hint'] = $item->val . '金币';
            }
            return [
                'type'  => '抽奖',
                'bi'  => $snap['hint'],
                'date' => $item->created_at
            ];
        })->filter()->values()->toArray();
    }
}