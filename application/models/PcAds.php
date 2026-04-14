<?php


use tools\RedisService;

/**
 * class PcAdsModel
 *
 * @property string $android_url andeoid下载地址
 * @property int $apply_type 应用场景：1.专题策划 2. 直播间广告 3.直播间广告2， 4.启动页 5 首页活动广告
 * @property string $channel 渠道广告
 * @property int $created_at 创建时间
 * @property string $description 广告词
 * @property int $id
 * @property int $img_type 1:上传的图片;2:网络图片
 * @property string $img_url 图片地址
 * @property string $ios_url ios下载地址
 * @property string $mv_m3u8 视频m3u8
 * @property int $position 位置:配置查看adscontroller
 * @property int $show_user 0全部1 48小时前248小时后
 * @property int $status 0-禁用，1-启用
 * @property int $click_number
 * @property string $title 广告标题
 * @property int $type 广告类型 1：下载链接 2：跳转qq 3:跳转微信
 * @property string $url 广告跳转地址/QQ号/微信号
 * @property int $value 目标ID,分类id|系列id|标签id|视频id
 * @property string $expired_date 广告过期时间  eg：2022-10-10 00:00:00
 *
 *
 * @mixin \Eloquent
 */
class PcAdsModel extends EloquentModel
{
    const STATUS_SUCCESS = 1;
    const STATUS_FAIL = 0;
    const STATUS = [
        self::STATUS_FAIL    => '禁用',
        self::STATUS_SUCCESS => '启用',
    ];

    const REDIS_PC_ADS_KEY = 'pc:ads:pos_';
    const REDIS_PC_ADS_GROOUP_KEY = 'pc:ads:group';

    const POSITION_POP = 1; //弹窗广告
    const POSITION_HOME_BANNER = 2; // 首页banner
    const POSITION_MV_BANNER = 3; // 视频列表banner
    const POSITION_COMMUNITY_BANNER = 4; //社区列表banner
    const POSITION_IMAGE_BANNER = 5; //图文列表banner
    const POSITION_DETAIL_TOP = 6; //详情页顶部banner
    const POSITION_DETAIL_BOTTOM = 7; //详情页底部banner
    const POSITION_DETAIL_TOP_APP = 8; //详情页顶部APP广告
    const POSITION_DETAIL_BOTTOM_APP = 9; //详情页底部APP广告

    const POSITION = [ // 广告位置
        self::POSITION_POP              => '弹窗广告',
        self::POSITION_HOME_BANNER      => '首页banner',
        self::POSITION_MV_BANNER        => '视频列表banner',
        self::POSITION_COMMUNITY_BANNER => '社区列表banner',
        self::POSITION_IMAGE_BANNER     => '图文列表banner',
        self::POSITION_DETAIL_TOP       => '详情页顶部banner',
        self::POSITION_DETAIL_BOTTOM    => '详情页底部banner',
        self::POSITION_DETAIL_TOP_APP   => '详情页顶部APP广告',
        self::POSITION_DETAIL_BOTTOM_APP=> '详情页底部APP广告',
    ];

    //应用场景：1.专题策划 2. 直播间广告 3.直播间广告2， 4.启动页 5 首页活动广告
    const APPLY_TYPE_THEMATIC = 1,
        APPLY_TYPE_LIVE_ROOM_ONE = 2,
        APPLY_TYPE_LIVE_ROOM_TWO = 3,
        APPLY_TYPE_START_PAGE = 4,
        APPLY_TYPE_HOME_ACTIVITY = 5;
    const APPLY_TYPE = [
        self::APPLY_TYPE_THEMATIC      => '专题策划',
        self::APPLY_TYPE_LIVE_ROOM_ONE => '直播间广告',
        self::APPLY_TYPE_LIVE_ROOM_TWO => '直播间广告2',
        self::APPLY_TYPE_START_PAGE    => '启动页',
        self::APPLY_TYPE_HOME_ACTIVITY => '首页活动广告',
    ];


    //  展示用户群体
    const SHOW_USER = [
        0 => '全部用户',
        1 => '注册时间48小时内用户',
        2 => '注册时间48小时后用户'
    ];

    // 广告类型
    const ADS_TYPE = [
        0 => '默认处理',
        1 => '外部跳转连接',
        2 => '内部跳转标签',
        3 => '内部跳转连接',
        4 => '内部跳转视频详情',
       // 5 => '直接安装App',
        6 => '跳转到VIP',
        7 => '跳转金币商城',
        8 => '跳转到游戏',
        9 => '图文漫画',
        10 => '用户分享',
        11 => '跳转社区帖子',
    ];

    protected $table = 'pc_ads';

    protected $fillable = [
        'title',
        'img_url',
        'img_type',
        'url',
        'position',
        'ios_url',
        'android_url',
        'type',
        'value',
        'status',
        'created_at',
        'show_user',
        'click_number',
        'expired_date'
    ];

    protected $appends = [
        'img_url_full',
        'is_expired'
    ];

    /**
     * 替换图片地址
     * @param $value
     * @return string
     */
    public function getImgUrlFullAttribute()
    {
        return $this->img_url ? url_ads($this->img_url) : '';
    }
    /**
     * @return bool
     */
    public function getIsExpiredAttribute()
    {
        $expired_date = $this->getAttribute('expired_date');
        if ($expired_date) {
            return !(strtotime($expired_date) > time());
        }
        return false;
    }

    public static function clearRedisCache($position)
    {
        $key = self::REDIS_PC_ADS_KEY . $position;
        $position && RedisService::del($key);
    }

}