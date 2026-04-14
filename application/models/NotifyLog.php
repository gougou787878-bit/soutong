<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class NotifyLogModel
 *
 * @property int $create_at 通知时间
 * @property int $id
 * @property string $log 内容
 * @property string $value 类型对应关键值  m3u8|订单号|回调单号
 * @property string $type 通知类型
 *
 * @author xiongba
 * @date 2020-11-12 12:16:40
 *
 * @mixin \Eloquent
 */
class NotifyLogModel extends Model
{

    protected $table = "notify_log";

    protected $primaryKey = 'id';

    protected $fillable = ['create_at', 'log', 'type','value'];

    protected $guarded = 'id';

    public $timestamps = false;

    const TYPE_PAY = 'pay';
    const TYPE_M3U8 = 'm3u8';
    const TYPE_EXCHANGE = 'exchange';
    const TYPE = [
        self::TYPE_PAY      => '支付',
        self::TYPE_M3U8     => '切片',
        self::TYPE_EXCHANGE => '提现',
    ];

    protected $appends = ['create_str'];

    public function getCreateStrAttribute()
    {
        return date('Y-m-d H:i:s', $this->attributes['create_at'] ?? 0);
    }

    /**
     * @param string $orderid
     * @param string $log
     * @return Model|NotifyLogModel
     */
    public static function addByPay(string $orderid,string $log)
    {
        return self::addBy(self::TYPE_PAY,$orderid, $log);
    }

    /**
     * @param string $mvid
     * @param string $log
     * @return Model|NotifyLogModel
     */
    public static function addByM3u8(string $mvid,string  $log)
    {
        return self::addBy(self::TYPE_M3U8,$mvid, $log);
    }

    /**
     * @param string $withdraw_cash_id
     * @param string $log
     * @return Model|NotifyLogModel
     */
    public static function addByExchange(string $withdraw_cash_id,string $log)
    {
        return self::addBy(self::TYPE_EXCHANGE,$withdraw_cash_id, $log);
    }

    /**
     * @param $type
     * @param $value
     * @param $log
     * @return Model|NotifyLogModel
     */
    private static function addBy($type,$value,$log)
    {
        return self::create(
            [
                'create_at' => time(),
                'value'       => $value,
                'log'       => $log,
                'type'      => $type
            ]
        );
    }

}
