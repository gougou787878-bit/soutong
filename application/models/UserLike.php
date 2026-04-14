<?php


/**
 * class UserLikesModel
 *
 * @property int $id
 * @property int $mv_id
 * @property string $updated_at
 * @property string $created_at
 * @property int $uid
 * @property int $type
 *
 * @author xiongba
 * @date 2020-02-28 15:24:48
 *
 * @mixin \Eloquent
 */
class UserLikeModel extends EloquentModel
{
    protected $table = 'user_likes';

    protected $fillable = [
        'mv_id',
        'uid',
        'type'
    ];

    public $timestamps = true;

    public function videos()
    {
        return $this->hasOne(MvModel::class, 'id', 'mv_id')
            ->selectRaw(PcMvModel::SELECT_FIELDS)
            ->with('user:uid,nickname,thumb,vip_level,sexType,expired_at,uuid,aff');
    }

    public static function listMvFavorite(MemberModel $member, $page, $limit)
    {
        return self::query()
            ->with('videos')
            ->where('uid', $member->uid)
            ->orderByDesc('created_at')
            ->forPage($page, $limit)
            ->get()
            ->pluck('videos');
    }
}