<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class PcMhTabModel
 *
 * @property int $tab_id 
 * @property string $tab_name 导航蓝标签组
 * @property string $tags_str 标签
 * @property string $show_style
 * @property int $show_number  默认 6
 * @property int $sort_num 排序
 * @property int $status 1 启用
 * @property int $is_tab 主页展示
 * @property int $is_category 分类过滤展示
 * @property string $intro 介绍
 *
 * @author xiongba
 * @date 2022-05-17 17:36:52
 *
 * @mixin \Eloquent
 */
class PcMhTabModel extends Model
{

    protected $table = "pc_mh_tab";

    protected $primaryKey = 'tab_id';

    protected $fillable = ['tab_name', 'tags_str', 'sort_num', 'status', 'is_tab', 'is_category','show_style','show_number','intro'];

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

    const CK_PC_MH_TAB = 'ck:pc:mh:tab';
    const GP_PC_MH_TAB = 'gp:pc:mh:tab';
    const CN_PC_MH_TAB = 'PC_漫画导航';

    const CK_PC_MH_TAB_INFO = 'ck:pc:mh:tab:info:%s';
    const GP_PC_MH_TAB_INFO = 'gp:pc:mh:tab:info';
    const CN_PC_MH_TAB_INFO = 'PC_漫画导航详情 ';

    const MH_TAB_SELECT_RAW = '';

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
    public static function getMatchString($tab_id)
    {
        $key = 't:manhua:' . $tab_id;
        return cached($key)->expired(900)->fetch(function () use ($tab_id) {
            $tagStr = self::where('tab_id', $tab_id)->value('tags_str');
            $tagStr = str_replace(',', ' ', $tagStr);
            return $tagStr;
        });

    }

    public static function listItems(){
        return cached(self::CK_PC_MH_TAB)
            ->group(self::GP_PC_MH_TAB)
            ->chinese(self::CN_PC_MH_TAB)
            ->fetchPhp(function (){
                return self::queryBase()
                    ->selectRaw('tab_id as id,tab_name')
                    ->orderByDesc('sort_num')
                    ->orderByDesc('id')
                    ->get();
            });
    }

    public static function getDetail($tab_id){
        return cached(sprintf(self::CK_PC_MH_TAB_INFO,$tab_id))
            ->group(self::GP_PC_MH_TAB_INFO)
            ->chinese(self::CN_PC_MH_TAB_INFO)
            ->fetchPhp(function () use ($tab_id){
                return self::queryBase()
                    ->selectRaw('tab_id as id,tab_name, tags_str, intro')
                    ->where('tab_id',$tab_id)
                    ->first();
            });
    }
}
