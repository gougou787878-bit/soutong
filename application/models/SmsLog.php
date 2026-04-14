<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class SmsLogModel
 *
 * @property int $id 
 * @property string $uuid 
 * @property string $prefix 国家码
 * @property string $mobile 手机号
 * @property int $code 短信验证码
 * @property string $ip IP地址
 * @property int $status 使用状态 0未用 1已用
 * @property int $type 类型 1绑定手机 2手机解绑 3找回账号
 * @property string $created_at 
 * @property string $updated_at 
 *
 * @date 2020-05-19 12:48:58
 *
 * @mixin \Eloquent
 */
class SmsLogModel extends Model
{

    protected $table = "sms_log";

    protected $primaryKey = 'id';

    protected $fillable = ['uuid', 'prefix', 'mobile', 'code', 'ip', 'status', 'type', 'created_at', 'updated_at'];

    protected $guarded = 'id';

    public $timestamps = false;

    const STATUS_USED = 1;
   const STATUS_WAIT = 0;

   const STAT = [
       self::STATUS_WAIT => '未启用',
       self::STATUS_USED => '已使用',
   ];


    /**
     * @param int $number 生成位数   建议 4- 6 位
     * @return int
     */
    static function genSmsCode($number = 4)
    {
        $chars = '01234567890123456789';
        // 位数过长重复字符串一定次数
        $chars = str_shuffle($chars);
        $str = substr($chars, 0, $number);
        return $str;

    }


}
