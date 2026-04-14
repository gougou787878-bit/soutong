<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class MhPayModel
 *
 * @property int $id 
 * @property int $uid 用户id
 * @property int $coins 购买时的价格
 * @property int $mh_id 漫画id
 * @property string $type 类型 购买 次数 赠送
 * @property string $created_at 购买时间
 * @property MhModel $manhua
 *
 * @author xiongba
 * @date 2022-05-17 17:35:31
 *
 * @mixin \Eloquent
 */
class MhPayModel extends Model
{

    protected $table = "mh_pay";

    protected $primaryKey = 'id';

    protected $fillable = ['uid', 'coins', 'mh_id', 'type', 'created_at'];

    protected $guarded = 'id';

    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function manhua()
    {
        return $this->hasOne(MhModel::class, 'id', 'mh_id');
    }

    /**
     * @param $uid
     * @param $mh_id
     * @return bool
     */
    static function hasBuy($uid,$mh_id){
        return self::where(['uid'=>$uid,'mh_id'=>$mh_id])->exists();
    }

    /**
     * @param $uid
     * @param $page
     * @param $limit
     * @return array
     */
    public static function getUserBuyData($uid, $page, $limit)
    {
        return self::query()
            ->where(['uid' => $uid])
            ->with('manhua')
            ->forPage($page, $limit)
            ->orderByDesc('id')
            ->get()
            ->map(function ($item) {
                /** @var MhModel $manhua */
                if (is_null($item) || is_null($manhua = $item->manhua)) {

                    return null;
                }
                return $manhua;
            })->filter()->toArray();

    }



}
