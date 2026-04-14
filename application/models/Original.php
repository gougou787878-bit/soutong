<?php
/**
 * class OriginalModel
 *
 * @property int $id
 * @property string $title 标题
 * @property string $desc 简介
 * @property string $actors 演员
 * @property string $category 分类
 * @property string $country 国家
 * @property string $directors 导演
 * @property string $is_series 系列 0 电影 2 剧集
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
 * @property string $last_see 最后观看时间
 * @property int $hot_c_month 月观看次数
 *
 *
 * @mixin \Eloquent
 */
class OriginalModel extends EloquentModel
{


    protected $table = 'original';

    protected $fillable = [
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
        'last_see',
        'hot_c_month',
    ];
    public $timestamps = true;
    const UPDATED_AT = null;

    const SHOW_LIST_COLUMS = ['id','title','play_count','created_at','cover','is_series'];
    const STATUS_TIPS = [
        0=>'下架',
        1=>'上架'
    ];

    const SERIES_TIPS = [
        0=>'电影',
        1=>'剧集',
        2=>'剧集',
        3=>'剧集',
    ];

    const SHOW_COLUMS = ['id','title','play_count','created_at','cover','is_series','tags'];

    protected $appends = [
        'cover_full','is_like'
    ];
    public function getCoverFullAttribute(): string
    {
        return url_cover($this->attributes['cover'] ?? '');
    }

    public static function queryBase()
    {
        return self::where('status', 1);
    }

    public function getIsLikeAttribute()
    {
        $watchUser = self::$watchUser;
        if (empty($watchUser) || !$this->getAttributeValue('id')) {
            return 0;
        }
        return OriginalUserLikeModel::hasLike($watchUser->getAttributeValue('uid'),
            $this->getAttributeValue('id')) ? 1 : 0;
    }

    public static function incrView($id, $number = 1)
    {
        $data = [
            'last_see' => \Carbon\Carbon::now(),
            'hot_c_month' => DB::raw("hot_c_month + {$number}"),
        ];
        self::find($id)->increment('play_count', $number, $data);
    }

    public static function list($tab, $kwy, $sort, $page, $limit){
        $expire = 3600;
        if ($sort == 'see'){
            $expire = 120;
        }
        return cached(sprintf('original:list:%s:%s:%s:%d:%d',$tab,$kwy,$sort,$page,$limit))
            ->fetchPhp(function () use ($tab, $kwy, $sort, $page,$limit) {
                return  \OriginalModel::queryBase()
                    ->select(self::SHOW_COLUMS)
                    ->when(!empty($kwy),function ($query)use($tab,$kwy){
                        switch ($tab){
                            case 'type':
                            case 'tag':
                            case 'plot':
                            case 'lgbt':
                            case 'area':
                                $query = $query->whereRaw("match(tags) against(?)", [$kwy]);
                                break;
                            case 'search':
                                $query = $query->whereRaw("title like ?", ['%' . $kwy . '%']);
                                break;
                        }
                        return $query;

                    })
                    ->when($sort=='see',function ($query)use($sort){
                        return $query->orderByDesc('last_see');
                    })
                    ->when($sort=='recommend',function ($query)use($sort){
                        return $query->orderByDesc('hot_c_month');
                    })
                    ->when($sort=='hot',function ($query)use($sort){
                        return $query->orderByDesc('play_count');
                    })
                    ->when($sort=='sale',function ($query)use($sort){
                        return $query->orderByDesc('pay_count');
                    })
                    ->orderByDesc('id')
                    ->forPage($page,$limit)
                    ->get()->map(function (\OriginalModel $item){
                        //列表不缓存内容字段
                        return $item;
                    });
            }, $expire);
    }

    public static function randList($kwy, $page, $limit)
    {
        $randKey = 'rand:original:list:v1:' . ':' . $kwy;
        $cacheKey = "list:original:v1:" . $kwy  . '-p' . $page;
        $setKey = 'rand:original:set:keys' . $kwy;
        redis()->sAdd($setKey, $cacheKey);

        if (!redis()->exists($randKey)) {
            $ids = self::queryBase()
                ->select('id')
                ->when(!empty($kwy),function ($query)use($kwy){
                    return $query->whereRaw("match(tags) against(?)", [$kwy]);
                })
                ->orderByDesc('play_count')
                ->limit(200)
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
                return self::queryBase()
                    ->whereIn("id", $ids)
                    ->get();
            }, rand(1800, 3600));
    }
}
