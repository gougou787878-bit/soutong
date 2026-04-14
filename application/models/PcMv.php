<?php

use Illuminate\Database\Eloquent\Builder;

/**
 * class PcMvModel
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
 *
 * @property string $play_url
 * @property int $is_pay
 *
 * @property MemberModel $user
 * @property UserTopicModel $user_topic
 *
 * @date 2020-03-03 18:25:48
 *
 * @mixin \Eloquent
 */
class PcMvModel extends EloquentModel
{
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
        self::IS_FREE_YES => '免费',
        self::IS_FREE_NO  => '收费',
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

    const CK_PC_TAB_MV = 'ck:pc:tab:mv:%s:%s:%s:%s';
    const GP_PC_TAB_MV = 'gp:pc:tab:mv';
    const CN_PC_TAB_MV = 'PC_视频列表';
    const CK_PC_TAB_MV_TOTAL = 'ck:pc:tab:mv:total:%s:%s';
    const GP_PC_TAB_MV_TOTAL = 'gp:pc:tab:mv:total';
    const CN_PC_TAB_MV_TOTAL = 'PC_视频总数';

    const CK_PC_TAB_REC_MV = 'ck:pc:tab:rec:mv:%s:%s:%s';
    const GP_PC_TAB_REC_MV = 'gp:pc:tab:rec:mv';
    const CN_PC_TAB_REC_MV = 'PC_视频推荐';

    const CK_PC_MV_DETAIL = 'ck:pc:mv:detail:%s';
    const GP_PC_MV_DETAIL = 'gp:pc:mv:detail';
    const CN_PC_MV_DETAIL = 'PC_视频详情';

    const CK_PC_RECOMMEND_MV = 'ck:pc:recommend:mv:%s';
    const GP_PC_RECOMMEND_MV = 'ck:pc:recommend:mv';
    const CN_PC_RECOMMEND_MV = 'PC_推荐视频';

    const CK_PC_SEARCH_MV = 'ck:pc:search:mv:%s:%s:%s:%s';
    const GP_PC_SEARCH_MV = 'ck:pc:search:mv';
    const CN_PC_SEARCH_MV = 'PC_搜索视频';

    const CK_MV_PREV = 'ck:pc:mv:prev:%s';
    const GP_MV_PREV = 'gp:pc:mv:prev';
    const CN_MV_PREV = 'PC_视频上一部';

    const CK_MV_NEXT = 'ck:pc:mv:next:%s';
    const GP_MV_NEXT = 'gp:pc:mv:next';
    const CN_MV_NEXT = 'PC_视频下一部';

    const SELECT_FIELDS = 'id,uid,tags,coins,full_m3u8,filter_vip,m3u8,cover_thumb,title,duration,thumb_width,thumb_height,rating,is_free,`like`,comment,is_aw,created_at,web_free';

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
        'web_free'
    ];

    protected $appends = [
        'tags_list',
        'cover_thumb_url',
        'created_str',
        'duration_str',
        'is_pay',
        'is_favorite'
    ];

    protected $hidden = [
        'full_m3u8',
        'm3u8',
        'created_at',
        'filter_vip',
        'cover_thumb'
    ];

    public function user()
    {
        return $this->hasOne(MemberModel::class, 'uid', 'uid');
    }

    public function getTagsListAttribute()
    {
        if (!isset($this->attributes['tags'])) {
            return [];
        }
        return array_map('trim', explode(',', $this->attributes['tags']));
    }

    public function getCoverThumbUrlAttribute()
    {
        return url_cover($this->attributes['cover_thumb'] ?? '');
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

    public function getIsPayAttribute()
    {
        //web端是否免费
        if (isset($this->attributes['web_free']) && $this->attributes['web_free'] == self::WEB_FREE_YES){
            return 1;
        }

        if (empty($this->watchUser) || !isset($this->attributes['id'])) {
            return 0;
        }

        if ($this->watchUser->getAttribute('uid') == $this->attributes['uid']) {
            return 1;
        }

        $is_vip = $this->watchUser->is_vip;
        $vip_level = $this->watchUser->vip_level;
        if ($this->attributes['coins']<=0){
            //暗网VIP视频
            if ($this->attributes['is_aw'] == self::AW_YES){
                if (in_array($vip_level,[MemberModel::VIP_LEVEL_AW_MON,MemberModel::VIP_LEVEL_AW_YEAR])){
                    return 1;
                }
            }else{
                //明网VIP视频
                if ($is_vip){
                    return 1;
                }
            }
        }else{
            //金币视频
            if ($this->attributes['filter_vip'] == self::FILTER_VIP_NO && FreeMemberModel::isFreeMember($this->watchUser->uid)){
                return 1;
            }
        }

        //是否购买
        static $ids = null;
        if (null === $ids) {
            $ids = MvPayModel::getVidArrByUser($this->watchUser['uid']);
        }
        if (in_array($this->attributes['id'], $ids)) {
            return 1;
        }

        return 0;
    }

    public function getIsFavoriteAttribute()
    {
        if (empty($this->watchUser) || !isset($this->attributes['id'])) {
            return 0;
        }

        $exists = UserLikeModel::where('mv_id',$this->attributes['id'])->where('uid',$this->watchUser->uid)->exists();
        if ($exists){
            return 1;
        }

        return 0;
    }

    /**
     * @return Builder
     * @author xiongba
     */
    public static function queryWithUser()
    {
        return self::queryBase()->with('user:uid,nickname,thumb,vip_level,sexType,expired_at,uuid,aff');
    }

    public static function queryBase()
    {
        return self::selectRaw(self::SELECT_FIELDS)
            ->where('status', '=', self::STAT_CALLBACK_DONE)
            ->where('is_hide', '=', self::IS_HIDE_NO);
    }

    public function user_topic(){
        return $this->hasOne(UserTopicModel::class , 'id' , 'topic_id');
    }

    public static function getRecommendData($sort, $limit){
        $ids = setting('home:recommend', '');
        $ids = array_map('intval', explode(',', $ids));
        $ids = array_filter($ids);
        $ids = array_slice($ids, 0, $limit);
        $ck = sprintf(self::CK_PC_TAB_MV,0,$sort,1,$limit);
        $list = cached($ck)
            ->group(self::GP_PC_TAB_MV)
            ->chinese(self::CN_PC_TAB_MV)
            ->fetchPhp(function () use ($ids,$limit){
                $data = PcMvModel::queryWithUser()
                    ->whereIn('id', $ids)
                    ->limit($limit)
                    ->get();
                if ($data) {
                    //重新排序
                    $data = array_sort_by_idx($data, $ids, 'id');
                }

                return $data;
            });
        $ct =  self::getTotal(0, '', $sort);
        // 这个需要分页
        return collect([
            'list'       => $list,
            'cur_page'   => 1,
            'total_page' => ceil($ct / $limit),
            'limit'      => $limit,
            'count'      => ceil($ct / $limit)
        ]);
    }

    public static function listMvs($tab_id, $tags, $sort, $page, $limit)
    {
        $ck = sprintf(self::CK_PC_TAB_MV,$tab_id,$sort,$page,$limit);
        $list = cached($ck)->group(self::GP_PC_TAB_MV)
            ->chinese(self::CN_PC_TAB_MV)
            ->fetchPhp(function () use ($tags,$sort,$page,$limit){
                $spage = $sort == 'recommend' ? $page - 1 : $page;
                return self::queryWithUser()
                    ->when($tags,function ($q) use ($tags){
                        return $q->whereRaw("match(tags) against(? in boolean mode)", [$tags]);
                    })
                    ->where('is_aw',PcMvModel::AW_NO)
//                    ->where('coins',0)
                    ->when($sort == 'recommend',function ($q){
                        return $q->orderByDesc('rating');
                    })
                    ->when($sort == 'new',function ($q){
                        return $q->orderByDesc('refresh_at');
                    })
                    ->when($sort == 'hot',function ($q){
                        return $q->where('created_at', '>=', strtotime('-30 days'))
                            ->orderByDesc('like');
                    })
                    ->forPage($spage,$limit)
                    ->get();
            });

        //总数
        $ct =  self::getTotal($tab_id, $tags, $sort);

        // 这个需要分页
        return collect([
            'list'       => $list,
            'cur_page'   => $page,
            'total_page' => ceil($ct / $limit),
            'limit'      => $limit,
            'count'      => ceil($ct / $limit)
        ]);
    }

    public static function getTotal($tab_id, $tags, $sort){
        $ck = sprintf(self::CK_PC_TAB_MV_TOTAL,$tab_id,$sort);
        return cached($ck)
            ->group(self::GP_PC_TAB_MV_TOTAL)
            ->chinese(self::CN_PC_TAB_MV_TOTAL)
            ->fetchJson(function () use ($tags,$sort) {
                return self::queryBase()
                    ->when($tags, function ($q) use ($tags) {
                        return $q->whereRaw("match(tags) against(? in boolean mode)", [$tags]);
                    })
                    ->where('is_aw', PcMvModel::AW_NO)
//                    ->where('coins', 0)
                    ->when($sort == 'hot', function ($q) {
                        return $q->where('created_at', '>=', strtotime('-30 days'));
                    })
                    ->count('id');
            });
    }


    public static function listRecommend($tab_id, $page, $limit)
    {
        $ck = sprintf(self::CK_PC_TAB_REC_MV, $tab_id, $page, $limit);
        $list = cached($ck)
            ->group(self::GP_PC_TAB_REC_MV)
            ->chinese(self::CN_PC_TAB_REC_MV)
            ->fetchPhp(function () use ($tab_id, $page, $limit){
                return PcMvRecommendModel::query()
                    ->with('mv')
                    ->where('tab_id', $tab_id)
                    ->orderByDesc('sort')
                    ->orderByDesc('updated_at')
                    ->orderByDesc('id')
                    ->forPage($page, $limit)
                    ->get()
                    ->pluck('mv')
                    ->filter()->values();
            });

        //总数
        $ct =  self::getRecTotal($tab_id);

        // 这个需要分页
        return collect([
            'list'       => $list,
            'cur_page'   => $page,
            'total_page' => ceil($ct / $limit),
            'limit'      => $limit,
            'count'      => ceil($ct / $limit)
        ]);
    }

    public static function getRecTotal($tab_id){
        $ck = sprintf(self::CK_PC_TAB_MV_TOTAL,$tab_id, 'recommend-new');
        return cached($ck)
            ->group(self::GP_PC_TAB_MV_TOTAL)
            ->chinese(self::CN_PC_TAB_MV_TOTAL)
            ->fetchJson(function () use ($tab_id) {
                return PcMvRecommendModel::query()
                    ->where('tab_id', $tab_id)
                    ->count('id');
            });
    }

    public static function getDetail($mvId)
    {
        $cacheKey = sprintf(self::CK_PC_MV_DETAIL, $mvId);
        return cached($cacheKey)
            ->group(self::GP_PC_MV_DETAIL)
            ->chinese(self::CN_PC_MV_DETAIL)
            ->fetchPhp(function () use ($mvId) {
                return self::queryWithUser()
                    ->where('id', $mvId)
                    ->first();
            });
    }

    public static function listRecommendMvs($mvId)
    {
        $cacheKey = sprintf(self::CK_PC_RECOMMEND_MV, $mvId);
        return cached($cacheKey)
            ->group(self::GP_PC_RECOMMEND_MV)
            ->chinese(self::CN_PC_RECOMMEND_MV)
            ->fetchPhp(function () use ($mvId) {
                return self::queryBase()
                    ->where('id', '>', $mvId + 10)
                    ->limit(2)
                    ->get();
            });
    }

    public static function listSearch($tags, $word, $page, $limit)
    {
        $cacheKey = sprintf(self::CK_PC_SEARCH_MV, substr(md5($tags),0,8), substr(md5($word),-1,8), $page, $limit);
        return cached($cacheKey)
            ->group(self::GP_PC_SEARCH_MV)
            ->chinese(self::CN_PC_SEARCH_MV)
            ->fetchPhp(function () use ($tags, $word, $page, $limit) {
                return self::queryWithUser()
                    //板内搜索
                    ->when($tags,function ($q) use ($tags, $word){
                        return $q->whereRaw("match(tags) against(? in boolean mode)", [$tags])
                            ->whereRaw("title like ?", ['%' . $word . '%']);
                    })
                    //全局搜索
                    ->when(!$tags,function ($q) use ($word){
                        return $q->whereRaw("match(tags) against(? in boolean mode)", [$word])
                            ->orWhereRaw("title like ?", ['%' . $word . '%']);
                    })
                    ->where('is_aw',self::AW_NO)
                    ->orderByDesc('id')
                    ->forPage($page, $limit)
                    ->get();
            }, rand(1800, 3600));
    }

    public static function prevMv($id)
    {
        $cacheKey = sprintf(self::CK_MV_PREV, $id);
        return cached($cacheKey)
            ->group(self::GP_MV_PREV)
            ->chinese(self::CN_MV_PREV)
            ->fetchPhp(function () use ($id) {
                return self::select(['id', 'title'])
                    ->where('status', '=', self::STAT_CALLBACK_DONE)
                    ->where('is_hide', '=', self::IS_HIDE_NO)
                    ->where('id', '<', $id)
                    ->orderByDesc('id')
                    ->first();
            });
    }

    public static function nextMv($id)
    {
        $cacheKey = sprintf(self::CK_MV_NEXT, $id);
        return cached($cacheKey)
            ->group(self::GP_MV_NEXT)
            ->chinese(self::CN_MV_NEXT)
            ->fetchPhp(function () use ($id) {
                return self::select(['id', 'title'])
                    ->where('status', '=', self::STAT_CALLBACK_DONE)
                    ->where('is_hide', '=', self::IS_HIDE_NO)
                    ->where('id', '>', $id)
                    ->orderBy('id')
                    ->first();
            });
    }

}