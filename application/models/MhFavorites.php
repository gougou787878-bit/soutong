<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class MhFavoritesModel
 *
 * @property int $id
 * @property int $uid 用户id
 * @property int $mh_id 漫画id
 * @property string $created_at 创建更新时间
 * @property MhModel $manhua
 * @author xiongba
 * @date 2022-05-17 17:36:16
 *
 * @mixin \Eloquent
 */
class MhFavoritesModel extends Model
{

    protected $table = "mh_favorites";

    protected $primaryKey = 'id';

    protected $fillable = ['uid', 'mh_id', 'created_at'];

    protected $guarded = 'id';

    public $timestamps = false;

    /**
     * @param $uid
     * @param $mh_id
     * @return bool
     */
    static function hasLike($uid, $mh_id)
    {
        return self::where(['uid' => $uid, 'mh_id' => $mh_id])->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function manhua()
    {
        return $this->hasOne(MhModel::class, 'id', 'mh_id');
    }


    public static function getUserData($uid, $page, $limit = 20)
    {
        return self::query()
            ->where(['uid' => $uid])
            ->with('manhua')
            ->orderByDesc('id')
            ->forPage($page, $limit)
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
