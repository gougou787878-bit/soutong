<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class ChatLogModel
 *
 * @property int $id 
 * @property string $window_id 此聊天窗，窗口id//如果是群聊这个是群id
 * @property string $from_avater 
 * @property string $from_nickname 
 * @property string $from_uuid 会话来源uuid
 * @property string $to_avater 
 * @property string $to_uuid 会话发送的 uuid 或 groupid
 * @property string $to_nickname 消息动作，这条消息是什么动作类型的消息
 * @property string $content
 * @property string $images
 * @property string $ext 扩展字段
 * @property string $created_at 记录时间
 *
 * @author xiongba
 * @date 2021-03-11 20:57:18
 *
 * @mixin \Eloquent
 */
class ChatLogModel extends Model
{

    protected $table = "chat_log";

    protected $primaryKey = 'id';

    protected $fillable = ['window_id', 'from_avater', 'from_nickname', 'from_uuid', 'to_avater', 'to_uuid', 'to_nickname',"content", 'images', 'ext', 'created_at'];

    protected $guarded = 'id';

    public $timestamps = false;

    static function createWindowID($from_uuid,$to_uuid){
        $window_id = array($from_uuid, $to_uuid);
        sort($window_id);
        return md5(json_encode($window_id));
    }




}
