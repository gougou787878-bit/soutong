<?php

/**
 * @property int $id
 * @property string $f_id 同步标识
 * @property string $name 名称
 * @property string $desc 描述
 * @property int $type 类型
 * @property int $symbol 符号类型
 * @property string $value 值
 * @property int $sort 排序
 * @property int $status 状态
 * @property string $created_at 创建时间
 * @property string $updated_at 创建时间
 * @mixin \Eloquent
 */
class LiveThemeModel extends EloquentModel
{
    protected $primaryKey = 'id';
    protected $table = 'live_theme';
    protected $fillable = [
        'f_id',
        'name',
        'desc',
        'type',
        'symbol',
        'value',
        'sort',
        'status',
        'created_at',
        'updated_at',
    ];

    public $timestamps = true;

    const TYPE_COUNTRY = 1;
    const TYPE_LANGUAGE = 2;
    const TYPE_GENDER = 3;
    const TYPE_TAG = 4;
    const TYPE_TIPS = [
        self::TYPE_COUNTRY  => '国家',
        self::TYPE_LANGUAGE => '语言',
        self::TYPE_GENDER   => '性别',
        self::TYPE_TAG      => '标签',
    ];

    const STATUS_NO = 0;
    const STATUS_OK = 1;
    const STATUS_TIPS = [
        self::STATUS_NO => '禁用',
        self::STATUS_OK => '启用',
    ];

    const SYMBOL_NO = 0;
    const SYMBOL_OK = 1;
    const SYMBOL_TIPS = [
        self::SYMBOL_NO => '不包含',
        self::SYMBOL_OK => '包含',
    ];

    const SE_LIVE_THEME_LIST = ['id', 'name'];
    const CK_LIVE_THEME_LIST = 'ck:live:theme:list';
    const GP_LIVE_THEME_LIST = 'gp:live:theme:list';
    const CN_LIVE_THEME_LIST = '直播-主题列表';

    const SE_REC_LIVE_THEME_LIST = ['id', 'name'];
    const CK_REC_LIVE_THEME_LIST = 'ck:rec:live:theme:list:%s:%s';
    const GP_REC_LIVE_THEME_LIST = 'gp:rec:live:theme:list';
    const CN_REC_LIVE_THEME_LIST = '直播-推荐列表';

    const SE_LIVE_THEME_DETAIL = ['id', 'type', 'symbol', 'value'];
    const CK_LIVE_THEME_DETAIL = 'ck:live:theme:detail:%s';
    const GP_LIVE_THEME_DETAIL = 'gp:live:theme:detail';
    const CN_LIVE_THEME_DETAIL = '直播-主题详情';

    public static function list_nav()
    {
        return cached(self::CK_LIVE_THEME_LIST)
            ->group(self::GP_LIVE_THEME_LIST)
            ->chinese(self::CN_LIVE_THEME_LIST)
            ->fetchPhp(function () {
                return self::select(self::SE_LIVE_THEME_LIST)
                    ->where('status', self::STATUS_OK)
                    ->orderByDesc('sort')
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->get();
            });
    }

    public static function list_rec($page, $limit)
    {
        $service = new \service\ApiLiveService();
        $cache_key = sprintf(self::CK_REC_LIVE_THEME_LIST, $page, $limit);
        return cached($cache_key)
            ->group(self::GP_REC_LIVE_THEME_LIST)
            ->chinese(self::CN_REC_LIVE_THEME_LIST)
            ->fetchPhp(function () use ($page, $limit, $service) {
                return self::select(self::SE_REC_LIVE_THEME_LIST)
                    ->where('status', self::STATUS_OK)
                    ->forPage($page, $limit)
                    ->orderByDesc('sort')
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->get();
            }, mt_rand(10, 30))
            ->map(function ($item) use ($service) {
                $item->lives = LiveModel::list_live($item->id, 1, 6);
                if ($item->lives->count() == 0) {
                    return null;
                }
                $item->lives = $service->v2format($item->lives);
                return $item;
            })
            ->filter()
            ->values();
    }

    public static function detail($id)
    {
        return cached(self::CK_LIVE_THEME_DETAIL)
            ->group(self::GP_LIVE_THEME_DETAIL)
            ->chinese(self::CN_LIVE_THEME_DETAIL)
            ->clearCached()
            ->fetchPhp(function () use ($id) {
                return self::select(self::SE_LIVE_THEME_DETAIL)
                    ->where('status', self::STATUS_OK)
                    ->where('id', $id)
                    ->first();
            });
    }
}