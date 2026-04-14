<?php

use tools\RedisService;

/**
 * class ProductModel
 *
 * @property int $coins 多少金币
 * @property int $created_at
 * @property string $description 产品描述
 * @property int $free_day
 * @property int $free_day_type 免费送类型 0普通 1视频 2视频+社区
 * @property int $free_coins 赠送多少金币
 * @property int $id
 * @property string $img 图片
 * @property string $pay_type 支付方式online线上支付agent代理支付
 * @property int $payway_alipay 支付宝支付1支持0不支持
 * @property int $payway_bank 银联支付1支持0不支持
 * @property int $payway_huabei 0支持1不支持
 * @property int $payway_agent 0支持1不支持
 * @property int $payway_ecny 0支持1不支持
 * @property int $payway_visa 01
 * @property int $payway_wechat 微信支付1支持0不支持
 * @property string $pname 产品名称
 * @property int $price 价格:单位分
 * @property int $promo_price 推广价格:单位分
 * @property int $sort_order 排序
 * @property int $status 产品状态 0:未上架 1:上架 2:下架
 * @property int $type 1:vip2金币
 * @property int $updated_at
 * @property int $message
 * @property int $ticket
 * @property string $url 跳转地址
 * @property int $valid_date VIP多少天
 * @property int $vip_level vip等级 0普通 1 月卡 2季卡 3 年卡
 * @property int $download_num 送下载次数
 * @property int $is_upgrade 是否是升级卡
 * @property string $corner_mark 角标
 * @property string $vip_icon VIP图标
 *
 * @author xiongba
 * @date 2020-03-07 11:21:00
 *
 * @mixin \Eloquent
 */
class ProductModel extends EloquentModel
{
    protected $table = 'product';

    const MONEY_PRODUCT_LIST = 'money_products_lists';
    const COINS_PRODUCT_LIST = 'coins_products_lists';

    protected $fillable = [
        'type',
        'pname',
        'img',
        'price',
        'promo_price',
        'valid_date',
        'coins',
        'free_coins',
        'free_day',
        'free_day_type',
        'pay_type',
        'status',
        'sort_order',
        'payway_wechat',
        'payway_bank',
        'payway_alipay',
        'payway_huabei',
        'payway_agent',
        'payway_visa',
        'payway_ecny',
        'description',
        'url',
        'updated_at',
        'created_at',
        'vip_level',
        'message',
        'ticket',
        'download_num',
        'is_upgrade',
        'corner_mark',
        'vip_icon',
    ];
    protected $guarded = 'id';
    public $timestamps = false;
    const PAY_TYPE_ONLINE = 'online';
    const PAY_TYPE_AGENT = 'agent';
    const PAY_TYPE = [
        self::PAY_TYPE_ONLINE=>'在线支付',
        self::PAY_TYPE_AGENT=>'代理支付',
    ];
    const STAT_OFF = 0;
    const STAT_ON = 1;
    const STAT = [
        self::STAT_OFF=>'失效',
        self::STAT_ON=>'启用',
    ];
    const TYPE_VIP = 1, TYPE_DIAMOND = 2,TYPE_GAME=3;
    const TYPE = [
        self::TYPE_VIP     => '会员',
        self::TYPE_DIAMOND => '金币',
        self::TYPE_GAME => '游戏',
    ];

    const UPGRADE_NO = 0;
    const UPGRADE_YES = 1;
    const UPGRADE_TIPS = [
        self::UPGRADE_NO=>'否',
        self::UPGRADE_YES=>'是',
    ];

    //vip等级 0普通 1 月卡 2季卡 3 年卡
    const VIP_LEVEL = MemberModel::USER_VIP_TYPE;
    const FREE_DAY_COMMON = 0;
    const FREE_DAY_MV = 1;
    const FREE_DAY_MV_ADD_COMMUNITY = 2;
    const FREE_TIPS = [
        self::FREE_DAY_COMMON => '普通',
        self::FREE_DAY_MV => '通卡',
        self::FREE_DAY_MV_ADD_COMMUNITY => '通卡plus',
    ];

    const PAY_WAY_ICON = [
        'payway_wechat' => '/upload/ads/20231113/2023111322212890286.png',
        'payway_bank'   => '/upload/ads/20231113/2023111322211069167.png',
        'payway_alipay' => '/upload/ads/20231113/2023111322194996934.png',
        'payway_visa'   => '/upload/ads/20231113/2023111322260442421.png',
        'payway_ecny'   => '/upload/ads/20231113/2023111322241723124.png',
        'payway_huabei' => '/upload/ads/20231113/2023111323020191054.png',
        'payway_agent'  => '/upload/ads/20231113/2023111322055640993.png'
    ];

    public function map()
    {
        return $this->hasMany(ProductRightMapModel::class, 'product_id', 'id');
    }

    public function productPrivilege(){
        return $this->hasMany(ProductPrivilegeModel::class, 'product_id', 'id');
    }

    static function clearRedisCache($type)
    {
        $redisKey = self::MONEY_PRODUCT_LIST . "_{$type}";
        $type && RedisService::del($redisKey);
        $key = \ProductModel::MONEY_PRODUCT_LIST . "_v2_{$type}";
        RedisService::del($key);
    }


    /**
     * @param int $type 要获取的类型
     * @return ProductModel[]|\Illuminate\Support\Collection
     * @author xiongba
     * @date 2020-03-12 15:50:25
     */
    public static function getByType($type)
    {
        $where = [
            'status' => 1,
            'type'   => $type,
        ];
        return \ProductModel::where($where)->orderBy('sort_order', 'asc')->get();
    }


    public function getPayWay()
    {
        $payWay = [];
        if ($this->payway_alipay) {
            $payWay[] = 'pa';
        }
        if ($this->payway_bank) {
            $payWay[] = 'pb';
        }
        if ($this->payway_visa) {
            $payWay[] = 'pv';
        }
        if ($this->payway_huabei) {
            $payWay[] = 'ph';
        }
        if ($this->payway_wechat) {
            $payWay[] = 'pw';
        }
        if ($this->payway_agent) {
            $payWay[] = 'pg';
        }
        if ($this->payway_ecny) {
            $payWay[] = 'ps';
        }
        return $payWay;
    }

    /**
     * @return array
     */
    public function getPayWayNew()
    {
        $payWay = [];
        if ($this->payway_alipay) {
            $payWay[] = [
                'type' => 'pa',
                'name' => '支付宝',
                'icon' => url_cover(self::PAY_WAY_ICON['payway_alipay']),
                'recommend' => 0,
            ];
        }
        if ($this->payway_bank) {
            $payWay[] = [
                'type' => 'pb',
                'name' => '银联',
                'icon' => url_cover(self::PAY_WAY_ICON['payway_bank']),
                'recommend' => 0,
            ];
        }
        if ($this->payway_visa) {
            $payWay[] = [
                'type' => 'pv',
                'name' => 'visa',
                'icon' => url_cover(self::PAY_WAY_ICON['payway_visa']),
                'recommend' => 0,
            ];
        }
        if ($this->payway_huabei) {
            $payWay[] = [
                'type' => 'ps',
                'name' => '花呗',
                'icon' => url_cover(self::PAY_WAY_ICON['payway_huabei']),
                'recommend' => 0,
            ];
        }
        if ($this->payway_wechat) {
            $payWay[] = [
                'type' => 'pw',
                'name' => '微信',
                'icon' => url_cover(self::PAY_WAY_ICON['payway_wechat']),
                'recommend' => 0,
            ];
        }
        if ($this->payway_agent) {
            $payWay[] = [
                'type' => 'pg',
                'name' => '人工充值',
                'icon' => url_cover(self::PAY_WAY_ICON['payway_agent']),
                'recommend' => 1,
            ];
        }
        if ($this->payway_ecny) {
            $payWay[] = [
                'type' => 'ps',
                'name' => '数字人名币',
                'icon' => url_cover(self::PAY_WAY_ICON['payway_ecny']),
                'recommend' => 0,
            ];
        }
        return $payWay;
    }

    public function toApiArray()
    {
        return [
            'img'         => url_ads($this->img),
            'op'          => (int)$this->price / 100,
            'p'           => (int)$this->promo_price / 100,
            'coins'       => (int)$this->coins,
            'free_coins'  => (int)$this->free_coins,
            'id'          => (int)$this->id,
            'pname'       => $this->pname,
            'pt'          => $this->pay_type,
            'description' => $this->description ?? '',
            'pw'          => $this->getPayWay()
        ];
    }

    /**
     * 活动商品  不计入渠道 或代理推广统计的
     * @return array
     */
    static function getActiveProductIDList(){
        $id = setting('active.product.id.list','');
        if($id){
            $data = explode('#',trim($id,'#'));
            return $data;
        }
        return [];
    }
    static function isActiveProduct($product_id){
       $data = self::getActiveProductIDList();
       if($data && in_array($product_id,$data)){
           return true;
       }
        return false;
    }

    /**
     * @return array
     */
    static function getAdminVIPDataList()
    {
        return self::where(['type' => self::TYPE_VIP])
            ->get(['id', 'pname'])
            ->mapWithKeys(function ($item) {
                return [$item->id => $item->id . '|' . $item->pname];
            })->toArray();
    }

    //后台使用
    public function getMapToString(){
        if($this->type == self::TYPE_VIP){
            if($mapData = $this->load('map')->map){
                $data = collect($mapData)->sortBy('id')->map(function ($item){
                    if($right =  $item->load('right')->right){
                        return "$right->id | $right->name | $right->desc";
                    }
                    return null;
                })->filter()->values()->toArray();
                return implode('<br/>',$data);
            }
        }
        return '';
    }

    //后台使用
    public function getPrivilegeToString(){
        if($this->type == self::TYPE_VIP){
            if($productPrivilegeData = $this->load('productPrivilege')->productPrivilege){
                $data = collect($productPrivilegeData)->sortBy('privilege_id')->map(function ($item){
                    if($privilege =  $item->load('privilege')->privilege){
                        return "{$privilege->id} | $privilege->resource_type_str | $privilege->privilege_type_str | $privilege->value";
                    }
                    return null;
                })->filter()->values()->toArray();
                return implode('<br/>',$data);
            }
        }
        return '';
    }
}