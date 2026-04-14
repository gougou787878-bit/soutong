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
 * @property string $intro 简介
 *
 * @mixin \Eloquent
 */
class PcTabModel extends EloquentModel
{

    const STATUS_YES = 1;
    const STATUS_NO = 0;
    const STATUS = [
        self::STATUS_NO  => '否',
        self::STATUS_YES => '是',
    ];

    const CK_PC_TAB = 'ck:pc:tab:list';
    const GP_PC_TAB = 'gp:pc:tab:list';
    const CN_PC_TAB = 'PC_视频导航';
    const CK_PC_TAB_DETAIL = 'ck:pc:tab:detail:%d';
    const GP_PC_TAB_DETAIL = 'gp:pc:tab:detail';
    const CN_PC_TAB_DETAIL = 'PC_导航详情';


    protected $table = "pc_tab";

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
                           'intro',
    ];

    protected $guarded = 'tab_id';

    public $timestamps = true;
    public $dateFormat = 'U';

    protected $appends = ['tags_ary'];

    public static function listItems()
    {
        return cached(self::CK_PC_TAB)
            ->group(self::GP_PC_TAB)
            ->chinese(self::CN_PC_TAB)
            ->fetchPhp(function (){
                return self::queryBase()
                    ->selectRaw('tab_id as id,tab_name')
                    ->orderByDesc('sort_num')
                    ->orderByDesc('id')
                    ->get();
            });
    }

    public static function getDetail($tab_id)
    {
        $cacheKey = sprintf(self::CK_PC_TAB_DETAIL, $tab_id);
        return cached($cacheKey)
            ->group(self::GP_PC_TAB_DETAIL)
            ->chinese(self::CN_PC_TAB_DETAIL)
            ->fetchPhp(function () use ($tab_id) {
                return self::queryBase()
                    ->selectRaw('tab_id as id,tab_name,tags_str,intro')
                    ->where('tab_id', $tab_id)
                    ->first();
            });
    }

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
}
