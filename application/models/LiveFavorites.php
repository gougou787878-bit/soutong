<?php

/**
 * class liveFavoritesModel
 *
 * @property int $id
 * @property int $aff 用户aff
 * @property int $live_id 直播ID
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 *
 *
 * @date 2024-08-31 15:08:21
 *
 * @mixin \Eloquent
 */
class LiveFavoritesModel extends EloquentModel
{

    protected $table = "live_favorites";

    protected $primaryKey = 'id';

    protected $fillable = [
        'aff',
        'live_id',
        'created_at',
        'updated_at'
    ];

    protected $guarded = 'id';

    public $timestamps = true;

    const MEMBER_FAVORITE_LIVE_SET = 'member:favorite:live:set:%s';

    public function live(){
        return $this->hasOne(LiveModel::class, 'id', 'live_id');
    }

    public static function liveList($aff, $page, $limit){
        return self::query()
            ->with(['live' => function($q){
                $q->select(LiveModel::SE_LAYOUT_1);
            }])
            ->where('aff', $aff)
            ->orderByDesc('id')
            ->forPage($page, $limit)
            ->get()
            ->map(function ($item) {
                if (is_null($item) || is_null($item->live)) {
                    return null;
                }
                return $item->live;
            })->filter()->values();
    }

    public static function favoriteCt(int $aff)
    {
        return self::query()->where('aff', $aff)->count('id');
    }
}
