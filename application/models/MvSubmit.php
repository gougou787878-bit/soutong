<?php

use Illuminate\Database\Eloquent\Builder;

/**
 * class MvSubmitModel
 *
 * @property string $actors 演员
 * @property string $category 分类
 * @property int $coins 定价
 * @property int $vip_coins 会员购买价格，-1表示没有设置会员价格
 * @property string $gif_thumb 视频动图
 * @property int $gif_height 视频动图宽
 * @property int $gif_width 视频动图高
 * @property int $comment 评论数
 * @property string $cover_thumb 封面小图
 * @property int $created_at 创建时间
 * @property string $directors 导演
 * @property int $duration 时长，秒
 * @property int $id
 * @property int $is_free 是否限免 0 收费 1 限免
 * @property int $is_hide 0显示1隐藏
 * @property int $like 喜欢点击数
 * @property string $m3u8 影片资源1
 * @property string $full_m3u8 影片资源1
 * @property int $music_id 音乐id
 * @property int $onshelf_tm 影片上映时间
 * @property int $rating 总历史点击数
 * @property int $refresh_at 刷新时间
 * @property int $status 0未审核1审核通过
 * @property array|string $tags 影片标签
 * @property int $thumb_duration 精彩时长：秒
 * @property int $thumb_height 封面高
 * @property int $thumb_start_time 精彩片段开始时间
 * @property int $thumb_width 封面宽
 * @property string $title 影片标题
 * @property int $uid 用户UUID
 * @property string $v_ext 视频格式类型
 * @property string $via 来源
 * @property int $is_recommend 来源
 * @property int $is_feature 是否是精选
 * @property string $y_cover
 * @property string $y_cover_url
 * @property int $is_top
 * @property int $count_pay
 * @property int $task_at 任务过期时间
 *
 * @property string $play_url
 * @property int is_pay
 *
 * @property MemberModel $user
 *
 * @author xiongba
 * @date 2020-03-03 18:25:48
 *
 * @mixin \Eloquent
 */
class MvSubmitModel extends EloquentModel
{


    const STAT_UNREVIEWED = 0;
    const STAT_CALLBACK_DONE = 1;
    const STAT_REFUSE = 2;
    const STAT_CALLBACK_ING = 3;
    const STAT_REMOVE = 4;
    const STAT = [
        self::STAT_UNREVIEWED    => '未审核',
        self::STAT_CALLBACK_DONE => '回调完成',
        self::STAT_REFUSE        => '未通过',
        self::STAT_CALLBACK_ING  => '回调中',
        self::STAT_REMOVE        => '逻辑删除',
    ];
    const IS_HIDE_YES = 1;
    const IS_HIDE_NO = 0;
    const IS_HIDE = [
        self::IS_HIDE_YES => '隐藏',
        self::IS_HIDE_NO  => '显示',
    ];

    const IS_FREE_YES = 1;
    const IS_FREE_NO = 0;
    const IS_FREE = [
        self::IS_FREE_YES => '免费',
        self::IS_FREE_NO  => '收费',
    ];
    const IS_FEATURE_YES = 1;
    const IS_FEATURE_NO = 0;
    const IS_FEATURE = [
        self::IS_FEATURE_YES => '是',
        self::IS_FEATURE_NO  => '否',
    ];
    const RECOMMEND_YES = 1;
    const RECOMMEND_NO = 0;
    const RECOMMEND = [
        self::RECOMMEND_NO  => '否',
        self::RECOMMEND_YES => '是',
    ];

    const IS_TOP_YES = 1;
    const IS_TOP_NO = 0;
    const IS_TOP = [
        self::IS_TOP_NO  => '否',
        self::IS_TOP_YES => '是',
    ];

    const VIA_USER = 'user';
    const VIA_OFFICAL = 'own';
    const VIA_LUSIR = 'lu91';
    const VIA = [
        self::VIA_USER    => '用户上传',
        self::VIA_OFFICAL => '官方出品',
        self::VIA_LUSIR   => '91撸',
    ];

    protected $table = 'mv_submit';

    protected $fillable = [
        'uid',
        'music_id',
        'coins',
        'title',
        'm3u8',
        'full_m3u8',
        'v_ext',
        'duration',
        'vip_coins',
        'gif_thumb',
        'gif_width',
        'gif_height',
        'cover_thumb',
        'thumb_width',
        'thumb_height',
        'directors',
        'actors',
        'category',
        'tags',
        'via',
        'onshelf_tm',
        'rating',
        'refresh_at',
        'is_free',
        'like',
        'is_recommend',
        'comment',
        'status',
        'thumb_start_time',
        'thumb_duration',
        'is_hide',
        'is_feature',
        'y_cover',
        'created_at',
        'is_top',
        'task_at',
        'count_pay'
    ];

    protected $appends = [
        'y_cover_url',
        'tags_list',
        'cover_thumb_url',
        'gif_thumb_url',
        'created_str',
        'is_like',
        'duration_str',
        'play_url',
        'is_pay'
    ];

    public function user()
    {
        return $this->hasOne(MemberModel::class, 'uid', 'uid');
    }

    public function getTagsListAttribute()
    {
        if (!isset($this->attributes['tags'])) {
            return [];
        }
        return array_map('trim', explode(',', $this->attributes['tags']));
    }

    public function getYCoverUrlAttribute()
    {
        return url_cover($this->attributes['y_cover'] ?? '');
    }

    public function getCoverThumbUrlAttribute()
    {
        return url_cover($this->attributes['cover_thumb'] ?? '');
    }

    public function getGifThumbUrlAttribute()
    {
        return url_cover($this->attributes['gif_thumb'] ?? '');
    }

    public function getCreatedStrAttribute()
    {
        return date('Y-m-d H:i:s', $this->attributes['created_at'] ?? TIMESTAMP);
    }

    public function getDurationStrAttribute()
    {
        return durationToString($this->attributes['duration'] ?? '');
    }

    public function getIsLikeAttribute()
    {
        if (empty($this->watchUser) || !isset($this->attributes['id'])) {
            return 0;
        }
        $ok = UserLikeModel::where(['uid' => $this->watchUser['uid'], 'mv_id' => $this->attributes['id']])->exists();
        if ($ok) {
            return 1;
        }
        return 0;
    }

    public function getIsPayAttribute()
    {
        if (empty($this->watchUser) || !isset($this->attributes['id'])) {
            return 0;
        }
        $ok = MvPayModel::where(['uid' => $this->watchUser['uid'], 'mv_id' => $this->attributes['id']])->exists();
        if ($ok) {
            return 1;
        }
        return 0;
    }

    public function getPlayUrlAttribute()
    {
        if (!isset($this->attributes['m3u8'])) {
            return null;
        }
        $ok = url_video($this->attributes['m3u8']);
    }


    public function calcRefuse_rate(){
        $item = $this;
        $count = MemberModel::where('uid', $item->uid)->value('videos_count');
        $jjCount = MvSubmitModel::where('uid', $item->uid)->where('status', MvSubmitModel::STAT_REFUSE)->count();
        $ccZ = MvSubmitModel::where('uid', $item->uid)->where('status', MvSubmitModel::STAT_CALLBACK_ING)->count();
        $total = $count + $jjCount + $ccZ;
        $item->video_count = $total;
        if ($jjCount == 0) {
            $item->refuse_rate = 0;
        } else {
            $item->refuse_rate = $jjCount / $total;
        }
    }



}