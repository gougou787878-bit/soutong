<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class ChatFriendsModel
 *
 * @property string $uuid 
 * @property string $to_uuid 
 * @property int $count 未读消息计数 未读消息计数 a->b a
 * @property int $t_count 未读消息计数 a->b b
 * @property int $window_id hash
 * @property string $update
 *
 * @author xiongba
 * @date 2021-03-11 20:56:52
 *
 * @mixin \Eloquent
 */
class ChatFriendsModel extends Model
{

    protected $table = "chat_friends";

    protected $primaryKey = 'id';
    protected $guarded = 'id';
    protected $fillable = ['uuid', 'to_uuid', 'count', 'update','window_id','t_count'];


    public $timestamps = false;

    public function user()
    {
        return $this->hasOne(MemberModel::class, 'uuid', 'uuid');
    }
    public function touser()
    {
        return $this->hasOne(MemberModel::class, 'uuid', 'to_uuid');
    }




}
