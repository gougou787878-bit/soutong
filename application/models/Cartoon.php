<?php
/**
 * class CartoonModel
 *
 * @property int $id
 * @property int $category_id 分类ID
 * @property string $title 标题
 * @property string $desc 简介
 * @property string $actors 演员
 * @property string $category 分类
 * @property string $country 国家
 * @property string $directors 导演
 * @property string $is_series 系列 0电影 1 剧集
 * @property string $cover 封面
 * @property string $tags 标签
 * @property string $year_released 上映年
 * @property int $video_num 视频数量
 * @property int $like_count 点赞数
 * @property int $play_count 播放数
 * @property int $pay_count 购买数
 * @property int $status 状态 0 下架 1上架
 * @property int $refresh_at 刷新时间
 * @property int $created_at 创建时间
 * @property int $source_id 资源id 采集识别
 * @property int $com_count 评论数
 * @property int $is_like 是否点赞
 * @property string $cover_full 封面全
 * @property string $langs 语言
 *
 *
 * @mixin \Eloquent
 */
class CartoonModel extends EloquentModel
{

    protected $table = 'cartoon';

    protected $fillable = [
        'category_id',
        'title',
        'desc',
        'actors',
        'category',
        'country',
        'directors',
        'is_series',
        'cover',
        'tags',
        'year_released',
        'video_num',
        'like_count',
        'play_count',
        'pay_count',
        'status',
        'refresh_at',
        'created_at',
        'source_id',
        'com_count',
        'langs',
    ];
    public $timestamps = true;
    const UPDATED_AT = null;

    const SHOW_LIST_COLUMS = ['id','title','play_count','created_at','cover','is_series'];

    const STATUS_NO = 0;
    const STATUS_OK = 1;
    const STATUS_TIPS = [
        self::STATUS_NO => '下架',
        self::STATUS_OK => '上架'
    ];

    const SERIES_TIPS = [
        0=>'电影',
        1=>'剧集',
    ];

    const SHOW_COLUMS = ['id','title','play_count','created_at','cover','is_series','tags'];

    protected $appends = [
        'cover_full','is_favorite'
    ];

    public function cate(){
        return $this->hasOne(CartoonCategoryModel::class, 'id', 'category_id');
    }

    public function getCoverFullAttribute(): string
    {
        return url_cover($this->attributes['cover'] ?? '');
    }

    public static function queryBase()
    {
        return self::where('status', 1);
    }

    public function getIsFavoriteAttribute()
    {
        $watchUser = self::$watchUser;
        if (empty($watchUser) || !$this->getAttributeValue('id')) {
            return 0;
        }
        return CartoonLikeModel::hasLike($watchUser->getAttributeValue('aff'),
            $this->getAttributeValue('id')) ? 1 : 0;
    }

    public static function incrView($id, $number = 1)
    {
        self::find($id)->increment('play_count', $number);
    }

    public static function list($category_id, $sort, $page, $limit){
        return cached(sprintf('cartoon:list:%s:%s:%d:%d',$category_id, $sort, $page, $limit))
            ->fetchPhp(function () use ($category_id, $sort, $page,$limit) {
                return self::queryBase()
                    ->select(self::SHOW_COLUMS)
                    ->where('category_id', $category_id)
                    ->when($sort=='recommend',function ($query){
                        return $query->orderByDesc('like_count');
                    })
                    ->when($sort=='hot',function ($query){
                        return $query->orderByDesc('play_count');
                    })
                    ->when($sort=='sale',function ($query){
                        return $query->orderByDesc('pay_count');
                    })
                    ->when($sort=='new',function ($query){
                        return $query->orderByDesc('refresh_at');
                    })
                    ->orderByDesc('id')
                    ->forPage($page,$limit)
                    ->get();
            });
    }

    public static function search($kwy, $page, $limit){
        return cached(sprintf('cartoon:search:list:%s:%d:%d', $kwy, $page, $limit))
            ->fetchPhp(function () use ($kwy , $page,$limit) {
                return self::queryBase()
                    ->select(self::SHOW_COLUMS)
                    ->where('title', 'like', "%{$kwy}%")
                    ->orderByDesc('id')
                    ->forPage($page,$limit)
                    ->get();
            });
    }

    public static function tagList($tag, $sort, $page, $limit){
        return cached(sprintf('cartoon:tag:list:%s:%s:%d:%d',$tag, $sort, $page, $limit))
            ->fetchPhp(function () use ($tag, $sort, $page, $limit) {
                return self::queryBase()
                    ->select(self::SHOW_COLUMS)
                    ->when(!empty($tag),function ($query)use($tag){
                        return $query->whereRaw("match(tags) against(?)", [$tag]);
                    })
                    ->when($sort=='recommend',function ($query){
                        return $query->orderByDesc('like_count');
                    })
                    ->when($sort=='hot',function ($query){
                        return $query->orderByDesc('play_count');
                    })
                    ->when($sort=='sale',function ($query){
                        return $query->orderByDesc('pay_count');
                    })
                    ->when($sort=='new',function ($query){
                        return $query->orderByDesc('refresh_at');
                    })
                    ->orderByDesc('id')
                    ->forPage($page,$limit)
                    ->get();
            });
    }

    public static function getRecommendPicBySeries($category_id, $limit){
        return self::queryBase()
            ->where("category_id", $category_id)
            ->orderByDesc('like_count')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public static function getRecommendPic($sort, $limit){
        return self::queryBase()
            ->when('sale' == $sort, function ($q){
                return $q->orderByDesc('pay_count');
            })
            ->when('like' == $sort, function ($q){
                return $q->orderByDesc('like_count');
            })
            ->when('new' == $sort, function ($q){
                return $q->orderByDesc('refresh_at');
            })
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }
}
