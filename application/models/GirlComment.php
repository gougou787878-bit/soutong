<?php


/**
 * class PostCommentModel
 *
 * @property int $id
 * @property int $girl_id 帖子ID
 * @property int $pid 评论ID,默认0(第一层评论)
 * @property int $aff 用户aff
 * @property string $comment 留言内容
 * @property int $status 0:待审核 1:审核通过 2.未通过 3.禁言
 * @property int $is_read 被回复者是否已读
 * @property int $like_num 此条评论点赞数量
 * @property int $video_num 视频数量
 * @property int $photo_num 图片数量
 * @property string $ipstr 用户ip
 * @property string $cityname 定位城市
 * @property int $complain_num 被举报次数
 * @property string $refuse_reason 拒绝通过原因
 * @property string $created_at
 * @property string $updated_at
 * @property int $is_finished 资源是否处理完 0未处理 1已处理
 * @property int $is_top 是否置顶 0未置顶 1已置顶
 *
 * @author xiongba
 * @date 2023-06-09 20:10:36
 *
 * @mixin \Eloquent
 */
class GirlCommentModel extends EloquentModel
{

    protected $table = "girl_comment";

    protected $primaryKey = 'id';

    protected $fillable = [
        'girl_id',
        'pid',
        'aff',
        'comment',
        'status',
        'is_read',
        'like_num',
        'video_num',
        'photo_num',
        'ipstr',
        'cityname',
        'complain_num',
        'refuse_reason',
        'created_at',
        'updated_at',
        'is_finished',
        'is_top'
    ];

    protected $guarded = 'id';

    public $timestamps = false;

    const STATUS_WAIT = 0;
    const STATUS_PASS = 1;
    const STATUS_UNPASS = 2;
    const STATUS_TIPS = [
        self::STATUS_WAIT => '待审核',
        self::STATUS_PASS => '已通过',
        self::STATUS_UNPASS => '已拒绝'
    ];

    const TOP_NO = 0;
    const TOP_OK = 1;
    const TOP_TIPS = [
        self::TOP_NO => '未置顶',
        self::TOP_OK => '已置顶',
    ];

    const FINISH_NO = 0;
    const FINISH_OK = 1;
    const FINISH_TIPS = [
        self::FINISH_NO => '未完成',
        self::FINISH_OK => '已完成'
    ];

    protected $appends = ['is_like'];

    const TOP_HINT = '亲爱的用户老爷您好~ 为净化社区评论环境，官方对恶意推广有严格限制。为避免被误封,如有个人交友需求请按此格式发布联系方式:火星中部地区交友请联系 VX:MarsCentre QQ:123456789';

    const GIRL_COMMENT_DETAIL_KEY = 'girl:comment:detail:%s';
    const GIRL_COMMENT_DETAIL_GROUP_KEY = 'girl_comment_detail';

    const GIRL_COMMENT_LIST_DETAIL_KEY = 'girl:comment:list:detail:%d:%d:%d';
    const GIRL_COMMENT_LIST_DETAIL_GROUP_KEY = 'girl_comment_list_detail:%d';
    const CREATE_COMMENT_COMMENT_CLEAR_GROUP = 'create_comment_commemnt_clear_%s';
    const GIRL_COMMENT_LIST_FIRST_KEY = 'girl:comment:list:first:%s:%s:%s';
    const CREATE_POST_COMMENT_CLEAR_GROUP = 'create_post_commemnt_clear_%s';
    const GIRL_COMMENT_LIST_FIRST_GROUP_KEY = 'girl_comment_list_first';

    public static function queryBase()
    {
        return self::query()
            ->where('status', self::STATUS_PASS)
            //->where('is_finished', self::FINISH_OK)
            ->with('user:aff,uid,nickname,thumb,aff,expired_at,vip_level,uuid,sexType');
    }

    // 清理帖子评论首页

    /**
     * @throws RedisException
     */
    public static function clearCacheWhenCreatePostComment($girlId)
    {
        //清理帖子详情
        GirlModel::clearDetailCache($girlId);// 清理帖子第一页评论
        $first_group = self::GIRL_COMMENT_LIST_FIRST_GROUP_KEY . $girlId;
        $second_group = sprintf(self::GIRL_COMMENT_LIST_DETAIL_GROUP_KEY, $girlId);
        cached('')->clearGroup($first_group, $second_group); // 清理第一、二层级
    }

    public function getIsLikeAttribute()
    {
        if (is_null($this->watchUser) || !$this->getAttributeValue('id')) {
            return 0;
        } elseif ($this->watchUser) {
            $flag = GirlCommentUserLikeModel::getIdsById($this->watchUser->uid, $this->getAttribute('id'));
            return $flag ? 1 : 0;
        }
        return 0;

    }

    public function user()
    {
        return $this->hasOne(MemberModel::class, 'aff', 'aff');
    }

    public static function listCommentsByPostId(MemberModel $member, $girlId, $authorAff, $page, $limit)
    {
        $comments = self::listCommentsByFirst($girlId, $page, $limit);
        $comments = self::formatComent($comments, $member);

        foreach ($comments as $v) {
            $v->is_landlord = (int)$v->aff === (int)$authorAff ? 1 : 0;
            $secondComments = self::listCommentsBySecond($v->id, $girlId, 1, 4);
            $v->comments = self::formatComent($secondComments, $member);
            foreach ($v->comments as $v1) {
                $v1->is_landlord = (int)$v1->aff === (int)$authorAff ? 1 : 0;
            }
        }
        return $comments;
    }

    public static function listCommentsByDetail($pid, $postId, $page, $limit)
    {
        $cacheKey = sprintf(self::GIRL_COMMENT_LIST_DETAIL_KEY, $pid, $postId, $page, $limit);
        $group = sprintf(self::GIRL_COMMENT_LIST_DETAIL_GROUP_KEY, $postId);
        return cached($cacheKey)
            ->group($group)
            ->fetchPhp(function () use ($pid, $postId, $page, $limit) {
                return self::queryBase()
                    ->where('girl_id', $postId)
                    ->where('pid', $pid)
                    ->orderByDesc('like_num')
                    ->orderByDesc('id')
                    ->forPage($page, $limit)
                    ->get();
            });
    }

    protected static function listCommentsByFirst($girlId, $page, $limit)
    {
        $cacheKey = sprintf(self::GIRL_COMMENT_LIST_FIRST_KEY, $girlId, $page, $limit);
        $group = self::GIRL_COMMENT_LIST_FIRST_GROUP_KEY . $girlId;
        return cached($cacheKey)
            ->group($group)
            ->fetchPhp(function () use ($girlId, $page, $limit) {
                return self::queryBase()
                    ->where('girl_id', $girlId)
                    ->where('pid', 0)
                    ->orderByDesc('is_top')
                    ->orderByDesc('like_num')
                    ->orderByDesc('id')
                    ->forPage($page, $limit)
                    ->get();
            });
    }

    protected static function listCommentsBySecond($pid, $girlId, $page, $limit)
    {
        return self::listCommentsByDetail($pid, $girlId, $page, $limit);
    }

    public static function formatComent($commentData, \MemberModel $member = null)
    {
        if (!$commentData) {
            return $commentData;
        }
        foreach ($commentData as $key => &$comment) {
            /** @var \GirlCommentModel $comment */

            if (!is_null($member)) {
                $comment->watchByUser($member);
            }
            if ($medias = $comment->medias) {
                $medias = collect($medias)->map(function ($media) use ($comment) {
                    /** @var \GirlMediaModel $media */
                    if ($media->type == \GirlMediaModel::TYPE_IMG) {
                        $media->media_url_full = url_cover($media->media_url);
                    } elseif ($media->type == \GirlMediaModel::TYPE_VIDEO) {
                        $extension = pathinfo($media->media_url, PATHINFO_EXTENSION);
                        if ($extension == 'm3u8') {
                            $media->media_url_full = getPlayUrl($media->media_url, true);
                        } else {
                            return null;//非法视频或 没有切完 等下放出去
                        }
                    }
                    return $media;
                })->filter()->values()->toArray();
            }
            unset($comment->medias);//取消关系
            $comment->medias = $medias;
        }
        return $commentData;
    }

    public static function listCommentsByCommentId(\MemberModel $member, $pid, $girlId, $authorAff, $page, $limit)
    {
        $comments = self::listCommentsByDetail($pid, $girlId, $page, $limit);
        $comments = self::formatComent($comments, $member);
        foreach ($comments as $v) {
            $v->is_landlord = (int)$v->aff === (int)$authorAff ? 1 : 0;
        }
        return $comments;
    }

    public static function getCommentById(\MemberModel $member, $commentId)
    {
        $cacheKey = sprintf(self::GIRL_COMMENT_DETAIL_KEY, $commentId);
        /** @var GirlCommentModel $comment */
        $comment = cached($cacheKey)
            ->group(self::GIRL_COMMENT_DETAIL_GROUP_KEY)
            ->fetchPhp(function () use ($commentId) {
                return self::with(['user'])
                    ->where('id', $commentId)
                    ->where('status', self::STATUS_PASS)
                    //->where('is_finished', self::FINISH_OK)
                    ->first();
            });
        if ($comment) {
            $comment->watchByUser($member);
        }

        return $comment;
    }
}
