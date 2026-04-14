<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class PostTopicUserLikeModel
 *
 * @property int $id 
 * @property int $aff 用户aff
 * @property int $related_id 帖子的话题ID
 * @property string $created_at 创建时间
 *
 * @author xiongba
 * @date 2023-06-09 20:11:30
 *
 * @mixin \Eloquent
 */
class GirlTopicUserLikeModel extends Model
{

    protected $table = "girl_topic_user_like";

    protected $primaryKey = 'id';

    protected $fillable = ['aff', 'related_id', 'created_at'];

    protected $guarded = 'id';

    public $timestamps = false;


    const FOLLOW_TOPICS_LIST_KEY = 'user:follow:topics:list:%s';

    /**
     * @param $related
     * @param $foreignKey
     * @param $localKey
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function topic()
    {
        return $this->hasOne(GirlTopicModel::class, 'id', 'related_id');
    }


    // 获取用户关注的话题
    public static function listFollowTopicIds($aff)
    {
        $cacheKey = sprintf(self::FOLLOW_TOPICS_LIST_KEY, $aff);
        return cached($cacheKey)->fetchJson(function () use ($aff) {
            return self::where('aff', $aff)->pluck('related_id')->toArray();
        });
    }

    /**
     *  某人是否关注某话题
     * @param $aff
     * @param $topic_id
     * @return bool
     */
    static function hasFollowedTopic($aff, $topic_id)
    {
        return self::where([
            'aff'        => $aff,
            'related_id' => $topic_id,
        ])->exists();
    }

    public static function getRecordByParam($aff, $relateId)
    {
        return self::where('aff', $aff)
            ->where('related_id', $relateId)
            ->first();
    }

    /**
     * @throws RedisException
     */
    public static function clearFollowCache($aff)
    {
        $cacheKey = sprintf(self::FOLLOW_TOPICS_LIST_KEY, $aff);
        redis()->del($cacheKey);
    }

    public static function listTopicFollow(MemberModel $member, $page, $limit)
    {
        $with = [
            'topic' => function ($q) {
                $q->select(['id', 'name', 'thumb', 'bg_thumb'])->where('status', GirlTopicModel::STATUS_NORMAL);
            }
        ];
        return self::select(['id', 'related_id'])
            ->with($with)
            ->where('aff', $member->aff)
            ->orderByDesc('id')
            ->forPage($page, $limit)
            ->get()
            ->map(function ($item) {
                if (!$item->topic) {
                    return null;
                }
                $item->topic->is_follow = 1;
                return $item->topic;
            })
            ->filter()
            ->values();
    }

}
