<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class PostUserLikeModel
 *
 * @property int $id 
 * @property int $aff 
 * @property int $related_id 
 * @property int $type 
 * @property string $created_at 
 * @property string $updated_at 
 *
 * @author xiongba
 * @date 2023-06-09 20:11:47
 *
 * @mixin \Eloquent
 */
class PostUserLikeModel extends EloquentModel
{

    protected $table = "post_user_like";

    protected $primaryKey = 'id';

    protected $fillable = ['aff', 'related_id', 'type', 'created_at', 'updated_at'];

    protected $guarded = 'id';

    public $timestamps = false;

    const TYPE_POST = 0;
    const TYPE_COMMENT = 1;
    const TYPE_TIPS = [
        self::TYPE_POST => '帖子',
        self::TYPE_COMMENT => '评论'
    ];
    const USER_POST_LIKE_LIST = 'user:community:post:like:list:%s';
    const USER_COMMENT_LIKE_LIST = 'user:community:comment:list:%s';

    /**
     * @param $related
     * @param $foreignKey
     * @param $localKey
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function post()
    {
        return $this->hasOne(PostModel::class, 'id', 'related_id');
    }
    public static function listLikePostIds($aff)
    {
        $cacheKey = sprintf(self::USER_POST_LIKE_LIST, $aff);
        return cached($cacheKey)
            ->expired(3600)
            ->serializerPHP()
            ->fetch(function () use ($aff) {
                $data = self::where('type', self::TYPE_POST)
                    ->where('aff', $aff)
                    ->get()
                    ->toArray();
                return array_column($data, 'related_id');
            });
    }

    public static function listLikeCommentIds($aff)
    {
        $cacheKey = sprintf(self::USER_COMMENT_LIKE_LIST, $aff);
        return cached($cacheKey)
            ->expired(3600)
            ->serializerPHP()
            ->fetch(function () use ($aff) {
                $data = self::where('type', self::TYPE_COMMENT)
                    ->where('aff', $aff)
                    ->get()
                    ->toArray();
                return array_column($data, 'related_id');
            });
    }


    public static function getIdsById($aff, $id)
    {
        return self::where('aff', $aff)
            ->where('related_id', $id)
            ->first();
    }

    /**
     * @throws RedisException
     */
    public static function clearCacheByAff($type, $aff)
    {
        $rule = $type == self::TYPE_POST ? self::USER_POST_LIKE_LIST : self::USER_COMMENT_LIKE_LIST;
        $cacheKey = sprintf($rule, $aff);
        redis()->del($cacheKey);
    }

    public static function listLikePosts($aff,$page,$limit)
    {
        return self::with(['post' => function($query){
            $query->with('topic:id,name')
                ->select(PcPostModel::SELECT_LIST_FIELDS)
                ->with('medias:pid,media_url,cover,thumb_width,thumb_height,type')
                ->with('user:aff,uid,nickname,thumb,aff,expired_at,vip_level,uuid,sexType')
                ->where('status',PostModel::STATUS_PASS)
                ->where('is_deleted',PostModel::DELETED_NO);
        }])
            ->where('aff', $aff)
            ->where('type', self::TYPE_POST)
            ->forPage($page,$limit)
            ->orderByDesc('created_at')
            ->get()->pluck('post')->filter()->values();
    }
}
