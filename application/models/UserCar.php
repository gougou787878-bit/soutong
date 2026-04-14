<?php

use Illuminate\Database\Capsule\Manager as DB;
use function Sodium\crypto_aead_chacha20poly1305_encrypt;

/**
 * class UsersCarModel
 *
 * @property int $addtime 添加时间
 * @property int $carid 坐骑ID
 * @property int $endtime 到期时间
 * @property int $id
 * @property int $status 是否启用
 * @property int $uid 用户ID
 *
 * @author xiongba
 * @date 2020-03-04 11:33:41
 *
 * @mixin \Eloquent
 */
class UserCarModel extends EloquentModel
{
    protected $table = 'users_car';
    protected $fillable = [
        'uid',
        'carid',
        'endtime',
        'status',
        'addtime'
    ];
    protected $guarded = [];

    public function withMember()
    {
        return $this->hasOne(MemberModel::class,'uid','uid');
    }
    public function withCar()
    {
        return $this->hasOne(CarModel::class,'id','carid');
    }

    /**
     * @param $carId
     * @param $uid
     * @return int
     */
    public function setUserCar($carId, $uid)
    {
        /** @var self $carModel */
        $cached = cached('car_' . $uid);
        $cached->clearCached();
        self::where(['uid' => $uid])->update(['status' => 0]);
        
        $carModel = self::where(['uid' => $uid, 'carid' => $carId])->first();
        if (empty($carModel)) {
            return false;
        }
        $carModel->status = 1;
        $carModel->save();
        if ($carModel->status == 1) {
            $cached->serializerJSON()
                ->expired($carModel->endtime - time())
                ->fetch(function () use ($carModel) {
                    return $carModel->toArray();
                });
        }
        return true;
    }

    /**
     * 新增用户坐骑
     *
     * @param $uid
     * @param $carid
     * @param array $data
     * @return \Illuminate\Database\Eloquent\Model|UserCarModel
     */
    public static function addUserCarData($uid, $carid, $data = [])
    {
        $insertData = [
            'uid'     => $uid,
            'carid'   => $carid,
            'status'  => 0,
            'endtime' => TIMESTAMP + 1 * 24 * 60 * 60,//默认1天
            'addtime' => TIMESTAMP
        ];
        if ($data) {
            $insertData = array_merge($insertData, $data);
        }
        return self::create($insertData);
    }
}