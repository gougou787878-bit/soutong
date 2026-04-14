<?php

use Illuminate\Database\Eloquent\Model;

/**
 * class PostRewardLogModel
 *
 *
 * @property int $id
 * @property int $aff 用户aff
 * @property int $girl_id 帖子ID
 * @property int $amount 打赏金额
 * @property string $created_at
 * @property string $aff_nickname
 * @property string $girl_title
 * @property string $thumb
 * @property int $girl_aff 帖子用户aff
 *
 *@property ?PostModel $post
 *
 * @mixin \Eloquent
 */
class GirlPayLogModel extends Model
{
    protected $table = 'girl_pay_log';
    protected $primaryKey = 'id';
    protected $fillable = [
        'id',
        'aff',
        'aff_nickname',
        'thumb',
        'girl_id',
        'girl_title',
        'amount',
        'created_at',
        'updated_at',
        'girl_aff'
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
    public function girl()
    {
        return $this->hasOne(GirlModel::class, 'id', 'girl_id');
    }


    static function hasBuy($aff, $post_id)
    {
        return self::where([
            'aff' => $aff,
            'girl_id' => $post_id
        ])->exists();
    }

    public static function listBuyGirls($aff, $page, $limit)
    {
        $data = self::with([
            'girl' => function ($query) {
                $query->with('topic:id,name')
                    ->with('medias:pid,media_url,cover,thumb_width,thumb_height,type')
                    ->with('user:aff,uid,nickname,thumb,aff,expired_at,vip_level,uuid,sexType')
                    ->where('status', GirlModel::STATUS_PASS)
                    ->where('is_deleted', GirlModel::DELETED_NO);
            }
        ])
            ->where('aff', $aff)
            ->forPage($page, $limit)
            ->orderByDesc('created_at')
            ->get()->pluck('girl')->filter()->values();

        return $data;
    }
}