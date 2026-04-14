<?php

use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * class SeedFavoritesModel
 *
 * @property string $created_at 创建时间
 * @property int $id
 * @property int $uid 用户id
 * @property int $zy_id 种子id
 *
 * @date 2025-01-30 20:31:22
 *
 * @mixin \Eloquent
 */
class SeedFavoritesModel extends EloquentModel
{

    protected $table = "seed_favorites";
    protected $primaryKey = 'id';
    protected $fillable = [
        'created_at',
        'uid',
        'zy_id'
    ];
    protected $guarded = 'id';
    public $timestamps = false;

    public function seed(): HasOne
    {
        $with = [
            'medias' => function ($q) {
                $q->select(['id', 'pid', 'cover', 'media_url', 'thumb_width', 'thumb_height', 'type'])
                    ->where('relate_type', SeedPostMediaModel::TYPE_RELATE_POST)
                    ->where('status', SeedPostMediaModel::STATUS_OK)
                    ->orderByDesc('type');
            }
        ];
        return $this->hasOne(SeedPostModel::class, 'id', 'zy_id')
            ->select(['id', 'topic_id', 'title', 'fake_view_ct', 'view_ct', 'comment_ct', 'set_top', 'photo_ct', 'video_ct', 'fake_like_ct', 'like_ct', 'favorite_ct', 'type'])
            ->with($with)
            ->where('status', SeedPostModel::STATUS_ON)
            ->where('is_finished', SeedPostModel::FINISHED_OK);
    }

    // 我收藏的帖子 无需缓存
    public static function listMyFavoriteSeeds($uid, $page, $limit)
    {
        return  self::with('seed')
            ->where('uid',$uid)
            ->orderByDesc('id')
            ->forPage($page, $limit)
            ->get()
            ->pluck('seed')
            ->filter()
            ->values();
    }
}
