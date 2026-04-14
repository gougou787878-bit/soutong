<?php

use Illuminate\Database\Eloquent\Model;

/**
 * Class FaceMaterialCommentModel
 *
 * AI换脸模版评论表
 *
 * @property int $id
 * @property int $material_id 模版ID
 * @property int $material_type 类型 0: 系统模版, 1: 客户自己的模版
 * @property int $pid 评论ID, 默认0 (第一层评论)
 * @property int $aff 用户aff
 * @property string $comment 留言内容
 * @property int $status 0:待审核, 1:审核通过, 2:未通过, 3:禁言
 * @property int $is_read 被回复者是否已读
 * @property int $like_num 此条评论点赞数量
 * @property string $ipstr 用户IP
 * @property string $cityname 定位城市
 * @property int $complain_num 被举报次数
 * @property string $refuse_reason 拒绝通过原因
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property int $is_finished 资源是否处理完 0:未处理, 1:已处理
 * @property int $is_top 是否置顶 0:未置顶, 1:已置顶
 *
 * @mixin \Eloquent
 */
class FaceMaterialCommentModel extends EloquentModel
{
    protected $table = "face_material_comment";
    protected $primaryKey = 'id';
    protected $fillable = [
        'material_id',
        'material_type',
        'pid',
        'aff',
        'comment',
        'status',
        'is_read',
        'like_num',
        'ipstr',
        'cityname',
        'complain_num',
        'refuse_reason',
        'created_at',
        'updated_at',
        'is_finished',
        'is_top'
    ];

    public $timestamps = false;

    const STATUS_WAIT = 0;
    const STATUS_PASS = 1;
    const STATUS_UNPASS = 2;
    const STATUS_BAN = 3;
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


    const POST_COMMENT_LIST_DETAIL_KEY = 'face_material:comment:list:detail:%d:%d:%d';
    const POST_COMMENT_LIST_DETAIL_GROUP_KEY = 'face_material_comment_list_detail:%d';
    const CREATE_COMMENT_COMMENT_CLEAR_GROUP = 'create_comment_commemnt_clear_%s';
    const POST_COMMENT_LIST_FIRST_KEY = 'face_material:comment:list:first:%s:%s:%s';
    const CREATE_POST_COMMENT_CLEAR_GROUP = 'create_face_material_commemnt_clear_%s';
    const POST_COMMENT_LIST_FIRST_GROUP_KEY = 'face_material_comment_list_first';


    const POST_COMMENT_DETAIL_KEY = 'face_material:comment:detail:%s';
    const POST_COMMENT_DETAIL_GROUP_KEY = 'face_material_comment_detail';



    //是否点赞
    public function getIsLikeAttribute()
    {
        $watchUser = self::$watchUser;
        if (empty($watchUser) || !isset($this->attributes['id'])) {
            return 0;
        }
        static $ids = null;
        if (null === $ids) {
            $ids = FaceMaterialUserLikeModel::getIdsByAff($watchUser->aff, FaceMaterialUserLikeModel::TYPE_COMMENT, FaceMaterialUserLikeModel::ACTION_LIKE);
        }
        if (in_array($this->attributes['id'], $ids)) {
            return 1;
        }
        return 0;
    }

    public static function queryBase()
    {
        return self::query()
            ->where('status', self::STATUS_PASS)
            //->where('is_finished', self::FINISH_OK)
            ->with('user:aff,uid,nickname,thumb,aff,expired_at,vip_level,uuid,sexType');
    }




    /**
     * 获取评论详情
     *
     * @param MemberModel $member
     * @param int $commentId
     * @return FaceMaterialCommentModel|null
     */
    public static function getCommentById($postId, $page, $limit)
    {
        $comments = self::listCommentsByFirst($postId, $page, $limit);
        foreach ($comments as $v) {
            $v->comments = self::listCommentsByCommentId($v->id, $v->material_id, 1, 3);
        }

        return $comments;
    }

    /**
     * 关联用户信息
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user()
    {
        return $this->hasOne(MemberModel::class, 'aff', 'aff');
    }


    protected static function listCommentsByFirst($postId, $page, $limit)
    {
        $cacheKey = sprintf(self::POST_COMMENT_LIST_FIRST_KEY, $postId, $page, $limit);
        $group = self::POST_COMMENT_LIST_FIRST_GROUP_KEY . $postId;
        return cached($cacheKey)
            ->group($group)
            ->fetchPhp(function () use ($postId, $page, $limit) {
                return self::queryBase()
                    ->where('material_id', $postId)
                    ->where('pid', 0)
                    ->orderByDesc('is_top')
                    ->orderByDesc('like_num')
                    ->orderByDesc('id')
                    ->forPage($page, $limit)
                    ->get();
            });
    }

    public static function listCommentsByCommentId($pid, $postId, $page, $limit)
    {
        return self::listCommentsByDetail($pid, $postId, $page, $limit);
    }


    public static function listCommentsByDetail($pid, $postId, $page, $limit)
    {
        $cacheKey = sprintf(self::POST_COMMENT_LIST_DETAIL_KEY, $pid, $postId, $page, $limit);
        $group = sprintf(self::POST_COMMENT_LIST_DETAIL_GROUP_KEY, $postId);
        return cached($cacheKey)
            ->group($group)
            ->clearCached()
            ->fetchPhp(function () use ($pid, $postId, $page, $limit) {
                return self::queryBase()
                    ->where('material_id', $postId)
                    ->where('pid', $pid)
                    ->orderByDesc('like_num')
                    ->orderByDesc('id')
                    ->forPage($page, $limit)
                    ->get();
            });
    }

        // 清理帖子评论首页

    /**
     * @throws RedisException
     */
    public static function clearCacheWhenCreatePostComment($postId)
    {
        //清理帖子详情
        // FaceMaterialModel::clearDetailCache($postId);// 清理帖子第一页评论
        $first_group = self::POST_COMMENT_LIST_FIRST_GROUP_KEY . $postId;
        $second_group = sprintf(self::POST_COMMENT_LIST_DETAIL_GROUP_KEY, $postId);
        cached('')->clearGroup($first_group, $second_group); // 清理第一、二层级
    }




    public static function getIdsById($aff, $id)
    {
        return self::where('aff', $aff)
            ->where('material_id', $id)
            ->first();
    }

    public static function getCommentByIds(\MemberModel $member, $commentId)
    {
        $cacheKey = sprintf(self::POST_COMMENT_DETAIL_KEY, $commentId);
        /** @var PostCommentModel $comment */
        $comment = cached($cacheKey)
            ->group(self::POST_COMMENT_DETAIL_GROUP_KEY)
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
