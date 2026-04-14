<?php

use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $aff
 * @property int $related_id
 * @property int $type
 * @property string $created_at
 * @property string $updated_at
 *
 * @mixin \Eloquent
 */
class SeedLikeModel extends EloquentModel
{
    protected $table = 'seed_like';
    public $timestamps = true;
    protected $fillable = [
        'id',
        'aff',
        'related_id',
        'type',
        'created_at',
        'updated_at'
    ];

    const TYPE_POST = 0;
    const TYPE_COMMENT = 1;
    const TYPE_TIPS = [
        self::TYPE_POST    => '帖子',
        self::TYPE_COMMENT => '评论'
    ];

    const CK_USER_LIKE = 'user:like:new:%s:%s';

    public function post(): HasOne
    {
        $with = [
            'topic'  => function ($q) {
                $q->select(['id', 'name'])
                    ->where('status', SeedPostTopicModel::STATUS_OK);
            },
            'medias' => function ($q) {
                $q->select(['id', 'pid', 'cover', 'media_url', 'thumb_width', 'thumb_height', 'type'])
                    ->where('relate_type', SeedPostMediaModel::TYPE_RELATE_POST)
                    ->where('status', SeedPostMediaModel::STATUS_OK)
                    ->orderByDesc('type');
            }
        ];
        return $this->hasOne(SeedPostModel::class, 'id', 'related_id')
            ->select(['id', 'topic_id', 'title', 'fake_view_ct', 'view_ct', 'comment_ct', 'set_top', 'photo_ct', 'video_ct', 'fake_like_ct', 'like_ct', 'favorite_ct', 'type'])
            ->with($with)
            ->where('status', SeedPostModel::STATUS_ON)
            ->where('is_finished', SeedPostModel::FINISHED_OK);
    }

    public static function generateId($type, $aff): string
    {
        return sprintf(self::CK_USER_LIKE, $type, $aff);
    }

    public static function list_ids($type, $aff): array
    {
        return redis()->sMembers(self::generateId($type, $aff));
    }

    public static function toggle($type, $aff, $id, $fn)
    {
        $record = self::where('type', $type)
            ->where('aff', $aff)
            ->where('related_id', $id)
            ->first();

        $key = self::generateId($type, $aff);
        if ($record) {
            return transaction(function () use ($record, $fn, $key, $id) {
                $isOk = $record->delete();
                test_assert($isOk, '删除记录失败');
                $rs = $fn(false);
                redis()->sRem($key, $id);
                return $rs;
            });
        }

        $data = [
            'aff'        => $aff,
            'type'       => $type,
            'related_id' => $id,
        ];
        return transaction(function () use ($data, $fn, $key, $id) {
            $isOk = self::create($data);
            test_assert($isOk, '新增记录失败');
            $rs = $fn(true);
            redis()->sAdd($key, $id);
            return $rs;
        });
    }

    public static function list_like_post($aff, $page, $limit)
    {
        return self::with('post')
            ->where('aff', $aff)
            ->where('type', self::TYPE_POST)
            ->forPage($page, $limit)
            ->orderByDesc('id')
            ->get()
            ->map(function ($item) {
                if (!$item->post) {
                    return null;
                }
                return $item->post;
            })
            ->filter()
            ->values();
    }
}
