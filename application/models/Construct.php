<?php

/**
 * class ConstructModel
 *
 * @property string $bg_thumb 背景图
 * @property int $created_at 创建时间
 * @property int $favorites_num 收藏数
 * @property int $has_hyh 是否换一换
 * @property string $icon 图标
 * @property int $id
 * @property string $intro 简介
 * @property int $is_recommend 0 默认  1推荐
 * @property int $nag_id 导航ID
 * @property int $rating 打点统计
 * @property int $show_max 展示数量
 * @property int $show_style 展示样式
 * @property int $sort_num 排序
 * @property int $status 1 启用
 * @property string $sub_title 副标题
 * @property string $title 导航蓝标签组
 * @property int $type 类型 0普通 1猜你喜欢 <后面可扩展>
 * @property int $updated_at 修改时间
 * @property int $work_num 作品数
 * @property int $has_tab 是否有查询导航栏
 *
 * @date 2024-10-15 15:31:21
 *
 * @mixin \Eloquent
 */
class ConstructModel extends EloquentModel
{

    protected $table = "construct";

    protected $primaryKey = 'id';

    protected $fillable = [
        'bg_thumb',
        'created_at',
        'favorites_num',
        'has_hyh',
        'icon',
        'intro',
        'is_recommend',
        'nag_id',
        'rating',
        'show_max',
        'show_style',
        'sort_num',
        'status',
        'sub_title',
        'title',
        'type',
        'updated_at',
        'work_num',
        'has_tab'
    ];

    protected $guarded = 'id';

    public $timestamps = true;

    const STATUS_YES = 1;
    const STATUS_NO = 0;
    const STATUS = [
        self::STATUS_NO  => '否',
        self::STATUS_YES => '是',
    ];

    const HAS_HYH_NO = 0;
    const HAS_HYH_YES = 1;
    const HYH_TIPS = [
        self::HAS_HYH_NO  => '否',
        self::HAS_HYH_YES => '是',
    ];

    const HAS_TAB_NO = 0;
    const HAS_TAB_YES = 1;
    const TAB_TIPS = [
        self::HAS_TAB_NO  => '否',
        self::HAS_TAB_YES => '是',
    ];

    const NSG_TAB_LIST = "nag:tab:list:%s:%s:%s:%s";
    const NSG_TAB_LIST_GROUP = "nag:tab:list";
    const NSG_TAB_COUNT = "nag:tab:count:%s";
    const NSG_TAB_COUNT_GROUP = "nag:tab:count";

    const NSG_TAB_TAGS_LIST = "nag:tab:tags:list:%d";
    const NSG_TAB_TAGS_GROUP = "nag:tab:tags:group";
    const NSG_TAB_TAGS_CN = "导航-结构列表";


    const TAB_RECOMMEND_LIST = "tab:recommend:list:%s";

    const CK_MEMBER_GUESS_LIKE_TAG = 'member:mv:guess:like:tag:%s:%s';
    const GP_MEMBER_GUESS_LIKE_TAG = 'member:mv:guess:like:tag';

    const SHOW_STYLE_HUA_HP = 0;
    const SHOW_STYLE_HUA_SP = 1;
    const SHOW_STYLE_C_TIAN = 2;
    //ver2.0 新增
    const SHOW_STYLE_HD_HP = 3;
    const SHOW_STYLE_HD_HP_PLUS = 4;
    const SHOW_STYLE_HD_SP = 5;
    const SHOW_STYLE = [
        self::SHOW_STYLE_HUA_HP     => '0.横屏2*2',
        self::SHOW_STYLE_HUA_SP     => '1.滑动竖屏',
        self::SHOW_STYLE_C_TIAN     => '2.横屏1+2*2',
        self::SHOW_STYLE_HD_HP      => '3.滑动横屏',
        self::SHOW_STYLE_HD_HP_PLUS => '4.滑动突出横屏',
        self::SHOW_STYLE_HD_SP      => '5.竖屏3*3排列'
    ];

    const TYPE_COMMON = 0;
    const TYPE_LIKE = 1;
    const TYPE_HOT = 2;
    const TYPE_SALE = 3;
    const TYPE_NEW = 4;
    const TYPE_HOT_TODAY = 5;
    const TYPE_TIPS = [
        self::TYPE_COMMON     => '普通',
        self::TYPE_LIKE       => '大家都喜欢',
        self::TYPE_HOT        => '最热',
        self::TYPE_SALE       => '畅销',
        self::TYPE_NEW        => '最新',
        self::TYPE_HOT_TODAY  => '今日热点',
    ];

    const FIND_TYPE_COINS = 'coins';
    const FIND_TYPE_VIP = 'vip';
    const FIND_TYPE_LIKE = 'like';
    const FIND_TYPE_VIEW = 'view';
    const FIND_TYPE_TOPIC = 'topic';
    const FIND_TYPE_FIND = 'find';

    public function navigation()
    {
        return $this->hasOne(NavigationModel::class, 'id', 'nag_id');
    }

    public function getIconAttribute()
    {
        if (MODULE_NAME == 'api'){
            return url_cover($this->attributes['icon']);
        }
        return $this->attributes['icon'];
    }

    public function getBgThumbAttribute()
    {
        if (MODULE_NAME == 'api'){
            return url_cover($this->attributes['bg_thumb']);
        }
        return $this->attributes['bg_thumb'];
    }

    public static function queryBase($where = [])
    {
        $query = self::where('status', '=', self::STATUS_YES);
        if ($where) {
            $query->where($where);
        }
        return $query;
    }

    public static function incrByRating($id)
    {
        bg_run(function () use ($id){
            $key = "tab:rating:key:%d";
            $key = sprintf($key, $id);
            $val = redis()->incrBy($key, 1);
            $val = intval($val);
            if ($val >= 20){
                TabModel::where('id',$id)->increment('rating', $val);
                redis()->del($key);
            }
        });
    }

    /**
     * 打点统计
     * @param $id
     * @param int $count
     * @return int
     */
    static function incrementRating($id,$count =1){
        return self::where('id',$id)->increment('rating',$count);
    }

    /**
     * 根据id 获取tab 搜索标签信息
     * @param $id
     * @return mixed
     */
    static function getMatchString($id)
    {
        if(!$id){
            return '';
        }
        $key = 'ts:' . $id;
        //打点统计
        self::incrementRating($id);
        return cached($key)->expired(900)->usingFuck(false)->fetch(function () use ($id) {
            $tagStr = TabModel::where('id', $id)->value('tags_str');
            $tagStr = str_replace(',', ' ', $tagStr);
            return $tagStr;
        });
    }

    public static function getTabListByNag($nag_id, $page, $limit){
        $cacheKey = sprintf(self::NSG_TAB_LIST,$nag_id, $page, $limit, 0);
        $groupKey = self::NSG_TAB_LIST_GROUP;
        return cached($cacheKey)
            ->group($groupKey)
            ->chinese('导航-视频分类列表')
            ->fetchPhp(function () use($nag_id, $limit, $page){
                return self::queryBase()
                    ->selectRaw('id,icon,bg_thumb,title,work_num,favorites_num')
                    ->where('nag_id',$nag_id)
                    ->orderByDesc('sort_num')
                    ->forPage($page,$limit)
                    ->get();
            });
    }

    public static function getTabCountByNag($nag_id){
        $cacheKey = sprintf(self::NSG_TAB_COUNT,$nag_id);
        $groupKey = self::NSG_TAB_COUNT_GROUP;
        return cached($cacheKey)
            ->group($groupKey)
            ->chinese('导航-视频分类数量')
            ->fetchJson(function () use($nag_id){
                return self::queryBase()
                    ->where('nag_id',$nag_id)
                    ->count();
            });
    }

    public static function getMvByTag(MemberModel $member, \NavigationModel $nag, $type, $page){
        $limit = 5;
        $nag_id = $nag->id;
        $cacheKey = sprintf(self::NSG_TAB_LIST,$nag_id, $type, $page, $limit);
        $data = cached($cacheKey)
            ->group(self::NSG_TAB_LIST_GROUP)
            ->chinese('导航-视频分类')
            ->fetchPhp(function () use($nag_id,$type,$page,$limit){
                return self::queryBase()
                    ->selectRaw('id,nag_id,title,sub_title,show_style,show_max,has_hyh,icon,type,has_tab')
                    ->where('nag_id',$nag_id)
                    ->orderByDesc('sort_num')
                    ->forPage($page, $limit)
                    ->get()
                    ->map(function (ConstructModel $item) use ($type) {
                        $mvData = [];
                        switch ($item->type) {
                            case self::TYPE_COMMON:
                                //普通
                                $mvData = MvModel::getHomeMvDataByTag($item->id, $type, 'like', 1 , 30);
                                break;
                            case self::TYPE_LIKE:
                                //大家都喜欢
                                $mvData = MvModel::getHomeMvDataByTag(0, $type, 'like',1,30);
                                break;
                            case self::TYPE_HOT:
                                //最热
                                $mvData = MvModel::getHomeMvDataByTag(0, $type, 'hot',1,30);
                                break;
                            case self::TYPE_SALE:
                                //畅销榜
                                $mvData = MvModel::getHomeMvDataByTag(0, $type, 'sale',1,30);
                                break;
                            case self::TYPE_NEW:
                                //最新
                                $mvData = MvModel::getHomeMvDataByTag(0, $type, 'new',1,30);
                                break;
                            case self::TYPE_HOT_TODAY:
                                //今日热点
                                $date = date('Y-m-d');
                                $tuiVidArray = [];
                                $mvData = DailyVideoModel::getVideoByDate($date, 30, $tuiVidArray);
                                $item->sub_title = date('m-d') . $item->sub_title;
                                break;
                            default:
                                break;
                        }
                        if ($mvData) {
                            $show_max = $item->show_max ?: 4;
                            return [
                                'id'         => $item->id,
                                'nag_id'     => $item->nag_id,
                                'icon'       => $item->icon,
                                'title'      => $item->title,
                                'sub_title'  => $item->sub_title,
                                'show_style' => $item->show_style,
                                'show_max'   => $show_max,
                                'list'       => $mvData,
                                'type'       => $item->type,
                                'has_tab'    => $item->has_tab,
                            ];
                        }
                        return [];
                    })->filter()->values();
            });

        return collect($data)->map(function ($item){
            return self::formatRecommendMv($item);
        })->filter()->values();
    }

    public static function getConstructIdsByNag($nag_id){
        $cacheKey = sprintf(self::NSG_TAB_TAGS_LIST, $nag_id);
        return cached($cacheKey)
            ->group(self::NSG_TAB_TAGS_GROUP)
            ->chinese(self::NSG_TAB_TAGS_CN)
            ->fetchJson(function () use ($nag_id){
                return self::queryBase()
                    ->where('nag_id',$nag_id)
                    ->get()->pluck('id')->toArray();
            });
    }

    public static function findById($id){
        return cached("tab:detail:".$id)
            ->group("tab:detail")
            ->chinese('视频分类详情')
            ->fetchPhp(function () use ($id){
                return self::queryBase()
                    ->selectRaw('id,title,icon,sub_title,show_max,nag_id,work_num,favorites_num,bg_thumb,intro,rating,type')
                    ->with('navigation')
                    ->where('id',$id)
                    ->first();
            });
    }

    public static function recommendList($p_type){
        //查询所有的导航
        $nags = NavigationModel::getListByType($p_type);
        $tabs = TabModel::getListByNags($nags);
        $key = sprintf(self::TAB_RECOMMEND_LIST, $p_type);
        return cached($key)
            ->fetchPhp(function () use ($tabs){
                return self::queryBase()
                    ->selectRaw('id,icon,title,work_num,favorites_num,bg_thumb')
                    ->whereIn('id',$tabs)
                    ->where('is_search',self::STATUS_YES)
                    ->limit(10)
                    ->orderByDesc('sort_num')
                    ->get()->map(function (TabModel $item){
                        $item->setHidden(['tags_ary']);
                        return $item;
                    });
            },900);
    }

    //格式化推荐视频
    public static function formatRecommendMv($item){
        $list = $item['list'];
        $rand_no = [];
        $rand_yes = [];
        foreach ($list as $v){
            if ($v->sort > 0){
                $rand_no[] = $v;
            }else{
                $rand_yes[] = $v;
            }
        }
        //排序视频数量大于需要取的数据
        if (count($rand_no) >= $item['show_max']){
            $new_list = array_slice($rand_no, 0 , $item['show_max']);
        }else{
            $new_list = $rand_no;
            $need_ct = $item['show_max'] - count($rand_no);
            if (count($rand_yes)  < $need_ct){
                $need_ct = count($rand_yes);
            }
            if ($need_ct > 1){
                $arr_index = array_rand($rand_yes, $need_ct);
                foreach ($arr_index as $k){
                    $new_list[] = $rand_yes[$k];
                }
            }else{
                $new_list = array_merge($new_list, $rand_yes);
            }
        }
        $item['list'] = $new_list;
        return $item;
    }

    public static function getAdminConstruct(){
        $construct4 = [];
        $nag_list = NavigationModel::queryBase()->where('is_find', NavigationModel::FIND_NO)->get();
        foreach ($nag_list as $v1) {
            $cur_construct = ['id' => $v1->id, 'name' => $v1->title];
            $constructs_tmp = self::where('nag_id', $v1->id)->where('type', self::TYPE_COMMON)->pluck('title', 'id')->toArray();
            foreach ($constructs_tmp as $k => $name) {
                $cur_construct['eles'][] = ['id' => $k, 'name' => $name];
            }
            $construct4[] = $cur_construct;
        }

        return $construct4;
    }
}
