<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class StoryTabModel
 *
 * @property int $tab_id 
 * @property string $tab_name 导航蓝标签组
 * @property string $tags_str 标签
 * @property int $sort_num 排序
 * @property int $status 1 启用
 * @property int $is_tab 主页展示
 * @property int $is_category 分类过滤展示
 * @property string $show_style 
 * @property int $show_number 
 *
 * @author xiongba
 * @date 2022-06-28 20:56:20
 *
 * @mixin \Eloquent
 */
class StoryTabModel extends Model
{

    protected $table = "story_tab";

    protected $primaryKey = 'tab_id';

    protected $fillable = ['tab_name', 'tags_str', 'sort_num', 'status', 'is_tab', 'is_category', 'show_style', 'show_number'];

    protected $guarded = 'tab_id';

    public $timestamps = false;


    const SHOW_STYLE_DF = 'H-1*N';
    const SHOW_NUMBER_DF = 6;
    const SHOW_STYLE = [
        self::SHOW_STYLE_DF => '水平-1xN展示',
        'V-3*N'             => '平铺-3xN展示',
        'V-2*N'             => '平铺-2xN展示',
    ];

    const SHOW_STYLE_TYPE = [
        self::SHOW_STYLE_DF => '1',
        'V-3*N'             => '3',
        'V-2*N'             => '2',
    ];

    const STATUS_YES = 1;
    const STATUS_NO = 0;
    const STATUS = [
        self::STATUS_NO  => '否',
        self::STATUS_YES => '是',
    ];
    protected $appends = ['tags_ary'];


    public function getTagsAryAttribute()
    {
        if (!isset($this->attributes['tags_str'])) {
            return [];
        }
        return explode(',', $this->attributes['tags_str']);
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
        $key = 't:story:' . $tab_id;
        return cached($key)->expired(900)->fetch(function () use ($tab_id) {
            $tagStr = self::where('tab_id', $tab_id)->value('tags_str');
            $tagStr = str_replace(',', ' ', $tagStr);
            return $tagStr;
        });

    }


}
