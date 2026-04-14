<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class ExchangeCodeModel
 *
 * @property int $id
 * @property string $code 兑换码
 * @property int $type 兑换码类型 1.会员 2 金币
 * @property int $validity 有效期
 * @property string $number 数量
 * @property string $ext 数量
 * @property int $serial_number  流水号
 * @property int $uid 使用者uid
 * @property int $status 状态， 0 失效， 1 正常， 2 已使用
 * @property string $created_at
 * @property string $updated_at
 *
 * @author xiongba
 * @date 2020-03-04 20:19:12
 *
 * @mixin \Eloquent
 */
class ExchangeCodeModel extends Model
{

    protected $table = "exchange_code";

    protected $primaryKey = 'id';

    protected $fillable = [
        'code',
        'type',
        'validity',
        'number',
        'uid',
        'status',
        'ext',
        'serial_number',
        'created_at',
        'updated_at'
    ];

    protected $guarded = 'id';

    const STATUS_FAIL = 0; // 失效
    const STATUS_SUCCESS = 1; // 正常
    const STATUS_USED = 2; // 已使用
    const STATUS = [
        self::STATUS_FAIL    => '失效',
        self::STATUS_SUCCESS => '正常',
        self::STATUS_USED    => '已使用',
    ];


    const TYPE_VIP = 1; // 会员
    const TYPE_COINS = 2; // 金币
    const TYPE_CAR = 3; // 坐骑
    const TYPE_LIANG = 4; // 靓号

    const TYPE = [ // 兑换码类型
        self::TYPE_VIP   => '会员',
        self::TYPE_COINS => '金币',
        self::TYPE_CAR   => '坐骑',
        self::TYPE_LIANG => '靓号'
    ];


    public $timestamps = true;


    /**
     * @return int
     * @see https://blog.csdn.net/u013303402/article/details/60139840
     */
    static function uniqid()
    {
        $arr = gettimeofday();
        $number = ($arr['sec'] * 100000 + $arr['usec'] / 10);
        $tmp = $number & 0x7FFFFFFF;
        $logId = $tmp | 0x80000000;
        return $logId;
    }

    /**
     * 默认送三天会员临时
     * @param int $type
     * @param int $hour
     * @return ExchangeCodeModel|Model
     */
    static function createCode($type=1,$hour = 72){
        $post['code'] = substr(md5(self::uniqid()), 5, 10);
        $post['serial_number'] = 6;
        $post['status'] = ExchangeCodeModel::STATUS_SUCCESS;
        $post['type'] = $type;
        $post['number'] = $hour;
        $post['validity'] = strtotime("+1 month");
        $post['created_at'] = date("Y-m-d H:i:s");
        $post['updated_at'] = date("Y-m-d H:i:s");
        $post['ext'] = 0;
        return self::create($post);
    }

}
