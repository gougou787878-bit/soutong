<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class HotModel
 *
 * @property int $id 
 * @property string $icon 图标
 * @property string $tips 描述
 * @property int $type 类型
 * @property int $status 状态  1 启用
 * @property string $link 同类型配合使用 跳转标识
 * @property int $created_at 
 *
 * @author xiongba
 * @date 2020-05-21 19:57:21
 *
 * @mixin \Eloquent
 */
class HotModel extends Model
{

    protected $table = "hot";

    protected $primaryKey = 'id';

    protected $fillable = ['icon', 'tips', 'type', 'status', 'link', 'created_at'];

    protected $guarded = 'id';

    public $timestamps = false;

    const TYPE_HOT_BUY = 1;//热购
    const TYPE_HOT_PLAY = 2;//热播
    const TYPE_HOT_NEWEST = 3;//最新
    const TYPE_HOT_TOPIC = 4;//专题、合集
    const TYPE_HOT_TAG = 5;//标签
    const TYPE_HOT_FIND = 6;//发现
    const TYPE_HOT_LINK = 7;//内部链接
    const TYPE_HOT_GOLD = 8;//金币视频播放

    //类型列表
    const HOT_TYPE = [
        self::TYPE_HOT_BUY        => '热购榜',
        self::TYPE_HOT_PLAY        => '热播榜',
        self::TYPE_HOT_NEWEST        => '最新上传',
        self::TYPE_HOT_TOPIC        => '合集专题',
        self::TYPE_HOT_TAG        => '标签视频',
        self::TYPE_HOT_FIND        => '搜索发现',
        self::TYPE_HOT_LINK        => '内部链接',
        self::TYPE_HOT_GOLD        => '金币视频播放'
    ];


    const STAT_ENABLE = 1;
    const STAT_DISABLE = 0;

    const STAT = [
        self::STAT_ENABLE  => '启用',
        self::STAT_DISABLE => '禁用',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function queryBase()
    {
        return self::where('status', '=', self::STAT_ENABLE);
    }




}
