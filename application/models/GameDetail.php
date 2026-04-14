<?php


use Illuminate\Database\Eloquent\Model;
use service\GameService;

/**
 * class GameDetailModel
 *
 * @property int $id
 * @property int $uid
 * @property string $action
 * @property string $day
 * @property string $value
 * @property string $note
 * @property string $description
 * @property int $status
 * @property int $type
 *
 * @author xiongba
 * @date 2021-05-24 15:47:22
 *
 * @mixin \Eloquent
 */
class GameDetailModel extends Model
{

    protected $table = "game_detail";

    protected $primaryKey = 'id';

    protected $fillable = ['uid', 'action', 'day', 'value', 'note', 'description', 'status', 'type'];

    protected $guarded = 'id';

    public $timestamps = false;

    const TYPE_GAVE = 1;//送现金
    const TYPE_VIP = 2;//送vip
    const TYPE_TRANS = 3;//划转入账
    const TYPE_GIFT = 4;//送现金 3
    const TYPE_DEFAULT = 0;

    /**
     * 游戏账号 操作流水
     * @param $uid
     * @param $action
     * @param $vlue
     * @param $msg
     * @param null $desciption
     * @param int $status
     * @param int $type
     * @return bool
     */
    static function addData($uid, $action, $vlue, $msg, $desciption = null, $status = 0, $type = self::TYPE_DEFAULT)
    {
        return self::insert([
            'uid'         => $uid,
            'action'      => $action,
            'day'         => date('Y-m-d'),
            'value'       => $vlue,
            'note'        => $msg,
            'description' => json_encode($desciption),
            'status'      => $status ? 1 : 0,
            'type'        => (int)$type
        ]);
    }

    static function getHasGameOrder($member, $limit = 2000)
    {
        $uuid = $member['uuid'] ?? '';
        $uid = $member['uid']??0;
        if (empty($uuid)) {
            return false;
        }
        $gameCharge = cached('u:ogame:' . $uid)->expired(300)
            ->serializerJSON()
            ->fetch(function () use ($uuid) {
                $where = [
                    'uuid'       => $uuid,
                    'status'     => OrdersModel::STATUS_SUCCESS,
                    'order_type' => OrdersModel::TYPE_GAME
                ];
                return OrdersModel::where($where)->sum('pay_amount');
            });
        return ($gameCharge >= ($limit * 100));
    }
    static function sendOver24HourActive($member, $value = 3)
    {
        $uid = $member['uid'] ?? 0;
        $regdate = $member['regdate'] ?? 0;
        if (empty($uid) || empty($regdate)) {
            return false;
        }
        $now = TIMESTAMP;
        if(($now-$regdate)<86400){
            return false;
        }
        $key = 'game:act:' . $uid.':'.self::TYPE_GIFT;
        $hasSendGift = self::checkHasActive($uid, $value,self::TYPE_GIFT);
        if (!$hasSendGift) {
            list($f,$m) = (new GameService())->transfer($uid, $value, 'add', "超24小时活动送{$value} ~", GameDetailModel::TYPE_GIFT);
            if($f){
                redis()->set($key,1);
            }
        }
        return true;
    }
    static function sendActive($member, $value = 3)
    {
        $uid = $member['uid'] ?? 0;
        $hasPhone = $member['phone'] ?? '';
        if (empty($uid) || empty($hasPhone)) {
            return false;
        }
        $key = 'game:act:' . $uid.':'.self::TYPE_GIFT;
        $hasSendGift = self::checkHasActive($uid, $value,self::TYPE_GAVE);
        if (!$hasSendGift) {
            list($f,$m) = (new GameService())->transfer($uid, $value, 'add', "{$value} 活动送~ phone:{$hasPhone}", GameDetailModel::TYPE_GAVE);
            if($f){
                redis()->set($key,1);
            }
        }
        return true;
    }

    /**
     * @param $uid
     * @param $value
     * @param int $status
     * @param int $type
     * @return mixed
     */
    static function checkHasActive($uid, $value,$type=0, $status = 1)
    {
        return cached('game:act:' . $uid.':'.$type)->serializerJSON()->expired(86400)->fetch(function () use (
            $uid,
            $value,
            $status,
            $type
        ) {
            $has = self::where([
                ['uid', '=', $uid],
                ['value', '=', $value],
                ['status', '=', $status],
                ['type', '=', $type],
            ])->count(['id']);
            return $has;
        });
    }

    /**
     * check 是否送礼
     * @param $uid
     * @param $type
     * @param int $status
     * @param null $value
     * @return mixed
     */
    static function checkHasGift($uid, $type, $status = 1, $value = null)
    {
        return cached("g:g:{$uid}:{$type}:{$value}")->serializerJSON()
            ->expired(3600)
            ->fetch(function () use (
                $uid,
                $value,
                $type,
                $status
            ) {
                $where = [
                    ['uid', '=', $uid],
                    ['type', '=', $type],
                    ['status', '=', $status],
                ];
                if ($value) {
                    $where[] = ['value', '=', $value];
                }
                $has = self::where($where)->count(['id']);
                return $has;
            });
    }

    /**
     * 送一次vip
     * @param $uid
     * @param int $value
     * @return bool
     */
    static function sendVip($uid, $value = 0)
    {
        if (!$uid || !$value) {
            return false;
        }
        $hasSendGift = self::checkHasGift($uid, self::TYPE_VIP, 1,$value);
        if (!$hasSendGift) {
            $member = MemberModel::where('uid', $uid)->first();
            if(is_null($member)){
                return false;
            }
            $use_exp = max($member->expired_at, time());
            $set_exp = $use_exp + $value * 86400;
            MemberModel::where('uid', $uid)->update(['expired_at' => $set_exp]);
            MemberModel::clearFor($member);
        }
        return true;
    }

}
