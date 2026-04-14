<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class MhModel
 *
 * @property int $id
 * @property string $origin_id
 * @property string $title 标题
 * @property string $description 描述
 * @property string $author 作者
 * @property int $category_id 漫画分类标识
 * @property int $uid 用户id
 * @property string $bg_thumb 详情背景图
 * @property string $thumb 封面图
 * @property int $favorites 收藏人数
 * @property string $tags 标签
 * @property int $is_finish 状态 0 未完结， 1已完结
 * @property string $update_time 更新时间 周一 - 周日
 * @property int $recommend 是否推荐
 * @property int $status 1上架0下架
 * @property int $is_free 0 免费 1 vip 2  钻石（金币）
 * @property string $refresh_at 刷新时间
 * @property int $rating 浏览数
 * @property int $coins 定价
 * @property int $from 来源
 * @property int $is_like
 * @property int $is_pay
 * @property int $newest_series 最近更新到的章节
 * @property string $type  类型  long  short  single
 * @property array|null $series
 *
 * @author xiongba
 * @date 2022-05-17 17:35:18
 *
 * @mixin \Eloquent
 */
class MhModel extends EloquentModel
{

    protected $table = "mh";

    protected $primaryKey = 'id';

    protected $fillable = [
        'origin_id',
        'title',
        'description',
        'author',
        'category_id',
        'uid',
        'bg_thumb',
        'thumb',
        'favorites',
        'tags',
        'is_finish',
        'update_time',
        'recommend',
        'status',
        'is_free',
        'refresh_at',
        'rating',
        'coins',
        'from',
        'newest_series',
        'type',
        'p_id'
    ];

    protected $guarded = 'id';

    public $timestamps = false;


    //状态 0 未完结， 1已完结
    const FINISH_YES = 1;
    const  FINISH_NO = 0;
    const  FINISH = [
        self::FINISH_NO  => '未完结',
        self::FINISH_YES => '已完结',
    ];
    //推荐
    const RECOMMEND_YES = 1;
    const RECOMMEND_NO = 0;
    const RECOMMEND = [
        self::RECOMMEND_YES => '已推荐',
        self::RECOMMEND_NO  => '未推荐',
    ];

    // 上架
    const STATUS_YES = 1;
    const STATUS_NO = 0;
    const STATUS = [
        self::STATUS_YES => '上架',
        self::STATUS_NO  => '下架',
    ];
    //类型
    //0 免费 1 vip 2  钻石（金币）3 vip 权限
    const IS_TYPE_FREE = 0;
    const IS_TYPE_VIP = 1;
    const IS_TYPE_COIN = 2;
    const IS_TYPE_TIMES = 3;
    const IS_TYPE = [
        self::IS_TYPE_FREE => '0 免费',
        self::IS_TYPE_VIP  => '1 vip',
        self::IS_TYPE_COIN => '2 金币',
        //self::IS_TYPE_TIMES,
    ];

    const CK_PC_MH_LIST = 'ck:pc:mh:list:%s:%s:%s:%s';
    const GP_PC_MH_LIST = 'gp:pc:mh:list';
    const CN_PC_MH_LIST = 'PC_漫画列表';

    const CK_PC_MH_LIST_CT = 'ck:pc:mh:list:ct:%s';
    const GP_PC_MH_LIST_CT = 'gp:pc:mh:list';
    const CN_PC_MH_LIST_CT = 'PC_漫画列表总和数';

    const CK_PC_MH_SEARCH_LIST = 'ck:pc:mh:search:list:%s:%s:%s:%s';
    const GP_PC_MH_SEARCH_LIST = 'gp:pc:mh:search:list';
    const CN_PC_MH_SEARCH_LIST = 'PC_搜索漫画列表';

    protected $hidden = ['bg_thumb'];
    protected $appends = ['thumb_full', 'is_pay', 'is_like', 'tags_list', 'sub_tips'];

    public function getSubTipsAttribute()
    {
        if ($this->is_finish) {
            return "共{$this->newest_series}话";
        }
        return "连载至{$this->newest_series}话";
    }
    public function getTagsListAttribute()
    {
        if (!isset($this->attributes['tags'])) {
            return [];
        }
        return array_map('trim', explode(',', $this->attributes['tags']));
    }

    public function getIsLikeAttribute()
    {
        $watchUser = self::$watchUser;
        if (empty($watchUser) || !$this->getAttributeValue('id')) {
            return 0;
        }
        return MhFavoritesModel::hasLike($watchUser->getAttributeValue('uid'),
            $this->getAttributeValue('id')) ? 1 : 0;
    }

    public function getIsPayAttribute()
    {
        $watchUser = self::$watchUser;
        if (empty($watchUser) || !$this->getAttributeValue('id')) {
            return 0;
        }

        $coins = $this->getAttributeValue('coins');
        if ($coins > 0){
            $resource_type = PrivilegeModel::RESOURCE_TYPE_COINS_MH;
        }else{
            $resource_type = PrivilegeModel::RESOURCE_TYPE_VIP_MH;
        }
        $hasPrivilege = UsersProductPrivilegeModel::hasPrivilege(USER_PRIVILEGE, $resource_type, PrivilegeModel::PRIVILEGE_TYPE_VIEW);
        if ($hasPrivilege){
            return 1;
        }

        if ($coins > 0){
            return MhPayModel::hasBuy($watchUser->getAttributeValue('uid'), $this->getAttributeValue('id')) ? 1 : 0;
        }

        return 0;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function series()
    {
        return self::hasMany(MhSeriesModel::class, 'pid', 'id')->orderBy('episode');
    }

    public function getThumbFullAttribute()
    {
        if ($thumb = $this->attributes['thumb']) {
            if (!$this->from) {
                // $thumb = 'images/mh/' . ltrim($thumb, '/');
                $thumb = ltrim($thumb, '/');
                $moduleName =  Yaf\Application::app()->getDispatcher()->getRequest()->getModuleName();
                if (strcasecmp($moduleName , 'admin') === 0){
                    return 'https://imgpublic.ycomesc.live/'.$thumb;
                }
            }
            return url_cover($thumb);
        }
        return '';
    }

    static function addView($comics_id)
    {
        return self::where(['id' => $comics_id])->increment('rating');
    }

    public static function queryBase()
    {
        return self::where(['status' => self::STATUS_YES]);
    }

    static function getRow($id)
    {
        return self::queryBase()->find($id);
    }

    public static function listByTab(PcMhTabModel $mhTab,$sort,$page,$limit){
        $key = sprintf(self::CK_PC_MH_LIST,$mhTab->id,$sort,$page,$limit);
        $tagStr = $mhTab->tags_str;
        $list = cached($key)
            ->group(self::GP_PC_MH_LIST)
            ->chinese(self::CN_PC_MH_LIST)
            ->fetchPhp(function () use ($tagStr,$sort,$page,$limit){
                return self::queryBase()
                    ->when($tagStr,function ($q) use ($tagStr){
                        return $q->whereRaw("match(tags) against(? in boolean mode)", [$tagStr]);
                    })
                    ->when($sort == 'recommend',function ($q){
                        return $q->orderByDesc('recommend');
                    })
                    ->when($sort == 'hot',function ($q){
                        return $q->orderByDesc('rating');
                    })
                    ->when($sort == 'favorites',function ($q){
                        return $q->orderByDesc('favorites');
                    })
                    ->when($sort == 'new',function ($q){
                        return $q->orderByDesc('refresh_at');
                    })
                    ->orderByDesc('id')
                    ->forPage($page,$limit)
                    ->get();
            });
        //总数
        $ct =  self::listByTabTotal($mhTab);

        // 这个需要分页
        return collect([
            'list'       => $list,
            'cur_page'   => $page,
            'total_page' => ceil($ct / $limit),
            'limit'      => $limit,
            'count'      => $ct
        ]);
    }

    public static function listByTabTotal(PcMhTabModel $mhTab){
        $key = sprintf(self::CK_PC_MH_LIST_CT,$mhTab->id);
        $tagStr = $mhTab->tags_str;
        return cached($key)
            ->group(self::GP_PC_MH_LIST_CT)
            ->chinese(self::CN_PC_MH_LIST_CT)
            ->fetchJson(function () use ($tagStr){
                return self::queryBase()
                    ->when($tagStr,function ($q) use ($tagStr){
                        return $q->whereRaw("match(tags) against(? in boolean mode)", [$tagStr]);
                    })
                    ->count('id');
            });
    }

    public static function searchList($tagStr,$kwy,$page,$limit){
        $key = sprintf(self::CK_PC_MH_SEARCH_LIST,$tagStr,$kwy,$page,$limit);
        return cached($key)
            ->group(self::GP_PC_MH_SEARCH_LIST)
            ->chinese(self::CN_PC_MH_SEARCH_LIST)
            ->fetchPhp(function () use ($tagStr,$kwy,$page,$limit){
                return self::queryBase()
                    ->when($tagStr,function ($q) use ($tagStr){
                        return $q->whereRaw("match(tags) against(? in boolean mode)", [$tagStr]);
                    })
                    ->when($kwy,function ($q) use ($kwy){
                        return $q->where('title','like',"%{$kwy}%");
                    })
                    ->orderByDesc('refresh_at')
                    ->orderByDesc('id')
                    ->forPage($page,$limit)
                    ->get();
            });
    }

    public static function guessByManHuaLike($manhua_id,$limit){
        return self::queryBase()
            ->where('id', '!=', $manhua_id)
            ->orderByDesc('recommend')
            ->orderByDesc('refresh_at')
            ->limit($limit)
            ->get();
    }

}
