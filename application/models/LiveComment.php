<?php

use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * class LiveCommentModel
 *
 * @property int $id
 * @property int $live_id 直播ID
 * @property int $pid 评论ID,默认0(第一层评论)
 * @property int $aff 用户aff
 * @property string $comment 留言内容
 * @property int $status 0:待审核 1:审核通过 2.未通过
 * @property string $ipstr 用户ip
 * @property string $cityname 定位城市
 * @property string $refuse_reason 拒绝通过原因
 * @property string $created_at
 * @property string $updated_at
 * @property int $is_top 是否置顶 0未置顶 1已置顶
 *
 *
 * @date 2024-08-31 15:08:03
 *
 * @mixin \Eloquent
 */
class LiveCommentModel extends EloquentModel
{

    protected $table = "live_comment";

    protected $primaryKey = 'id';

    protected $fillable = [
        'live_id',
        'pid',
        'aff',
        'comment',
        'status',
        'ipstr',
        'cityname',
        'refuse_reason',
        'created_at',
        'updated_at',
        'is_top'
    ];
    protected $guarded = 'id';
    public $timestamps = true;


    const STATUS_WAIT = 0;
    const STATUS_PASS = 1;
    const STATUS_REJECT = 2;
    const STATUS_TIPS = [
        self::STATUS_WAIT   => '待审核',
        self::STATUS_PASS   => '已通过',
        self::STATUS_REJECT => '已拒绝'
    ];

    const TOP_NO = 0;
    const TOP_OK = 1;
    const TOP_TIPS = [
        self::TOP_NO => '未置顶',
        self::TOP_OK => '已置顶',
    ];

    const CK_LIVE_COMMENT_LIST_FIRST_KEY = 'ck:live:comment:list:first:%s:%s:%s';
    const GP_LIVE_COMMENT_LIST_FIRST_KEY = 'gp:live:comment:list:first:%s';

    const CK_LIVE_COMMENT_LIST_DETAIL_KEY = 'ck:live:comment:list:detail:%s:%s:%s:%s';
    const GP_LIVE_COMMENT_LIST_DETAIL_KEY = 'gk:live:comment:list:detail:%s:%s';

    const CK_LIVE_COMMENT_DETAIL_KEY = 'ck:live:comment:detail:%s';
    const GP_LIVE_COMMENT_DETAIL_KEY = 'gk:live:comment:detail';

    public function user(): HasOne
    {
        return $this->hasOne(MemberModel::class, 'aff', 'aff');
    }

    public static function queryBase()
    {
        return self::query()
            ->where('status', self::STATUS_PASS)
            ->with('user:aff,uid,nickname,thumb,aff,expired_at,vip_level,uuid,auth_status');
    }

    public static function listCommentsByLiveId(MemberModel $member, $live_id, $page, $limit)
    {
        $comments = self::listCommentsByFirst($live_id, $page, $limit);
        foreach ($comments as $v) {
            $v->comments = self::listCommentsBySecond($v->id, $live_id, 1, 10);
        }
        return $comments;
    }

    protected static function listCommentsByFirst($live_id, $page, $limit)
    {
        $cacheKey = sprintf(self::CK_LIVE_COMMENT_LIST_FIRST_KEY, $live_id, $page, $limit);
        $group = sprintf(self::GP_LIVE_COMMENT_LIST_FIRST_KEY, $live_id);
        return cached($cacheKey)
            ->group($group)
            ->fetchPhp(function () use ($live_id, $page, $limit) {
                return self::queryBase()
                    ->where('live_id', $live_id)
                    ->where('pid', 0)
                    ->orderByDesc('is_top')
                    ->orderByDesc('id')
                    ->forPage($page,$limit)
                    ->get();
            });
    }

    protected static function listCommentsBySecond($pid, $live_id, $page, $limit)
    {
        return self::listCommentsByDetail($pid, $live_id, $page, $limit);
    }

    public static function listCommentsByDetail($pid, $live_id, $page, $limit)
    {
        $cacheKey = sprintf(self::CK_LIVE_COMMENT_LIST_DETAIL_KEY, $pid, $live_id, $page, $limit);
        $group = sprintf(self::GP_LIVE_COMMENT_LIST_DETAIL_KEY, $pid, $live_id);
        return cached($cacheKey)
            ->group($group)
            ->fetchPhp(function () use ($pid, $live_id, $page, $limit) {
                return self::queryBase()
                    ->where('live_id', $live_id)
                    ->where('pid', $pid)
                    ->orderByDesc('id')
                    ->forPage($page,$limit)
                    ->get();
            });
    }

    public static function listCommentsByCommentId($pid, $postId, $page, $limit)
    {
        return self::listCommentsByDetail($pid, $postId, $page, $limit);
    }

    public static function getCommentById($commentId)
    {
        $cacheKey = sprintf(self::CK_LIVE_COMMENT_DETAIL_KEY, $commentId);
        return cached($cacheKey)
            ->group(self::GP_LIVE_COMMENT_DETAIL_KEY)
            ->fetchPhp(function () use ($commentId) {
                return self::queryBase()
                    ->where('id', $commentId)
                    ->where('status', self::STATUS_PASS)
                    ->first();
            });
    }
}
