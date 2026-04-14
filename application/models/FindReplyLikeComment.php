<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class FindReplyLikesModel
 *
 * @property int $id
 * @property int $comment_id 喜欢的回复
 * @property string $uuid 用户ID
 *
 * @author xiongba
 * @date 2020-07-10 16:05:30
 *
 * @mixin \Eloquent
 */
class FindReplyLikeCommentModel extends Model
{
    use \traits\EventLog;
    protected $table = "find_reply_likes_comment";

    protected $primaryKey = 'id';

    protected $fillable = ['comment_id', 'uuid'];

    protected $guarded = 'id';

    public $timestamps = false;


}
