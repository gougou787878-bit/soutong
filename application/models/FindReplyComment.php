<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class FindReplyCommentModel
 *
 * @property string $comment 留言内容
 * @property int $created_at 
 * @property int $find_id 求片编号
 * @property int $id 
 * @property int $is_checked 是否通过审核
 * @property int $like_num 此条评论点赞数量
 * @property int $recommend_id 回复编号
 * @property int $reply_num 品论回复数
 * @property string $uuid 用户UUID
 * @property string to_uuid 回复的谁
 *
 * @property MemberModel $member
 * @property FindModel $withFind
 *
 * @author xiongba
 * @date 2020-07-10 16:05:39
 *
 * @mixin \Eloquent
 */
class FindReplyCommentModel extends Model
{
    use \traits\EventLog;
    protected $table = "find_reply_comment";

    protected $primaryKey = 'id';

    protected $fillable = ['comment', 'created_at', 'find_id', 'is_checked', 'like_num', 'reply_id', 'reply_num', 'uuid','reply_id'];

    protected $guarded = 'id';

    public $timestamps = false;


    protected $appends = ['created_str', 'like_num_str', 'reply_num_str', 'is_like' ];

    const FIND_REPLAY_COMMENT_LIST = 'find:replay:comment:list:%d:%d:%d';
    const FIND_REPLAY_COMMENT_LIST_GROUP = 'find:replay:comment:list:group';

    public function getIsLikeAttribute()
    {
        //是否有点赞
        $id = $this->attributes['id'] ?? null;
        if (isset($id)) {
            $uuid = request()->getMember()->uuid;
            if ($uuid){
                return FindReplyLikeCommentModel::where(['comment_id' => $id, 'uuid' => $uuid])->count() > 0 ? 1 : 0;
            }
        }
        return 0;
    }


    public function member(){
        return $this->hasOne(MemberModel::class , 'uuid' , 'uuid');
    }


    public function withFind(){
        return $this->hasOne(MemberModel::class , 'id' , 'find_id');
    }



    public function getCreatedStrAttribute()
    {
        return date('Y-m-d H:i:s', $this->attributes['created_at']);
    }

    public function getLikeNumStrAttribute()
    {
        return (string)($this->attributes['like_num'] ?? 0);
    }

    public function getReplyNumStrAttribute()
    {
        return (string)($this->attributes['reply_num'] ?? 0);
    }

    /**
     * 回复求片
     */
    public static function getReplyByReply(int $reply_id, $page, $limit)
    {
        $key = sprintf(self::FIND_REPLAY_COMMENT_LIST,$reply_id, $page, $limit);
        return cached($key)
            ->group(self::FIND_REPLAY_COMMENT_LIST_GROUP)
            ->fetchPhp(function () use ($reply_id, $page, $limit) {
                return self::with('member:uuid,nickname,username,auth_status,followed_count,fans_count,videos_count,thumb')
                    ->where('reply_id',$reply_id)
                    ->forPage($page,$limit)
                    ->get()
                    ->map(function (FindReplyCommentModel $item){
                        $item->makeHidden(['like_num', 'reply_num', 'created_at', 'to_uuid', 'uuid']);
                        return $item;
                    });
            },600);
    }

}
