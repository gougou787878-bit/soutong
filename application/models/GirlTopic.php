<?php


/**
 * class GirlTopicModel
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
 * @author xiongba
 * @date 2023-06-09 20:11:13
 *
 * @mixin \Eloquent
 */
class GirlTopicModel extends EloquentModel
{

    protected $table = "girl_topic";

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
        'girl_num',
        'intro',
        'web_name',
        'web_show'
    ];

    const POST_TOPIC_DETAIL_KEY = 'p:td:%s';
    const GIRL_TOPIC_ALL_GROUP_KEY = 'girl_topic_list';

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


    const CK_TOPIC_ALL = 'ck:girl:topic:all:%s:%s';
    const GP_TOPIC_ALL = 'gp:girl:topic:all';

    const GIRL_TOPIC_HOT_LIST_KEY = 'girl:hot:tab';

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
        return self::query()->where('status', '=', self::STATUS_NORMAL);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Tbold\Library\LibBuilder[]|\Tbold\Library\LibCollection
     */
    static function getAllTopic()
    {
        return self::queryBase()->select([
            'id',
            'name'
        ])->orderByDesc('is_hot')->orderByDesc('sort')->orderByDesc('id')->get();
    }

    public function getIsFollowAttribute(): int
    {
        if (is_null($this->watchUser) || !$this->getAttributeValue('id')) {
            return 0;
        }
        $flag = GirlTopicUserLikeModel::hasFollowedTopic($this->watchUser->getAttributeValue('uid'), $this->getAttributeValue('id'));
        return $flag ? 1 : 0;
    }

    // 获取话题详情
    public static function getTopicById($topicId,$member = null)
    {
        $cacheKey = sprintf(self::POST_TOPIC_DETAIL_KEY, $topicId);

        $topic = cached($cacheKey)
            ->clearCached()
            ->fetchJson(function () use ($topicId) {
                $data = self::queryBase()->where(['id' => $topicId])->first();
                return is_null($data) ? [] : $data->getAttributes();
            }, 900);
        if ($topic) {
            $topic = GirlTopicModel::makeOnce($topic);
            if (!is_null($member)) {
                $topic->watchByUser($member);
            }
        }
        return $topic;
    }

    // 获取分页话题数据, $member = null
    public static function listTopics($page, $limit, $member = null)
    {
        $cacheKey = sprintf(self::CK_TOPIC_ALL, $page, $limit);
        $topics = cached($cacheKey)
            ->group(self::GP_TOPIC_ALL)
            ->fetchJson(function () use ($page, $limit) {
                return self::queryBase()
                    ->orderByDesc('sort')
                    ->orderByDesc('id')
                    ->forPage($page, $limit)
                    ->get()->toArray();
            }, 900);
        if ($topics) {
            return collect($topics)->map(function ($topic) use ($member) {
                $topic = GirlTopicModel::makeOnce($topic);
                if (!is_null($member)) {
                    $topic->watchByUser($member);
                }
                return $topic;
            })->values();

        }
        return $topics;
    }


    public static function listHotTopics($limit = 6)
    {
        return cached(self::GIRL_TOPIC_HOT_LIST_KEY)->group(self::GIRL_TOPIC_ALL_GROUP_KEY)
            ->fetchPhp(function () use ($limit) {
                return self::queryBase()
                    ->where('is_hot', self::HOT_OK)
                    ->orderByDesc('sort')
                    ->limit($limit)
                    ->get();
            }, 900);
    }
}
