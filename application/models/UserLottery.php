<?php

/**
 * 用户抽奖
 * Class UserLotteryModel
 * @property int $id
 * @property int $aff aff
 * @property int $val 抽奖次数
 * @property int $total 总的抽奖次数
 * @property int $total_money 活动期间金币充值总金额(分)
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @mixin \EloquentModel
 */
class UserLotteryModel extends EloquentModel
{
    protected $table = 'user_lottery';
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
        /** @var UserLotteryModel $userLottery */
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
        /** @var UserLotteryModel $userLottery */
        $userLottery = self::where('aff', $member->aff)->first();
        $val = intval($total_money / 10000);
        if (!$userLottery){
            self::addLog($member->aff, $val, intval($total_money / 100));
        }else{
            $userLottery->val = $val;
            $userLottery->total += $val;
            $userLottery->total_money += intval($total_money / 100);
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
        /** @var UserLotteryModel $userLottery */
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