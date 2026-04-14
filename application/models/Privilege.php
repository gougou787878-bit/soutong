<?php

/**
 * class PrivilegeModel
 *
 * @property int $id
 * @property int $resource_type 资源类型
 * @property int $privilege_type 权限类型
 * @property int $value 总数值
 * @property int $day_value 每日数值
 * @property string $created_at
 * @property int $sort
 *
 *
 * @date 2022-04-08 21:27:32
 *
 * @mixin \Eloquent
 */
class PrivilegeModel extends EloquentModel
{

    protected $table = "privilege";

    protected $primaryKey = 'id';

    protected $fillable = [
        'resource_type',
        'privilege_type',
        'value',
        'created_at',
        'sort',
        'day_value'
    ];

    protected $guarded = 'id';

    public $timestamps = false;
    protected $appends = [
        'resource_type_str',
        'privilege_type_str'
    ];

    /**
     * @param $value
     * @return string
     */
    public function getResourceTypeStrAttribute()
    {
        return self::RESOURCE_TYPE[$this->getAttribute('resource_type')];
    }

    /**
     * @param $value
     * @return string
     */
    public function getPrivilegeTypeStrAttribute()
    {
        return self::PRIVILEGE_TYPE[$this->getAttribute('privilege_type')];
    }

    const RESOURCE_TYPE_SYSTEM = 1;
    const RESOURCE_TYPE_NORMAL_VIP_VIDEO = 2;//明网VIP视频
    const RESOURCE_TYPE_NORMAL_COINS_VIDEO = 3;//明网金币视频
    const RESOURCE_TYPE_AW_VIP_VIDEO = 4;//暗网VIP视频
    const RESOURCE_TYPE_AW_COINS_VIDEO = 5;//暗网金币视频
    const RESOURCE_TYPE_NORMAL_VIP_POST = 6;//VIP社区
    const RESOURCE_TYPE_NORMAL_COINS_POST = 7;//金币社区
    const RESOURCE_TYPE_VIP_PICTURE = 8;//VIP图集
    const RESOURCE_TYPE_COINS_PICTURE = 9;//金币图集
    const RESOURCE_TYPE_VIP_NOVEL = 10;//VIP小说
    const RESOURCE_TYPE_COINS_NOVEL = 11;//金币小说
    const RESOURCE_TYPE_VIP_MH = 12;//VIP漫画
    const RESOURCE_TYPE_COINS_MH = 13;//金币漫画
    const RESOURCE_TYPE_VIP_CARTOON = 14;//VIP动漫
    const RESOURCE_TYPE_COINS_CARTOON = 15;//金币动漫
    const RESOURCE_TYPE_VIP_SEED = 16;//VIP种子
    const RESOURCE_TYPE_COINS_SEED = 17;//金币种子
    const RESOURCE_TYPE_PORN_GAME = 18;//黄游
    const RESOURCE_TYPE_LIVE_VIP = 19;//VIP直播
    const RESOURCE_TYPE_LIVE_COINS = 20;//金币直播
    const RESOURCE_TYPE_AI_HL = 21;//AI换脸
    const RESOURCE_TYPE_ZT_MV = 22;
    const RESOURCE_TYPE_AI_MAGIC=23;//AI图生视频
    const RESOURCE_TYPE_AI_TY=24;//AI脱衣

    const RESOURCE_TYPE = [
        self::RESOURCE_TYPE_SYSTEM                  => '系统',
        self::RESOURCE_TYPE_NORMAL_VIP_VIDEO        => '明网VIP视频',
        self::RESOURCE_TYPE_NORMAL_COINS_VIDEO      => '明网金币视频',
        self::RESOURCE_TYPE_AW_VIP_VIDEO            => '暗网VIP视频',
        self::RESOURCE_TYPE_AW_COINS_VIDEO          => '暗网金币视频',
        self::RESOURCE_TYPE_NORMAL_VIP_POST         => 'VIP社区',
        self::RESOURCE_TYPE_NORMAL_COINS_POST       => '金币社区',
        self::RESOURCE_TYPE_VIP_PICTURE             => 'VIP图集',
        self::RESOURCE_TYPE_COINS_PICTURE           => '金币图集',
        self::RESOURCE_TYPE_VIP_NOVEL               => 'VIP小说',
        self::RESOURCE_TYPE_COINS_NOVEL             => '金币小说',
        self::RESOURCE_TYPE_VIP_MH                  => 'VIP漫画',
        self::RESOURCE_TYPE_COINS_MH                => '金币漫画',
        self::RESOURCE_TYPE_VIP_CARTOON             => 'VIP动漫',
        self::RESOURCE_TYPE_COINS_CARTOON           => '金币动漫',
        self::RESOURCE_TYPE_VIP_SEED                => 'VIP种子',
        self::RESOURCE_TYPE_COINS_SEED              => '金币种子',
        self::RESOURCE_TYPE_PORN_GAME               => '黄游',
        self::RESOURCE_TYPE_LIVE_VIP                => 'VIP直播',
        self::RESOURCE_TYPE_LIVE_COINS              => '金币直播',
        self::RESOURCE_TYPE_AI_HL                   => 'AI图片换脸',
        self::RESOURCE_TYPE_ZT_MV                   => '正太资源',
        self::RESOURCE_TYPE_AI_MAGIC                => "AI图生视频",
        self::RESOURCE_TYPE_AI_TY                   => "AI脱衣",
    ];
    const RESOURCE_TYPE_NUM = [
        self::RESOURCE_TYPE_SYSTEM ,
        self::RESOURCE_TYPE_NORMAL_VIP_VIDEO,
        self::RESOURCE_TYPE_NORMAL_COINS_VIDEO,
        self::RESOURCE_TYPE_AW_VIP_VIDEO,
        self::RESOURCE_TYPE_AW_COINS_VIDEO,
        self::RESOURCE_TYPE_NORMAL_VIP_POST,
        self::RESOURCE_TYPE_NORMAL_COINS_POST,
        self::RESOURCE_TYPE_VIP_PICTURE,
        self::RESOURCE_TYPE_COINS_PICTURE,
        self::RESOURCE_TYPE_VIP_NOVEL,
        self::RESOURCE_TYPE_COINS_NOVEL,
        self::RESOURCE_TYPE_VIP_MH,
        self::RESOURCE_TYPE_COINS_MH,
        self::RESOURCE_TYPE_VIP_CARTOON,
        self::RESOURCE_TYPE_COINS_CARTOON,
        self::RESOURCE_TYPE_VIP_SEED,
        self::RESOURCE_TYPE_COINS_SEED,
        self::RESOURCE_TYPE_PORN_GAME,
        self::RESOURCE_TYPE_LIVE_VIP,
        self::RESOURCE_TYPE_LIVE_COINS,
        self::RESOURCE_TYPE_AI_HL,
        self::RESOURCE_TYPE_ZT_MV,
        self::RESOURCE_TYPE_AI_MAGIC,
        self::RESOURCE_TYPE_AI_TY,
    ];

    //视频资源类型
    const MV_RESOURCE_TYPE = [
        self::RESOURCE_TYPE_NORMAL_VIP_VIDEO => 1,
        self::RESOURCE_TYPE_NORMAL_COINS_VIDEO => 2,
        self::RESOURCE_TYPE_AW_VIP_VIDEO => 3,
        self::RESOURCE_TYPE_AW_COINS_VIDEO => 4,
    ];

    const PRIVILEGE_TYPE_VIEW = 1;
    const PRIVILEGE_TYPE_DOWNLOAD = 2;
    const PRIVILEGE_TYPE_DISCOUNT = 4;
    const PRIVILEGE_TYPE_UNLOCK = 5;
    const PRIVILEGE_TYPE_SETTING = 6;
    const PRIVILEGE_TYPE_FEED = 7;
    const PRIVILEGE_TYPE_GIFT_PRODUCT = 8;
    const PRIVILEGE_TYPE_MV_NO_AD = 9;
    const PRIVILEGE_TYPE_POST_CREATE = 10;
    const PRIVILEGE_TYPE = [
        self::PRIVILEGE_TYPE_VIEW => '观看',
        self::PRIVILEGE_TYPE_DOWNLOAD => '下载',
        self::PRIVILEGE_TYPE_DISCOUNT => '折扣',
        self::PRIVILEGE_TYPE_UNLOCK => '免费解锁',
        self::PRIVILEGE_TYPE_SETTING => '设置个人信息',
        self::PRIVILEGE_TYPE_FEED => '使用工单',
        self::PRIVILEGE_TYPE_MV_NO_AD => '视频免广告',
        self::PRIVILEGE_TYPE_POST_CREATE => '发帖',
    ];
    const PRIVILEGE_TYPE_NUM = [
        self::PRIVILEGE_TYPE_VIEW,
        self::PRIVILEGE_TYPE_DOWNLOAD,
        self::PRIVILEGE_TYPE_DISCOUNT,
        self::PRIVILEGE_TYPE_UNLOCK,
        self::PRIVILEGE_TYPE_SETTING,
        self::PRIVILEGE_TYPE_FEED,
        self::PRIVILEGE_TYPE_MV_NO_AD,
        self::PRIVILEGE_TYPE_POST_CREATE,
    ];

    const PRIVILEGE_DAY_TYPE = [
        self::PRIVILEGE_TYPE_DOWNLOAD,
        self::PRIVILEGE_TYPE_UNLOCK
    ];

    /**
     * @return array
     */
    public static function getDataList()
    {
        return self::get([
            'id',
            'resource_type',
            'privilege_type',
            'value'
        ])->mapWithKeys(function ($item) {
            return [$item->id => $item->id . '|' . $item->resource_type_str . '|' . $item->privilege_type_str];
        })->toArray();
    }

    /**
     * @return array
     */
    public static function getDataListForProduct()
    {
        return self::get(['id', 'resource_type', 'privilege_type', 'value'])->toArray();
    }

}
