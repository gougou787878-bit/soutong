<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class ActivityInviteLotteryLogModel
 *
 * @property int $created_at 抽奖时间
 * @property int $id
 * @property string $item 奖品
 * @property int $item_id 奖品id
 * @property string $item_icon 奖品icon
 * @property string $log 说明
 * @property float $reward_amount 奖品金额
 * @property int $uid 用户
 *
 * @author xiongba
 * @date 2020-12-16 20:41:38
 *
 * @mixin \Eloquent
 */
class ActivityInviteLotteryLogModel extends Model
{

    protected $table = "activity_invite_lottery_log";

    protected $primaryKey = 'id';

    protected $fillable = ['created_at', 'item', 'item_id', 'item_icon', 'log', 'reward_amount', 'uid'];

    protected $guarded = 'id';

    public $timestamps = false;

    protected $appends = ['created_str'];

    public function getCreatedStrAttribute()
    {
        return date('Y-m-d H:i', intval($this->attributes['created_at'] ?? 0));
    }


}
