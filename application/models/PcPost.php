<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class PcPostModel
 *
 * @property int $id
 * @property string $aff 用户AFF
 * @property string $content 内容
 * @property int $is_deleted 用户删除标识 0否 1是
 * @property int $like_num 点赞数量
 * @property int $comment_num 评论数量
 * @property int $is_best 置精 0否 1是
 * @property int $category 帖子类型 1图片 2视频 3图文
 * @property string $refuse_reason 拒绝通过的原因
 * @property int $photo_num 图片数量
 * @property int $video_num 视频数量
 * @property int $is_finished 资源是否完成 0否1是
 * @property string $ipstr 用户ip
 * @property string $cityname 定位城市
 * @property int $topic_id 话题ID
 * @property int $view_num 浏览数量
 * @property string $refresh_at 刷新时间
 * @property string $title 标题
 * @property int $reward_amount 打赏金币
 * @property int $status 0:待审核 1:审核中 2.审核通过 3.未通过 4.被举报
 * @property string $created_at 创建时间
 * @property string $updated_at 修改时间
 * @property int $sort 排序 越大越前
 * @property int $favorite_num 收藏数
 * @property int $reward_num 打赏次数
 * @property int $set_top
 * @property int $_id
 * @property int $price 解锁金币数
 *
 * @mixin \Eloquent
 */
class PcPostModel extends EloquentModel
{

    const STATUS_WAIT = 0;
    const STATUS_PASS = 1;
    const STATUS_UNPASS = 2;
    const STATUS_TIPS = [
        self::STATUS_WAIT => '待审核',
        self::STATUS_PASS => '通过',
        self::STATUS_UNPASS => '未通过'
    ];

    const BEST_NO = 0;
    const BEST_OK = 1;
    const BEST_TIPS = [
        self::BEST_NO => '未置精',
        self::BEST_OK => '置精'
    ];


    const TOP_TIPS = [
        self::BEST_NO => '未置顶',
        self::BEST_OK => '置顶'
    ];

    const TYPE_IMG = 1;
    const TYPE_VIDEO = 2;
    const TYPE_MIX = 3;
    const TYPE_TIPS = [
        self::TYPE_IMG => '图片',
        self::TYPE_VIDEO => '视频',
        self::TYPE_MIX => '图文'
    ];

    const FINISH_NO = 0;
    const FINISH_OK = 1;
    const FINISH_TIPS = [
        self::FINISH_NO => '未完成',
        self::FINISH_OK => '已完成'
    ];

    const DELETED_NO = 0;
    const DELETED_OK = 1;
    const DELETED_TIPS = [
        self::DELETED_NO => '未删除',
        self::DELETED_OK => '已删除'
    ];

    const TYPE_FOLLOW_TOPIC = 1;
    const TYPE_FOLLOW_USER = 2;
    const TYPE_FOLLOW_TOPIC_TIPS = [
        self::TYPE_FOLLOW_TOPIC => '关注话题',
        self::TYPE_FOLLOW_USER => '关注用户'
    ];
    const CK_PC_POST_TOPIC_LIST = 'ck:pc:post:topic:list:%s:%s:%s:%s';
    const GP_PC_POST_TOPIC_LIST = 'gp:pc:post:topic:list';
    const CN_PC_POST_TOPIC_LIST = 'PC_社区帖子列表';

    const CK_PC_POST_TOPIC_LIST_CT = 'ck:pc:post:topic:list:ct:%s:%s';
    const GP_PC_POST_TOPIC_LIST_CT = 'gp:pc:post:topic:list:ct';
    const CN_PC_POST_TOPIC_LIST_CT = 'PC_社区帖子列表总和';

    const CK_PC_POST_DETAIL = 'ck:pc:post:detail:%s';
    const GP_PC_POST_DETAIL = 'gp:pc:post:detail';
    const CN_PC_POST_DETAIL = 'PC_社区帖子详情';

    const CK_PC_USER_POST = 'ck:pc:topic:post:%s:%s:%s';
    const GP_PC_USER_POST = 'gp:pc:topic:post';
    const CN_PC_USER_POST = 'PC_用户帖子列表';

    const CK_POST_PREV = 'ck:pc:post:prev:%s';
    const GP_POST_PREV = 'gp:pc:post:prev';
    const CN_POST_PREV = 'PC_上一篇';

    const CK_POST_NEXT = 'ck:pc:post:next:%s';
    const GP_POST_NEXT = 'gp:pc:post:next';
    const CN_POST_NEXT = 'PC_下一篇';

    const SELECT_MY_LIST_FIELDS = ['id', 'title', 'is_best', 'view_num', 'favorite_num', 'aff', 'topic_id', 'like_num', 'status', 'refuse_reason','comment_num','created_at'];
    const SELECT_LIST_FIELDS = ['id', 'title', 'is_best', 'view_num', 'favorite_num', 'aff', 'topic_id', 'like_num','comment_num','created_at'];
    const SELECT_DETAIL_FIELDS = ['id', 'title', 'is_best', 'view_num', 'favorite_num', 'content', 'aff', 'topic_id', 'created_at', 'like_num', 'price', 'video_num'];

    protected $table = "post";

    protected $primaryKey = 'id';

    protected $fillable = [
        'aff',
        'content',
        'is_deleted',
        'like_num',
        'comment_num',
        'is_best',
        'category',
        'refuse_reason',
        'photo_num',
        'video_num',
        'is_finished',
        'ipstr',
        'cityname',
        'topic_id',
        'view_num',
        'refresh_at',
        'title',
        'reward_amount',
        'status',
        'created_at',
        'updated_at',
        'sort',
        'favorite_num',
        'reward_num',
        'set_top',
        '_id',
        "price"
    ];

    protected $guarded = 'id';

    public $timestamps = false;

    protected $appends = [
        'is_follow',
        'is_pay'
    ];

    public function getIsFollowAttribute()
    {
        if (is_null($this->watchUser) || !$this->getAttributeValue('id')) {
            return 0;
        }
        $flag = PostUserLikeModel::getIdsById($this->watchUser->getAttributeValue('uid'), $this->getAttributeValue('id'));
        return $flag ? 1 : 0;
    }

    public function getIsPayAttribute()
    {
        if (is_null($this->watchUser) || !$this->getAttributeValue('id')) {
            return 0;
        }
        if ($this->watchUser->uid == $this->aff) {
            return 1;
        }
        if ($this->price) {
            //是否是通卡plus
            $isFreeMember = FreeMemberModel::isFreeMember($this->watchUser->uid, FreeMemberModel::FREE_DAY_MV_ADD_COMMUNITY);
            if ($isFreeMember) {
                return 1;
            }
            $flag = PostRewardLogModel::hasBuy($this->watchUser->uid, $this->getAttribute('id'));
            return $flag ? 1 : 0;
        } else {
            $flag = $this->watchUser->is_vip && $this->watchUser->vvLevel;
            return $flag ? 1 : 0;
        }
        return 0;
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(MemberModel::class, 'aff', 'aff');
    }

    public function topic(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PostTopicModel::class, 'id', 'topic_id');
    }

    public function medias(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PostMediaModel::class, 'pid', 'id');
    }

    public static function queryBase()
    {
        return self::with('user:aff,uid,nickname,thumb,aff,expired_at,vip_level,uuid,sexType')
            ->where('status', self::STATUS_PASS)
            ->where('is_deleted', self::DELETED_NO)
            ->where('is_finished', self::FINISH_OK);
    }

    public static function queryBaseWithOutUser()
    {
        return self::where('status', self::STATUS_PASS)
            ->where('is_deleted', self::DELETED_NO)
            ->where('is_finished', self::FINISH_OK);
    }

    public static function clearDetailCache($postId)
    {
        cached(sprintf(self::CK_PC_POST_DETAIL, $postId))->clearCached();
    }

    //帖子详情
    public static function getPostById($id)
    {
        return cached(sprintf(self::CK_PC_POST_DETAIL,$id))
            ->group(self::GP_PC_POST_DETAIL)
            ->chinese(self::CN_PC_POST_DETAIL)
            ->clearCached()
            ->fetchPhp(function () use ($id){
                return self::queryBase()
                    ->with('topic:id,name')
                    ->with('medias:pid,media_url,cover,thumb_width,thumb_height,type,status')
                    ->select(self::SELECT_DETAIL_FIELDS)
                    ->find($id);
        });
    }

    // 帖子列表
    public static function listTopicPosts($topicId, $cate, $page, $limit)
    {
        $cacheKey = sprintf(self::CK_PC_POST_TOPIC_LIST, $cate, $topicId, $page, $limit);
        $list = cached($cacheKey)
            ->group(self::GP_PC_POST_TOPIC_LIST)
            ->chinese(self::CN_PC_POST_TOPIC_LIST)
            ->fetchPhp(function () use ($cate, $topicId, $page, $limit) {
                 return self::queryBase()
                     ->with([
                         'topic' => function($q){
                            return $q->selectRaw('id,name');
                         },
                         'medias' => function($q){
                             return $q->selectRaw('pid,media_url,cover,thumb_width,thumb_height,type,status')
                                 ->where('status',PostMediaModel::STATUS_OK);
                         }
                     ])
                     ->select(self::SELECT_LIST_FIELDS)
                     ->when($topicId,function ($q) use ($topicId){
                         return $q->where("topic_id",$topicId);
                     })
                     //推荐
                     ->when($cate == 'recommend',function ($q){
                         return $q->orderByDesc('set_top')
                             ->orderByDesc('sort');
                     })
                     //最新
                     ->when($cate == 'new',function ($q){
                         return $q->orderByDesc('refresh_at');
                     })
                     //最热
                     ->when($cate == 'hot',function ($q){
                         return $q->orderByDesc('like_num');
                     })
                     //精华
                     ->when($cate == 'choice', function ($q) {
                         return $q->where('is_best',PcPostModel::BEST_OK);
                     })
                     //视频
                    ->when($cate == 'video', function ($q) {
                        return $q->where('video_num','>',0);
                    })
                    ->orderByDesc('id')
                    ->forPage($page,$limit)
                    ->get();
            });

        //总数
        $ct =  self::listTopicPostsTotal($topicId,$cate);

        // 这个需要分页
        return collect([
            'list'       => $list,
            'cur_page'   => $page,
            'total_page' => ceil($ct / $limit),
            'limit'      => $limit,
            'count'      => $ct
        ]);

    }

    public static function listTopicPostsTotal($topicId, $cate){
        $cacheKey = sprintf(self::CK_PC_POST_TOPIC_LIST_CT, $cate, $topicId);
        return cached($cacheKey)
            ->group(self::GP_PC_POST_TOPIC_LIST_CT)
            ->chinese(self::CN_PC_POST_TOPIC_LIST_CT)
            ->fetchPhp(function () use ($cate, $topicId) {
                return self::queryBase()
                    ->when($topicId,function ($q) use ($topicId){
                        return $q->where("topic_id",$topicId);
                    })
                    //推荐
                    ->when($cate == 'recommend',function ($q){
                        return $q->orderByDesc('set_top')
                            ->orderByDesc('sort');
                    })
                    //最新
                    ->when($cate == 'new',function ($q){
                        return $q->orderByDesc('refresh_at');
                    })
                    //最热
                    ->when($cate == 'hot',function ($q){
                        return $q->orderByDesc('like_num');
                    })
                    //精华
                    ->when($cate == 'choice', function ($q) {
                        return $q->where('is_best',PcPostModel::BEST_OK);
                    })
                    //视频
                    ->when($cate == 'video', function ($q) {
                        return $q->where('video_num','>',0);
                    })
                    ->count('id');
            });
    }

    public static function listUserPosts($aff, $page, $limit)
    {
        $cacheKey = sprintf(self::CK_PC_USER_POST, $aff, $page, $limit);
        return cached($cacheKey)
            ->group(self::GP_PC_USER_POST)
            ->chinese(self::CN_PC_USER_POST)
            ->fetchPhp(function () use ($aff, $page, $limit) {

                $idAry = self::queryBase()
                    ->where('aff', $aff)
                    ->orderByDesc('id')
                    ->forPage($page, $limit)
                    ->pluck('id');

                $list = self::with([
                    'topic'  => function ($q) {
                        $q->select(['id', 'name'])
                            ->where('status', PostTopicModel::STATUS_NORMAL);
                    },
                    'medias' => function ($q) {
                        $q->where('relate_type', PostMediaModel::TYPE_RELATE_POST)
                            ->where('status', PostMediaModel::STATUS_OK)
                            ->orderBy('type');
                    }
                ])
                    ->select(self::SELECT_LIST_FIELDS)
                    ->whereIn('id', $idAry)
                    ->orderByDesc('id')
                    ->get();

                return array_keep_idx($list, $idAry, 'id');
            });
    }

    const CK_PC_SEARCH_POST = 'ck:pc:search:post:%s:%s:%s:%s';
    const GP_PC_SEARCH_POST = 'gp:pc:search:post';
    const CN_PC_SEARCH_POST = 'PC_搜索帖子列表';

    public static function listSearch($topic_id, $word, $page, $limit)
    {
        $cacheKey = sprintf(self::CK_PC_SEARCH_POST, $topic_id, substr(md5($word),0,8), $page, $limit);
        return cached($cacheKey)
            ->group(self::GP_PC_SEARCH_POST)
            ->chinese(self::CN_PC_SEARCH_POST)
            ->clearCached()
            ->fetchPhp(function () use ($topic_id, $word, $page, $limit) {

                $idAry = self::queryBaseWithOutUser()
                    ->when($topic_id, function ($q) use ($topic_id){
                        return $q->where('topic_id',$topic_id);
                    })
                    ->whereRaw("title like ?", ['%' . $word . '%'])
                    ->orderByDesc('id')
                    ->forPage($page, $limit)
                    ->pluck('id');

                $list = self::with([
                    'user'  => function ($q) {
                        $q->select(['aff','uid','nickname','thumb','expired_at','vip_level','uuid','sexType']);
                    },
                    'topic'  => function ($q) {
                        $q->select(['id', 'name'])
                            ->where('status', PostTopicModel::STATUS_NORMAL);
                    },
                    'medias' => function ($q) {
                        $q->where('relate_type', PostMediaModel::TYPE_RELATE_POST)
                            ->where('status', PostMediaModel::STATUS_OK)
                            ->orderBy('type');
                    }
                ])
                    ->select(self::SELECT_LIST_FIELDS)
                    ->whereIn('id', $idAry)
                    ->get();

                return array_keep_idx($list, $idAry, 'id');
            });
    }

    public static function listMyPosts($aff, $status, $page, $limit)
    {
        return self::with([
                'topic' => function($q){
                    return $q->selectRaw('id,name');
                },
                'medias' => function($q){
                    return $q->selectRaw('pid,media_url,cover,thumb_width,thumb_height,type,status')
                        ->where('status',PostMediaModel::STATUS_OK);
                }
            ])
            ->select(self::SELECT_MY_LIST_FIELDS)
            ->where('aff',$aff)
            ->where('status',$status)
            ->orderByDesc('id')
            ->forPage($page,$limit)
            ->get();
    }

    public static function prevPost($id)
    {
        $cacheKey = sprintf(self::CK_POST_PREV, $id);
        return cached($cacheKey)
            ->group(self::GP_POST_PREV)
            ->chinese(self::CN_POST_PREV)
            ->fetchPhp(function () use ($id) {
                return self::queryBaseWithOutUser()
                    ->select(['id', 'title'])
                    ->where('id', '<', $id)
                    ->orderByDesc('id')
                    ->first();
            });
    }

    public static function nextPost($id)
    {
        $cacheKey = sprintf(self::CK_POST_NEXT, $id);
        return cached($cacheKey)
            ->group(self::GP_POST_NEXT)
            ->chinese(self::CN_POST_NEXT)
            ->fetchPhp(function () use ($id) {
                return self::queryBaseWithOutUser()
                    ->select(['id', 'title'])
                    ->where('id', '>', $id)
                    ->orderBy('id')
                    ->first();
            });
    }

}
