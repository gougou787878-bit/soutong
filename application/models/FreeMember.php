<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class FreeMemberModel
 * @property int $id null
 * @property int $uid 账号
 * @property int $created_at
 * @property int $expired_at
 * @property int $type
 * @package App\models;
 * @mixin \Eloquent
 */
class FreeMemberModel extends Model
{

    protected $table = "free_member";

    protected $primaryKey = 'id';

    protected $fillable = [
        'uid',
        'type',
        'created_at',
        'expired_at'
    ];

    const FREE_DAY_MV = 1;
    const FREE_DAY_MV_ADD_COMMUNITY = 2;
    const FREE_TIPS = [
        self::FREE_DAY_MV => '通卡',
        self::FREE_DAY_MV_ADD_COMMUNITY => '通卡plus',
    ];

    const REDIS_FREE_DAY_TYPE = 'user:fre:mv:com:%s:%s';

    protected $guarded = ['id'];

    public $timestamps = false;

    /**
     * 是否是免费用户
     * @param $uid
     * @param int $type 1mv 2mv+community
     * @return bool
     */
    public static function isFreeMember($uid, $type = self::FREE_DAY_MV)
    {
        if (false){
            $model = self::where('uid', $uid)->first();
            if (is_null($model) || $model->expired_at < time() || !in_array($model->type, array_unique([
                    self::FREE_DAY_MV_ADD_COMMUNITY,
                    $type
                ]))) {
                $expired_at = 0;
            } else {
                $expired_at = $model->expired_at;
            }

            if ($expired_at < time()) {
                $isFreeMember = false;
            } else {
                $isFreeMember = true;
            }

            return $isFreeMember;
        }
        static $data = [];
        $sk = "$uid-$type";
        if (isset($data[$sk])){
            return $data[$sk];
        }

        $s = sprintf(self::REDIS_FREE_DAY_TYPE,$uid,$type);
        $expired_at = redis()->get($s);
        if ($expired_at === false || $expired_at === null) {
            /** @var self $model */
            $model = self::where('uid', $uid)->first();
            if (is_null($model) || $model->expired_at < time()) {
                $expired_at = 0;
                $ttl = 86400;
            } else {
                if ($model->type == self::FREE_DAY_MV_ADD_COMMUNITY || $model->type == $type){
                    $expired_at = $model->expired_at;
                    $ttl = $model->expired_at - $model->created_at;
                }else{
                    $expired_at = 0;
                    $ttl = 86400;
                }
            }
            redis()->set($s, $expired_at, $ttl);
        }
        if ($expired_at < time()) {
            $isFreeMember = false;
        } else {
            $isFreeMember = true;
        }
        $data[$sk] = $isFreeMember;

        return $isFreeMember;
    }

    /**
     * 是否是免费用户
     * @param $uid
     * @return bool
     */
    public static function isFreeMember_old($uid)
    {
        static $isFreeMember = null;
        if (!isset($isFreeMember)) {
            $s = 'user:freemv:' . $uid;
            $expired_at = redis()->get($s);
            if ($expired_at === false || $expired_at === null) {
                /** @var self $model */
                $model = self::where('uid', $uid)->first();
                if (is_null($model) || $model->expired_at < time()) {
                    $expired_at = 0;
                    $ttl = 86400;
                } else {
                    $expired_at = $model->expired_at;
                    $ttl = $model->expired_at - $model->created_at;
                }
                redis()->set($s, $expired_at, $ttl);
            }
            if ($expired_at < time()) {
                $isFreeMember = false;
            } else {
                $isFreeMember = true;
            }
        }
        return $isFreeMember;
    }

    public static function createInit($uid, $day, $type = self::FREE_DAY_MV)
    {
        /** @var FreeMemberModel $model */
        $model = self::where('uid', $uid)->first();
        $ttl = max($day, 0) * 86400;
        if (empty($model)) {
            $model = self::create([
                'uid' => $uid,
                'type' => $type,
                'created_at' => time(),
                'expired_at' => time() + $ttl
            ]);
        } else {
            //取消通卡
            if ($day == 0) {
                $model->expired_at = $model->created_at;
                $model->save();
                redis()->del(sprintf(self::REDIS_FREE_DAY_TYPE, $uid, self::FREE_DAY_MV));
                redis()->del(sprintf(self::REDIS_FREE_DAY_TYPE, $uid, self::FREE_DAY_MV_ADD_COMMUNITY));
            } else {
                $model->created_at = time();
                //类型不变或者新充值是通卡
                if ($model->type == $type || $type == self::FREE_DAY_MV) {
                    $model->expired_at = max($model->expired_at, time()) + $ttl;
                } else {
                    $model->expired_at = time() + $ttl;
                }
                $model->type = $type;
                $model->save();
            }
        }
        redis()->del(sprintf(self::REDIS_FREE_DAY_TYPE, $uid, self::FREE_DAY_MV));
        redis()->del(sprintf(self::REDIS_FREE_DAY_TYPE, $uid, self::FREE_DAY_MV_ADD_COMMUNITY));
        return true;
    }

    public static function createInit_Old($uid, $day)
    {
        $model = self::where('uid', $uid)->first();
        $ttl = max($day, 0) * 86400;
        if (empty($model)) {
            $model = self::create([
                'uid' => $uid,
                'created_at' => time(),
                'expired_at' => time() + $ttl
            ]);
        } else {
            $model->created_at = time();
            $model->expired_at = max($model->expired_at, time()) + $ttl;
            $model->save();
        }
        redis()->set('user:freemv:' . $uid, $model->expired_at, $ttl);
        return true;
    }


}
