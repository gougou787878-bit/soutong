<?php

use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $aff 用户AFF
 * @property int $seed_id 帖子ID
 * @property int $coins 解锁金币
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @mixin \Eloquent
 */
class SeedPostBuyLogModel extends EloquentModel
{
    protected $table = 'seed_post_buy_log';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'aff',
        'seed_id',
        'post_aff',
        'coins',
        'created_at',
        'updated_at',
    ];
    const CK_BUY_SEED = 'ck:buy:seed:%s';

    public static function generateId($aff): string
    {
        return sprintf(self::CK_BUY_SEED, $aff);
    }

    /**
     * @throws RedisException
     */
    public static function list_buy_ids($aff): array
    {
        return redis()->sMembers(self::generateId($aff));
    }

    public static function buy_seed($aff, $id, $coins)
    {
        $data = [
            'aff'        => $aff,
            'seed_id'    => $id,
            'coins'      => $coins,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $isOK = SeedPostBuyLogModel::create($data);
        test_assert($isOK, '解锁日志记录失败');

        $rs = redis()->sAdd(self::generateId($aff), $id);
        test_assert($rs, '系统错误');
    }

    public function post(): HasOne
    {
        $with = [
//            'topic'  => function ($q) {
//                $q->select(['id', 'name'])
//                    ->where('status', SeedPostTopicModel::STATUS_OK);
//            },
            'medias' => function ($q) {
                $q->select(['id', 'pid', 'cover', 'media_url', 'thumb_width', 'thumb_height', 'type'])
                    ->where('relate_type', SeedPostMediaModel::TYPE_RELATE_POST)
                    ->where('status', SeedPostMediaModel::STATUS_OK)
                    ->orderByDesc('type');
            }
        ];
        return $this->hasOne(SeedPostModel::class, 'id', 'seed_id')
            ->select(['id', 'title', 'fake_view_ct', 'view_ct', 'comment_ct', 'set_top', 'photo_ct', 'video_ct', 'fake_like_ct', 'like_ct', 'favorite_ct', 'type'])
            ->with($with)
            ->where('status', SeedPostModel::STATUS_ON)
            ->where('is_finished', SeedPostModel::FINISHED_OK);
    }

    public static function list_buy_post($aff, $page, $limit)
    {
        return self::with('post')
            ->where('aff', $aff)
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
