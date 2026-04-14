<?php

use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * class PornLikeModel
 *
 * @property int $aff 用户aff
 * @property string $created_at 创建时间
 * @property int $id
 * @property int $porn_id 图集id
 * @property string $updated_at 更新时间
 *
 *
 * @date 2024-04-01 15:51:46
 *
 * @mixin \Eloquent
 */
class PornLikeModel extends EloquentModel
{
    protected $table = "porn_like";
    protected $primaryKey = 'id';
    protected $fillable = [
        'aff',
        'created_at',
        'porn_id',
        'updated_at'
    ];
    protected $guarded = 'id';
    public $timestamps = true;

    const PORN_GAME_LIKE_SET = 'porn:game:like:set:%d';

    public function game(): HasOne
    {
        return $this->hasOne(PornGameModel::class, 'id', 'porn_id');
    }

    public static function hasRecord($aff, $porn_id){
        return self::where('aff', $aff)->where('porn_id', $porn_id)->first();
    }

    public static function list($aff, $page, $limit){
        return self::with(['game' => function($q){
            return $q->selectRaw(PornGameModel::PORN_GAME_LIST_COLUMN);
        }])
            ->where('aff', $aff)
            ->orderByDesc('created_at')
            ->forPage($page, $limit)
            ->get()
            ->pluck('game')
            ->filter()
            ->values();
    }

    public static function getRecommendMyData($aff)
    {
        return self::with(['game'=>function($query){
            return $query->where('status', PornGameModel::STATUS_OK);
        }])
            ->where('aff', $aff)
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                if (is_null($item) || is_null($item->game)) {
                    return null;
                }
                return $item->game->category_title;
            })->filter()->values();
    }
}
