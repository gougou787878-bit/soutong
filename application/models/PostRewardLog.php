<?php

use Illuminate\Database\Eloquent\Model;

/**
 * class PostRewardLogModel
 *
 *
 * @property int $id
 * @property int $aff 用户aff
 * @property int $post_id 帖子ID
 * @property int $amount 打赏金额
 * @property string $created_at
 * @property string $aff_nickname
 * @property string $post_title
 * @property string $thumb
 * @property int $post_aff 帖子用户aff
 *
 *@property ?PostModel $post
 *
 * @mixin \Eloquent
 */
class PostRewardLogModel extends Model
{
    protected $table = 'post_reward_log';
    protected $primaryKey = 'id';
    protected $fillable = [
        'id',
        'aff',
        'aff_nickname',
        'thumb',
        'post_id',
        'post_title',
        'amount',
        'created_at',
        'updated_at',
        'post_aff'
    ];
    protected $guarded = 'id';
    public $timestamps = false;
    protected $appends = [
        'thumb_url_full',
    ];

    public function getThumbUrlFullAttribute()
    {
        return url_avatar($this->attributes['thumb'] ?? '');
    }

    /**
     * @param $related
     * @param $foreignKey
     * @param $localKey
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function post()
    {
        return $this->hasOne(PostModel::class, 'id', 'post_id');
    }

    const MEMBER_POST_INCOME_LIST = "post:income:aff:%s|page:%d|limit:%d";
    const MEMBER_POST_INCOME = "member_post_income";

    public static function memberPostIncome($page, $limit)
    {
        $aff = request()->getMember()->aff;
        return cached(sprintf(self::MEMBER_POST_INCOME_LIST, $aff, $page, $limit))
            ->group(self::MEMBER_POST_INCOME)
            ->clearCached()
            ->fetchPhp(function () use ($aff, $page, $limit) {
                return self::where("post_aff", $aff)
                    ->forPage($page, $limit)
                    ->orderByDesc("id")
                    ->get();
            });
    }

    static function hasBuy($aff, $post_id)
    {
        return self::where([
            'aff' => $aff,
            'post_id' => $post_id
        ])->exists();
    }

    public static function listBuyPosts($aff, $page, $limit)
    {
        $data = self::with([
            'post' => function ($query) {
                $query->with('topic:id,name')
                    ->with('medias:pid,media_url,cover,thumb_width,thumb_height,type')
                    ->with('user:aff,uid,nickname,thumb,aff,expired_at,vip_level,uuid,sexType')
                    ->where('status', PostModel::STATUS_PASS)
                    ->where('is_deleted', PostModel::DELETED_NO);
            }
        ])
            ->where('aff', $aff)
            ->forPage($page, $limit)
            ->orderByDesc('created_at')
            ->get()->pluck('post')->filter()->values();

        return $data;
    }

    public static function unlockList($id,$page,$limit){
        return self::query()
            ->where('post_id',$id)
            ->orderByDesc('id')
            ->forPage($page,$limit)
            ->get();
    }
}