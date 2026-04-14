<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class UserProxyCashBackDetailModel
 *
 * @property int $id
 * @property int $type 类型 推广|提现|退回
 * @property string $order_id 关联订单
 * @property int $pay_amount 实际付款分
 * @property int $from_aff 那位用户为此用户增加的
 * @property int $aff 用户aff
 * @property int $amount 分为单位
 * @property string $descp 备注
 * @property int $created_at
 * @property string $rate 费率
 *
 * @mixin \Eloquent
 */
class UserProxyCashBackDetailModel extends Model
{

    protected $table = "user_proxy_cash_back_detail";

    protected $primaryKey = 'id';

    protected $fillable = [
        'type',
        'order_id',
        'pay_amount',
        'from_aff',
        'aff',
        'amount',
        'descp',
        'created_at',
        'rate'
    ];

    protected $guarded = 'id';

    public $timestamps = false;

    const TUI_RATE = 0.3;//推广提成比例

    const TYPE_TUI = 0;
    const TYPE_WITHDRAW = 1;
    const TYPE_BACK = 2;

    const TYPE = [
        self::TYPE_TUI      => '推广提成',
        self::TYPE_WITHDRAW => '推广抵扣',
        self::TYPE_BACK     => '提现退回'
    ];

    /**
     * 代理 收益支持明细日志
     *
     * @param $type
     * @param array $data
     * @param $descp
     * @return bool
     */
    static function insertProxyDetail($type, array $data, $descp)
    {
        $_t = [
            'type'       => $type,
            'order_id'   => '',
            'pay_amount' => 0,
            'from_aff'   => 0,
            'aff'        => 0,
            'amount'     => 0,
            'descp'      => $descp,
            'created_at' => TIMESTAMP,
            'rate'       => self::TUI_RATE
        ];
        $temPdata = array_merge($_t, $data);
        //errLog(var_export([$_t,$data,$temPdata]));
        return self::insert($temPdata);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function member()
    {
        return self::hasOne(MemberModel::class, 'aff', 'aff');
    }
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function fmeber()
    {
        return self::hasOne(MemberModel::class, 'aff', 'from_aff');
    }


}
