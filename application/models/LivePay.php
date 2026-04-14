<?php

use Illuminate\Database\Eloquent\Relations\HasOne;


/**
 * class LivePayModel
 *
 * @property int $id
 * @property int $aff 用户aff
 * @property int $live_id 直播id
 * @property int $coins 购买时的价格
 * @property string $created_at 购买时间
 * @property string $updated_at 更新时间
 *
 *
 * @date 2024-09-01 01:25:07
 *
 * @mixin \Eloquent
 */
class LivePayModel extends EloquentModel
{

    protected $table = "live_pay";
    protected $primaryKey = 'id';
    protected $fillable = [
        'aff',
        'live_id',
        'coins',
        'created_at',
        'updated_at'
    ];
    protected $guarded = 'id';
    public $timestamps = true;

    public function live(): HasOne
    {
        return $this->hasOne(LiveModel::class, 'id', 'live_id');
    }

    public static function hasBuy($aff, $live_id){
        return self::useWritePdo()
            ->where('aff', $aff)
            ->where('live_id', $live_id)
            ->first();
    }

    public static function listBuy($aff, $page, $limit){
        return self::with([
            'live' => function ($query) {
                return $query->select(LiveModel::SE_LAYOUT_2)
                    ->where('status', LiveModel::STATUS_ON);
            }
        ])
            ->where('aff', $aff)
            ->forPage($page, $limit)
            ->orderByDesc('id')
            ->get()
            ->pluck('live')
            ->filter()
            ->values();
    }
}
