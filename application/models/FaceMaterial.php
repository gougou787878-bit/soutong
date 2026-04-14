<?php

use Illuminate\Database\Eloquent\Relations\HasOne;


/**
 * class FaceMaterialModel
 *
 * @property int $id
 * @property string $title 素材标题
 * @property int $cate_id 分类ID
 * @property string $thumb 素材图片
 * @property int $thumb_w
 * @property int $thumb_h
 * @property int $sort
 * @property int $use_ct 使用数
 * @property int $status 状态
 * @property string $created_at
 * @property string $updated_at
 * @property int $type 0金币 1次数/金币
 * @property int $coins
 *
 *
 * @date 2024-01-02 20:10:27
 *
 * @mixin \Eloquent
 */
class FaceMaterialModel extends EloquentModel
{
    protected $table = "face_material";
    protected $primaryKey = 'id';
    protected $fillable = [
        'title',
        'cate_id',
        'thumb',
        'thumb_w',
        'thumb_h',
        'sort',
        'use_ct',
        'status',
        'created_at',
        'updated_at',
        'type',
        'coins',
        'weekly_usage_count',
        'like_count',
        'favorite_count',
        'is_random',
        'comment_num'

    ];
    protected $guarded = 'id';
    public $timestamps = true;

    public $appends = [
        'is_like',
        'is_favorite',
    ];
    const SE_FACE_MATERIAL_LIST = ['id', 'title', 'thumb', 'thumb_w', 'thumb_h', 'type', 'coins','use_ct','weekly_usage_count','like_count','favorite_count','is_random','comment_num'];
    const CK_FACE_MATERIAL_LIST = 'ck:face:material:list:%d:%d:%d';
    const GP_FACE_MATERIAL_LIST = 'gp:face:material:list';
    const CN_FACE_MATERIAL_LIST = '图片换头素材列表';

    const CK_FACE_MATERIAL_DETAIL = 'ck:face:material:detail:%d';
    const GP_FACE_MATERIAL_DETAIL = 'gp:face:material:detail';
    const CN_FACE_MATERIAL_DETAIL = '图片换脸素材详情';

    const STATUS_NO = 0;
    const STATUS_OK = 1;
    const STATUS_TIPS = [
        self::STATUS_NO => '下架',
        self::STATUS_OK => '上架',
    ];

    const TYPE_COINS = 0;
    const TYPE_FIX = 1;
    const TYPE_TIPS = [
        self::TYPE_COINS => '金币',
        self::TYPE_FIX => '金币/次数',
    ];

    // 定义常量来表示不同的排序方式
    const SEARCH_SORT_BY_WEEKLY_USAGE = 1;  // 按周使用次数排序
    const SEARCH_SORT_BY_NEWEST = 2;        // 按最新上架排序
    const SEARCH_SORT_BY_USE_COUNT = 3;     // 按使用次数排序
    const SEARCH_SORT_BY_LIKE_COUNT = 4;    // 按点赞数排序
    const SEARCH_SORT_BY_FAVORITE_COUNT = 5; // 按收藏数排序
    const SEARCH_SORT_RANDOM = 6;           // 随机排序       

    public function cate(): HasOne
    {
        return $this->hasOne(FaceCateModel::class, 'id', 'cate_id');
    }

    public function setThumbAttribute($value)
    {
        parent::resetSetPathAttribute('thumb', $value);
    }

    public function getThumbAttribute(): string
    {
        return $this->attributes['thumb'] ? url_cover($this->attributes['thumb']) : '';
    }

    //是否点赞
    public function getIsLikeAttribute()
    {
        $watchUser = self::$watchUser;
        if (empty($watchUser) || !isset($this->attributes['id'])) {
            return 0;
        }
        static $ids = null;
        if (null === $ids) {
            $ids = FaceMaterialUserLikeModel::getIdsByAff($watchUser->aff, FaceMaterialUserLikeModel::TYPE_MATERIAL, FaceMaterialUserLikeModel::ACTION_LIKE);
        }
        if ($ids && in_array($this->attributes['id'], $ids)) {
            return 1;
        }
        return 0;
    }

    //是否收藏
    public function getIsFavoriteAttribute()
    {
        $watchUser = self::$watchUser;
        if (empty($watchUser) || !isset($this->attributes['id'])) {
            return 0;
        }
        static $ids = null;
        if (null === $ids) {
            $ids = FaceMaterialUserLikeModel::getIdsByAff($watchUser->aff, FaceMaterialUserLikeModel::TYPE_MATERIAL, FaceMaterialUserLikeModel::ACTION_COLLECT);
        }
        if ($ids && in_array($this->attributes['id'], $ids)) {
            return 1;
        }
        return 0;
    }

    public static function list_material($id, $page, $limit, $searchId = 0)
    {
        $cache_key = sprintf(self::CK_FACE_MATERIAL_LIST, $id, $page, $limit);
        
        return cached($cache_key)
            ->group(self::GP_FACE_MATERIAL_LIST)
            ->chinese(self::CN_FACE_MATERIAL_LIST)
            ->clearCached()
            ->fetchPhp(function () use ($id, $page, $limit,$searchId) {
                return self::select(self::SE_FACE_MATERIAL_LIST)
                    ->where('status', self::STATUS_OK)
                    ->when($id, function ($q) use ($id) {
                        return $q->where('cate_id', $id);
                    })
                    ->when($searchId == self::SEARCH_SORT_BY_WEEKLY_USAGE, function ($q){
                        return $q->orderByDesc('weekly_usage_count');
                    })
                    ->when($searchId == self::SEARCH_SORT_BY_NEWEST, function ($q){
                        return $q->orderByDesc('created_at');
                    })
                    ->when($searchId == self::SEARCH_SORT_BY_USE_COUNT, function ($q){
                        return $q->orderByDesc('use_ct');
                    })
                    ->when($searchId == self::SEARCH_SORT_BY_LIKE_COUNT, function ($q){
                        return $q->orderByDesc('like_count');
                    })
                    ->when($searchId == self::SEARCH_SORT_BY_FAVORITE_COUNT, function ($q){
                        return $q->orderByDesc('favorite_count');
                    })
                    ->when($searchId == self::SEARCH_SORT_RANDOM, function ($q){
                        return $q->inRandomOrder();
                    })
                    ->orderByDesc('id')
                    ->forPage($page, $limit)
                    ->get();
            });
    }

    public static function get_detail($id)
    {
        $cache_key = sprintf(self::CK_FACE_MATERIAL_DETAIL, $id);
        return cached($cache_key)
            ->group(self::GP_FACE_MATERIAL_DETAIL)
            ->chinese(self::CN_FACE_MATERIAL_DETAIL)
            ->fetchPhp(function () use ($id) {
                return self::select(self::SE_FACE_MATERIAL_LIST)
                    ->where('id', $id)
                    ->where('status', self::STATUS_OK)
                    ->first();
            });
    }


}
