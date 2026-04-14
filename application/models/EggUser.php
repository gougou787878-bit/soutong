<?php

/**
 * 用户抽奖
 * Class EggUserModel
 * @property int $id
 * @property int $aff aff
 * @property int $val 抽奖次数
 * @property int $total 总的抽奖次数
 * @property int $total_money 活动期间金币充值总金额(分)
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @mixin \EloquentModel
 */
class EggUserModel extends EloquentModel
{
    protected $table = 'egg_user';
    public $timestamps = true;
    protected $fillable = [
        'aff',
        'val',
        'total',
        'total_money',
        'created_at',
        'updated_at',
    ];

    protected $primaryKey = 'id';

    public static function addUserLottery(MemberModel $member, $val){
        /** @var EggUserModel $userLottery */
        $userLottery = self::where('aff', $member->aff)->first();
        if (!$userLottery){
            self::addLog($member->aff, $val);
        }else{
            $userLottery->val = max($userLottery->val + $val, 0);
            if ($val > 0){
                $userLottery->total += $val;
            }
            $userLottery->updated_at = \Carbon\Carbon::now();
            $userLottery->save();
        }
    }

    public static function addUserLotteryByPay(MemberModel $member, $total_money){
        $money_yuan = intval($total_money / 100);
        $send_time = [
            100 => 3,
            200 => 10,
            300 => 15,
            500 => 25
        ];
        $val = $send_time[$money_yuan];
        /** @var EggUserModel $userLottery */
        $userLottery = self::where('aff', $member->aff)->first();
        if (!$userLottery){
            self::addLog($member->aff, $val, intval($money_yuan));
        }else{
            $userLottery->val += $val;
            $userLottery->total += $val;
            $userLottery->total_money += intval($money_yuan);
            $userLottery->updated_at = \Carbon\Carbon::now();
            $userLottery->save();
        }
    }

    public static function addLog($aff, $val, $total_money = 0){
        self::create(
            [
                'aff'           => $aff,
                'val'           => max($val, 0),
                'total'         => max($val, 0),
                'total_money'   => $total_money,
                'created_at'    => \Carbon\Carbon::now(),
                'updated_at'    => \Carbon\Carbon::now(),
            ]
        );
    }

    public static function getRemianTimes($aff){
        /** @var EggUserModel $userLottery */
        $userLottery = self::where('aff', $aff)->first();
        if (!$userLottery){
            return 0;
        }

        return max($userLottery->val, 0);
    }

    public static function getInfoByAff($aff){
        return self::onWriteConnection()->where('aff', $aff)->first();
    }

}