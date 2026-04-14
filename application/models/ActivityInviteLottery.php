<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class ActivityInviteLotteryModel
 *
 * @property int $id 
 * @property float $reward_amount 用户可用奖金
 * @property float $reward_amount_total 用户总奖金
 * @property int $reward_remainder 剩余抽奖次数
 * @property int $uid 用户
 *
 * @property MemberModel $member
 *
 * @author xiongba
 * @date 2020-12-16 18:36:48
 *
 * @mixin \Eloquent
 */
class ActivityInviteLotteryModel extends Model
{

    protected $table = "activity_invite_lottery";

    protected $primaryKey = 'id';

    protected $fillable = ['reward_amount', 'reward_amount_total', 'reward_remainder', 'uid'];

    protected $guarded = 'id';

    public $timestamps = false;


    public function member(){
        return $this->hasOne(MemberModel::class , 'uid' ,'uid');
    }



}
