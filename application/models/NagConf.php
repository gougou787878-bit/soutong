<?php

/**
 * class NagConfModel
 *
 * @property string $api 结构API
 * @property string $icon 图标
 * @property int $id
 * @property int $mid_style 中部样式
 * @property int $nag_id 导航ID
 * @property int $show_num 数量
 * @property int $sort 排序
 * @property int $status 状态
 * @property string $title 文案
 * @property int $type 配置类型
 * @property int $up_catid Up主分类ID
 *
 * @date 2024-10-15 15:31:34
 *
 * @mixin \Eloquent
 */
class NagConfModel extends EloquentModel
{

    protected $table = "nag_conf";

    protected $primaryKey = 'id';

    protected $fillable = [
        'api',
        'icon',
        'mid_style',
        'nag_id',
        'show_num',
        'sort',
        'status',
        'title',
        'type',
        'up_catid'
    ];

    protected $guarded = 'id';

    public $timestamps = false;

    const RECOMMEND_RANK = 1;
    const RECOMMEND_YC = 2;
    const RECOMMEND_HJ = 3;
    const RECOMMEND_NS = 4;
    const RECOMMEND_CAT = 5;
    const RECOMMEND_ADS = 6;
    const RECOMMEND_VIP = 7;
    const RECOMMEND_COINS = 8;
    const RECOMMEND_DY = 9;
    const RECOMMEND_LIVE = 10;
    const RECOMMEND_AI = 11;

    const CONF_TIPS = [
        self::RECOMMEND_RANK => "排行榜类型",
        self::RECOMMEND_YC => "原创",
        self::RECOMMEND_HJ => "合集",
        self::RECOMMEND_NS => "男色",
        self::RECOMMEND_CAT => "分类更多类型",
        self::RECOMMEND_ADS => "活动-广告",
        self::RECOMMEND_VIP => "VIP充值",
        self::RECOMMEND_COINS => "金币充值",
        self::RECOMMEND_DY => "抖音",
        self::RECOMMEND_LIVE => "直播",
        self::RECOMMEND_AI => "AI",
    ];

    const NAG_CONF_LIST = "nag:conf:%s:%s:%s";
    const NAG_CONF_LIST_ONE = "nag:conf:%s:%s";

    public function navigation()
    {
        return $this->hasOne(NavigationModel::class, 'id', 'nag_id');
    }

    public function getIconAttribute()
    {
        if (MODULE_NAME == 'admin'){
            return url_cover($this->attributes['icon']);
        }
        return url_cover($this->attributes['icon']);
    }

    public function setIconAttribute($value)
    {
        if (strpos($value, '://') !== false){
            $value = parse_url($value,PHP_URL_PATH);
        }
        $this->attributes['icon'] = $value;
    }

    public static function getConf($nag_id,$mid_style,$limit = 10){
        $rKey = sprintf(self::NAG_CONF_LIST,$nag_id,$mid_style,$limit);
        return cached($rKey)
            ->group('nag:conf:list')
            ->chinese('导航-配置列表')
            ->fetchPhp(function () use ($nag_id,$mid_style,$limit){
                return self::where('nag_id',$nag_id)
                    ->where('mid_style',$mid_style)
                    ->orderByDesc('sort')
                    ->orderByDesc('id')
                    ->limit($limit)
                    ->get();
            });
    }

    public static function getConfFirst($nag_id,$mid_style){
        $rKey = sprintf(self::NAG_CONF_LIST_ONE,$nag_id,$mid_style);
        return cached($rKey)
            ->group('nag:conf:middle')
            ->chinese('导航-中部配置')
            ->fetchPhp(function () use ($nag_id,$mid_style){
                return self::where('nag_id',$nag_id)
                    ->where('mid_style',$mid_style)
                    ->orderByDesc('sort')
                    ->orderByDesc('id')
                    ->first();
            });
    }
}
