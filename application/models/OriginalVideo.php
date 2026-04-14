<?php
/**
 * class OriginalVideoModel
 *
 * @property int $id
 * @property string $pid 原创ID
 * @property string $cover 封面
 * @property string $source 资源地址
 * @property int $duration 时长
 * @property int $width 宽
 * @property int $height 高
 * @property int $sort 剧集排序
 * @property int $type 类型 1 电影 2 剧集

 * @property int $coins 价格
 * @property int $is_free 是否免费
 * @property int $like_count 点赞数
 * @property int $play_count 播放数
 * @property int $pay_count 购买数
 * @property int $status 状态 0 下架 1上架
 * @property int $refresh_at 刷新时间
 * @property int $created_at 创建时间
 * @property string $source_id 资源id 采集识别
 * @property int $source_video_id 资源视频id 采集识别
 * @property int $is_pay 是否支付
 *
 *
 * @mixin \Eloquent
 */
class OriginalVideoModel extends EloquentModel
{

    protected $table = 'original_video';

    protected $fillable = [
        'pid',
        'cover',
        'source',
        'duration',
        'width',
        'height',
        'sort',
        'type',
        'coins',
        'is_free',
        'like_count',
        'play_count',
        'pay_count',
        'status',
        'refresh_at',
        'created_at',
        'source_id',
        'source_video_id',
        'title'
    ];
    public $timestamps = true;
    const UPDATED_AT = null;

    protected $appends = [
        'cover_full','is_pay'
    ];

    const TYPE_TIPS = [
        1=>'电影',
        2=>'剧集'
    ];
    const FREE_TIPS = [
        0=>'收费',
        1=>'免费'
    ];
    const STATUS_TIPS = [
        0=>'下架',
        1=>'上架'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function original()
    {
        return $this->hasOne(OriginalModel::class, 'id', 'pid');
    }
    public function getCoverFullAttribute(): string
    {
        return url_cover($this->attributes['cover'] ?? '');
    }

    public static function queryBase()
    {
        return self::where('status', '=',1);
    }

    public function getIsPayAttribute()
    {
        $watchUser = self::$watchUser;
        if (empty($watchUser) || !$this->getAttributeValue('id')) {
            return 0;
        }

        $coins = $this->getAttributeValue('coins');
        if ($coins > 0){
            $resource_type = PrivilegeModel::RESOURCE_TYPE_NORMAL_COINS_VIDEO;
        }else{
            $resource_type = PrivilegeModel::RESOURCE_TYPE_NORMAL_VIP_VIDEO;
        }
        $hasPrivilege = UsersProductPrivilegeModel::hasPrivilege(USER_PRIVILEGE, $resource_type, PrivilegeModel::PRIVILEGE_TYPE_VIEW);
        if ($hasPrivilege){
            return 1;
        }

        if ($coins > 0){
            return OriginalPayModel::hasBuy($watchUser->getAttributeValue('uid'), $this->getAttributeValue('id')) ? 1 : 0;
        }

        return 0;
    }
}
