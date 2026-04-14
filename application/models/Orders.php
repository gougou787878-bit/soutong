<?php

/**
 * class OrdersModel
 *
 * @property int $amount 订单金额，单位分
 * @property string $app_order 第三方订单号
 * @property string $build_id 渠道
 * @property string $channel 订单支付渠道来源1-自有渠道
 * @property int $created_at
 * @property string $desc_img 上传成功截图
 * @property string $descp 我方对订单简单说明
 * @property int $expired_at 过期时间
 * @property int $id
 * @property string $msg 支付接口返回的状态说明
 * @property string $oauth_type ios/android
 * @property string $order_id 唯一订单号
 * @property int $order_type 订单类型 同商品类型
 * @property int $pay_amount 实付金额,单位分
 * @property string $pay_type online线上充值/agent代理充值
 * @property string $pay_url 支付链接
 * @property string $payway 支付方式
 * @property int $product_id
 * @property int $status 订单状态: 0-未支付，2-支付中，3-支付完成，99-交易失败
 * @property int $updated_at
 * @property string $uuid 用户uuid标识
 *
 * @property ProductModel $product;
 *
 * @author xiongba
 * @date 2020-03-07 11:01:57
 *
 * @mixin \Eloquent
 */
class OrdersModel extends EloquentModel
{
    protected $table = 'orders';

    const ORDER_LIST = 'order_list_';

    const PAY_WAY_MAP = array(
        'payway_wechat' => 'wechat',
        'payway_bank'   => 'bankcard',
        'payway_alipay' => 'alipay',
        'payway_huabei' => 'huabei',
        'payway_visa'   => 'visa',
        'payway_agent'  => 'agent',
    );

    const TYPE_VIP = 1;//vip订单
    const TYPE_GLOD = 2;//金币订单
    const TYPE_GAME = 3;//游戏订单

    const TYPE = [
        self::TYPE_VIP  => '会员',
        self::TYPE_GLOD => '金币',
        self::TYPE_GAME => '游戏',
    ];
    const PAY_WAY_MAP_NEW = array(
        'pw' => 'wechat',
        'pb' => 'bankcard',
        'pa' => 'alipay',
        'ph' => 'huabei',
        'pv' => 'visa',
        'pg' => 'agent',
        'ps' => 'ecny',
    );

    const STATUS_SUCCESS = 3; // 支付完成
    const STATUS_WAIT = 0; // 代支付
    const STATUS_PAYING = 2, STATUS_FAILED = 99;
    const STATUS = [
        self::STATUS_WAIT    => '未支付',
        self::STATUS_PAYING  => '支付中',
        self::STATUS_SUCCESS => '支付完成',
        self::STATUS_FAILED  => '交易失败',
    ];

    const STATUS_NAME = [
        '0' => '未支付',
        '3' => '支付完成',
    ];

    const PAY_WAY_MAP_CH = [
        'wechat'   => '微信',
        'bankcard' => '银行卡',
        'alipay'   => '支付宝',
        'huabei'   => '花呗',
        'visa'     => 'visa'
    ];


    protected $fillable = [
        'uuid',
        'product_id',
        'order_id',
        'order_type',
        'app_order',
        'descp',
        'amount',
        'pay_amount',
        'payway',
        'pay_url',
        'status',
        'msg',
        'channel',
        'updated_at',
        'created_at',
        'expired_at',
        'is_callback',
        'pay_type',
        'desc_img',
        'gift_diamond',
        'oauth_type',
        'build_id'
    ];

    public static function hasChargeVip($uuid)
    {
        return cached('v2:user:viporder:' . $uuid)
            ->setSaveEmpty(true)
            ->expired(7200)
            ->serializerPHP()
            ->fetch(function () use ($uuid) {
                $where = [
                    'uuid'       => $uuid,
                    'status'     => self::STATUS_SUCCESS,
                    'order_type' => self::TYPE_VIP
                ];
                return self::where($where)->first();
            });
    }
    public static function hasCharge($uuid)
    {
        return cached('v2:user:order:' . $uuid)
            ->setSaveEmpty(true)
            ->expired(7200)
            ->serializerPHP()
            ->fetch(function () use ($uuid) {
                $where = [
                    'uuid'       => $uuid,
                    'status'     => self::STATUS_SUCCESS
                ];
                return self::where($where)->first();
            });
    }

    public static function clearFor($memberInfo)
    {
        $uuid = is_object($memberInfo) ? $memberInfo->uuid : $memberInfo['uuid'];
        $redisKey1 = \OrdersModel::ORDER_LIST."1_{$uuid}_0_24";
        $redisKey2 = \OrdersModel::ORDER_LIST."2_{$uuid}_0_24";
        $redisKey3 = \OrdersModel::ORDER_LIST."3_{$uuid}_0_24";
        redis()->del($redisKey1);
        redis()->del($redisKey2);
        redis()->del($redisKey3);
        return  cached('v2:user:viporder:' . $memberInfo['uuid'])->clearCached();
    }

    public function getOldPayType()
    {
        return array_flip(self::PAY_WAY_MAP_NEW)[$this->payway] ?? 'unknow';
    }

    public static function queryMember($uuid)
    {
        return self::where(['uuid' => $uuid]);
    }

    public function product()
    {
        return $this->hasOne(ProductModel::class, 'id', 'product_id');
    }

    public function withMember()
    {
        return $this->hasOne(MemberModel::class, 'uuid', 'uuid');
    }
    /*protected function product(){
        return $this->belongsTo(ProductModel::class, 'id', 'product_id');
    }*/


    /**
     * 7 天内的金币充值总量
     * @param $uuid
     * @return float|int
     */
    public static function check7DaysOrder($uuid)
    {
        $where = [
            'uuid'       => $uuid,
            'status'     => self::STATUS_SUCCESS,
            'order_type' => self::TYPE_GLOD
        ];
        $total7DaysPayAmount = self::where($where)->where('updated_at', '>=', strtotime('-7 days'))->sum('pay_amount');
        return $total7DaysPayAmount / 100;

    }
    public static function checkGameOrder($uuid)
    {
        $where = [
            'uuid'       => $uuid,
            'status'     => self::STATUS_SUCCESS,
            'order_type' => self::TYPE_GAME
        ];
        return self::where($where)->count();
    }

    /**
     * @param $uuid
     * @return array
     */
    static function limitGameOrder($uuid)
    {
        $where = [
            ['uuid', '=', $uuid],
            ['order_type', '=', self::TYPE_GAME],
            ['created_at', '>=', strtotime(date('Y-m-d', TIMESTAMP))],
        ];
        $orderGame = self::where($where)->select(['id', 'created_at', 'status'])->get();
        $total = collect($orderGame)->count();
        $totalNoPay = collect($orderGame)->where('status', '=', 0)->count();
        $totalNoPay1H = collect($orderGame)->where('status', '=', 0)
            ->where('created_at', '>=', strtotime("-1 hours"))->count();
        return [$total, $totalNoPay, $totalNoPay1H];
    }

    /**
     * @param array $where
     * @param int $limit
     * @return bool
     * @example  条件下 某个用户类型订单在5个小时内超过4笔 就提示验证码
     */
    static function verifyFrequency(array $where , int $limit )
    {
        /* $where = [
             ['uuid', '=', $uuid],
             ['order_type', '=', self::TYPE_GAME],
             ['created_at', '>=', strtotime(date('Y-m-d', TIMESTAMP))],
         ];*/
        $order = self::where($where)->select(['id', 'created_at', 'status'])->orderByDesc('id')->limit($limit)->get();
        return $order->where('status', '=', 0)->count() >= $limit;
    }

    public static function tenWaitNum($uuid){
        return self::query()->where('uuid', $uuid)
            ->where('status', self::STATUS_WAIT)
            ->where('created_at', '>=', strtotime('-10 minutes'))
            ->count('id');
    }
}