<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class PcPostTopicModel
 *
 * @property int $id
 * @property string $thumb 话题封面
 * @property string $bg_thumb 背景图片
 * @property string $name 话题名称
 * @property int $follow_num 关注人数
 * @property int $view_num 浏览人数
 * @property int $status 0不显示 1显示
 * @property string $created_at 创建时间
 * @property int $sort 排序 越大越前
 * @property int $is_hot 热门 0非热门 1热门
 * @property int $post_num 帖子数
 * @property string $intro 简介
 * @property string $web_name web名称
 * @property int $web_show web是否展示
 *
 *
 * @mixin \Eloquent
 */
class PcPostTopicModel extends EloquentModel
{

    protected $table = "post_topic";

    protected $primaryKey = 'id';

    protected $fillable = [
        'thumb',
        'bg_thumb',
        'name',
        'follow_num',
        'view_num',
        'status',
        'created_at',
        'sort',
        'is_hot',
        'post_num',
        'intro',
        'web_name',
        'web_show'
    ];

    const STATUS_HIDE = 0;
    const STATUS_NORMAL = 1;
    const STATUS_TIPS = [
        self::STATUS_HIDE => '屏蔽',
        self::STATUS_NORMAL => '生效'
    ];

    const HOT_NO = 0;
    const HOT_OK = 1;
    const HOT_TIPS = [
        self::HOT_NO => '否',
        self::HOT_OK => '热门'
    ];

    const WEB_NO = 0;
    const WEB_OK = 1;
    const WEB_TIPS = [
        self::WEB_NO => '否',
        self::WEB_OK => '是'
    ];

    const CK_PC_POST_TOPIC_DETAIL = 'ck:pc:post:topic:detail:%s';
    const GP_PC_POST_TOPIC_DETAIL = 'gp:pc:post:topic:detail';
    const CN_PC_POST_TOPIC_DETAIL = 'PC_论坛详情';

    const CK_PC_POST_TOPIC = 'ck:pc:post:topic';
    const GP_PC_POST_TOPIC = 'gp:pc:post:topic';
    const CN_PC_POST_TOPIC = 'PC_论坛列表';

    protected $guarded = 'id';

    public $timestamps = false;

    protected $appends = [
        'is_follow',
        'thumb_full',
        'bg_thumb_full'
    ];

    public function getThumbFullAttribute(): string
    {
        return url_cover($this->attributes['thumb'] ?? '');
    }

    public function getBgThumbFullAttribute(): string
    {
        return url_cover($this->attributes['bg_thumb'] ?? '');
    }

    public static function queryBase()
    {
        return self::query()
            ->where('status', '=', self::STATUS_NORMAL)
            ->where('web_show',self::WEB_OK);
    }

    public function getIsFollowAttribute(): int
    {
        if (is_null($this->watchUser) || !$this->getAttributeValue('id')) {
            return 0;
        }
        $flag = PostTopicUserLikeModel::hasFollowedTopic($this->watchUser->getAttributeValue('uid'), $this->getAttributeValue('id'));
        return $flag ? 1 : 0;
    }

    public static function listItems()
    {
        return cached(self::CK_PC_POST_TOPIC)
            ->clearCached()
            ->group(self::GP_PC_POST_TOPIC)
            ->chinese(self::CN_PC_POST_TOPIC)
            ->fetchPhp(function () {
                return self::queryBase()
                    ->selectRaw('id,web_name')
                    ->orderByDesc('sort')
                    ->orderByDesc('id')
                    ->get();
            });
    }

    public static function getTopicById($topicId)
    {
        $key = sprintf(self::CK_PC_POST_TOPIC_DETAIL, $topicId);
        return cached($key)
            ->group(self::GP_PC_POST_TOPIC_DETAIL)
            ->chinese(self::CN_PC_POST_TOPIC_DETAIL)
            ->fetchPhp(function () use ($topicId) {
                return self::queryBase()
                    ->where('id', $topicId)
                    ->first();
            });
    }

}
