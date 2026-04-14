<?php

use Illuminate\Database\Eloquent\Builder;

/**
 * class MvModel
 *
 * @property string $actors 演员
 * @property string $category 分类
 * @property int $coins 定价
 * @property int $vip_coins 会员购买价格，-1表示没有设置会员价格
 * @property string $gif_thumb 视频动图
 * @property int $gif_height 视频动图宽
 * @property int $gif_width 视频动图高
 * @property int $comment 评论数
 * @property string $cover_thumb 封面小图
 * @property int $created_at 创建时间
 * @property string $directors 导演
 * @property int $duration 时长，秒
 * @property int $id
 * @property int $is_free 是否限免 0 收费 1 限免
 * @property int $is_hide 0显示1隐藏
 * @property int $like 喜欢点击数
 * @property string $m3u8 影片资源1
 * @property string $full_m3u8 影片资源1
 * @property int $music_id 音乐id
 * @property int $onshelf_tm 影片上映时间
 * @property int $rating 总历史点击数
 * @property int $refresh_at 刷新时间
 * @property int $status 0未审核1审核通过
 * @property array|string $tags 影片标签
 * @property int $thumb_duration 精彩时长：秒
 * @property int $thumb_height 封面高
 * @property int $thumb_start_time 精彩片段开始时间
 * @property int $thumb_width 封面宽
 * @property string $title 影片标题
 * @property int $uid 用户UUID
 * @property string $v_ext 视频格式类型
 * @property string $via 来源
 * @property int $is_recommend 来源
 * @property int $is_feature 是否是精选
 * @property string $y_cover
 * @property string $y_cover_url
 * @property int $is_top
 * @property int $count_pay
 * @property int $topic_id 合集id
 * @property int $is_aw
 * @property int $filter_vip 金币视频是否过滤通卡功能
 * @property int $web_free web是否免费
 * @property int $construct_id 结构id
 * @property int $type 视频类型
 * @property int $is_18 是否18 0未满18 1 满足18
 * 
 * @property string $play_url
 * @property int $is_pay
 * @property int $collect_id 专题合集id
 *
 * @property MemberModel $user
 * @property UserTopicModel $user_topic
 *
 * @author xiongba
 * @date 2020-03-03 18:25:48
 *
 * @mixin \Eloquent
 */
class MvModel extends EloquentModel
{

    const COIN_DEFAULT = 88;//91撸av 金币视频同步后的默认价格 如果那边设置为0的话

    const REDIS_USER_TODAY_MV_LIST = 'user_today_list:'; // 用户当天看过的视频
    const REDIS_WATCH_COUNT = 'mv_watch_count'; // 视频总观看数
    const REDIS_USER_VIDEOS_ITEM = 'user_video_itemss:'; // 用户的视频列表
    const REDIS_USER_LIKE_VIDEOS_ITEM = 'user_like_video_item:'; // 用户点赞的视频列表

    const REDIS_USER_LIKE_TODAY_COUNT = 'stat_like_number'; // 点赞排行

    const STAT_UPLOAD_NUMBER = 'stat_upload_number'; // 用户每天上传视频总数

    const RECOMMEND_FEE_KEY = 'index:mv:recommend:fee';
    const RECOMMEND_FREE_KEY = 'index:mv:recommend:free';

    const REDIS_MV_DETAIL = 'mv:detail:%d';
    const REDIS_MV_DETAIL_GROUP = 'mv:detail:griup';

    const CK_TAG_SORT_LIST = 'ck:tag:sort:list:%s:%s:%s:%s:%s';
    const GP_TAG_SORT_LIST = 'gp:tag:sort:list';
    const CN_TAG_SORT_LIST = '标签视频列表';
    const RK_PLAYING = 'rk:playing:%s';

    const REDIS_KEY_RANK_HOTTEST_LIST_WEEKLY = 'mv:rank:hottest:list:weekly:';


    const REDIS_NAG_TAB_MV_KEY = 'redis:nag:tab:mv:key:%s:%s:%s:%s:%s';// 分类key
    const REDIS_NAG_TAB_MV_GROUP = 'redis:nag:tab:mv:group';//分类group
    const REDIS_NAG_TAB_MV_CN = '导航-视频列表';

    //正在看
    const RK_SEE_CONSTRUCT = 'rk:see:construct:%d';
    const RK_SEE_NAVIGATION = 'rk:see:navigation:%d';
    const RK_SEE_SHORT_MV = 'rk:see:short:mv';
    const RK_SEE_LONG_MV = 'rk:see:long:mv';
    //推荐
    const RK_RECOMMEND_CONSTRUCT = 'rk:recommend:construct:%s:%d';
    const RK_RECOMMEND_NAVIGATION = 'rk:recommend:navigation:%s:%d';
    const RK_RECOMMEND_SHORT_MV = 'rk:recommend:short:mv:%s';
    const RK_RECOMMEND_LONG_MV = 'rk:recommend:long:mv:%s';
    const RK_RECOMMEND_SET = 'rk:recommend:%s:%s';

    const REDIS_FIND_LIST_MV_KEY = 'redis:find:list:mv:key:%s:%s:%s:%s:%s';//发现
    const REDIS_FIND_LIST_MV_GP = 'redis:find:list:mv:group';
    const REDIS_FIND_LIST_MV_CN = '发现-金币/VIP列表';

    const CK_TAG_LIST_MV = 'redis:tag:list:mv:key:%s:%s:%s:%s:%s';
    const GP_TAG_LIST_MV_GP = 'redis:tag:list:mv:group';
    const CN_TAG_LIST_MV_CN = '标签视频列表';

    const CK_ALL_MV_LIST = 'ck:short:mv:list:%s:%s:%s:%s';
    const GP_ALL_MV_LIST = 'gp:short:mv:list';
    const CN_ALL_MV_LIST = '视频列表';

    //用户视频推荐列表
    const RECOMMEND_USER_MV_LIST = 'recommend.mv:%d';
    const REDIS_MV_MAX_ID = 'mv_max_id';
    const MV_RAND_LIMIT = 2000;
    const MV_BEGIN_ID = 1000;
    const RECOMMEND_SHORT_MV_ID_LIST_KEY = 'ck:recommend:mv:id:list';//推荐小视频池子
    const COINS_SHORT_MV_ID_LIST_KEY = 'ck:coins:mv:id:list';//金币小视频池子

    //搜索KEY
    const SEARCH_MV_LIST = 'search:mv:list:%s:%s:%s:%s';

    const STAT_UNREVIEWED = 0;
    const STAT_CALLBACK_DONE = 1;
    const STAT_REFUSE = 2;
    const STAT_CALLBACK_ING = 3;
    const STAT_REMOVE = 4;
    const STAT = [
        self::STAT_UNREVIEWED    => '未审核',
        self::STAT_CALLBACK_DONE => '回调完成',
        self::STAT_REFUSE        => '未通过',
        self::STAT_CALLBACK_ING  => '回调中',
        self::STAT_REMOVE        => '逻辑删除',
    ];
    const IS_HIDE_YES = 1;
    const IS_HIDE_NO = 0;
    const IS_HIDE = [
        self::IS_HIDE_YES => '隐藏',
        self::IS_HIDE_NO  => '显示',
    ];

    const IS_FREE_YES = 1;
    const IS_FREE_NO = 0;
    const IS_FREE = [
        self::IS_FREE_YES => 'VIP',
        self::IS_FREE_NO  => '金币',
    ];
    const IS_FEATURE_YES = 1;
    const IS_FEATURE_NO = 0;
    const IS_FEATURE = [
        self::IS_FEATURE_YES => '是',
        self::IS_FEATURE_NO  => '否',
    ];
    const RECOMMEND_YES = 1;
    const RECOMMEND_NO = 0;
    const RECOMMEND = [
        self::RECOMMEND_NO  => '否',
        self::RECOMMEND_YES => '是',
    ];

    const IS_TOP_YES = 1;
    const IS_TOP_NO = 0;
    const IS_TOP = [
        self::IS_TOP_NO  => '否',
        self::IS_TOP_YES => '是',
    ];
    const AW_YES = 1;
    const AW_NO = 0;
    const IS_AW_TIPS = [
        self::AW_NO  => '否',
        self::AW_YES => '是',
    ];
    const VIA_USER = 'user';
    const VIA_OFFICAL = 'own';
    const VIA_LUSIR = 'lu91';
    const VIA = [
        self::VIA_USER    => '用户上传',
        self::VIA_OFFICAL => '官方出品',
        self::VIA_LUSIR   => '91撸',
    ];

    const FILTER_VIP_NO = 0;
    const FILTER_VIP_YES = 1;
    const FILTER_VIP_TIPS = [
        self::FILTER_VIP_NO => '否',
        self::FILTER_VIP_YES => '是',
    ];

    const WEB_FREE_NO = 0;
    const WEB_FREE_YES = 1;
    const WEB_FREE_TIPS = [
        self::WEB_FREE_NO => '否',
        self::WEB_FREE_YES => '是',
    ];

    const TYPE_LONG = 0;
    const TYPE_SHORT = 1;
    const TYPE_TIPS = [
        self::TYPE_LONG => '长视频',
        self::TYPE_SHORT => '短视频',
    ];

    const IS_18_YES = 1;
    const IS_18_NO = 0;
    const IS_18 = [
        self::IS_18_NO => '未满18',
        self::IS_18_YES => '18+',
    ];

    protected $table = 'mv';

    protected $fillable = [
        'uid',
        'music_id',
        'coins',
        'title',
        'm3u8',
        'full_m3u8',
        'v_ext',
        'duration',
        'vip_coins',
        'gif_thumb',
        'gif_width',
        'gif_height',
        'cover_thumb',
        'thumb_width',
        'thumb_height',
        'directors',
        'actors',
        'category',
        'tags',
        'via',
        'onshelf_tm',
        'rating',
        'refresh_at',
        'is_free',
        'like',
        'is_recommend',
        'comment',
        'status',
        'thumb_start_time',
        'thumb_duration',
        'is_hide',
        'is_feature',
        'y_cover',
        'created_at',
        'is_top',
        'topic_id',
        'count_pay',
        'is_aw',
        'filter_vip',
        'web_free',
        'collect_id',
        'construct_id',
        'type',
        'is_18',
    ];

    protected $appends = [
        'y_cover_url',
        'tags_list',
        'cover_thumb_url',
        'gif_thumb_url',
        'created_str',
        'is_like',
        'duration_str',
        'is_pay'
    ];

    public function getTitleAttribute()
    {
        $title = $this->attributes['title'];
        return $title ? emojiDecode($title) : '';
    }

    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = emojiEncode($value);
    }

    /**
     * @param MemberModel|array $member
     *
     * @return string
     */
    public static function generateWatchKey($member)
    {
        return self::REDIS_USER_TODAY_MV_LIST . date('d') .'-'.$member['uid'];
    }

    public function user()
    {
        return $this->hasOne(MemberModel::class, 'uid', 'uid');
    }

    public function construct()
    {
        return $this->hasOne(ConstructModel::class, 'id', 'construct_id');
    }

    public function getTagsListAttribute()
    {
        if (!isset($this->attributes['tags'])) {
            return [];
        }
        return array_map('trim', explode(',', $this->attributes['tags']));
    }

    public function getYCoverUrlAttribute()
    {
        return url_cover($this->attributes['y_cover'] ?? '');
    }

    public function getCoverThumbUrlAttribute()
    {
        if(APP_TYPE_FLAG && IS_FAKE_CLIENT){
            return url_cover(FAKE_IMG);
        }
        return url_cover($this->attributes['cover_thumb'] ?? '');
    }

    public function getGifThumbUrlAttribute()
    {
        return url_cover($this->attributes['gif_thumb'] ?? '');
    }

    public function getCreatedStrAttribute()
    {
        $created = $this->attributes['created_at'] ?? 0;
        if(is_numeric($created)){
            return date('Y-m-d h:i:s',$created );
        }
        return $created;
    }

    public function getDurationStrAttribute()
    {
        return durationToString($this->attributes['duration'] ?? '');
    }

    public function getIsLikeAttribute()
    {
        $watchUser = self::$watchUser;
        if (empty($watchUser) || !isset($this->attributes['id'])) {
            return 0;
        }
        static $ids = null;
        if (null === $ids) {
            $ids = redis()->sMembers(\MemberModel::REDIS_USER_LIKING_LIST . $watchUser->uid);
        }
        if (in_array($this->attributes['id'], $ids)) {
            return 1;
        }
        return 0;
    }

    public function getIsPayAttribute()
    {
        if (isset($this->attributes['is_pay'])){
            return $this->attributes['is_pay'];
        }

        $watchUser = self::$watchUser;
        if (empty($watchUser) || !isset($this->attributes['id'])) {
            return 0;
        }
        if ($watchUser->uid == $this->attributes['uid']) {
            return 1;
        }
        //VIP视频
        if ($this->attributes['coins']<=0){
            if ($this->attributes['is_aw'] == self::AW_NO){
                $resourceType = PrivilegeModel::RESOURCE_TYPE_NORMAL_VIP_VIDEO;
            }else{
                $resourceType = PrivilegeModel::RESOURCE_TYPE_AW_VIP_VIDEO;
            }
            if (UsersProductPrivilegeModel::hasPrivilege(USER_PRIVILEGE, $resourceType, PrivilegeModel::PRIVILEGE_TYPE_VIEW)) {
                return 1;
            }
        }else{
            if ($this->attributes['filter_vip'] == self::FILTER_VIP_NO){
                if ($this->attributes['is_aw'] == self::AW_NO){
                    $resourceType = PrivilegeModel::RESOURCE_TYPE_NORMAL_COINS_VIDEO;
                }else{
                    $resourceType = PrivilegeModel::RESOURCE_TYPE_AW_COINS_VIDEO;
                }
                if (UsersProductPrivilegeModel::hasPrivilege(USER_PRIVILEGE, $resourceType, PrivilegeModel::PRIVILEGE_TYPE_VIEW)) {
                    return 1;
                }
            }
            static $ids = null;
            if (null === $ids) {
                $ids = MvPayModel::getVidArrByUser($watchUser->uid);
            }
            if ($ids && in_array($this->attributes['id'], $ids)) {
                return 1;
            }
            //购买的专题 判断
            if (!isset($this->attributes['collect_id'])){
                return 0;
            }
            $topicIds = TopicPayModel::getIdsArrByUser($watchUser->uid);
            if($topicIds && in_array($this->attributes['collect_id'],$topicIds)){
                return 1;
            }
        }

        return 0;
    }

    /**
     * @return Builder
     * @author xiongba
     */
    public static function queryWithUser()
    {
        return self::queryBase()->with('user');
    }

    public static function queryBase()
    {
        return self::where('status', '=', self::STAT_CALLBACK_DONE)
            ->where('is_hide', '=', self::IS_HIDE_NO);
    }

    public static function queryFee()
    {
        return self::queryWithUser()->where('coins', '>', 0);
    }

    public static function queryRecommend()
    {
        return self::queryWithUser()->where('is_recommend', MvModel::RECOMMEND_YES);
    }

    public static function queryFeeRecommend()
    {
        return self::queryRecommend()->where('coins', '>', 0)->where('is_aw', self::AW_NO);
    }


    public static function virtualByForDelele()
    {
        $model = self::make();
        $model->title = "视频已被删除";
        $model->is_null = true;
        $model->uid = 0;
        $model->id = 0;
        $model->cover_thumb = '';
        return $model;
    }

    public function emitChange($release)
    {
        if ($release) {
            redis()->sRem('mv:feature:fee', $this->id);
            redis()->sRem('mv:feature:free', $this->id);
            redis()->zRem(\service\VideoScoreService::VIDEO_SCORE_KEY, $this->id);
            VideoScoreModel::where('vid', $this->id)->delete();
            MemberModel::where('uid', $this->uid)->decrement('videos_count');
            $uidAry = UserLikeModel::where('mv_id', $this->id)->pluck('uid');
            MemberModel::whereIn('uid', $uidAry)->decrement('likes_count');
            UserLikeModel::where('mv_id', $this->id)->delete();
            return;
        }

        if ($this->status != self::STAT_CALLBACK_DONE || $this->is_hide == self::IS_HIDE_YES) {
            redis()->zRem(\service\VideoScoreService::VIDEO_SCORE_KEY, $this->id);
        }

        if ($this->status != self::STAT_CALLBACK_DONE || $this->is_feature != self::IS_FEATURE_YES || $this->is_hide == self::IS_HIDE_YES) {
            redis()->sRem('mv:feature:fee', $this->id);
            redis()->sRem('mv:feature:free', $this->id);
        } else {
            if ($this->is_feature == self::IS_FEATURE_YES) {
                if ($this->coins > 0) {
                    redis()->sAdd('mv:feature:fee', $this->id);
                } else {
                    redis()->sAdd('mv:feature:free', $this->id);
                }
            }
        }
    }

    /**
     * 砖石视频价格设置配置
     * @return array
     */
    static function getGoldMVPriceConf()
    {

        return [
            [
                'title' => '免费',
                'price' => 0,
                'key'   => 'price_free'
            ]
            ,
            [
                'title' => '10金币',
                'price' => 10,
                'key'   => 'price_ten'
            ]
            ,
            [
                'title' => '20金币',
                'price' => 20,
                'key'   => 'price_two'
            ]
            ,
            [
                'title' => '30金币',
                'price' => 30,
                'key'   => 'price_three'
            ]
            ,
            [
                'title' => '40金币',
                'price' => 40,
                'key'   => 'price_four'
            ]
            ,
            [
                'title' => '50金币',
                'price' => 50,
                'key'   => 'price_five'
            ]
        ];
    }


    public function coinsAfterDiscount(MemberModel $member)
    {
        //获取价格
        $total = abs(intval($this->coins));
        //计算折扣配置
        $discount = []; //$this->getDiscountConfig();
        //会员价格优先
        if ($this->vip_coins != -1 && $member->expired_at > TIMESTAMP) {
            $total = abs(intval($this->vip_coins));
        } elseif ($discount && is_array($discount)) {
            //获取用户买过多少次视频
            $buyCount = \MvPayModel::buyCount($member->uid);
            //直接使用下标获取配置的折折扣后的价格
            if (isset($discount[$buyCount])) {
                $total = abs(ceil($total * $discount[$buyCount]));
            }
        }
        return $total;//应付金币
    }

    public function user_topic(){
        return $this->hasOne(UserTopicModel::class , 'id' , 'topic_id');
    }
    public function topic(){
        return $this->hasOne(TopicModel::class , 'id' , 'collect_id');
    }

    /**
     * 检查用户有没有发布收费视频的权限
     * @param $uid
     * @return array
     */
    static function checkMemberToReleaseGoldMV($uid = 0)
    {
        /** @var MemberModel $member */
        $member = MemberModel::where('uid', $uid)->first();
        $blackList = MvBackUserModel::getBackUserList();
        if ($uid && $blackList && in_array($uid, $blackList)) {
            $result = [
                'can_release'     => 0,
                'can_release_fee' => 0,
                'msg_tips'=>'上传黑名单用户',
            ];
            return $result;
        }
        /*if($member->vip_level<MemberModel::VIP_LEVEL_JIKA){
            $result = [
                'can_release'     => 0,
                'can_release_fee' => 0,
                'msg_tips'=>'季卡会员才能上传~',
            ];
            return $result;
        }*/
        //ip上传 限制取消 先
        if (false && MvUploadIpInfoModel::checkIPNum() > 10) {
            errLog("触发今日ip额度10上传限制~ uid:{$uid}");
            $result = [
                'can_release'     => 0,
                'can_release_fee' => 0,
                'msg_tips'        => '触发今日额度上传限制~',
            ];
            return $result;
        }

        $result = [
            'can_release'     => 1,
            'can_release_fee' => 1,
            'msg_tips'=>'上传更多精彩视频',
        ];
        return $result;
        if (!$uid) {
            return $result;
        }
        $w = [];
        $toaday = strtotime(date('Y-m-d 00:00:00'));
        $w[] = ['uid', '=', $uid];
        $w[] = ['created_at', '>=', $toaday];
        $w[] = ['is_hide', '=', self::IS_HIDE_NO];
        $dataMv = MvModel::where($w)->select(['coins'])->get();

        $dataSubMit = MvSubmitModel::where(
            [
                ['uid', '=', $uid],
                ['created_at', '>=', $toaday],
            ]
        )->whereIn('status', [MvSubmitModel::STAT_UNREVIEWED, MvSubmitModel::STAT_CALLBACK_ING])
            ->select('coins')
            ->get();
        $data = collect($dataMv)->merge($dataSubMit);
        $hasCount = $data->count();
        if ($hasCount < 2) {//前2个视频强制免费,
            $result['can_release_fee'] = 0;//是否可以发布付费视频
            return $result;
        }

        /**1、上传数量限制：
         * · 制片人可上传10部/日
         * · 普通用户可上传3部/日
         * 超过数量提示*/
        $default = 20;
        if ($member->auth_status) {
            $default = 20;
        }
        if ($hasCount > $default) {
            $result['can_release'] = 0;
            $result['msg_tips'] = (20 == $default) ? '已达到当日上传数量20部/日上限，成为制片人可提高上限~' : '已达到当日上传数量20部/日上限，明日再来噢～';
            return $result;
        }

        if ($data) {
            $data = $data->toArray();
            $data = array_column($data, 'coins');
            $ct_fee = 0;//上传的付费视频
            $ct_free = 0;//上传的免费视频
            foreach ($data as $_isFee) {
                $_isFee > 0 ? $ct_fee++ : $ct_free++;
            }
            $hasCount = $hasCount + 1;//1 为虚拟数 当前待上传
            if ($ct_fee / $hasCount <= 0.5) { //比例调成50
                $result['can_release_fee'] = 1;//是否可以发布付费视频
            } else {
                $result['can_release_fee'] = 0;//是否可以发布付费视频
            }
        }
        return $result;
    }

    static function virtualData()
    {
        return MvModel::make([
            'id'               => -1,
            'uid'              => 100,
            'music_id'         => 0,
            'coins'            => 0,
            'title'            => '该视频涉嫌违规，已下架',
            'm3u8'             => '',
            'full_m3u8'        => '',
            'v_ext'            => '',
            'duration'         => 0,
            'vip_coins'        => 0,
            'gif_thumb'        => '',
            'gif_width'        => '',
            'gif_height'       => '',
            'cover_thumb'      => '',
            'thumb_width'      => 0,
            'thumb_height'     => 0,
            'directors'        => '',
            'actors'           => '',
            'category'         => '',
            'tags'             => '',
            'via'              => '',
            'onshelf_tm'       => '',
            'rating'           => 0,
            'refresh_at'       => time(),
            'is_free'          => 1,
            'like'             => 0,
            'is_recommend'     => 0,
            'comment'          => 0,
            'status'           => self::STAT_CALLBACK_DONE,
            'thumb_start_time' => 0,
            'thumb_duration'   => 0,
            'is_hide'          => self::IS_HIDE_NO,
            'is_feature'       => self::IS_FEATURE_NO,
            'y_cover'          => '',
            'created_at'       => time(),
            'is_top'           => self::IS_TOP_NO,
            'count_pay'        => 0
        ]);
    }

    public static function mvInfo($mv_id){
        $key = sprintf(self::REDIS_MV_DETAIL,$mv_id);
        return cached($key)
            ->group(self::REDIS_MV_DETAIL_GROUP)
            ->fetchPhp(function () use ($mv_id){
                return self::find($mv_id);
            });
    }

    public static function tagList($tag, $type, $sort, $page, $limit){
        $ck = sprintf(self::CK_TAG_SORT_LIST, $tag, $type, $sort, $page, $limit);
        return cached($ck)
            ->group(self::GP_TAG_SORT_LIST)
            ->chinese(self::CN_TAG_SORT_LIST)
            ->fetchPhp(function () use ($tag, $type, $sort, $page, $limit){
                $tag = str_replace(',',' ',$tag);
                $tag = trim($tag);
                return self::queryBase()
                    ->with('user:uid,nickname,thumb,aff,expired_at,vip_level,uuid,sexType')
                    ->where('is_aw', MvModel::AW_NO)
                    ->where('type', self::TYPE_LONG)
                    ->when($page <=2,function ($q){
                        return $q->where('is_18',self::IS_18_YES);
                    })
                    ->whereRaw("match(tags) against(? in boolean mode)", [$tag])
                    ->when($type == 1, function ($q){
                        return $q->where('coins', 0);
                    })
                    ->when($type == 2, function ($q){
                        return $q->where('coins', '>', 0);
                    })
                    ->when($sort == 'hottest', function ($q){
                        return $q->orderByDesc('like');//最热
                    })
                    ->orderByDesc('id')
                    ->forPage($page, $limit)
                    ->get();
            });
    }

    protected static function listPlayingIds($tab_id, $offset, $limit): array
    {
        $rankKey = sprintf(self::RK_PLAYING, $tab_id);
        $ids = redis()->zRevRangeByScore(
            $rankKey, '+inf', '-inf',
            [
                'withscores' => TRUE,
                'limit'      => [$offset, $limit]
            ]
        );
        return array_keys($ids);
    }
    public static function getStartOfWeek(): string
    {
        return \Carbon\Carbon::today()->startOfWeek()->format('Y-m-d');
    }
    private static function getRankKey($tab_id, $date): string
    {
        return self::REDIS_KEY_RANK_HOTTEST_LIST_WEEKLY . $tab_id . ':' . $date;
    }
    public static function add2Rank($tab_id, $mvId, $startOfWeek)
    {
        $rankKey = self::getRankKey($tab_id, $startOfWeek);
        if (!redis()->exists($rankKey)) {
            redis()->zIncrBy($rankKey, 1, $mvId);
            redis()->expire($rankKey, 691200);
        } else {
            redis()->zIncrBy($rankKey, 1, $mvId);
        }
    }

    protected static function listHotIds($tab_id, $offset, $limit)
    {
        $startOfWeek = self::getStartOfWeek();
        $rankKey = self::getRankKey($tab_id, $startOfWeek);
        $ids = redis()->zRevRangeByScore(
            $rankKey, '+inf', '-inf',
            [
                'withscores' => TRUE,
                'limit'      => [$offset, $limit]
            ]
        );
        return array_keys($ids);
    }
    public static function listSee($tab_id, $page, $limit,$flag=0)
    {
        $offset = ($page - 1) * $limit;
        $result =  cached(sprintf('see:mv:list:%s:%d:%d',$tab_id,$offset,$limit))
            ->fetchPhp(function () use ($tab_id, $offset, $limit) {
                $ids = self::listPlayingIds($tab_id, $offset, $limit);
                $rs = self::queryWithUser()
                    ->with('user_topic')
                    ->whereIn('id', $ids)
                    ->get();
                return array_keep_idx($rs, $ids);
            },30);
        $list =  (new \service\MvService())->v2format($result, request()->getMember());

        if($flag){
            return $list;
        }
        $last_idx = '0';
        $last = end($list);
        if (!empty($list)) {
            $last_idx = $last['id'] ?? '0';
        }
        return [
            'list'     => $list,
            'last_idx' => $last_idx,
        ];
    }
    public static function listRank($tab_id,$page, $limit,$flag=0)
    {
        $offset = ($page - 1) * $limit;

        $result =  cached(sprintf('hottest:mv:list:%s:%d:%d',$tab_id,$offset,$limit))
            ->fetchPhp(function () use ($tab_id, $offset, $limit) {
                $ids = self::listHotIds($tab_id, $offset, $limit);
                $rs = self::queryWithUser()
                    ->with('user_topic')
                    ->whereIn('id', $ids)
                    ->get();
                return array_keep_idx($rs, $ids);
            });
        $list = (new \service\MvService())->v2format($result, request()->getMember());
        if($flag){
            return $list;
        }
        $last_idx = '0';
        $last = end($list);
        if (!empty($list)) {
            $last_idx = $last['id'] ?? '0';
        }
        return [
            'list'     => $list,
            'last_idx' => $last_idx,
        ];
    }

    public static function getHomeMvDataByTag($construct_id, $is_aw, $sort, $page, $limit)
    {
        return self::queryBase()
            ->when($construct_id, function ($q) use ($construct_id) {
                $q->where('construct_id', $construct_id);
            })
            ->where('is_aw', $is_aw)
            ->when($page <=2,function ($q){
                return $q->where('is_18',self::IS_18_YES);
            })
            ->orderByDesc('is_recommend')
            ->when($sort == 'like', function ($q) {
                $q->orderByDesc('like');
            })
            ->when($sort == 'hot', function ($q) {
                $q->orderByDesc('rating');
            })
            ->when($sort == 'sale', function ($q) {
                $q->orderByDesc('count_pay');
            })
            ->when($sort == 'new', function ($q) {
                $q->orderByDesc('refresh_at');
            })
            ->orderByDesc('id')
            ->forPage($page, $limit)
            ->get();
    }


    public static function randMvs(MemberModel $member, $c_key, $construct_arr, $is_aw, $page, $limit)
    {
        $randKey = 'rand:mv:list:v1:' . $member->aff . ':' . $c_key . ":" . $is_aw;
        $cacheKey = "list:nag:" . $c_key . '-t' . $is_aw . '-p' . $page . '-a' . $member->aff;
        $setKey = 'rand:mv:set:keys' . $member->aff . ':' . $c_key. ':' . $is_aw;
        redis()->sAdd($setKey, $cacheKey);

        if (!redis()->exists($randKey)) {
            $ids = self::queryBase()
                ->selectRaw('id')
                ->whereIn('construct_id', $construct_arr)
                ->where("is_aw",$is_aw)
                ->when($page <=2,function ($q){
                    return $q->where('is_18',self::IS_18_YES);
                })
                ->orderBy('like')
                ->limit(300)
                ->get()
                ->pluck('id')
                ->toArray();

            shuffle($ids);
            redis()->sAddArray($randKey, $ids);
            redis()->expire($randKey, 3600);
            $setKeys = redis()->sMembers($setKey);
            foreach ($setKeys as $k) {
                redis()->del($k);
            }
        }
        $ids = redis()->sMembers($randKey);
        $ids = collect($ids)->forPage($page, $limit);

        return cached($cacheKey)
            ->group('list_rand_mv')
            ->fetchPhp(function () use ($ids) {
                return MvModel::queryBase()
                    ->whereIn("id", $ids)
                    ->get();
            }, rand(1800, 3600));
    }

    public static function getMvDataByTags($c_key, $construct_arr, $is_aw, $sort, $page, $limit){
        $cacheKey = sprintf(self::REDIS_NAG_TAB_MV_KEY,$c_key,$is_aw,$sort,$page,$limit);
        return cached($cacheKey)
            ->group(self::REDIS_NAG_TAB_MV_GROUP)
            ->chinese(self::REDIS_NAG_TAB_MV_CN)
            ->fetchPhp(function () use($construct_arr, $is_aw, $sort, $page, $limit){
                return self::queryBase()
                    ->when(is_array($construct_arr),function ($q) use ($construct_arr){
                        $q->whereIn('construct_id', $construct_arr);
                    }, function ($q) use ($construct_arr){
                        if ($construct_arr){
                            return $q->where('construct_id', $construct_arr);
                        }
                        return $q;
                    })
                    ->when($page <=2,function ($q){
                        return $q->where('is_18',self::IS_18_YES);
                    })
                    ->where("is_aw",$is_aw)
                    //热门
                    ->when($sort=="hot",function ($q){
                        $q->orderByDesc('rating');
                    })
                    //大家都喜欢
                    ->when($sort=="like",function ($q){
                        $q->orderByDesc('like');
                    })
                    //最新
                    ->when($sort=="new",function ($q){
                        $q->orderByDesc('refresh_at');
                    })
                    //畅销
                    ->when($sort == "sale", function ($q) {
                        $q->where('coins', '>', 0)->orderByDesc('count_pay');
                    })
                    ->orderByDesc('id')
                    ->forPage($page,$limit)
                    ->get();
            });
    }

    public static function add2SeeRank($mv_id, $construct_id)
    {
        //发现-最多观看
        //self::addWeekRank(self::WEEK_VIEW_TYPE, $mv_id);

        if (!$construct_id) {
            return;
        }
        //正在看
        $seeConstruct = sprintf(MvModel::RK_SEE_CONSTRUCT, $construct_id);
        redis()->zAdd($seeConstruct, time(), $mv_id);
        if (redis()->sCard($seeConstruct) > 1000){
            redis()->zRemRangeByRank($seeConstruct, 1000, -1);
        }
        //推荐
        $month = date('Ym');
        $recommendConstruct = sprintf(self::RK_RECOMMEND_CONSTRUCT, $month, $construct_id);
        if (!redis()->exists($recommendConstruct)) {
            redis()->zIncrBy($recommendConstruct, 1, $mv_id);
        }
        redis()->zIncrBy($recommendConstruct, 1, $mv_id);
        $construct = ConstructModel::findById($construct_id);
        if (is_object($construct) && isset($construct->nag_id) && $construct->nag_id){
            $nav = NavigationModel::findById($construct->nag_id);
            //下面是列表
            if (is_object($nav) && isset($nav->bot_style) && $nav->bot_style == NavigationModel::BOT_STYLE_TWO){
                //导航正在看
                $seeNavigation = sprintf(MvModel::RK_SEE_NAVIGATION, $construct->nag_id);
                redis()->zAdd($seeNavigation, time(), $mv_id);
                if (redis()->sCard($seeNavigation) > 1000){
                    redis()->zRemRangeByRank($seeNavigation, 1000, -1);
                }
                //导航推荐
                $recommendNavigation = sprintf(self::RK_RECOMMEND_NAVIGATION, $month, $construct->nag_id);
                if (redis()->ttl($recommendNavigation) == -1) {
                    redis()->expire($recommendNavigation, 2764800);
                }
                redis()->zIncrBy($recommendNavigation, 1, $mv_id);
            }
        }
    }

    //短视频正在看
    public static function add2ShortMvSeeRank($mv_id)
    {
        //正在看
        $key = self::RK_SEE_SHORT_MV;
        redis()->zAdd($key, time(), $mv_id);
        if (redis()->sCard($key) > 1000){
            redis()->zRemRangeByRank($key, 1000, -1);
        }
        //推荐
        $month = date('Ym');
        $recommendConstruct = sprintf(self::RK_RECOMMEND_SHORT_MV, $month);
        if (redis()->ttl($recommendConstruct) == -1) {
            redis()->expire($recommendConstruct, 2764800);
        }
        redis()->zIncrBy($recommendConstruct, 1, $mv_id);
    }

    //长视频正在看
    public static function add2LongMvSeeRank($mv_id)
    {
        //排行榜
        /** @var MvModel $mv */
        $mv = self::findById($mv_id);
        if (empty($mv)){
            return;
        }
        MvTotalModel::addCacheData($mv->id, $mv->is_aw, MvTotalModel::FIELD_VIEW, $mv->type, 1);

        //正在看
        $key = self::RK_SEE_LONG_MV;
        redis()->zAdd($key, time(), $mv_id);
        if (redis()->sCard($key) > 1000){
            redis()->zRemRangeByRank($key, 1000, -1);
        }
        //推荐
        $month = date('Ym');
        $recommendConstruct = sprintf(self::RK_RECOMMEND_LONG_MV, $month);
        if (redis()->ttl($recommendConstruct) == -1) {
            redis()->expire($recommendConstruct, 2764800);
        }
        redis()->zIncrBy($recommendConstruct, 1, $mv_id);
    }

    public static function addWeekRank($type, $mv_id)
    {
        //发现-最多观看/最多点赞
        $week = date('YW');
        $viewKey = sprintf(MvModel::RK_RECOMMEND_SET, $type, $week);
        if (!redis()->exists($viewKey)) {
            redis()->expire($viewKey, 604900);
        }
        redis()->zIncrBy($viewKey, 1, $mv_id);
    }

    const SEE_LIST = 'see:list:%s:%s:%s:%s';
    const RECOMMEND_LIST = 'recommend:list:%s:%s:%s:%s';
    const NAV_TYPE = 1;
    const CONSTRUCT_TYPE = 2;

    protected static function listPlayingIdsNew($rk_id, $type, $offset, $limit)
    {
        $rankKey = sprintf(self::RK_SEE_CONSTRUCT, $rk_id);
        if ($type == self::NAV_TYPE){
            $rankKey = sprintf(self::RK_SEE_NAVIGATION, $rk_id);
        }
        $ids = redis()->zRevRangeByScore(
            $rankKey, '+inf', '-inf',
            [
                'withscores' => TRUE,
                'limit'      => [$offset, $limit]
            ]
        );
        return array_keys($ids);
    }

    //type 1 导航 2结构
    public static function listSeeNew($rk_id, $type, $page, $limit)
    {
        $rankKey = sprintf(self::SEE_LIST, $rk_id, $type, $page, $limit);
        return cached($rankKey)
            ->fetchPhp(function () use ($rk_id, $type, $page, $limit){
                $offset = ($page - 1) * $limit;
                $ids = self::listPlayingIdsNew($rk_id, $type, $offset, $limit);
                $rs = self::queryBase()
                    ->when($page <=2,function ($q){
                        return $q->where('is_18',self::IS_18_YES);
                    })
                    ->whereIn('id', $ids)
                    ->get();
                return array_keep_idx($rs, $ids);
            }, 60);
    }

    protected static function ListRecommendIds($rk_id, $type, $offset, $limit)
    {
        $date = date('Ym');
        $rankKey = sprintf(self::RK_RECOMMEND_CONSTRUCT, $date, $rk_id);
        if ($type == self::NAV_TYPE){
            $rankKey = sprintf(self::RK_RECOMMEND_NAVIGATION, $date, $rk_id);
        }
        $ids = redis()->zRevRangeByScore(
            $rankKey, '+inf', '-inf',
            [
                'withscores' => TRUE,
                'limit'      => [$offset, $limit]
            ]
        );
        return array_keys($ids);
    }

    //type 1 导航 2结构
    public static function listRecommend($rk_id, $type, $page, $limit)
    {
        $rankKey = sprintf(self::RECOMMEND_LIST, $rk_id, $type, $page, $limit);
        return cached($rankKey)
            ->fetchPhp(function () use ($rk_id, $type, $page, $limit){
                $offset = ($page - 1) * $limit;
                $ids = self::ListRecommendIds($rk_id, $type, $offset, $limit);
                $rs = self::queryBase()
                    ->when($page <=2,function ($q){
                        return $q->where('is_18',self::IS_18_YES);
                    })
                    ->whereIn('id', $ids)
                    ->get();
                return array_keep_idx($rs, $ids);
            }, 60);
    }

    public static function randConstructMvs(MemberModel $member, $c_key, $construct_id, $is_aw, $page, $limit)
    {
        $randKey = 'rand:mv:list:v1:' . $member->aff . ':' . $c_key . ":" . $is_aw;
        $cacheKey = "list:nag:" . $c_key . '-t' . $is_aw . '-p' . $page . '-a' . $member->aff;
        $setKey = 'rand:mv:set:keys' . $member->aff . ':' . $c_key. ':' . $is_aw;
        redis()->sAdd($setKey, $cacheKey);

        if (!redis()->exists($randKey)) {
            $ids = self::queryBase()
                ->selectRaw('id')
                ->where('construct_id', $construct_id)
                ->where("is_aw",$is_aw)
                ->when($page <=2,function ($q){
                    return $q->where('is_18',self::IS_18_YES);
                })
                ->orderBy('like')
                ->limit(300)
                ->get()
                ->pluck('id')
                ->toArray();

            shuffle($ids);
            redis()->sAddArray($randKey, $ids);
            redis()->expire($randKey, 3600);
            $setKeys = redis()->sMembers($setKey);
            foreach ($setKeys as $k) {
                redis()->del($k);
            }
        }
        $ids = redis()->sMembers($randKey);
        $ids = collect($ids)->forPage($page, $limit);

        return cached($cacheKey)
            ->group('list_rand_mv')
            ->fetchPhp(function () use ($ids) {
                return MvModel::queryBase()
                    ->whereIn("id", $ids)
                    ->get();
            }, rand(1800, 3600));
    }

    public static function randFindMvs(MemberModel $member, $type, $is_aw, $page, $limit)
    {
        $randKey = 'rand:mv:find:list:v1:' . $member->aff . ':' . $type . ":" . $is_aw;
        $cacheKey = "list:find:" . $type . '-t' . $is_aw . '-p' . $page . '-a' . $member->aff;
        $setKey = 'rand:find:mv:set:keys' . $member->aff . ':' . $type. ':' . $is_aw;
        redis()->sAdd($setKey, $cacheKey);

        if (!redis()->exists($randKey)) {
            $ids = self::queryBase()
                ->selectRaw('id')
                ->where("is_aw",$is_aw)
                ->where('type', self::TYPE_LONG)
                ->when($page <=2,function ($q){
                    return $q->where('is_18',self::IS_18_YES);
                })
                ->when($type == ConstructModel::FIND_TYPE_VIP, function ($q){
                    return $q->where("coins", 0);
                })
                ->when($type == ConstructModel::FIND_TYPE_COINS, function ($q){
                    return $q->where("coins", '>', 0);
                })
                ->orderBy('like')
                ->limit(300)
                ->get()
                ->pluck('id')
                ->toArray();

            shuffle($ids);
            redis()->sAddArray($randKey, $ids);
            redis()->expire($randKey, 3600);
            $setKeys = redis()->sMembers($setKey);
            foreach ($setKeys as $k) {
                redis()->del($k);
            }
        }
        $ids = redis()->sMembers($randKey);
        $ids = collect($ids)->forPage($page, $limit);

        return cached($cacheKey)
            ->group('list_rand_mv')
            ->fetchPhp(function () use ($ids) {
                return MvModel::queryBase()
                    ->whereIn("id", $ids)
                    ->get();
            }, rand(1800, 3600));
    }

    public static function listFindMvs($type, $is_aw, $sort, $page, $limit){
        $cacheKey = sprintf(self::REDIS_FIND_LIST_MV_KEY, $type, $is_aw, $sort, $page, $limit);
        return cached($cacheKey)
            ->group(self::REDIS_FIND_LIST_MV_GP)
            ->chinese(self::REDIS_FIND_LIST_MV_CN)
            ->fetchPhp(function () use($type, $is_aw, $sort, $page, $limit){
                return self::queryBase()
                    ->where("is_aw",$is_aw)
                    ->where('type', self::TYPE_LONG)
                    ->when($page <=2,function ($q){
                        return $q->where('is_18',self::IS_18_YES);
                    })
                    ->when($type == ConstructModel::FIND_TYPE_COINS, function ($q){
                        $q->where('coins', '>', 0);
                    })
                    ->when($type == ConstructModel::FIND_TYPE_VIP, function ($q){
                        $q->where('coins', 0);
                    })
                    //热门
                    ->when($sort=="hot",function ($q){
                        $q->orderByDesc('like');
                    })
                    //最新
                    ->when($sort=="new",function ($q){
                        $q->orderByDesc('refresh_at');
                    })
                    ->orderByDesc('id')
                    ->forPage($page,$limit)
                    ->get();
            });
    }

    const RECOMMEND_WEEK_LIST = 'recommend:list:%s:%s:%s';
    const WEEK_VIEW_TYPE = 'view';
    const WEEK_LIKE_TYPE = 'like';
    protected static function ListRecommendWeekIds($type, $offset, $limit)
    {
        $date = date('YW');
        $rankKey = sprintf(self::RK_RECOMMEND_SET, $type, $date);
        $ids = redis()->zRevRangeByScore(
            $rankKey, '+inf', '-inf',
            [
                'withscores' => TRUE,
                'limit'      => [$offset, $limit]
            ]
        );
        return array_keys($ids);
    }

    public static function listRecommendWeek($type, $page, $limit)
    {
        $rankKey = sprintf(self::RECOMMEND_WEEK_LIST, $type, $page, $limit);
        return cached($rankKey)
            ->fetchPhp(function () use ($type, $page, $limit){
                $offset = ($page - 1) * $limit;
                $ids = self::ListRecommendWeekIds($type, $offset, $limit);
                $rs = self::queryBase()
                    ->when($page <=2,function ($q){
                        return $q->where('is_18',self::IS_18_YES);
                    })
                    ->whereIn('id', $ids)
                    ->get();
                return array_keep_idx($rs, $ids);
            });
    }

    public static function randTagMvs(MemberModel $member, $tag, $show_aw, $page, $limit)
    {
        $c_key = substr(md5($tag), 0, 8);
        $randKey = 'rand:mv:tag:list:v1:' . $member->aff . ':' . $c_key . ":" . $show_aw;
        $cacheKey = "list:nag:" . $c_key . '-t' . $show_aw . '-p' . $page . '-a' . $member->aff;
        $setKey = 'rand:mv:set:keys' . $member->aff . ':' . $c_key. ':' . $show_aw;
        redis()->sAdd($setKey, $cacheKey);

        if (!redis()->exists($randKey)) {
            $ids = self::queryBase()
                ->selectRaw('id')
                ->when($page <=2,function ($q){
                    return $q->where('is_18',self::IS_18_YES);
                })
                ->whereRaw("match(tags) against(? in boolean mode)", [$tag])
                ->when($show_aw == 'no', function ($q){
                    return $q->where('is_aw', MvModel::AW_NO);
                })
                ->where('type', self::TYPE_LONG)
                ->orderBy('like')
                ->limit(300)
                ->get()
                ->pluck('id')
                ->toArray();

            shuffle($ids);
            redis()->sAddArray($randKey, $ids);
            redis()->expire($randKey, 3600);
            $setKeys = redis()->sMembers($setKey);
            foreach ($setKeys as $k) {
                redis()->del($k);
            }
        }
        $ids = redis()->sMembers($randKey);
        $ids = collect($ids)->forPage($page, $limit);

        return cached($cacheKey)
            ->group('list_rand_mv')
            ->fetchPhp(function () use ($ids) {
                return MvModel::queryBase()
                    ->whereIn("id", $ids)
                    ->get();
            }, rand(1800, 3600));
    }

    public static function tagMvList($tag, $sort, $show_aw, $page, $limit){
        $c_key = substr(md5($tag), 0, 8);
        $cacheKey = sprintf(self::CK_TAG_LIST_MV, $c_key, $sort, $show_aw, $page, $limit);
        return cached($cacheKey)
            ->group(self::GP_TAG_LIST_MV_GP)
            ->chinese(self::CN_TAG_LIST_MV_CN)
            ->fetchPhp(function () use ($tag, $sort, $show_aw, $page, $limit){
                return self::queryBase()
                    ->whereRaw("match(tags) against(? in boolean mode)", [$tag])
                    ->when($show_aw == 'no', function ($q){
                        return $q->where('is_aw', MvModel::AW_NO);
                    })
                    ->where('type', self::TYPE_LONG)
                    ->when($page <=2,function ($q){
                        return $q->where('is_18',self::IS_18_YES);
                    })
                    ->when($sort == 'new', function ($q){
                        return $q->orderByDesc('refresh_at');
                    })
                    ->when($sort == 'hot', function ($q){
                        return $q->orderByDesc('rating');
                    })
                    ->when($sort == 'sale', function ($q) {
                        $q->orderByDesc('count_pay');
                    })
                    ->when($sort == 'recommend', function ($q) {
                        $q->orderByDesc('like');
                    })
                    ->orderByDesc('id')
                    ->forPage($page, $limit)
                    ->get();
            });
    }

    /**
     * @param $id
     * @return MvModel
     */
    public static function findById($id){
        return cached(sprintf(MvModel::REDIS_MV_DETAIL,$id))
            ->group(MvModel::REDIS_MV_DETAIL_GROUP)
            ->chinese('视频详情')
            ->fetchPhp(function () use ($id){
                return \MvModel::where('id', $id)
                    ->with('user_topic')
                    ->with('user:uid,nickname,thumb,aff,expired_at,vip_level,uuid,sexType')
                    ->first();
            });
    }

    public static function getFollowMvs($uid, $up_ids, $page, $limit){
        $cacheKey = sprintf('redis:follow:recommend:mv:%s:%s:%s', $uid, $page, $limit);
        return cached($cacheKey)
            ->group('redis:follow:recommend:mv')
            ->chinese('推荐关注/视频列表')
            ->clearCached()
            ->fetchPhp(function () use($up_ids, $page, $limit){
                return self::queryBase()
                    ->whereIn('uid',$up_ids)
                    ->where("is_aw", self::AW_NO)
                    ->where('type', self::TYPE_SHORT)
                    ->when($page <=2,function ($q){
                        return $q->where('is_18',self::IS_18_YES);
                    })
                    ->orderByDesc('id')
                    ->forPage($page, $limit)
                    ->get();
            },60);
    }

    //取用户前10条记录
    public static function getMvListByUid($uid){
        return cached('recommend:short:mv:' . $uid)
            ->fetchPhp(function () use ($uid){
                return MvModel::queryBase()
                    ->where('uid', $uid)
                    ->where('is_aw', self::AW_NO)
                    ->when($page <=2,function ($q){
                        return $q->where('is_18',self::IS_18_YES);
                    })
                    ->where('type', MvModel::TYPE_SHORT)
                    ->orderByDesc('like')
                    ->limit(10)
                    ->get();
            });
    }

    public static function randShortMvs($page, $limit)
    {
        $randKey = 'rand:short:mv:list:v1';
        if (!redis()->exists($randKey)) {
            $ids = self::queryBase()
                ->selectRaw('id')
                ->where("is_aw", self::AW_NO)
                ->when($page <=2,function ($q){
                    return $q->where('is_18',self::IS_18_YES);
                })
                ->orderBy('like')
                ->limit(1000)
                ->get()
                ->pluck('id')
                ->toArray();

            shuffle($ids);
            redis()->sAddArray($randKey, $ids);
            redis()->expire($randKey, 1800);
        }
        $ids = redis()->sMembers($randKey);
        $ids = collect($ids)->forPage($page, $limit);

        return MvModel::queryBase()
            ->whereIn("id", $ids)
            ->get();
    }

    const SEE_SHORT_MV_LIST = 'see:short:mv:list:%s:%s';
    const RECOMMEND_SHORT_MV_LIST = 'recommend:list:%s:%s';

    protected static function listPlayingIdsShortMv($offset, $limit)
    {
        $rankKey = self::RK_SEE_SHORT_MV;
        $ids = redis()->zRevRangeByScore(
            $rankKey, '+inf', '-inf',
            [
                'withscores' => TRUE,
                'limit'      => [$offset, $limit]
            ]
        );
        return array_keys($ids);
    }

    public static function listSeeShortMv($page, $limit)
    {
        $rankKey = sprintf(self::SEE_SHORT_MV_LIST ,$page, $limit);
        return cached($rankKey)
            ->fetchPhp(function () use ($page, $limit){
                $offset = ($page - 1) * $limit;
                $ids = self::listPlayingIdsShortMv($offset, $limit);
                $rs = self::queryBase()
                    ->when($page <=2,function ($q){
                        return $q->where('is_18',self::IS_18_YES);
                    })
                    ->whereIn('id', $ids)
                    ->get();
                return array_keep_idx($rs, $ids);
            }, 60);
    }

    protected static function ListRecommendShortMvIds($offset, $limit)
    {
        $date = date('Ym');
        $rankKey = sprintf(self::RK_RECOMMEND_SHORT_MV, $date);
        $ids = redis()->zRevRangeByScore(
            $rankKey, '+inf', '-inf',
            [
                'withscores' => TRUE,
                'limit'      => [$offset, $limit]
            ]
        );
        return array_keys($ids);
    }

    public static function listRecommendShortMv($page, $limit)
    {
        $rankKey = sprintf(self::RECOMMEND_SHORT_MV_LIST, $page, $limit);
        return cached($rankKey)
            ->fetchPhp(function () use ($page, $limit){
                $offset = ($page - 1) * $limit;
                $ids = self::ListRecommendShortMvIds($offset, $limit);
                $rs = self::queryBase()
                    ->when($page <=2,function ($q){
                        return $q->where('is_18',self::IS_18_YES);
                    })
                    ->whereIn('id', $ids)
                    ->get();
                return array_keep_idx($rs, $ids);
            }, 60);
    }

    const SEE_LONG_MV_LIST = 'see:short:mv:list:%s:%s';
    const RECOMMEND_LONG_MV_LIST = 'recommend:list:%s:%s';

    protected static function listPlayingIdsLongMv($offset, $limit)
    {
        $rankKey = self::RK_SEE_LONG_MV;
        $ids = redis()->zRevRangeByScore(
            $rankKey, '+inf', '-inf',
            [
                'withscores' => TRUE,
                'limit'      => [$offset, $limit]
            ]
        );
        return array_keys($ids);
    }

    public static function listSeeLongMv($page, $limit)
    {
        $rankKey = sprintf(self::SEE_LONG_MV_LIST ,$page, $limit);
        return cached($rankKey)
            ->fetchPhp(function () use ($page, $limit){
                $offset = ($page - 1) * $limit;
                $ids = self::listPlayingIdsLongMv($offset, $limit);
                $rs = self::queryBase()
                    ->when($page <=2,function ($q){
                        return $q->where('is_18',self::IS_18_YES);
                    })
                    ->whereIn('id', $ids)
                    ->get();
                return array_keep_idx($rs, $ids);
            }, 60);
    }

    protected static function ListRecommendLongMvIds($offset, $limit)
    {
        $date = date('Ym');
        $rankKey = sprintf(self::RK_RECOMMEND_LONG_MV, $date);
        $ids = redis()->zRevRangeByScore(
            $rankKey, '+inf', '-inf',
            [
                'withscores' => TRUE,
                'limit'      => [$offset, $limit]
            ]
        );
        return array_keys($ids);
    }

    public static function listRecommendLongMv($page, $limit)
    {
        $rankKey = sprintf(self::RECOMMEND_LONG_MV_LIST, $page, $limit);
        return cached($rankKey)
            ->fetchPhp(function () use ($page, $limit){
                $offset = ($page - 1) * $limit;
                $ids = self::ListRecommendLongMvIds($offset, $limit);
                $rs = self::queryBase()
                    ->when($page <=2,function ($q){
                        return $q->where('is_18',self::IS_18_YES);
                    })
                    ->whereIn('id', $ids)
                    ->get();
                return array_keep_idx($rs, $ids);
            }, 60);
    }

    public static function getAllMvList($type, $sort, $page, $limit){
        $cacheKey = sprintf(self::CK_ALL_MV_LIST, $type, $sort, $page, $limit);
        return cached($cacheKey)
            ->group(self::GP_ALL_MV_LIST)
            ->chinese(self::CN_ALL_MV_LIST)
            ->fetchPhp(function () use($type, $sort, $page, $limit){
                return self::queryBase()
                    ->where("is_aw", self::AW_NO)
                    ->where("type", $type)
                    ->when($page <=2,function ($q){
                        return $q->where('is_18',self::IS_18_YES);
                    })
                    //热门
                    ->when($sort=="hot",function ($q){
                        $q->orderByDesc('rating');
                    })
                    //大家都喜欢
                    ->when($sort=="like",function ($q){
                        $q->orderByDesc('like');
                    })
                    //最新
                    ->when($sort=="new",function ($q){
                        $q->orderByDesc('refresh_at');
                    })
                    //畅销
                    ->when($sort == "sale", function ($q) {
                        $q->where('coins', '>', 0)->orderByDesc('count_pay');
                    })
                    ->orderByDesc('id')
                    ->forPage($page,$limit)
                    ->get();
            });
    }

    public static function randLongMvs(MemberModel $member, $is_aw, $page, $limit)
    {
        $randKey = 'rand:long:mv:list:v1:' . $member->aff . ":" . $is_aw;
        $cacheKey = "list:long:t" . $is_aw . '-p' . $page . '-a' . $member->aff;
        $setKey = 'rand:long:mv:set:keys' . $member->aff . ':' . $is_aw;
        redis()->sAdd($setKey, $cacheKey);

        if (!redis()->exists($randKey)) {
            $ids = self::queryBase()
                ->selectRaw('id')
                ->where("is_aw",$is_aw)
                ->when($page <=2,function ($q){
                    return $q->where('is_18',self::IS_18_YES);
                })
                ->where('type', self::TYPE_LONG)
                ->orderBy('like')
                ->limit(500)
                ->get()
                ->pluck('id')
                ->toArray();

            shuffle($ids);
            redis()->sAddArray($randKey, $ids);
            redis()->expire($randKey, 3600);
            $setKeys = redis()->sMembers($setKey);
            foreach ($setKeys as $k) {
                redis()->del($k);
            }
        }
        $ids = redis()->sMembers($randKey);
        $ids = collect($ids)->forPage($page, $limit);

        return cached($cacheKey)
            ->group('list_rand_mv')
            ->fetchPhp(function () use ($ids) {
                return MvModel::queryBase()
                    ->whereIn("id", $ids)
                    ->get();
            }, rand(1800, 3600));
    }

}