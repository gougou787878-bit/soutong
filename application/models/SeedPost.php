<?php

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $p_id 原始记录ID
 * @property int $type 类型
 * @property int $coins 金币数
 * @property int $payed_ct 购买次数
 * @property int $payed_coins 购买金币数
 * @property string $title 标题
 * @property int $topic_id 话题ID
 * @property string $content 内容
 * @property string $secret 密码
 * @property string $link 下载链接
 * @property int $photo_ct 图片数
 * @property int $video_ct 视频数
 * @property int $set_top 置精
 * @property int $is_finished 资源是否完成
 * @property int $sort 排序
 * @property int $view_ct 浏览数
 * @property int $comment_ct 评论数
 * @property int $fake_view_ct 浏览数
 * @property int $like_ct 点赞数
 * @property int $fake_like_ct 假点赞数
 * @property int $status 状态
 * @property string $created_at 创建时间
 * @property string $updated_at 修改时间
 * @property int $favorite_count 显示收藏数
 * @property int $rec_sort 3个月内点赞/收藏
 * @property int $hot_sort 本月浏览量
 * @property string $extract_code 提取码
 * @mixin \Eloquent
 */
class SeedPostModel extends EloquentModel
{
    protected $table = 'seed_post';
    protected $primaryKey = 'id';
    protected $fillable = [
        'p_id',
        'type',
        'coins',
        'payed_ct',
        'payed_coins',
        'title',
        'topic_id',
        'content',
        'secret',
        'link',
        'photo_ct',
        'video_ct',
        'set_top',
        'is_finished',
        'sort',
        'view_ct',
        'like_ct',
        'favorite_ct',
        'comment_ct',
        'fake_view_ct',
        'fake_like_ct',
        'status',
        'created_at',
        'updated_at',
        'favorite_count',
        'rec_sort',
        'hot_sort',
        'extract_code',
    ];

    const FINISHED_NO = 0;
    const FINISHED_OK = 1;
    const FINISHED_TIPS = [
        self::FINISHED_NO => '未完成',
        self::FINISHED_OK => '已完成',
    ];

    const STATUS_OFF = 0;
    const STATUS_ON = 1;
    const STATUS_TIPS = [
        self::STATUS_OFF => '隐藏',
        self::STATUS_ON => '显示',
    ];

    const TYPE_FREE = 0;
    const TYPE_VIP = 1;
    const TYPE_COIN = 2;
    const TYPE_TIPS = [
        self::TYPE_FREE => '免费',
        self::TYPE_VIP => 'vip',
        self::TYPE_COIN => '金币',
    ];

    const CK_SEED_POST_LIST = 'ck:seed:post:list:%s:%s:%s';
    const GP_SEED_POST_LIST = 'gp:seed:post:list';
    const CN_SEED_POST_LIST = '种子首页';

    const CK_SEED_POST_DETAIL = 'ck:seed:post:detail:%s';
    const GP_SEED_POST_DETAIL = 'gp:seed:post:detail';
    const CN_SEED_POST_DETAIL = '种子详情';

    const CK_SEED_POST_SEARCH_LIST = 'ck:seed:post:search:list:v1:%s:%s:%s';
    const GP_SEED_POST_SEARCH_LIST = 'gp:seed:post:search:list';
    const CN_SEED_POST_SEARCH_LIST = '种子搜索';

    const KEY_AFF_SEED_FAVORITE_SET = 'key:aff:seed:favorite:set:%d';

    protected $appends = [
        'like_num',
        'comment_num',
        'view_num',
        'is_pay',
        'is_like',
        'is_favorite'
    ];

    public function getLikeNumAttribute()
    {
        return (int)($this->attributes['like_ct'] ?? 0) + (int)($this->attributes['fake_like_ct'] ?? 0);
    }

    public function getFavoriteCtAttribute()
    {
        return (int)($this->attributes['favorite_ct'] ?? 0) + (int)($this->attributes['favorite_count'] ?? 0);
    }

    public function getViewNumAttribute()
    {
        $rating = $this->attributes['fake_view_ct'];
        if ($rating < 300000){
            $id = $this->attributes['id'];
            $rating = rand(300000, 1000000);
            bg_run(function () use ($id, $rating) {
                $like = intval($rating / rand(6, 10));
                $favorite = intval($like /  rand(6, 10));
                $data = [
                    'fake_view_ct' => $rating,
                    'fake_like_ct' => $like,
                    'favorite_count' => $favorite
                ];
                self::where('id', $id)->update($data);
            });
        }
        return (int)$rating + (int)($this->attributes['fake_view_ct'] ?? 0);
    }

    public function getCommentNumAttribute()
    {
        return (int)($this->attributes['comment_ct'] ?? 0);
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
            $ary = SeedLikeModel::list_ids(SeedLikeModel::TYPE_POST, $watchUser->aff);
        }
        return in_array($id, $ary) ? 1 : 0;
    }

    public function getIsFavoriteAttribute()
    {
        $watchUser = self::$watchUser;
        if (!$watchUser) {
            return 0;
        }
        $id = (int)($this->attributes['id'] ?? 0);
        $key = sprintf(self::KEY_AFF_SEED_FAVORITE_SET, $watchUser->aff);
        $rs = redis()->sIsMember($key, $id);
        return $rs ? 1 : 0;
    }

    /**
     * @throws RedisException
     */
    public function getIsPayAttribute()
    {
        $watchUser = self::$watchUser;
        if (is_null($watchUser) || !$this->getAttributeValue('id')) {
            return 0;
        }

        $type = $this->getAttributeValue('type');
        if ($type == self::TYPE_VIP) {
            $resourceType = PrivilegeModel::RESOURCE_TYPE_VIP_SEED;
            if (UsersProductPrivilegeModel::hasPrivilege(USER_PRIVILEGE, $resourceType, PrivilegeModel::PRIVILEGE_TYPE_VIEW)) {
                return 1;
            }
            return 0;
        } elseif ($type == self::TYPE_COIN) {
            $resourceType = PrivilegeModel::RESOURCE_TYPE_COINS_SEED;
            if (UsersProductPrivilegeModel::hasPrivilege(USER_PRIVILEGE, $resourceType, PrivilegeModel::PRIVILEGE_TYPE_VIEW)) {
                return 1;
            }

            $flag = $this->post_is_payed($watchUser->aff, $this->getAttribute('id'));
            return $flag ? 1 : 0;
        } else {
            return 1;
        }
    }

    /**
     * @throws RedisException
     */
    protected function post_is_payed($aff, $id): int
    {
        static $ary = null;
        if ($ary === null) {
            $ary = SeedPostBuyLogModel::list_buy_ids($aff);
        }
        return in_array($id, $ary) ? 1 : 0;
    }

    public function getTitleAttribute(): string
    {
        return emojiDecode($this->attributes['title'] ?? '') ?? '';
    }

    public function setTitleAttribute($value)
    {
        $value = htmlspecialchars(addslashes($value)) ?? '';
        $this->attributes['title'] = emojiEncode($value);
    }

    public function getContentAttribute(): string
    {
        return emojiDecode($this->attributes['content'] ?? '') ?? '';
    }

    public function setContentAttribute($value)
    {
        $this->attributes['content'] = emojiEncode($value) ?? '';
    }

    public static function incrViewNum($id, $ct)
    {
        $data = [
            'fake_view_ct' => DB::raw("fake_view_ct + {$ct}"),
            'hot_sort' => DB::raw("hot_sort + 1"),
        ];
        self::where('id', $id)->increment('view_ct', 1, $data);
    }

    public static function incrCommentNum($id, $num = 1, $fakeCt = 1)
    {
        self::where('id', $id)->increment('comment_ct', $num, []);
    }

    public static function incrementFavoriteNum($id)
    {
        self::where('id', $id)->increment('favorite_ct', 1, ['favorite_count' => DB::raw("favorite_count + 5")]);
    }

    public static function decrementFavoriteNum($id)
    {
        self::where('id', $id)->where('favorite_ct', '>', 0)->decrement('favorite_ct');
    }

    public static function incrementLikeNum($id)
    {
        self::where('id', $id)->increment('like_ct', 1, ['fake_like_ct' => DB::raw("fake_like_ct + 5")]);
    }
    public static function incrementRecSort($id)
    {
        self::where('id', $id)->increment('rec_sort');
    }

    public static function decrementLikeNum($id)
    {
        self::where('id', $id)->where('like_ct', '>', 0)->decrement('like_ct');
    }

    public function medias(): HasMany
    {
        return $this->hasMany(SeedPostMediaModel::class, 'pid', 'id');
    }

    public static function queryBasePostListRelated()
    {
        $with = [
            'medias' => function ($q) {
                $q->select([
                    'id',
                    'pid',
                    'cover',
                    'media_url',
                    'thumb_width',
                    'thumb_height',
                    'type'
                ])
                    ->where('relate_type', SeedPostMediaModel::TYPE_RELATE_POST)
                    ->where('status', SeedPostMediaModel::STATUS_OK)
                    ->orderByDesc('type');
            }
        ];
        return parent::query()
            ->select([
                'id',
                'topic_id',
                'title',
                'fake_view_ct',
                'view_ct',
                'comment_ct',
                'set_top',
                'photo_ct',
                'video_ct',
                'fake_like_ct',
                'like_ct',
                'favorite_count',
                'favorite_ct',
                'type'
            ])
            ->with($with)
            ->where('status', self::STATUS_ON)
            ->where('is_finished', self::FINISHED_OK);
    }

    public static function queryBasePostDetailRelated()
    {
        $with = [
            'medias' => function ($q) {
                $q->select([
                    'id',
                    'pid',
                    'cover',
                    'media_url',
                    'thumb_width',
                    'thumb_height',
                    'type'
                ])
                    ->where('relate_type', SeedPostMediaModel::TYPE_RELATE_POST)
                    ->where('status', SeedPostMediaModel::STATUS_OK)
                    ->orderByDesc('type');
            }
        ];
        return parent::query()
            ->select([
                'id',
                'topic_id',
                'title',
                'type',
                'coins',
                'fake_view_ct',
                'view_ct',
                'fake_like_ct',
                'like_ct',
                'favorite_count',
                'favorite_ct',
                'secret',
                'content',
                'link',
                'comment_ct',
                'favorite_ct',
                'created_at',
                'updated_at',
                'extract_code'
            ])
            ->with($with)
            ->where('status', self::STATUS_ON)
            ->where('is_finished', self::FINISHED_OK);
    }

    public static function queryBase()
    {
        return parent::query()
            ->where('status', self::STATUS_ON)
            ->where('is_finished', self::FINISHED_OK);
    }

    const RK_SEE_SEED_POST = 'rk_see_seed_post';
    public static function addSee($id)
    {
        //正在看
        $key = self::RK_SEE_SEED_POST;
        redis()->zAdd($key, time(), $id);
        if (redis()->sCard($key) > 1000){
            redis()->zRemRangeByRank($key, 1000, -1);
        }
    }

    const SEE_SEED_POST_LIST = 'see:seed:post:list:%s:%s';

    protected static function listPlayingIdsSeedPost($offset, $limit)
    {
        $rankKey = self::RK_SEE_SEED_POST;
        $ids = redis()->zRevRangeByScore(
            $rankKey, '+inf', '-inf',
            [
                'withscores' => TRUE,
                'limit'      => [$offset, $limit]
            ]
        );
        return array_keys($ids);
    }

    public static function listSeeSeedPost($page, $limit)
    {
        $rankKey = sprintf(self::SEE_SEED_POST_LIST ,$page, $limit);
        return cached($rankKey)
            ->fetchPhp(function () use ($page, $limit){
                $offset = ($page - 1) * $limit;
                $ids = self::listPlayingIdsSeedPost($offset, $limit);
                $rs = self::queryBase()
                    ->whereIn('id', $ids)
                    ->get();
                return array_keep_idx($rs, $ids);
            }, 60);
    }

    public static function process_post($post)
    {
        if (!$post) {
            return NULL;
        }

        return $post;
    }

    public static function list_post($sort, $page, $limit)
    {
        $cache_key = sprintf(self::CK_SEED_POST_LIST, $sort, $page, $limit);
        return cached($cache_key)
            ->group(self::GP_SEED_POST_LIST)
            ->chinese(self::CN_SEED_POST_LIST)
            ->fetchPhp(function () use ($sort, $page, $limit) {
                $idAry = self::queryBase()
                    ->when($sort == 'recommend', function ($q) {
                        return $q->orderByDesc('sort');
                    })
                    ->when($sort == 'top', function ($q) {
                        return $q->where('set_top', '>', 0)
                            ->orderByDesc('set_top');
                    })
                    ->when($sort == 'favorite', function ($q) {
                        return $q->orderByDesc('favorite_ct');
                    })
                    ->when($sort == 'recommend', function ($q) {
                        return $q->orderByDesc('rec_sort');
                    })
                    ->when($sort == 'hot', function ($q) {
                        return $q->orderByDesc('hot_sort');
                    })
                    ->orderByDesc('updated_at')
                    ->forPage($page, $limit)
                    ->pluck('id');

                return self::queryBasePostListRelated()
                    ->whereIn('id', $idAry)
                    ->orderByDesc('updated_at')
                    ->get()
                    ->map(function ($item) {
                        $item->created_at = $item->updated_at;
                        return self::process_post($item);
                    })
                    ->filter()
                    ->values();
            });
    }

    public static function post_detail($id)
    {
        $cacheKey = sprintf(self::CK_SEED_POST_DETAIL, $id);
        $post = cached($cacheKey)
            ->group(self::GP_SEED_POST_DETAIL)
            ->chinese(self::CN_SEED_POST_DETAIL)
            ->fetchPhp(function () use ($id) {
                return self::queryBasePostDetailRelated()
                    ->where('id', $id)
                    ->first();
            });
        return self::process_post($post);
    }

    public static function clear_seed_detail($id)
    {
        $key = sprintf(self::CK_SEED_POST_DETAIL, $id);
        trigger_log('清理缓存KEY:' . $key);
        cached($key)->clearCached();
    }

    public static function list_search_post($word, $page, $limit)
    {
        $cacheKey = sprintf(self::CK_SEED_POST_SEARCH_LIST, md5($word), $page, $limit);
        return cached($cacheKey)
            ->group(self::GP_SEED_POST_SEARCH_LIST)
            ->chinese(self::CN_SEED_POST_SEARCH_LIST)
            ->fetchPhp(function () use ($word, $page, $limit) {
                return self::queryBasePostListRelated()
                    ->where('title', 'like', '%' . $word . '%')
                    ->orderByDesc('view_ct')
                    ->orderByDesc('id')
                    ->forpage($page, $limit)
                    ->get()
                    ->map(function ($item) {
                        return self::process_post($item);
                    })
                    ->filter()
                    ->values();
            });
    }
}