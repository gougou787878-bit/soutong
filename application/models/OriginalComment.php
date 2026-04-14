<?php


/**
 * class OriginalCommentModel
 *
 * @property int $id
 * @property int $original_id 帖子ID
 * @property int $pid 评论ID,默认0(第一层评论)
 * @property int $aff 用户aff
 * @property string $content 留言内容
 * @property int $status 0:待审核 1:审核通过 2.未通过 3.禁言
 * @property int $like_num 此条评论点赞数量
 * @property string $ipstr 用户ip
 * @property string $cityname 定位城市
 * @property string $refuse_reason 拒绝通过原因
 * @property string $created_at
 * @property string $updated_at
 *
 * @author xiongba
 * @date 2023-06-09 20:10:36
 *
 * @mixin \Eloquent
 */
class OriginalCommentModel extends EloquentModel
{

    protected $table = "original_comment";

    protected $primaryKey = 'id';

    protected $fillable = [
        'original_id',
        'pid',
        'aff',
        'content',
        'status',
        'like_num',
        'ipstr',
        'cityname',
        'refuse_reason',
        'created_at',
        'updated_at'
    ];

    protected $guarded = 'id';

    public $timestamps = true;

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

    const ORIGINAL_COMMENT_LIST_DETAIL_KEY = 'original:comment:list:detail:%d:%d:%d';
    const ORIGINAL_COMMENT_LIST_DETAIL_GROUP_KEY = 'original_comment_list_detail:%d';


    const ORIGINAL_COMMENT_DETAIL_KEY = 'original:comment:detail:%s';
    const ORIGINAL_COMMENT_DETAIL_GROUP_KEY = 'original_comment_detail';

    const ORIGINAL_COMMENT_LIST_FIRST_KEY = 'original:comment:list:first:%s:%s:%s';
    const ORIGINAL_COMMENT_LIST_FIRST_GROUP_KEY = 'original_comment_list_first';
    public static function queryBase()
    {
        return self::query()
            ->where('status', self::STATUS_PASS)
            ->with('user:aff,uid,nickname,thumb,aff,expired_at,vip_level,uuid,sexType');
    }

    public function getIsLikeAttribute()
    {
        $watchUser = self::$watchUser;
        if (is_null($watchUser) || !$this->getAttributeValue('id')) {
            return 0;
        } elseif ($watchUser) {
            $flag = OriginalCommentUserLikeModel::getIdsById($watchUser->uid, $this->getAttribute('id'));
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

        foreach ($comments as $v) {
            $v->is_landlord = (int)$v->aff === (int)$authorAff ? 1 : 0;
            $v->comments = self::listCommentsBySecond($v->id, $girlId, 1, 4);
            foreach ($v->comments as $v1) {
                $v1->is_landlord = (int)$v1->aff === (int)$authorAff ? 1 : 0;
            }
        }
        return $comments;
    }

    public static function listCommentsByDetail($pid, $postId, $page, $limit)
    {
        $cacheKey = sprintf(self::ORIGINAL_COMMENT_LIST_DETAIL_KEY, $pid, $postId, $page, $limit);
        $group = sprintf(self::ORIGINAL_COMMENT_LIST_DETAIL_GROUP_KEY, $postId);
        return cached($cacheKey)
            ->group($group)
            ->fetchPhp(function () use ($pid, $postId, $page, $limit) {
                return self::queryBase()
                    ->where('original_id', $postId)
                    ->where('pid', $pid)
                    ->orderByDesc('like_num')
                    ->orderByDesc('id')
                    ->forPage($page, $limit)
                    ->get();
            });
    }

    protected static function listCommentsByFirst($postId, $page, $limit)
    {
        $cacheKey = sprintf(self::ORIGINAL_COMMENT_LIST_FIRST_KEY, $postId, $page, $limit);
        $group = self::ORIGINAL_COMMENT_LIST_FIRST_GROUP_KEY . $postId;
        return cached($cacheKey)
            ->group($group)
            ->fetchPhp(function () use ($postId, $page, $limit) {
                return self::queryBase()
                    ->where('original_id', $postId)
                    ->where('pid', 0)
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



    public static function getCommentById(\MemberModel $member, $commentId)
    {
        $cacheKey = sprintf(self::ORIGINAL_COMMENT_DETAIL_KEY, $commentId);
        /** @var GirlCommentModel $comment */
        $comment = cached($cacheKey)
            ->group(self::ORIGINAL_COMMENT_DETAIL_GROUP_KEY)
            ->fetchPhp(function () use ($commentId) {
                return self::with(['user'])
                    ->where('id', $commentId)
                    ->where('status', self::STATUS_PASS)
                    ->first();
            });
        if ($comment) {
            $comment->watchByUser($member);
        }

        return $comment;
    }
}
