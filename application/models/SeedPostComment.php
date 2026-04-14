<?php

/**
 * @property int $id
 * @property int $seed_id 帖子ID
 * @property int $pid 父级 默认0
 * @property int $aff 用户AFF
 * @property int $comment 留言内容
 * @property int $status 0:待审核 1:审核通过 2.未通过
 * @property int $is_top 是否置顶 0未 1已
 * @property int $is_read 被回复者是否已读 0未读 1已读
 * @property int $like_num 点赞数
 * @property int $video_num 视频数量
 * @property int $photo_num 图片数量
 * @property string $ipstr IP
 * @property string $cityname 城市名
 * @property int $complain_num 被举报次数
 * @property int $is_finished 资源是否处理完 0未处理 1已处理
 * @property string $refuse_reason 拒绝通过原因
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @mixin \Eloquent
 */
class SeedPostCommentModel extends EloquentModel
{
    protected $table = 'seed_post_comment';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'id',
        'seed_id',
        'pid',
        'aff',
        'comment',
        'is_read',
        'is_top',
        'is_finished',
        'like_num',
        'video_num',
        'photo_num',
        'ipstr',
        'cityname',
        'complain_num',
        'status',
        'refuse_reason',
        'created_at',
        'updated_at',
    ];

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

    const SELECT_RAW = 'id,seed_id,pid,aff,comment,like_num,created_at';

    const CK_SEED_POST_COMMENT = 'ck:seed:post:comment:%s-%s-%s';
    const RU_SEED_POST_COMMENT = 'ck:seed:post:comment:%s';
    const GP_SEED_POST_COMMENT = 'gp:seed:post:comment';
    const CN_SEED_POST_COMMENT = '种子一级评论';

    const CK_SEED_COMMENT_COMMENT = 'ck:seed:comment:comment:%s-%s-%s-%s';
    const RU_SEED_COMMENT_COMMENT = 'ck:seed:comment:comment:%s';
    const GP_SEED_COMMENT_COMMENT = 'gp:seed:comment:comment';
    const CN_SEED_COMMENT_COMMENT = '种子二级评论';

    const CK_SEED_COMMENT_DETAIL = 'ck:seed:comment:detail:%s';
    const GP_SEED_COMMENT_DETAIL = 'ck:seed:comment:detail';
    const CN_SEED_COMMENT_DETAIL = '种子评论详情';

    protected $appends = ['comments', 'medias', 'is_like'];

    public function getCommentsAttribute($key)
    {
        return $this->attributes['comments'] ?? [];
    }

    public function getIsLikeAttribute()
    {
        $watchUser = self::$watchUser;
        if (!$watchUser) {
            return 0;
        }
        $id = (int)($this->attributes['id'] ?? 0);
        static $ary = null;
        if ($ary === null) {
            $ary = SeedLikeModel::list_ids(SeedLikeModel::TYPE_COMMENT, $watchUser->aff);
        }
        return in_array($id, $ary) ? 1 : 0;
    }

    public function getIsLandlordAttribute()
    {
        $watchUser = self::$watchUser;
        if (!$watchUser) {
            return 0;
        }
        $aff = (int)($this->attributes['aff'] ?? 0);
        return $aff == $watchUser->aff ? 1 : 0;
    }

    public function user()
    {
        return $this->hasOne(MemberModel::class, 'aff', 'aff');
    }

    public function getMediasAttribute()
    {
        return $this->attributes['medias'] ?? [];
    }

    public static function getDetail($commentId)
    {
        $cacheKey = sprintf(self::CK_SEED_COMMENT_DETAIL, $commentId);
        return cached($cacheKey)
            ->group(self::GP_SEED_COMMENT_DETAIL)
            ->chinese(self::CN_SEED_COMMENT_DETAIL)
            ->fetchPhp(function () use ($commentId) {
                return self::where('id', $commentId)
                    ->where('status', self::STATUS_PASS)
                    ->where('is_finished', self::FINISH_OK)
                    ->first();
            });
    }

    public static function listCommentsByFirst(MemberModel $member, $postId, $page, $limit)
    {
        $cacheKey = sprintf(self::CK_SEED_POST_COMMENT, $postId, $page, $limit);
        $list = cached($cacheKey)
            ->group(self::GP_SEED_POST_COMMENT)
            ->chinese(self::CN_SEED_POST_COMMENT)
            ->fetchPhp(function () use ($postId, $page, $limit, $member) {

                $idAry = self::where('seed_id', $postId)
                    ->where('status', self::STATUS_PASS)
                    ->where('is_finished', self::FINISH_OK)
                    ->where('pid', 0)
                    ->orderByDesc('is_top')
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->forPage($page, $limit)
                    ->pluck('id');

                $list = self::with('user:aff,uid,nickname,thumb,aff,expired_at,vip_level,uuid,auth_status')
                    ->selectRaw(self::SELECT_RAW)
                    ->whereIn('id', $idAry)
                    ->get()
                    ->map(function ($item) use ($member) {
                        if (!$item->user) {
                            return null;
                        }
                        $item->comments = self::listCommentsBySecond($member, $item->id, $item->seed_id, 0, 10);
                        return $item;
                    })
                    ->filter()
                    ->values();

                return array_keep_idx($list, $idAry, 'id');
            });
        return self::formatComent($list, $member);
    }

    public static function listCommentsBySecond(MemberModel $member, $pid, $postId, $page, $limit)
    {
        $cacheKey = sprintf(self::CK_SEED_COMMENT_COMMENT, $pid, $postId, $page, $limit);
        $list = cached($cacheKey)
            ->group(self::GP_SEED_COMMENT_COMMENT)
            ->chinese(self::CN_SEED_COMMENT_COMMENT)
            ->fetchPhp(function () use ($pid, $postId, $page, $limit) {

                $idAry = self::where('seed_id', $postId)
                    ->where('status', self::STATUS_PASS)
                    ->where('is_finished', self::FINISH_OK)
                    ->where('pid', $pid)
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->forPage($page, $limit)
                    ->pluck('id');

                $list = self::with('user:aff,uid,nickname,thumb,aff,expired_at,vip_level,uuid,auth_status')
                    ->selectRaw(self::SELECT_RAW)
                    ->whereIn('id', $idAry)
                    ->get()
                    ->map(function ($item) {
                        if (!$item->user) {
                            return null;
                        }
                        return $item;
                    })
                    ->filter()
                    ->values();

                return array_keep_idx($list, $idAry, 'id');
            });
        return self::formatComent($list, $member);
    }

    public static function clearCache($model)
    {
        if ($model->pid == 0) {
            // 删除一级评论
            cached('')->clearGroup(self::GP_SEED_POST_COMMENT);
        } else {
            // 清理二级评论列表
            cached('')->clearGroup(self::GP_SEED_COMMENT_COMMENT);
        }
        cached(sprintf(SeedPostModel::CK_SEED_POST_DETAIL, $model->seed_id))->clearCached();
    }

    // 清理帖子评论首页
    public static function clearCacheWhenCreatePostComment($postId)
    {
        // 删除一级评论
        cached('')->clearGroup(self::GP_SEED_POST_COMMENT);
        cached(sprintf(SeedPostModel::CK_SEED_POST_DETAIL, $postId))->clearCached();
    }

    public static function clearCacheWhenCreateComment($commentId, $postId)
    {
        // 清理二级评论列表
        cached('')->clearGroup(self::GP_SEED_COMMENT_COMMENT);
        cached(sprintf(self::CK_SEED_COMMENT_DETAIL, $commentId))->clearCached();
        cached(sprintf(SeedPostModel::CK_SEED_POST_DETAIL, $postId))->clearCached();
    }

    public function setCommentAttribute($value)
    {
        $this->attributes['comment'] = emojiEncode($value);
    }

    public function getCommentAttribute($value): string
    {
        return emojiDecode($value);
    }

    public static function incrementLikeNum($id)
    {
        self::where('id', $id)->increment('like_num');
    }

    public static function decrementLikeNum($id)
    {
        self::where('id', $id)->decrement('like_num');
    }

    public static function formatComent($commentData,\MemberModel $member =null){
        if(!$commentData){
            return $commentData;
        }
        foreach ($commentData as &$comment){
            if ($medias = $comment->medias) {
                $medias = collect($medias)->map(function ($media) use ($comment) {
                    if ($media->type == \PostMediaModel::TYPE_IMG) {
                        $media->media_url_full = url_cover($media->media_url);
                    } elseif ($media->type == \PostMediaModel::TYPE_VIDEO) {
                        $extension = pathinfo($media->media_url, PATHINFO_EXTENSION);
                        if ($extension == 'm3u8') {
                            $media->media_url_full = getPlayUrl($media->media_url, false);
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
}
