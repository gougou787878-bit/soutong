<?php

use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * class LiveLikeModel
 *
 * @property int $id
 * @property int $aff 用户aff
 * @property int $live_id 直播id
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 *
 *
 * @date 2024-08-31 15:08:29
 *
 * @mixin \Eloquent
 */
class LiveLikeModel extends EloquentModel
{

    protected $table = "live_like";

    protected $primaryKey = 'id';

    protected $fillable = [
        'aff',
        'live_id',
        'created_at',
        'updated_at'
    ];

    protected $guarded = 'id';

    public $timestamps = true;

    const MEMBER_LIVE_LIKE_SET = 'member:live:like:set:%s';

    public function live(): HasOne
    {
        return $this->hasOne(liveModel::class, 'id', 'live_id');
    }
}
