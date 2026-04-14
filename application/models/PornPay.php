<?php

use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * class PornPayModel
 *
 * @property int $aff 用户aff
 * @property int $coins 购买时的价格
 * @property string $created_at 购买时间
 * @property int $id
 * @property int $porn_id 黄游id
 * @property int $status 状态 0 未支付 1已完成
 * @property int $type 类型 1 次数解锁 2金币解锁
 * @property string $updated_at 更新时间
 *
 *
 * @date 2024-04-01 15:51:09
 *
 * @mixin \Eloquent
 */
class PornPayModel extends EloquentModel
{
    protected $table = "porn_pay";
    protected $primaryKey = 'id';
    protected $fillable = [
        'aff',
        'coins',
        'created_at',
        'porn_id',
        'status',
        'type',
        'updated_at'
    ];
    protected $guarded = 'id';
    public $timestamps = true;

    const PRON_GAME_BUY_SET_AFF = 'pron:game:buy:set:aff:%s';

    public function game(): HasOne
    {
        return $this->hasOne(PornGameModel::class, 'id', 'porn_id');
    }

    public static function hasBuy($aff, $novelId){
        return self::where('aff', $aff)->where('porn_id', $novelId)->first();
    }

    public static function listBuy($aff, $page, $limit){
        return self::with([
            'game' => function ($query) {
                return $query->selectRaw(PornGameModel::PORN_GAME_LIST_COLUMN)
                    ->where('status', PornGameModel::STATUS_OK);
            }
        ])
            ->where('aff', $aff)
            ->forPage($page, $limit)
            ->orderByDesc('created_at')
            ->get()->pluck('game')->filter()->values();
    }
}
