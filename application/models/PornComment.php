<?php

use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * class PornCommentModel
 *
 * @property int $aff 用户aff
 * @property string $cityname 定位城市
 * @property string $comment 留言内容
 * @property string $created_at
 * @property int $id
 * @property string $ipstr 用户ip
 * @property int $is_top 是否置顶 0未置顶 1已置顶
 * @property int $pid 评论ID,默认0(第一层评论)
 * @property int $porn_id 帖子ID
 * @property string $refuse_reason 拒绝通过原因
 * @property int $status 0:待审核 1:审核通过 2.未通过
 * @property string $updated_at
 *
 *
 * @date 2024-04-01 15:50:12
 *
 * @mixin \Eloquent
 */
class PornCommentModel extends EloquentModel
{
    protected $table = "porn_comment";
    protected $primaryKey = 'id';
    protected $fillable = [
        'aff',
        'cityname',
        'comment',
        'created_at',
        'ipstr',
        'is_top',
        'pid',
        'porn_id',
        'refuse_reason',
        'status',
        'updated_at'
    ];
    protected $guarded = 'id';
    public $timestamps = true;

    const STATUS_WAIT = 0;
    const STATUS_PASS = 1;
    const STATUS_UNPASS = 2;
    const STATUS_TIPS = [
        self::STATUS_WAIT   => '待审核',
        self::STATUS_PASS   => '已通过',
        self::STATUS_UNPASS => '已拒绝'
    ];

    const FINISH_NO = 0;
    const FINISH_OK = 1;
    const FINISH_TIPS = [
        self::FINISH_NO => '未完成',
        self::FINISH_OK => '已完成'
    ];

    const TOP_NO = 0;
    const TOP_OK = 1;
    const TOP_TIPS = [
        self::TOP_NO => '未置顶',
        self::TOP_OK => '已置顶',
    ];


    const CK_PORN_COMMENT_LIST_FIRST_KEY = 'ck:porn:comment:list:first:%s:%s:%s';
    const GP_PORN_COMMENT_LIST_FIRST_KEY = 'gp:porn:comment:list:first:%s';

    const CK_PORN_COMMENT_LIST_DETAIL_KEY = 'ck:porn:comment:list:detail:%s:%s:%s:%s';
    const GP_PORN_COMMENT_LIST_DETAIL_KEY = 'gk:porn:comment:list:detail:%s:%s';

    const CK_PORN_COMMENT_DETAIL_KEY = 'ck:porn:comment:detail:%s';
    const GP_PORN_COMMENT_DETAIL_KEY = 'gk:porn:comment:detail';

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

    public static function listCommentsByPornId(MemberModel $member, $porn_id, $page, $limit)
    {
        $comments = self::listCommentsByFirst($porn_id, $page, $limit);
        foreach ($comments as $v) {
            $v->comments = self::listCommentsBySecond($v->id, $porn_id, 1, 10);
        }
        return $comments;
    }

    protected static function listCommentsByFirst($porn_id, $page, $limit)
    {
        $cacheKey = sprintf(self::CK_PORN_COMMENT_LIST_FIRST_KEY, $porn_id, $page, $limit);
        $group = sprintf(self::GP_PORN_COMMENT_LIST_FIRST_KEY, $porn_id);
        return cached($cacheKey)
            ->group($group)
            ->fetchPhp(function () use ($porn_id, $page, $limit) {
                return self::queryBase()
                    ->where('porn_id', $porn_id)
                    ->where('pid', 0)
                    ->orderByDesc('is_top')
                    ->orderByDesc('id')
                    ->forPage($page,$limit)
                    ->get();
            });
    }

    protected static function listCommentsBySecond($pid, $porn_id, $page, $limit)
    {
        return self::listCommentsByDetail($pid, $porn_id, $page, $limit);
    }

    public static function listCommentsByDetail($pid, $porn_id, $page, $limit)
    {
        $cacheKey = sprintf(self::CK_PORN_COMMENT_LIST_DETAIL_KEY, $pid, $porn_id, $page, $limit);
        $group = sprintf(self::GP_PORN_COMMENT_LIST_DETAIL_KEY, $pid, $porn_id);
        return cached($cacheKey)
            ->group($group)
            ->fetchPhp(function () use ($pid, $porn_id, $page, $limit) {
                return self::queryBase()
                    ->where('porn_id', $porn_id)
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
        $cacheKey = sprintf(self::CK_PORN_COMMENT_DETAIL_KEY, $commentId);
        return cached($cacheKey)
            ->group(self::GP_PORN_COMMENT_DETAIL_KEY)
            ->fetchPhp(function () use ($commentId) {
                return self::queryBase()
                    ->where('id', $commentId)
                    ->where('status', self::STATUS_PASS)
                    ->first();
            });
    }

}
