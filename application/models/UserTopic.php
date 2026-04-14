<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class UserTopicModel
 *
 * @property string $desp 合集介绍
 * @property int $id
 * @property string $image 合集图片
 * @property int $like_count 点赞数量
 * @property string $mv_id_str 视频id
 * @property int $status 状态
 * @property string $title 合集标题
 * @property int $uid 用户uid
 * @property int $is_top
 * @property int $video_count 视频数量
 *
 * @property int[] $mv_id_ary
 *
 * @property MemberModel $user
 *
 * @author xiongba
 * @date 2021-02-23 15:57:33
 *
 * @mixin \Eloquent
 */
class UserTopicModel extends EloquentModel
{

    protected $table = "user_topic";

    protected $primaryKey = 'id';

    protected $fillable = ['desp', 'image', 'like_count', 'mv_id_str', 'is_top', 'status', 'title', 'uid', 'video_count'];

    protected $guarded = 'id';

    public $timestamps = false;

    const STAT_ENABLE = 1;
    const STAT_DISABLE = 0;

    const STAT = [
        self::STAT_ENABLE  => '启用',
        self::STAT_DISABLE => '禁用',
    ];
    const IS_TOP_YES = 1;
    const IS_TOP_NO = 0;

    const IS_TOP = [
        self::IS_TOP_YES => '是',
        self::IS_TOP_NO  => '否',
    ];

    protected $appends = ['image_url' , 'mv_id_ary' , 'is_like'];

    public function getImageUrlAttribute()
    {
        $xCoverUrl = $this->attributes['image'] ?? null;
        return url_cover($xCoverUrl);
    }

    public function getMvIdAryAttribute()
    {
        $str = $this->attributes['mv_id_str'] ?? null;
        if(empty($str)){
            return  [];
        }
        return collect(explode(',' , $str))->unique()->map(function ($v){
            return intval($v);
        })->filter()->toArray();
    }

    public function getIsLikeAttribute()
    {
        $id = $this->attributes['id'] ?? null;
        static $likeTopicAry = null;
        if (empty($id)) {
            return 0;
        }
        if (empty($this->watchUser) || empty($this->watchUser['uid'])) {
            return 0;
        }
        $uid = $this->watchUser['uid'];
        if ($likeTopicAry === null) {
            $likeTopicAry = UserTopicLikeModel::where('uid', $uid)->pluck('topic_id')->toArray();
        }
        return in_array($id, $likeTopicAry) ? 1 : 0;
    }

    public static function queryBase()
    {
        return self::where('status', self::STAT_ENABLE);
    }

    public function user()
    {
        return $this->hasOne(MemberModel::class, 'uid', 'uid');
    }


    public static function queryUser()
    {
        return self::queryBase()->with('user:uid,nickname,thumb,aff,expired_at,vip_level,uuid,sexType');
    }

    static function createBy($uid, $title, $desc, $image, $idStr, $videoCount)
    {
        return self::create([
            'title'       => $title,
            'desp'        => $desc,
            'image'       => $image,
            'status'      => self::STAT_ENABLE,
            'uid'         => $uid,
            'video_count' => $videoCount,
            'like_count'  => 0,
            'is_top'      => self::IS_TOP_NO,
            'mv_id_str'   => $idStr
        ]);
    }


}
