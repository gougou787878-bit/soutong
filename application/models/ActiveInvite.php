<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class ActiveInviteModel
 *
 * @property int $id
 * @property int $uid
 * @property string $nickname
 * @property int $gain_day
 *
 * @author xiongba
 * @date 2022-07-14 16:28:58
 *
 * @mixin \Eloquent
 */
class ActiveInviteModel extends Model
{

    protected $table = "active_invite";

    protected $primaryKey = 'id';

    protected $fillable = ['uid', 'nickname', 'gain_day'];

    protected $guarded = 'id';

    public $timestamps = false;

    const  START_DAY = 20220804;
    const  END_DAY = 20220815;
    const ACTIVE_PRODUCT_ID = [76,77,78,95];//指定活动商品才算  修改这里

    static function addData($inviteByUid, $productData)
    {

        $date = date("Ymd");
        if (self::START_DAY <= $date && $date <= self::END_DAY) {
        } else {
            return;
        }
        $_pid = $productData['id']??0;
        if(!in_array($_pid,self::ACTIVE_PRODUCT_ID)){//指定活动商品才算
            return;
        }
        $incrDay = 3;//周年感恩卡
        $vip_level = $productData['vip_level'] ?? 0;
        if ($vip_level == MemberModel::VIP_LEVEL_LONG) {
            $incrDay = 30;
        } elseif ($vip_level == MemberModel::VIP_LEVEL_YEAR) {
            $incrDay = 12;
        } elseif ($vip_level == MemberModel::VIP_LEVEL_JIKA) {
            $incrDay = 5;
        }
        errLog("ivite-{$date}:{$inviteByUid} day:{$incrDay}");
        /** @var MemberModel $user */
        $user = MemberModel::find($inviteByUid);
        if (is_null($user)) {
            return;
        }
        try{
            $has = self::where(['uid' => $user->uid])->first();
            if (is_null($has)) {
                return self::insert([
                    'uid'      => $user->uid,
                    'nickname' => $user->nickname,
                    'gain_day' => $incrDay,
                ]);
            }
            return self::where('id', $has->id)->increment('gain_day', $incrDay);
        }catch (Exception $e){
            errLog("ivite-error{$e->getMessage()}");
        }
    }

    static function getData($limit = 8)
    {
        return cached("avt:invite:{$limit}")->setSaveEmpty(true)->fetchJson(function () use ($limit) {
            $data =  self::query()->orderByDesc('gain_day')->limit($limit)->get(['id','nickname','gain_day']);
            return $data?$data->toArray():[];
        }, 1600);
    }

    static function getRow($uid)
    {
        return cached("avt:row:{$uid}")->setSaveEmpty(true)->fetchJson(function () use ($uid) {
            $data= self::query()->where(['uid' => $uid])->first(['id','nickname','gain_day']);
            return $data?$data->toArray():[];
        }, 600);
    }

    static function clearCache($uid = 0)
    {
        redis()->del("avt:row:{$uid}");
        $flag = redis()->del("avt:invite:8");
        return $flag;
    }

    static function getID2Code($id)
    {
        $aff_code = generate_code($id);
        $verify_code = substr(sha1($id), -4);
        return "{$aff_code}-{$verify_code}";
    }

    static function getCode2ID($code)
    {
        if(empty($code)){
            return '';
        }

        list($aff_code, $verfiy_code) = explode('-', $code);
        $id = get_num($aff_code);
        $verify_code_id = substr(sha1($id), -4);
        if ($verify_code_id == $verfiy_code) {
            return $id;
        }
        return 0;//返回一个0
    }


}
