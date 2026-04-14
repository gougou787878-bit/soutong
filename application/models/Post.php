<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class PostModel
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
 * @property int $type 类型 0免费 1VIP 2金币
 *
 * @author xiongba
 * @date 2023-06-09 20:10:18
 *
 * @mixin \Eloquent
 */
class PostModel extends EloquentModel
{

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
        "price",
        "type",
        'p_id',
    ];

    protected $guarded = 'id';

    public $timestamps = false;

    const TYPE_PAY_FREE = 0;
    const TYPE_PAY_VIP = 1;
    const TYPE_PAY_COINS = 2;
    const TYPE_PAY_TIPS = [
        self::TYPE_PAY_FREE    => '免费',
        self::TYPE_PAY_VIP     => 'VIP',
        self::TYPE_PAY_COINS   => '金币',
    ];

    const ES_index = "post";
    protected $appends = [
        'is_follow',
        'is_pay'
    ];

    /*public function getContentAttribute():string{
        return strip_tags($this->getAttributeValue('content'));
    }*/

    public function getIsFollowAttribute(): int
    {
        $watchUser = self::$watchUser;
        if (is_null($watchUser) || !$this->getAttributeValue('id')) {
            return 0;
        }
        $flag = PostUserLikeModel::getIdsById($watchUser->getAttributeValue('uid'), $this->getAttributeValue('id'));
        return $flag ? 1 : 0;
    }

    public function getIsPayAttribute()
    {
        $watchUser = self::$watchUser;
        if (is_null($watchUser) || !$this->getAttributeValue('id')) {
            return 0;
        }
        if ($watchUser->aff == $this->aff) {
            return 1;
        }

        $type = $this->type;
        if ($type == self::TYPE_PAY_FREE){
            return 1;
        }

        if ($type == self::TYPE_PAY_VIP){
            $resourceType = PrivilegeModel::RESOURCE_TYPE_NORMAL_VIP_POST;
        }elseif ($type == self::TYPE_PAY_COINS){
            $resourceType = PrivilegeModel::RESOURCE_TYPE_NORMAL_COINS_POST;
        }
        if(UsersProductPrivilegeModel::hasPrivilege(USER_PRIVILEGE ,$resourceType,PrivilegeModel::PRIVILEGE_TYPE_VIEW)){
            return 1;
        }

        if ($this->price) {
            $flag = PostRewardLogModel::hasBuy($watchUser->uid, $this->getAttribute('id'));
            return $flag ? 1 : 0;
        }

        return 0;
    }

    const POST_TOPIC_LIST_KEY = 'post:topic:post:list:%s:%s:%s:%s';

    const POST_SEARCH_LIST_KEY = 'post:search:list:%s:%s:%s';

    const POST_SEARCH_LIST_GROUP_KEY = 'post_search_list';


    // 第一页单独GROUP
    const POST_FIRST_PAGE_GROUP_KEY = 'post_first_page';
    // GROUP
    const POST_TOPIC_LIST_GROUP_KEY = 'post_topic_post_list';
    const POST_NEW_LIST_GROUP_KEY = 'post_new_list';
    const POST_CHOICE_LIST_GROUP_KEY = 'post_choice_list';
    const POST_MEMBER_STATUS_KEY = "member_posts:aff:%s|status:%d|offset:%d|limit:%d";
    const POST_MEMBER_GROUP = "member_posts";
    const POST_DETAIL_GROUP_KEY = 'post_detail';
    const POST_DETAIL_KEY = 'post:detail:%s';
    const POST_MEMBER_FOLLOWED = "posts_member_follow:memberaff:%s|offset:%d|limit:%d";

    const POST_PEER_AFF_LIST_KEY = 'post:peer:aff:%s:%s:%s';// 对方的aff:offset:limit
    const POST_PEER_AFF_LIST_GROUP_KEY = 'post_peer_aff';

    const CK_POST_PEER_AFF_LIST_KEY = 'ck:post:peer:aff:list:key:%s:%s:%d:%d';
    const GP_POST_PEER_AFF_LIST_KEY = 'gp:post:peer:aff:list:key';
    const CN_POST_PEER_AFF_LIST_KEY = '他人中心帖子列表';

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

    public function member()
    {
        return $this->user();
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
        return self::with('user:aff,uid,nickname,thumb,aff,expired_at,vip_level,uuid,sexType,post_auth')
            ->where('status', self::STATUS_PASS)
            ->where('is_deleted', self::DELETED_NO)
            ->where('is_finished', self::FINISH_OK);
    }

    /**
     * @throws RedisException
     */
    public static function clearDetailCache($postId)
    {
        redis()->del(sprintf(self::POST_DETAIL_KEY, $postId));
    }


    /**
     * @throws RedisException
     */
    public static function getPostById($id)
    {
        return cached(sprintf(self::POST_DETAIL_KEY,$id))
            ->group(self::POST_DETAIL_GROUP_KEY)
            ->chinese('帖子详情')
            ->fetchPhp(function () use ($id){
                return self::queryBase()
                    ->with('topic:id,name')
                    ->with('medias:pid,media_url,cover,thumb_width,thumb_height,type,status')
                    ->find($id);
        });
    }

    // 帖子列表
    public static function listTopicPosts($cate, $topicId, $page, $limit)
    {
        $cacheKey = sprintf(\PostModel::POST_TOPIC_LIST_KEY, $cate, $topicId, $page, $limit);
        $group = self::POST_TOPIC_LIST_GROUP_KEY;
        return cached($cacheKey)
            ->group($group)
            ->chinese("社区帖子列表")
            ->fetchPhp(function () use ($cate, $topicId, $page, $limit) {
                 return PostModel::queryBase()
                     ->with('topic:id,name')
                     ->with('medias:pid,media_url,cover,thumb_width,thumb_height,type,status')
                     ->when($topicId,function ($q) use ($topicId){
                         return $q->where("topic_id",$topicId);
                     })
                    ->when($cate == 'choice', function ($q) {
                        return $q->where('is_best',\PostModel::BEST_OK);
                    })
                     ->when($cate == 'recommend', function ($q) {
                         return $q->orderByDesc('set_top')
                             ->orderByDesc('sort');
                     })
                    ->when($cate == 'new', function ($q) {
                        return $q->orderByDesc('refresh_at');
                    })
                     ->when($cate == 'video', function ($q) {
                         return $q->where('video_num', '>', 0)->orderByDesc('refresh_at');
                     })
                    ->orderByDesc('id')
                    ->forPage($page,$limit)
                    ->get()->map(function (\PostModel $item){
                        //列表不缓存内容字段
                         $item->content = '';
                         return $item;
                     });
            });
    }

    // 帖子列表
    public static function listMemberPosts($aff, $kwy, $page, $limit)
    {
        $kwy_key = substr(md5($kwy), 0, 8);
        $cacheKey = sprintf(self::CK_POST_PEER_AFF_LIST_KEY, $aff, $kwy_key, $page, $limit);
        return cached($cacheKey)
            ->group(self::GP_POST_PEER_AFF_LIST_KEY)
            ->chinese("他人中心列表")
            ->fetchPhp(function () use ($aff, $kwy, $page, $limit) {
                return PostModel::queryBase()
                    ->with('topic:id,name')
                    ->with('medias:pid,media_url,cover,thumb_width,thumb_height,type,status')
                    ->where('aff', $aff)
                    ->when($kwy, function ($q) use ($kwy) {
                        return $q->whereRaw("(title like '%{$kwy}%' or content like '%{$kwy}%')");
                    })
                    ->orderByDesc('set_top')
                    ->orderByDesc('refresh_at')
                    ->orderByDesc('id')
                    ->forPage($page,$limit)
                    ->get()->map(function (\PostModel $item){
                        //列表不缓存内容字段
                        $item->content = '';
                        return $item;
                    });
            });
    }

    // 帖子列表
    public static function incomeList($aff, $type, $page, $limit)
    {
        return PostModel::query()
            ->selectRaw('id,title,favorite_num,reward_amount,reward_num')
            ->with('medias:pid,media_url,cover,thumb_width,thumb_height,type,status')
            ->where('aff',$aff)
            ->where('status',self::STATUS_PASS)
            ->where('is_deleted',self::DELETED_NO)
            ->when($type == 'new', function ($q) {
                return $q->orderByDesc('id');
            })
            ->when($type == 'hot', function ($q) {
                return $q->orderByDesc('reward_num')
                    ->orderByDesc('id');
            })
            ->forPage($page,$limit)
            ->get();
    }

}
