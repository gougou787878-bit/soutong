<?php

/**
 * class NavigationModel
 *
 * @property int $bot_style 底部样式
 * @property int $click_num 点击数
 * @property string $created_at 创建时间
 * @property int $id
 * @property int $is_aw 是否暗网
 * @property int $is_dm 是否动漫
 * @property int $is_find 是否发现
 * @property int $mid_style 中部样式
 * @property int $open_light 是否开启跑马灯
 * @property int $sort_num 排序
 * @property int $status 状态
 * @property string $title 显示名称
 * @property string $updated_at 更新时间
 * @property int $is_h5 是否H5
 * @property string $h5_url H5地址
 * @property int $is_current 当前是否选中
 *
 * @date 2024-10-15 15:31:07
 *
 * @mixin \Eloquent
 */
class NavigationModel extends EloquentModel
{

    protected $table = "navigation";

    protected $primaryKey = 'id';

    protected $fillable = [
        'bot_style',
        'click_num',
        'created_at',
        'is_aw',
        'is_dm',
        'is_find',
        'mid_style',
        'open_light',
        'sort_num',
        'status',
        'title',
        'updated_at',
        'is_h5',
        'h5_url',
        'is_current',
    ];

    protected $guarded = 'id';

    public $timestamps = true;

    const MID_STYLE_NULL = 0;
    const MID_STYLE_RECOMMEND = 1;
    const MID_STYLE_CATEGORY = 2;
    const MID_STYLE_TIPS = [
        self::MID_STYLE_NULL => '无',
        self::MID_STYLE_RECOMMEND => '推荐类型',
        self::MID_STYLE_CATEGORY => '分类类型',
    ];

    const MID_STYLE_CONF_TIPS = [
        self::MID_STYLE_RECOMMEND => '推荐类型',
        self::MID_STYLE_CATEGORY => '分类类型',
    ];

    const NAG_CAT_COM = 0;
    const NAG_CAT_ATT = 1;
    const NAG_CAT_FIND = 2;
    const NAG_CAT_H5 = 3;
    const NAG_CAT_TIPS = [
        self::NAG_CAT_COM => '普通类型',
        self::NAG_CAT_ATT => '关注类型',
        self::NAG_CAT_FIND => '发现类型',
        self::NAG_CAT_H5   => 'H5类型',
    ];

    const BOT_STYLE_NULL = 0;
    const BOT_STYLE_ONE = 1;
    const BOT_STYLE_TWO = 2;
    const BOT_STYLE_TIPS = [
        self::BOT_STYLE_NULL => '无',
        self::BOT_STYLE_ONE => '分类类型',
        self::BOT_STYLE_TWO => '列表类型'
    ];

    const STATUS_NO = 0;
    const STATUS_YES = 1;
    const STATUS_TIPS = [
        self::STATUS_NO => '关闭',
        self::STATUS_YES => '开启',
    ];

    const FIND_NO = 0;
    const FIND_YES = 1;
    const FIND_TIPS = [
        self::STATUS_NO => '否',
        self::STATUS_YES => '是',
    ];

    const NAG_TYPE_MW = 0;
    const NAG_TYPE_AW = 1;
    const NAG_TYPE_TIPS = [
        self::NAG_TYPE_MW => '明网',
        self::NAG_TYPE_AW => '暗网',
    ];

    const IS_DM_NO = 0;
    const IS_DM_YES = 1;
    const DM_TIPS = [
        self::IS_DM_NO  => '否',
        self::IS_DM_YES => '是',
    ];

    const IS_H5_NO = 0;
    const IS_H5_YES = 1;
    const H5_TIPS = [
        self::IS_H5_NO  => '否',
        self::IS_H5_YES => '是',
    ];

    const CURRENT_NO = 0;
    const CURRENT_OK = 1;
    const CURRENT_TIPS = [
        self::CURRENT_NO  => '否',
        self::CURRENT_OK => '是',
    ];

    const H5_VERSION = '1.1.1';

    public static function queryBase($where = [])
    {

        $query = self::where('status', '=', self::STATUS_YES);
        if ($where) {
            $query->where($where);
        }
        return $query;
    }

    public static function incrByClickNum($id)
    {
        $key = "nav:click:num:key:%d";
        $key = sprintf($key, $id);
        $val = redis()->incrBy($key, 1);
        $val = intval($val);
        if ($val >= 50){
            self::find($id)->increment('click_num', $val);
            redis()->del($key);
        }
    }

    public static function clearCache($nag_id){
        cached('nag_detail'.$nag_id)->clearCached();
    }

    public static function findById($id)
    {
        return cached("nag_detail".$id)
            ->group("nag_detail")
            ->fetchPhp(function () use ($id){
                return self::queryBase()->where('id',$id)->first();
            });
    }

    public static function getListByType($p_type){
        return self::where('status',NavigationModel::STATUS_YES)
            ->where('is_aw',$p_type)
            ->get()
            ->pluck('id')->toArray();
    }

    public static function getList($type, $version){
        return cached('nag:list:key:' . $type . ':' . $version)
            ->group('gp:nag:list')
            ->chinese('视频导航列表')
            ->fetchPhp(function () use ($type, $version){
                return NavigationModel::queryBase()
                    ->select(['id','title','mid_style','bot_style','is_find', 'is_h5', 'h5_url', 'is_current'])
                    ->where('is_aw',$type)
                    ->when(version_compare($version, self::H5_VERSION, '<='), function ($q){
                        $q->where('is_h5',self::IS_H5_NO);
                    })
                    ->orderByDesc('sort_num')
                    ->get();
            });
    }
}
