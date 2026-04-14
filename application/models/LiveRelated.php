<?php

/**
 * class LiveRelatedModel
 *
 * @property int $id
 * @property int $theme_id 主题ID
 * @property int $live_id 直播ID
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @mixin \Eloquent
 */
class LiveRelatedModel extends EloquentModel
{
    protected $primaryKey = 'id';
    protected $table = "live_related";
    protected $fillable = [
        'theme_id',
        'live_id',
        'created_at',
        'updated_at'
    ];

    const CK_LIVE_RELATED_THEME_IDS = 'ck:live:related:theme_ids:%s';
    const GP_LIVE_RELATED_THEME_IDS = 'ck:live:related:theme_ids';
    const CN_LIVE_RELATED_THEME_IDS = '直播-关联主题IDS';

    public static function list_theme_ids($id)
    {
        $cache_key = sprintf(self::CK_LIVE_RELATED_THEME_IDS, $id);
        return cached($cache_key)
            ->group(self::GP_LIVE_RELATED_THEME_IDS)
            ->chinese(self::CN_LIVE_RELATED_THEME_IDS)
            ->clearCached()
            ->fetchPhp(function () use ($id) {
                return self::select(['theme_id'])
                    ->where('live_id', $id)
                    ->get()
                    ->pluck('theme_id');
            });
    }
}