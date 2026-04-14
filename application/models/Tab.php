<?php


/**
 * class TabModel
 *
 * @property int $tab_id
 * @property string $tab_name 导航蓝标签组
 * @property string $tags_str 标签id
 * @property int $sort_num 排序
 * @property int $is_tab 首页导航
 * @property int $is_search
 * @property int $is_category 分类
 * @property int $created_at 创建时间
 * @property int $updated_at 修改时间
 *
 * @author xiongba
 * @date 2019-12-16 13:02:49
 *
 * @mixin \Eloquent
 */
class TabModel extends EloquentModel
{

    const STATUS_YES = 1;
    const STATUS_NO = 0;
    const STATUS = [
        self::STATUS_NO  => '否',
        self::STATUS_YES => '是',
    ];


    protected $table = "tab";

    protected $primaryKey = 'tab_id';

    protected $fillable = ['tab_name',
                           'tags_str',
                           'sort_num',
                           'status',
                           'created_at',
                           'updated_at',
                           'is_tab',
                           'is_search',
                           'is_category',
                           'is_aw',
    ];

    protected $guarded = 'tab_id';

    public $timestamps = true;
    public $dateFormat = 'U';

    protected $appends = ['tags_ary', 'created_str', 'updated_str'];


    public function getTagsAryAttribute()
    {
        if (!isset($this->attributes['tags_str'])) {
            return [];
        }
        return explode(',', $this->attributes['tags_str']);
    }


    public function getCreatedStrAttribute()
    {
        if (!isset($this->attributes['created_at'])) {
            return null;
        }
        return date('Y-m-d h:i:s', $this->attributes['created_at']);
    }


    public function getUpdatedStrAttribute()
    {
        if (!isset($this->attributes['updated_at'])) {
            return null;
        }
        return date('Y-m-d h:i:s', $this->attributes['updated_at']);
    }

    public static function queryBase($where = [])
    {

        $query = self::where('status', '=', self::STATUS_YES);
        if ($where) {
            $query->where($where);
        }
        return $query;
    }

    /**
     * 根据tab_id 获取tab 搜索标签信息
     * @param $tab_id
     * @return mixed
     */
    static function getMatchString($tab_id)
    {
        $key = 't:m' . $tab_id;
        return cached($key)->expired(900)->fetch(function () use ($tab_id) {
            $tagStr = TabModel::where('tab_id', $tab_id)->value('tags_str');
            $tagStr = explode(',', $tagStr);
            if (count($tagStr) > 10){
                $tagStr = array_slice($tagStr, 0, 10);
            }
            return implode(' ', $tagStr);
        });

    }


}
