<?php

/**
 * class PcCommentsModel
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
class PcCommentModel extends EloquentModel
{
    const STATUS_WAIT = 0; //  待审核评论状态
    const STATUS_SUCCESS = 1; // 正常评论状态

    const CK_PC_MV_COMMENT = 'ck:pc:mv:comment:%s:%s:%s';
    const GP_PC_MV_COMMENT = 'gp:pc:mv:comment';
    const CN_PC_MV_COMMENT = 'PC_视频评论列表';

    const CK_PC_MV_COMMENT_DETAIL = 'ck:pc:mv:comment:detail:%s';
    const GP_PC_MV_COMMENT_DETAIL = 'gp:pc:mv:comment:detail';
    const CN_PC_MV_COMMENT_DETAIL = 'PC_视频评论详情';

    const CK_PC_SECOND_MV_COMMENT_LIST = 'ck:pc:second:mv:comment:list:%d:%d:%d';
    const GP_PC_SECOND_MV_COMMENT_LIST = 'gp:pc:second:mv:comment';
    const CN_PC_SECOND_MV_COMMENT_LIST = 'PC_视频二级评论列表';

    protected $table = 'comments';

    protected $fillable = [
        'mv_id',
        'c_id',
        'uid',
        'comment',
        'is_read',
        'like_num',
        'ipstr',
        'cityname',
        'status',
        'complain_num',
        'created_at',
        'updated_at'
    ];

    public $timestamps = true;

    public static function createComment(MemberModel $member, PcMvModel $mv, $cId, $content)
    {
        $mvID = $mv->id;
        if ('product' == APP_ENVIRON) {
            $maybesample = AdsampleModel::checkTextSimilar($content);
            $status = $maybesample ? 0 : 1;
        }else{
            $status = PcCommentModel::STATUS_SUCCESS;
        }

        $data = [
            'mv_id' => $mvID,
            'c_id' => $cId,
            'uid' => $member->uid,
            'comment' => $content,
            'ipstr' => USER_IP,
            'cityname' => IP_POSITION['city'],
            'status' => $status
        ];

        self::create($data);
        $cId == 0 && \MvModel::where('id', $mvID)->increment('comment');

        \tools\RedisService::redis()->zIncrBy(\CommentModel::REDIS_COMMENT_TODAY_COUNT, 1, date('Ymd'));
        \tools\RedisService::redis()->del(\CommentModel::REDIS_COMMENT_LIST . $mvID . '_1');

        \tools\RedisService::set(\CommentModel::REDIS_COMMENT_LAST_KEY . $member->uuid, $mvID, 60);
        \tools\RedisService::redis()->sAdd(\CommentModel::REDIS_COMMENT_5MIN_KEY . $member->uuid, $mvID);
        \tools\RedisService::expire(\CommentModel::REDIS_COMMENT_5MIN_KEY . $member->uuid, 300);

        return true;
    }

    /**
     * 二级评论
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function child()
    {
        return $this->hasMany(self::class, 'c_id', 'id')
            ->with('user:uid,nickname,thumb,vip_level,sexType,expired_at,uuid,uid');
    }

    public function user()
    {
        return $this->hasOne(MemberModel::class, 'uid', 'uid');
    }

    public static function listComments($mvId, $page, $limit)
    {
        $cacheKey = sprintf(self::CK_PC_MV_COMMENT, $mvId, $page, $limit);
        $list = cached($cacheKey)
            ->group(self::GP_PC_MV_COMMENT)
            ->chinese(self::CN_PC_MV_COMMENT)
            ->fetchPhp(function () use ($mvId, $page, $limit) {
                $with = [
                    'user' => function ($q) {
                        $q->select([
                            'uid',
                            'aff',
                            'nickname',
                            'thumb',
                            'vip_level'
                        ]);
                    }
                ];

                return self::with($with)
                    ->select([
                        'id',
                        'uid',
                        'comment',
                        'like_num',
                        'created_at'
                    ])
                    ->where('mv_id', $mvId)
                    ->where('c_id',0)
                    ->where('status', PcCommentModel::STATUS_SUCCESS)
                    ->forPage($page, $limit)
                    ->get();
            });

        return $list->map(function ($item) {
            if (!$item->user) {
                return null;
            }
            $item->comments = self::listCommentsByDetail($item->id, 1, 10);
            return $item;
        })->filter()->values();
    }

    public static function listCommentsByDetail($c_id, $page, $limit)
    {
        $cacheKey = sprintf(self::CK_PC_SECOND_MV_COMMENT_LIST, $c_id, $page, $limit);
        return cached($cacheKey)
            ->group(self::GP_PC_SECOND_MV_COMMENT_LIST)
            ->chinese(self::CN_PC_SECOND_MV_COMMENT_LIST)
            ->fetchPhp(function () use ($c_id, $page, $limit) {
                $with = [
                    'user' => function ($q) {
                        $q->select([
                            'uid',
                            'aff',
                            'nickname',
                            'thumb',
                            'vip_level'
                        ]);
                    }
                ];
                return self::query()
                    ->with($with)
                    ->select([
                        'id',
                        'uid',
                        'comment',
                        'like_num',
                        'created_at'
                    ])
                    ->where('c_id', $c_id)
                    ->where('status', PcCommentModel::STATUS_SUCCESS)
                    ->orderByDesc('id')
                    ->forPage($page,$limit)
                    ->get();
            });
    }

    public static function getDetail($id)
    {
        $cacheKey = sprintf(self::CK_PC_MV_COMMENT_DETAIL, $id);
        return cached($cacheKey)
            ->group(self::GP_PC_MV_COMMENT_DETAIL)
            ->chinese(self::CN_PC_MV_COMMENT_DETAIL)
            ->fetchPhp(function () use ($id) {
                return self::select([
                    'c_id',
                    'uid',
                    'mv_id'
                ])
                    ->where('c_id', $id)
                    ->where('status', PcCommentModel::STATUS_SUCCESS)
                    ->first();
            });
    }

}