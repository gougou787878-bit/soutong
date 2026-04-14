<?php

/**
 * class CommentsModel
 *
 * @property int $c_id 评论ID,默认0(第一层评论)
 * @property string $cityname 评论地点
 * @property string $comment 留言内容
 * @property int $complain_num 被举报次数
 * @property string $created_at
 * @property int $id
 * @property string $ipstr ip地址
 * @property int $is_read 被回复者是否已读
 * @property int $like_num 此条评论点赞数量
 * @property int $mv_id 视频ID
 * @property int $status 状态 0 未通过审核 1正常 2被举报
 * @property int $uid 用户id
 * @property string $updated_at
 *
 * @mixin \Eloquent
 */
class CommentModel extends EloquentModel
{
    const REDIS_COMMENT_TODAY_COUNT = 'comment_today'; // 当日评论总数
    const REDIS_COMMENT_LAST_KEY = 'comment_last:'; // 最新评论key
    const REDIS_COMMENT_5MIN_KEY = 'comment_5min:'; // 5分钟内评论次数
    const REDIS_COMMENT_LIST = 'comment_items:'; // 评论列表
    const REDIS_COMMENT_LIKED = 'comment_like:'; // 点赞的评论列表
    const REDIS_COMMENT_BAN = 'cmt:ban:'; // 禁言用户评论

    const STATUS_WAIT = 0; // 待审核状态
    const STATUS_SUCCESS = 1; // 正常评论状态
    const STATUS_FAIL = 2; // 被拒绝评论状态
    const STATUS_TIPS = [
        self::STATUS_WAIT => '待审核',
        self::STATUS_SUCCESS => '正常',
        self::STATUS_FAIL => '被拒绝',
    ];

    protected $table = 'comments';

    protected $fillable = [
        'mv_id', 'c_id', 'uid', 'comment', 'is_read', 'like_num', 'ipstr', 'cityname', 'status', 'complain_num'
    ];

    public $timestamps = true;

    /**
     * 二级评论
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function child()
    {
        return $this->hasMany(self::class, 'c_id', 'id')->with('user:uid,nickname,thumb,vip_level,sexType,expired_at,uuid,uid');
    }

    public function user()
    {
        return $this->hasOne(MemberModel::class, 'uid', 'uid');
    }
}