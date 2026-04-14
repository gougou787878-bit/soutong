<?php


/**
 * class IndexModelModel
 *
 * @property int $id
 * @property string $title 标题
 * @property string $desc 描述
 * @property string $bgUrl 背景图片
 * @property string $bgColor 背景色
 * @property string $type 类型
 * @property string $icon 图标，
 * @property string $flag 条船到指定ui要传递的参数
 * @property int $sort 排序
 * @property int $ver 版本号
 *
 * @author xiongba
 * @date 2020-06-16 20:05:32
 *
 * @mixin \Eloquent
 */
class IndexModelModel extends EloquentModel
{

    protected $table = "index_model";

    protected $primaryKey = 'id';

    protected $fillable = ['title', 'desc', 'bgUrl', 'bgColor', 'type', 'icon', 'flag', 'sort', 'ver', 'status'];
    protected $appends = ['exp'];

    protected $guarded = 'id';

    public $timestamps = false;


    const STATUS_OK = 1;
    const STATUS_NO = 0;
    const STATUS = [
        self::STATUS_OK => '正常',
        self::STATUS_NO => '隐藏',
    ];


    const TYPE_NEWEST = 'newest';
    const TYPE_HOTTEST = 'hottest';
    const TYPE_DIAMOND_PLAZA = 'diamond_plaza';
    const TYPE_TOPIC = 'topic';
    const TYPE_TAG = 'tag';
    const TYPE = [
        self::TYPE_NEWEST        => '最新',
        self::TYPE_HOTTEST       => '最热',
        self::TYPE_DIAMOND_PLAZA => '砖石广场',
        self::TYPE_TOPIC         => '合集',
        self::TYPE_TAG           => '标签',
    ];

    public static function queryBase()
    {
        return self::where('status', '=', self::STATUS_OK);
    }

    public static function queryBaseV15($where = [])
    {
        $query = self::where('status', '=', self::STATUS_OK);
        if ($where){
            $query->where($where);
        }
        return $query;
    }

    public function getExpAttribute(){

        $type = $this->attributes['type'] ?? '';
        $flag = $this->attributes['flag'] ?? '';
        $exp = 0;
        if ($type == self::TYPE_TOPIC) {
            $exp = TopicRelationModel::where('topic_id' , '=' , $flag)->count();
        } elseif ($type == self::TYPE_TAG) {
            $exp = MvTagModel::where('tag' , '=' , $flag)->count();
        }
        return $exp;
    }

    public function emitChange($release)
    {
        redis()->del('index:model');
    }

    public function getImgList()
    {
        $imgs = [];
        $query = MvModel::queryBase()->select(['cover_thumb']);
        if ($this->type == self::TYPE_NEWEST) {
            $query->orderByDesc('id')
                ->limit(3)
                ->get()
                ->map(function ($item) use (&$imgs) {
                    $imgs[] = url_cover($item->cover_thumb);
                });
        } elseif ($this->type == self::TYPE_HOTTEST) {
            $query->orderByDesc('like')
                ->limit(3)
                ->get()
                ->map(function (MvModel $item) use (&$imgs) {
                    $imgs[] = url_cover($item->cover_thumb);
                });
        } elseif ($this->type == self::TYPE_TOPIC) {
            TopicRelationModel::from('topic_relation as tr')
                ->leftJoin('mv', function (/**@var \Illuminate\Database\Query\JoinClause $join */ $join) {
                    return $join->on('mv.id', '=', 'tr.mv_id');
                })
                ->where('tr.topic_id', '=', $this->flag)
                ->select(['mv.cover_thumb'])
                ->limit(4)
                ->get()->map(function ($item) use (&$imgs) {
                    $imgs[] = url_cover($item->cover_thumb);
                });
        } elseif ($this->type == self::TYPE_TAG) {
            TopicRelationModel::from('mv_tags as tag')
                ->leftJoin('mv', function (/**@var \Illuminate\Database\Query\JoinClause $join */ $join) {
                    return $join->on('mv.id', '=', 'tag.mv_id');
                })
                ->where('tag.tag', '=', $this->flag)
                ->select(['mv.cover_thumb'])
                ->limit(4)
                ->get()->map(function ($item) use (&$imgs) {
                    $imgs[] = url_cover($item->cover_thumb);
                });
        }

        return $imgs;
    }


}
