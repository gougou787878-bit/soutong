<?php

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * class PornGameModel
 *
 * @property string $_id 资源ID
 * @property int $buy_coins 购买总金币
 * @property int $buy_fake 显示解锁量
 * @property int $buy_num 购买次数
 * @property int $category_id 分类
 * @property int $coins 金币
 * @property int $comment_count 评论数
 * @property string $created_at
 * @property string $desc 说明
 * @property int $id
 * @property string $intro 简介
 * @property int $is_recommend 是否推荐
 * @property int $is_hot 是否热门
 * @property int $like_count 喜欢数
 * @property string $name 标题
 * @property string $play_intro 游戏玩法
 * @property int $real_like_count 真实喜欢数
 * @property int $real_view_count 真实浏览数
 * @property string $refresh_at 刷新时间
 * @property string $remark 备注
 * @property int $score 评分
 * @property int $sort 排序
 * @property int $status 状态
 * @property string $tags 标签
 * @property string $thumb 封面
 * @property int $type 类型 0免费 1次数 2金币 3次数和金币
 * @property string $updated_at
 * @property int $view_count 浏览数
 * @property int $favorite_ct 收藏数
 * @property int $real_favorite 真实收藏数
 * @property string $content 下载地址及密码
 *
 *
 * @date 2024-04-01 15:50:31
 *
 * @mixin \Eloquent
 */
class PornGameModel extends EloquentModel
{
    protected $table = "porn_game";
    protected $primaryKey = 'id';
    protected $fillable = [
        '_id',
        'buy_coins',
        'buy_fake',
        'buy_num',
        'category_id',
        'coins',
        'comment_count',
        'created_at',
        'desc',
        'intro',
        'is_recommend',
        'is_hot',
        'like_count',
        'name',
        'play_intro',
        'real_like_count',
        'real_view_count',
        'refresh_at',
        'remark',
        'score',
        'sort',
        'status',
        'tags',
        'thumb',
        'type',
        'updated_at',
        'view_count',
        'favorite_ct',
        'content',
        'real_favorite',
    ];
    protected $guarded = 'id';
    public $timestamps = true;

    const STATUS_NO = 0;
    const STATUS_OK = 1;
    const STATUS_TIPS = [
        self::STATUS_NO   => '下架',
        self::STATUS_OK   => '上架'
    ];

    const TYPE_FREE = 0;
    const TYPE_COINS = 1;
    const TYPE_MIX = 2;
    const TYPE_TIPS = [
        self::TYPE_FREE   => '免费',
        self::TYPE_COINS   => '金币',
        self::TYPE_MIX   => 'VIP'
    ];

    const HOT_NO = 0;
    const HOT_OK = 1;
    const HOT_TIPS = [
        self::HOT_NO => '否',
        self::HOT_OK => '是',
    ];

    const MOBILE_TAGS = ['安卓游戏', 'IOS游戏'];

    const PORN_GAME_LIST_COLUMN = 'id, name, thumb, type, coins, tags, is_hot, like_count, view_count, buy_fake, comment_count, created_at, refresh_at, favorite_ct';
    const PORN_GAME_DETAIL_COLUMN = "id, name, thumb, type, coins, tags, is_hot, intro, play_intro, `desc`, like_count, view_count, buy_fake, comment_count, created_at, refresh_at, category_id, content, favorite_ct";

    public function medias(): HasMany
    {
        return $this->hasMany(PornMediaModel::class, 'pid', 'id');
    }

    const CK_PORN_GAME_DETAIL = 'ck:porn:game:detail:%d';
    const GP_PORN_GAME_DETAIL = 'ck:porn:game:detail';
    const CN_PORN_GAME_DETAIL = '黄游详情';

    const CK_PORN_GAME_LIST = 'ck:porn:game:list:%s:%s:%d:%d';
    const GP_PORN_GAME_LIST = 'gp:porn:game:list';
    const CN_PORN_GAME_LIST = '黄游列表';

    const CK_PORN_GAME_SEARCH_LIST = 'ck:porn:game:search:list:%s:%d:%d';
    const GP_PORN_GAME_SEARCH_LIST = 'gp:porn:game:search:list';
    const CN_PORN_GAME_SEARCH_LIST = '黄游搜索列表';

    const CK_PORN_GAME_NEXT = 'ck:porn:game:next:%s:%s';
    const GP_PORN_GAME_NEXT = 'gp:porn:game:next';
    const CN_PORN_GAME_NEXT = '黄游详情上下篇';

    const CK_PORN_GAME_DETAIL_RECOMMEND = 'ck:porn:game:detail:recommend:%s';
    const GP_PORN_GAME_DETAIL_RECOMMEND = 'gp:porn:game:detail:recommend';
    const CN_PORN_GAME_DETAIL_RECOMMEND = '黄游详情推荐';

    const CK_PORN_GAME_TAG_LIST = 'ck:porn:game:tag:list:%s:%s:%s:%s';
    const GP_PORN_GAME_TAG_LIST = 'gp:porn:game:tag:list';
    const CN_PORN_GAME_TAG_LIST = '黄游标签列表';

    const KEY_AFF_PORN_FAVORITE_SET = 'key:aff:porn:favorite:set:%d';

    protected $appends = [
        'is_pay',
        'is_like',
        'is_favorite',
    ];

    public function category(){
        return $this->hasOne(PornCategoryModel::class, 'id', 'category_id');
    }

    //假浏览数
    public function getViewCountAttribute()
    {
        $rating = $this->attributes['view_count'];
        if ($rating < 300000){
            $id = $this->attributes['id'];
            $rating = rand(300000, 1000000);
            bg_run(function () use ($id, $rating) {
                $like = intval($rating /  rand(6, 10));
                $favorite = intval($like / rand(6, 10));
                $data = [
                    'view_count' => $rating,
                    'like_count' => $like,
                    'favorite_ct' => $favorite
                ];
                self::where('id', $id)->update($data);
            });
        }
        return $rating;
    }

    public static function queryBase()
    {
        return self::selectRaw(self::PORN_GAME_LIST_COLUMN)->where('status', self::STATUS_OK);
    }

    public function getThumbAttribute()
    {
        return url_cover($this->attributes['thumb']);
    }

    /**
     * 是否有权限看
     * @return int
     */
    public function getIsPayAttribute()
    {
        $watchUser = self::$watchUser;
        if (empty($watchUser) || !isset($this->attributes['id'])) {
            return 0;
        }
        $aff = $watchUser->getAttribute('aff') ?? 0;
        $type = $this->attributes['type'];
        if ($type == self::TYPE_FREE){
            return 1;
        }

        $resourceType = PrivilegeModel::RESOURCE_TYPE_PORN_GAME;
        if(UsersProductPrivilegeModel::hasPrivilege(USER_PRIVILEGE ,$resourceType,PrivilegeModel::PRIVILEGE_TYPE_VIEW)){
            return 1;
        }

        if ($type == self::TYPE_COINS){
            //权限
            $key = sprintf(PornPayModel::PRON_GAME_BUY_SET_AFF, $aff);
            return redis()->sIsMember($key, $this->attributes['id']) ? 1 : 0;
        }

        return 0;
    }

    //是否点赞
    public function getIsLikeAttribute(){
        $watchUser = self::$watchUser;
        if (empty($watchUser) || !isset($this->attributes['id'])) {
            return 0;
        }

        $key = sprintf(PornLikeModel::PORN_GAME_LIKE_SET, $watchUser->aff);
        return redis()->sIsMember($key, $this->attributes['id']) ? 1 : 0;
    }

    //是否收藏
    public function getIsFavoriteAttribute()
    {
        $watchUser = self::$watchUser;
        if (empty($watchUser) || !isset($this->attributes['id'])) {
            return 0;
        }
        $key = sprintf(self::KEY_AFF_PORN_FAVORITE_SET, $watchUser->aff);
        return redis()->sIsMember($key, $this->attributes['id']) ? 1 : 0;
    }

    public static function decrByFavorite($id){
        self::where('id', $id)->where('real_like_count', '>', 0)->decrement('real_like_count');
    }

    public static function incrByFavorite($id){
        self::where('id', $id)->increment('real_like_count', 1, ['like_count' => DB::raw('like_count + 3')]);
    }

    public static function incrByView($id){
        self::where('id', $id)->increment('real_view_count', 1, ['view_count' => DB::raw('view_count + 5')]);
    }

    public static function incrementFavoriteNum($id)
    {
        self::where('id', $id)->increment('real_favorite', 1, ['favorite_ct' => DB::raw('favorite_ct + 5')]);
    }

    public static function decrementFavoriteNum($id)
    {
        self::where('id', $id)->where('real_favorite', '>', 0)->decrement('real_favorite');
    }

    public static function detail($id){
        return cached(sprintf(self::CK_PORN_GAME_DETAIL, $id))
            ->group(self::GP_PORN_GAME_DETAIL)
            ->fetchPhp(function () use ($id){
                return self::selectRaw(self::PORN_GAME_DETAIL_COLUMN)
                    ->with('medias')
                    ->where('id', $id)
                    ->first();
            });
    }

    public static function getRecommendPicBySeries($category_id, $limit){
        return self::queryBase()
            ->where("category_id", $category_id)
            ->orderByDesc('sort')
            ->orderByDesc('real_like_count')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public static function getRecommendPic($sort, $limit){
        return self::queryBase()
            ->when('sale' == $sort, function ($q){
                return $q->whereIn('type', [self::TYPE_COINS, self::TYPE_MIX])->orderByDesc('buy_num');
            })
            ->when('like' == $sort, function ($q){
                return $q->orderByDesc('real_like_count');
            })
            ->when('new' == $sort, function ($q){
                return $q->orderByDesc('refresh_at');
            })
            ->when('vip' == $sort, function ($q){
                return $q->where('type', self::TYPE_MIX)->orderByDesc('real_like_count');
            })
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public static function list($category_id, $sort, $page, $limit){
        $key = sprintf(self::CK_PORN_GAME_LIST, $category_id, $sort, $page, $limit);
        return cached($key)
            ->group(self::GP_PORN_GAME_LIST)
            ->chinese(self::CN_PORN_GAME_LIST)
            ->fetchPhp(function () use($category_id, $sort, $page, $limit){
                return self::queryBase()
                    ->when($category_id, function ($q) use ($category_id){
                        return $q->where("category_id", $category_id);
                    })
                    //随机
                    ->when('rand' == $sort, function ($q){
                        return $q->inRandomOrder();
                    })
                    //最新
                    ->when('new' == $sort, function ($q){
                        return $q->orderByDesc('refresh_at');
                    })
                    //畅销
                    ->when('sale' == $sort, function ($q){
                        return $q->orderByDesc('sort')
                            ->orderByDesc('buy_num');
                    })
                    //最多喜欢
                    ->when('like' == $sort, function ($q){
                        return $q->orderByDesc('real_like_count');
                    })
                    //最多收藏
                    ->when('favorite' == $sort, function ($q){
                        return $q->orderByDesc('real_favorite');
                    })
                    //推荐
                    ->when('recommend' == $sort, function ($q){
                        return $q->orderByDesc('is_recommend')
                            ->orderByDesc('sort')
                            ->orderByDesc('real_like_count');
                    })
                    //最热
                    ->when('hot' == $sort, function ($q){
                        return $q->orderByDesc('sort')
                            ->orderByDesc('real_view_count');
                    })
                    //手机
                    ->when('mobile' == $sort, function ($q){
//                        return $q->whereRaw("tags not like '%PC游戏%'")
//                            ->orderByDesc('real_view_count');
                        $param = '-PC游戏 >安卓游戏>IOS游戏';
                        //return $q->whereRaw("match(tags) against(? in boolean mode)", [implode(' ', self::MOBILE_TAGS)])
                        return $q->whereRaw("match(tags) against(? in boolean mode)", [$param])
                            ->orderByDesc('real_view_count');
                    })
                    //VIP
                    ->when('vip' == $sort, function ($q){
                        return $q->where('type', self::TYPE_MIX)->orderByDesc('real_like_count');
                    })
                    ->forPage($page, $limit)
                    ->get();
            });
    }

    // 搜索
    public static function listBySearch($word, $page, $limit)
    {
        $cacheKey = sprintf(self::CK_PORN_GAME_SEARCH_LIST, substr(md5($word), 0, 8), $page, $limit);
        return cached($cacheKey)
            ->group(self::GP_PORN_GAME_SEARCH_LIST)
            ->fetchPhp(function () use ($word, $page, $limit) {
                return self::queryBase()
                    ->where('name', 'like', '%' . $word . '%')
                    ->orderByDesc('real_view_count')
                    ->orderByDesc('id')
                    ->forpage($page,$limit)
                    ->get();
            });
    }

    // 搜索
    public static function listByTag($tag, $sort, $page, $limit)
    {
        $cacheKey = sprintf(self::CK_PORN_GAME_TAG_LIST, substr(md5($tag), 0, 8), $sort, $page, $limit);
        return cached($cacheKey)
            ->group(self::GP_PORN_GAME_TAG_LIST)
            ->fetchPhp(function () use ($tag, $sort, $page, $limit) {
                return self::queryBase()
                    ->whereRaw("match(tags) against(? in boolean mode)", [$tag])
                    //随机
                    ->when('rand' == $sort, function ($q){
                        return $q->inRandomOrder();
                    })
                    //最新
                    ->when('new' == $sort, function ($q){
                        return $q->orderByDesc('refresh_at');
                    })
                    //畅销
                    ->when('sale' == $sort, function ($q){
                        return $q->orderByDesc('buy_num');
                    })
                    //最热
                    ->when('hot' == $sort, function ($q){
                        return $q->orderByDesc('real_view_count');
                    })
                    ->orderByDesc('id')
                    ->forpage($page,$limit)
                    ->get();
            });
    }

    public static function next($id, $type){
        $key = sprintf(self::CK_PORN_GAME_NEXT, $id, $type);
        return cached($key)
            ->group(self::GP_PORN_GAME_NEXT)
            ->chinese(self::CN_PORN_GAME_NEXT)
            ->fetchPhp(function () use ($id, $type){
                return self::queryBase()
                    //上一篇
                    ->when($type == 1, function ($q) use ($id){
                        return $q->where('id', '<', $id)
                            ->orderByDesc('id');
                    })
                    //下一篇
                    ->when($type == 2, function ($q) use ($id){
                        return $q->where('id', '>', $id)
                            ->orderBy('id');
                    })
                    ->first();
            });
    }

    public static function detail_recommend($id, $category_id){
        $key = sprintf(self::CK_PORN_GAME_DETAIL_RECOMMEND, $id);
        return cached($key)
            ->group(self::GP_PORN_GAME_DETAIL_RECOMMEND)
            ->chinese(self::CN_PORN_GAME_DETAIL_RECOMMEND)
            ->fetchPhp(function () use ($id, $category_id){
                return self::queryBase()
                    ->where('id', '!=', $id)
                    ->where('category_id', $category_id)
                    ->orderByDesc('real_like_count')
                    ->limit(10)
                    ->get();
            });
    }

}
