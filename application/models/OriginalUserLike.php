<?php


/**
 * class OriginalUserLikeModel
 *
 * @property int $id 
 * @property int $aff 
 * @property int $related_id 
 * @property int $type 
 * @property string $created_at 
 * @property string $updated_at 
 *
 * @author xiongba
 * @date 2023-06-09 20:11:47
 *
 * @mixin \Eloquent
 */
class OriginalUserLikeModel extends EloquentModel
{

    protected $table = "original_user_like";

    protected $primaryKey = 'id';

    protected $fillable = ['original_id', 'uid', 'updated_at', 'created_at'];

    protected $guarded = 'id';

    public $timestamps = true;


    /**
     * @param $related
     * @param $foreignKey
     * @param $localKey
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function original()
    {
        return $this->hasOne(OriginalModel::class, 'id', 'original_id');
    }


    public static function getIdsById($aff, $id)
    {
        return self::where('uid', $aff)
            ->where('original_id', $id)
            ->first();
    }

    static function hasLike($uid, $id)
    {
        return self::where(['uid' => $uid, 'original_id' => $id])->first();
    }


    /**
     * @throws RedisException
     */
    public static function clearCacheByAff($type, $aff)
    {
        $rule = $type == self::TYPE_POST ? self::USER_POST_LIKE_LIST : self::USER_COMMENT_LIKE_LIST;
        $cacheKey = sprintf($rule, $aff);
        redis()->del($cacheKey);
    }

    public static function listLikeOriginal($aff,$page,$limit)
    {
        return self::with(['original' => function($query){
            $query->where('status',1)->select(OriginalModel::SHOW_LIST_COLUMS);
        }])
        ->where('uid', $aff)
        ->forPage($page,$limit)
        ->orderByDesc('created_at')
        ->get()->pluck('original')->filter()->values();
    }


}
