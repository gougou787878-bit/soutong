<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class MvUploadIpInfoModel
 *
 * @property int $id
 * @property int $uid
 * @property string $nickname
 * @property int $is_up
 * @property int $number
 * @property string $ip
 * @property string $address
 * @property string $day
 *
 * @author xiongba
 * @date 2021-05-18 10:45:30
 *
 * @mixin \Eloquent
 */
class MvUploadIpInfoModel extends Model
{

    protected $table = "mv_upload_ip_info";

    protected $primaryKey = 'id';

    protected $fillable = ['uid', 'nickname', 'is_up', 'ip', 'address', 'day', 'number'];

    protected $guarded = 'id';

    public $timestamps = false;

    public static function addData(MemberModel $memberData)
    {
        $day = date('Y-m-d');
        $where = [
            'uid' => $memberData->uid,
            'day' => $day,
        ];
        if (self::where($where)->exists()) {
            return self::where($where)->increment('number', 1);
        }
        $where['nickname'] = $memberData->nickname;
        $where['is_up'] = $memberData->auth_status;
        $where['ip'] = USER_IP;
        $where['address'] = UserWithdrawModel::convertIPToAddress($where['ip']);
        $where['number'] = 1;
        return self::insert($where);
    }

    /**
     * @param $uid
     * @param null $day
     * @return int
     */
    static function checkUserNum($uid, $day = null)
    {
        is_null($day) && $day = date('Y-m-d');
        $where = [
            'uid' => $uid,
            'day' => $day,
        ];
        /** @var MvUploadIpInfoModel $row */
        $row = self::where($where)->first();
        return is_null($row) ? 0 : $row->number;

    }

    /**
     * @param null $ip
     * @param null $day
     * @return int
     */
    static function checkIPNum($ip = null, $day = null)
    {
        is_null($day) && $day = date('Y-m-d');
        is_null($ip) && $ip = USER_IP;
        $where = [
            'ip'  => $ip,
            'day' => $day,
        ];
        /** @var MvUploadIpInfoModel $row */
        $row = self::where($where)->first();
        return is_null($row) ? 0 : $row->number;
    }


}
