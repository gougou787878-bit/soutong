<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class MemberTalkModel
 *
 * @property int $uid
 * @property int $uuid
 * @property string $pwd 暗号
 * @property int $province 用户省份
 * @property string $province_str 用户省份
 * @property int $hide_province 隐藏位置
 * @property string $age_range 年龄
 * @property string $tag 标签
 * @property int $expired_at 过期时间
 * @property int $hope_province 希望对方的省份
 * @property string $hope_province_str 希望对方的省份
 * @property int $hope_age_range 希望对方的省份
 * @property int $created_at 创建时间
 * @property int $updated_at 修改时间
 * @property int $match_status 是否进行匹配
 *
 * @property int $left_time 剩余时间
 *
 * @author xiongba
 * @date 2021-06-26 16:39:08
 *
 * @mixin \Eloquent
 */
class MemberTalkModel extends Model
{

    protected $table = "member_talk";

    protected $primaryKey = 'uid';

    public $incrementing = false;

    protected $fillable = ['uid', 'uuid', 'pwd', 'province', 'province_str', 'hide_province', 'age_range', 'tag',
                           'expired_at', 'hope_province', 'hope_province_str',
                           'hope_age_range', 'created_at', 'updated_at', 'match_status'];

    protected $guarded = 'uid';

    const MATCH_STATUS_NO = 0;
    const MATCH_STATUS_YES = 1;
    const MATCH_STATUS = [
        self::MATCH_STATUS_NO  => '关闭',
        self::MATCH_STATUS_YES => '开启',
    ];


    public $timestamps = false;

    protected $appends = ['left_time', 'is_timeout'];

    public static function createInit(int $uid, string $uuid, $expired_at = 0 , $initArea = true)
    {
        if (empty($expired_at)) {
            $expired_at = 0;//time() + 1800;
        }

        $adcode = 0;
        $pStr = '';
        if ($initArea) {
            $area = AreaModel::getPosByIp();
            if (!empty($area)) {
                $pStr = $area->name;
                $adcode = $area->adcode;
            }
        }

        return self::create([
            'uid'               => $uid,
            'uuid'              => $uuid,
            'pwd'               => '',
            'province'          => $adcode,
            'province_str'      => $pStr,
            'hide_province'     => 0,
            'hope_province'     => 0,
            'hope_province_str' => '',
            'age_range'         => '',
            'hope_age_range'    => '',
            'expired_at'        => $expired_at,
            'match_status'      => 0,
            'created_at'        => time(),
            'updated_at'        => time()
        ]);
    }


    public function getLeftTimeAttribute(): int
    {
        $expired_at = $this->attributes['expired_at'] ?? 0;
        $left_time = $expired_at - time();
        if ($left_time <= 0) {
            return 0;
        }
        return $left_time;
    }

    public function getIsTimeoutAttribute(): int
    {
        $left_time = $this->getLeftTimeAttribute();
        if ($left_time <= 60) {
            return 1;
        }
        return 0;
    }


}
