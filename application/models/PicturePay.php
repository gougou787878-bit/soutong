<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class PicturePayModel
 *
 * @property int $id 
 * @property int $uid 用户id
 * @property int $coins 购买时的价格
 * @property int $zy_id 资源小说编号id
 * @property string $type 类型 购买 次数 赠送
 * @property string $created_at 购买时间
 * @property PictureModel $manhua
 * @author xiongba
 * @date 2022-06-28 20:54:20
 *
 * @mixin \Eloquent
 */
class PicturePayModel extends Model
{

    protected $table = "picture_pay";

    protected $primaryKey = 'id';

    protected $fillable = ['uid', 'coins', 'zy_id', 'type', 'created_at'];

    protected $guarded = 'id';

    public $timestamps = false;


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function manhua()
    {
        return $this->hasOne(PictureModel::class, 'id', 'zy_id');
    }

    /**
     * @param $uid
     * @param $mh_id
     * @return bool
     */
    static function hasBuy($uid,$mh_id){
        return self::where(['uid'=>$uid,'zy_id'=>$mh_id])->exists();
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
                /** @var PictureModel $manhua */
                if (is_null($item) || is_null($manhua = $item->manhua)) {

                    return null;
                }
                return $manhua;
            })->filter()->toArray();

    }


}
