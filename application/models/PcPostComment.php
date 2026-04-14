<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class PostCommentModel
 *
 * @property int $id
 * @property int $post_id 帖子ID
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
 * @mixin \Eloquent
 */
class PcPostCommentModel extends EloquentModel
{

    const CK_PC_POST_COMMENT_LIST = 'ck:pc:post:comment:list:%s:%s:%s';
    const GP_PC_POST_COMMENT_LIST = 'gp:pc:post:comment:list';
    const CN_PC_POST_COMMENT_LIST = 'PC_一级评论';

    const CK_PC_POST_COMMENT_DETAIL = 'pc:post:comment:detail:%s';
    const GP_PC_POST_COMMENT_DETAIL = 'pc_post_comment_detail';
    const CN_PC_POST_COMMENT_DETAIL = 'PC_评论详情';

    const CK_PC_COMMENT_COMMENT_LIST = 'ck:pc:comment:comment:list:%s:%s:%s';
    const GP_PC_COMMENT_COMMENT_LIST = 'gp:pc:comment:comment:list';
    const CN_PC_COMMENT_COMMENT_LIST = 'PC_二级评论';

    protected $table = "post_comment";

    protected $primaryKey = 'id';

    protected $fillable = [
        'post_id',
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

    public function post(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PcPostModel::class, 'id', 'post_id');
    }

    public function comments(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(self::class, 'id', 'pid');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(MemberModel::class, 'aff', 'aff');
    }

    public static function queryBase()
    {
        return self::query()
            ->selectRaw('id,post_id,pid,aff,comment,like_num,created_at')
            ->where('status', self::STATUS_PASS)
            ->with('user:aff,uid,nickname,thumb,aff,expired_at,vip_level,uuid,sexType');
    }

    public static function listFirstComments($post_id, $page, $limit)
    {
        $cacheKey = sprintf(self::CK_PC_POST_COMMENT_LIST, $post_id, $page, $limit);
        return cached($cacheKey)
            ->clearCached()
            ->group(self::GP_PC_POST_COMMENT_LIST)
            ->chinese(self::CN_PC_POST_COMMENT_LIST)
            ->fetchPhp(function () use ($post_id, $page, $limit) {
                $list = self::queryBase()
                    ->where('post_id', $post_id)
                    ->where('pid', 0)
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->forPage($page, $limit)
                    ->get();

                return $list->map(function ($item) {
                    $item->comments = self::listSecondComments($item->id, 1, 15);
                    return self::filterComment($item);
                })->filter()->values();
            });
    }

    public static function listSecondComments($comment_id, $page, $limit)
    {
        $cacheKey = sprintf(self::CK_PC_COMMENT_COMMENT_LIST, $comment_id, $page, $limit);
        return cached($cacheKey)
            ->group(self::GP_PC_COMMENT_COMMENT_LIST)
            ->chinese(self::CN_PC_COMMENT_COMMENT_LIST)
            ->clearCached()
            ->fetchPhp(function () use ($comment_id, $page, $limit) {
                $list = self::queryBase()
                    ->where('pid', $comment_id)
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->forPage($page, $limit)
                    ->get();

                return $list->map(function ($item) {
                    return self::filterComment($item);
                })->filter()->values();
            });
    }

    private static function filterComment($comment)
    {
        if (!$comment->user) {
            return NULL;
        }
        return $comment;
    }

    public static function listMyComments($aff, $page, $limit)
    {
        return self::queryBase()
            ->with([
                'post' => function ($q) {
                    $q->select(['id', 'title', 'topic_id'])
                        ->with([
                            'topic' => function ($q) {
                                $q->select(['id', 'name']);
                            },
                        ])
                        ->where('status',PcPostModel::STATUS_PASS);
                }
            ])
            ->where('pid', 0)
            ->where('aff', $aff)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->forPage($page, $limit)
            ->get()
            ->map(function ($item) {
                if (!$item->post) {
                    return NULL;
                }
                if (!$item->post->topic) {
                    return NULL;
                }
                return $item;
            })
            ->filter()
            ->values();
    }

    public static function listReply($aff, $page, $limit)
    {
        return self::queryBase()
            ->with([
                'comments' => function ($q) {
                    $q->selectRaw('id,post_id,pid,aff,comment,like_num,created_at')
                        ->with([
                            'post' => function ($q) {
                                $q->select(['id', 'title', 'topic_id'])
                                    ->with([
                                        'topic' => function ($q) {
                                            $q->select(['id', 'name']);
                                        },
                                    ])
                                    ->where('status', PcPostModel::STATUS_PASS)
                                    ->where('is_finished', PcPostModel::FINISH_OK)
                                    ->where('is_deleted', PcPostModel::DELETED_NO);
                            }
                        ])
                        ->where('status', self::STATUS_PASS);
                },
            ])
            ->where('pid', '!=', 0)
            ->where('aff', $aff)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->forPage($page, $limit)
            ->get()
            ->map(function ($item) {
                if (!$item->comment) {
                    return NULL;
                }
                if (!$item->comments->post) {
                    return NULL;
                }
                if (!$item->comments->post->topic) {
                    return NULL;
                }
                return $item;
            })
            ->filter()
            ->values();
    }

    public static function getCommentById($commentId,\MemberModel $member = null)
    {
        $cacheKey = sprintf(self::CK_PC_POST_COMMENT_DETAIL, $commentId);
        /** @var PcPostCommentModel $comment */
        $comment = cached($cacheKey)
            ->group(self::GP_PC_POST_COMMENT_DETAIL)
            ->chinese(self::CN_PC_POST_COMMENT_DETAIL)
            ->fetchPhp(function () use ($commentId) {
                return self::with(['user'])
                    ->where('id', $commentId)
                    ->where('status', self::STATUS_PASS)
                    //->where('is_finished', self::FINISH_OK)
                    ->first();
            });
        if ($comment && $member){
            $comment->watchByUser($member);
        }

        return $comment;
    }
}
