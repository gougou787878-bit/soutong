<?php

use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * class PcMvRecommendModel
 *
 * @property int $id
 * @property int $tab_id PC导航ID
 * @property int $mv_id 视频ID
 * @property int $sort 排序
 * @property string $created_at
 * @property string $updated_at
 *
 * @author xiongba
 * @date 2024-01-10 18:29:39
 *
 * @mixin \Eloquent
 */
class PcMvRecommendModel extends EloquentModel
{
    protected $table = "pc_mv_recommend";
    protected $primaryKey = 'id';
    protected $fillable = [
        'tab_id',
        'mv_id',
        'sort',
        'created_at',
        'updated_at'
    ];
    protected $guarded = 'id';
    public $timestamps = true;

    public function mv(): HasOne
    {
        return $this->hasOne(MvModel::class, 'id', 'mv_id');
    }

    public function tab(): HasOne
    {
        return $this->hasOne(PcTabModel::class, 'tab_id', 'tab_id');
    }
}
