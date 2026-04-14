<?php


/**
 * class CartoonLikeModel
 *
 * @property int $id 
 * @property int $aff 
 * @property int $cartoon_id
 * @property string $created_at 
 * @property string $updated_at 

 * @date 2023-06-09 20:11:47
 *
 * @mixin \Eloquent
 */
class CartoonLikeModel extends EloquentModel
{

    protected $table = "cartoon_like";

    protected $primaryKey = 'id';

    protected $fillable = ['cartoon_id', 'aff', 'updated_at', 'created_at'];

    protected $guarded = 'id';

    public $timestamps = true;


    /**
     * @param $related
     * @param $foreignKey
     * @param $localKey
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function cartoon()
    {
        return $this->hasOne(CartoonModel::class, 'id', 'cartoon_id');
    }


    public static function getIdsById($aff, $id)
    {
        return self::where('aff', $aff)
            ->where('cartoon_id', $id)
            ->first();
    }

    static function hasLike($aff, $id)
    {
        return self::where(['aff' => $aff, 'cartoon_id' => $id])->first();
    }

    public static function listLike($aff, $page, $limit)
    {
        return self::with(['cartoon' => function($query){
            $query->where('status', CartoonModel::STATUS_OK)
                ->select(OriginalModel::SHOW_LIST_COLUMS);
        }])
        ->where('aff', $aff)
        ->forPage($page,$limit)
        ->orderByDesc('created_at')
        ->get()
        ->pluck('cartoon')
        ->filter()
        ->values();
    }


}
