<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class StoryFavoritesModel
 *
 * @property int $id 
 * @property int $uid 用户id
 * @property int $zy_id 小说id
 * @property string $created_at 创建更新时间
 * @property StoryModel $manhua
 * @date 2022-06-28 20:55:24
 *
 * @mixin \Eloquent
 */
class StoryFavoritesModel extends Model
{

    protected $table = "story_favorites";

    protected $primaryKey = 'id';

    protected $fillable = ['uid', 'zy_id', 'created_at'];

    protected $guarded = 'id';

    public $timestamps = false;

    /**
     * @param $uid
     * @param $mh_id
     * @return bool
     */
    static function hasLike($uid, $mh_id)
    {
        return self::where(['uid' => $uid, 'zy_id' => $mh_id])->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function manhua()
    {
        return $this->hasOne(StoryModel::class, 'id', 'zy_id');
    }


    public static function getUserData($uid, $page, $limit = 20)
    {

        return self::query()
            ->where(['uid' => $uid])
            ->with('manhua')
            ->orderByDesc('id')
            ->forPage($page, $limit)
            ->get()
            ->map(function ($item) {
                /** @var StoryModel $manhua */
                if (is_null($item) || is_null($manhua = $item->manhua)) {
                    return null;
                }
                return $manhua;
            })->filter()->toArray();
    }


}
