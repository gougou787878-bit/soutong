<?php


use App\library\helper\QueryHelper;
use Illuminate\Database\Eloquent\Model;

/**
 * class MvTicketModel
 *
 * @property int $id
 * @property int $uid
 * @property int $status 0 未使用 1 已使用 2 过期
 * @property int $expired_at
 * @property string $name 优惠券名称
 * @property int $create_at
 * @property int $used_at
 * @property int $mv_id
 * @property int $mv_uid
 * @property MvModel $mv
 * @property MemberModel $mvMember
 * @property MemberModel $member
 *
 *
 * @date 2021-04-12 18:11:31
 *
 * @mixin \Eloquent
 */
class MvTicketModel extends Model
{

    protected $table = "mv_ticket";

    protected $primaryKey = 'id';

    protected $fillable = ['uid', 'status', 'expired_at', 'name', 'add_at', 'used_at', 'mv_id', 'mv_uid'];

    protected $guarded = 'id';

    public $timestamps = false;
    const STATUS_INIT = 0;
    const STATUS_USED = 1;
    const STATUS_TIMEOUT = 2;

    const STATUS = [
        self::STATUS_INIT    => '未使用',
        self::STATUS_USED    => '已使用',
        self::STATUS_TIMEOUT => '已过期',
    ];
    protected $appends = ['expired_str', 'status_str'];


    public function getExpiredStrAttribute()
    {
        return date('Y-m-d', strtotime($this->expired_at . "230000"));
    }

    public function getStatusStrAttribute()
    {
        return self::STATUS[$this->status];
    }

    public function mv()
    {
        return self::hasOne(MvModel::class, 'id', 'mv_id');
    }

    public function mvMember()
    {
        return self::hasOne(MemberModel::class, 'uid', 'mv_uid');
    }

    public function member()
    {
        return self::hasOne(MemberModel::class, 'uid', 'uid');
    }

    /**
     * 我的观影券 票数
     * @param MemberModel $member
     * @return \Illuminate\Database\Eloquent\Builder|Model|object|null
     */
    public static function myLatestMvTicketRow(MemberModel $member)
    {
        $expired_at = date('Ymd', TIMESTAMP);
        return self::where([
            ['uid', '=', $member->uid],
            ['status', '=', \MvTicketModel::STATUS_INIT],
            ['expired_at', '>=', $expired_at],
        ])->orderBy('expired_at')->first();
    }
    /**
     * 我的观影券 票数
     * @param MemberModel $member
     * @return int
     */
    public static function myInitMvTicketNumber(MemberModel $member)
    {
        $expired_at = date('Ymd', TIMESTAMP);
        return self::where([
            ['uid', '=', $member->uid],
            ['status', '=', \MvTicketModel::STATUS_INIT],
            ['expired_at', '>=', $expired_at],
        ])->count('id');
    }

    /**
     * 用户的观影卷
     * @param MemberModel $member
     * @param $type
     * @return mixed
     */
    public static function myMvTicket(MemberModel $member, $type)
    {
        list($limit, $offset) = \helper\QueryHelper::restLimitOffset();
        $where = [
            ['uid', '=', $member->uid],
        ];
        $expired_at = date('Ymd', TIMESTAMP);
        if ($type == 'timeout') {
            $where[] = ['expired_at', '<', $expired_at];
            return self::select([
                'id',
                'name',
                'status',
                'expired_at'
            ])->where($where)->orWhere([
                ['uid', '=', $member->uid],
                ['status', '=', \MvTicketModel::STATUS_TIMEOUT]
            ])->limit($limit)->offset($offset)->get();
        } elseif ($type == 'used') {
            $where[] = ['status', '=', \MvTicketModel::STATUS_USED];

        }else{
            $where[] = ['status', '=', \MvTicketModel::STATUS_INIT];
            $where[] = ['expired_at', '>=', $expired_at];
        }
        //all
        return self::select([
            'id',
            'name',
            'status',
            'expired_at'
        ])->where($where)->limit($limit)->offset($offset)->get();

    }

    /**
     * @param $uid
     * @param null $expired_date
     * @param int $number
     * @param string $name
     * @return bool
     * 发放观影券
     */
    static function sendUserTicket($uid, $expired_date = null, $number = 100, $name = '免费观影券')
    {
        return self::newTicket($uid, $expired_date, $number, $name);
    }

    /**
     * 创建观影券
     * @param $expired_date 默认30
     * @param int $number
     * @param string $name
     * @return bool
     */
    static function newTicket($uid = 0, $expired_date = null, $number = 100, $name = '免费观影券')
    {
        $data = [];
        is_null($expired_date) && $expired_date = date('Ymd', strtotime("+ 30 days"));
        for ($i = 0; $i < $number; $i++) {
            $data[] = [
                'uid'        => $uid,
                'name'       => $name,
                'expired_at' => $expired_date,
                'add_at'     => date("Ymd"),
                'used_at'    => 0,
                'mv_id'      => 0,
                'mv_uid'     => 0,
                'status'     => \MvTicketModel::STATUS_INIT
            ];
        }
        //print_r($data);die;
        if ($data) {
            return self::insert($data);
        }
        return false;
    }


}
