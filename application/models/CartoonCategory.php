<?php

/**
 * class CartoonCategoryModel
 * @property int $id
 * @property int $is_recommend 1 推荐
 * @property int $rating 点击量
 * @property int $show_max 默认最大展示数量
 * @property int $show_style 0:1*3 1:1*2 2:1*1 3:1*N
 * @property int $sort 排序
 * @property int $status 状态
 * @property string $sub_title 副标题
 * @property string $thumb 封面
 * @property string $title 标题
 * @property int $type 类型 0普通 1最多喜欢 2畅销榜 3最新 4手游
 * @property string $updated_at 更新时间
 * @property int $works_num 作品数
 * @property string $created_at 创建时间
 *
 *
 *
 * @date 2024-04-01 15:51:22
 *
 * @mixin \Eloquent
 */
class CartoonCategoryModel extends EloquentModel
{
    protected $table = "cartoon_category";
    protected $primaryKey = 'id';
    protected $fillable = [
        'created_at',
        'is_recommend',
        'rating',
        'show_max',
        'show_style',
        'sort',
        'status',
        'sub_title',
        'thumb',
        'title',
        'type',
        'updated_at',
        'works_num'
    ];
    protected $guarded = 'id';
    public $timestamps = true;

    const STATUS_NO = 0;
    const STATUS_OK = 1;
    const STATUS_TIPS = [
        self::STATUS_NO => '下架',
        self::STATUS_OK => '上架',
    ];

    const SHOW_STYLE_ONE = 0;
    const SHOW_STYLE_TWO = 1;
    const SHOW_STYLE_THREE = 2;
    const SHOW_STYLE_FOUR = 3;
    const SHOW_STYLE_TIPS = [
        self::SHOW_STYLE_ONE => '1 X 3',
        self::SHOW_STYLE_TWO => '1 X 2',
        self::SHOW_STYLE_THREE => '1 X 1',
        self::SHOW_STYLE_FOUR => '1 X N',
    ];

    const TYPE_COM = 0;
    const TYPE_LIKE = 1;
    const TYPE_SALE = 2;
    const TYPE_NEW = 3;
    const TYPE_TIPS = [
        self::TYPE_COM => '普通',
        self::TYPE_LIKE => '最多喜欢',
        self::TYPE_SALE => '畅销',
        self::TYPE_NEW => '最新',
    ];

    const RECOMMEND_NO = 0;
    const RECOMMEND_OK = 1;
    const RECOMMEND_TIPS = [
        self::RECOMMEND_NO => '否',
        self::RECOMMEND_OK => '是',
    ];

    const CK_CARTOON_SERIES_DETAIL = 'redis:cartoon:series:detail:%d';
    const GP_CARTOON_SERIES_DETAIL = 'redis:cartoon:series:detail:group';
    const CN_CARTOON_SERIES_DETAIL = '动漫分类详情';

    const CK_CARTOON_SERIES_HOME_CAT = 'ck:cartoon:game:series:home:cat:key:%d:%d';
    const GP_CARTOON_SERIES_HOME_CAT = 'gp:cartoon:game:series:home:cat:key';
    const CN_CARTOON_SERIES_HOME_CAT = '动漫首页系列分类列表';

    public function getThumbAttribute()
    {
        $thumb = $this->attributes['thumb'];
        if (MODULE_NAME != 'admin'){
            return $thumb ? url_cover($thumb) : url_cover('/upload/ads/20240118/2024011815375482464.jpeg');
        }else{
            return $thumb ? url_cover($thumb) : '';
        }
    }

    public static function queryBase(){
        return self::query()->where('status', self::STATUS_OK);
    }

    public static function findById($id)
    {
        $key = sprintf(self::CK_CARTOON_SERIES_DETAIL, $id);
        return cached($key)
            ->group(self::GP_CARTOON_SERIES_DETAIL)
            ->chinese(self::CN_CARTOON_SERIES_DETAIL)
            ->fetchPhp(function () use ($id){
                return self::queryBase()->where('id',$id)->first();
            });
    }

    public static function getListByCat(MemberModel $member, $page, $limit = 5){
        $key = sprintf(self::CK_CARTOON_SERIES_HOME_CAT, $page, $limit);
        return cached($key)
            ->group(self::GP_CARTOON_SERIES_HOME_CAT)
            ->chinese(self::CN_CARTOON_SERIES_HOME_CAT)
            ->fetchPhp(function () use ($page, $limit){
                return self::queryBase()
                    ->orderByDesc('sort')
                    ->orderByDesc('id')
                    ->forPage($page, $limit)
                    ->get()
                    ->map(function (self $item) {
                        $cartoon_data = [];
                        switch ($item->type) {
                            case self::TYPE_COM:
                                $cartoon_data = CartoonModel::getRecommendPicBySeries($item->id, $item->show_max);
                                $more_api_params = ['sort' => 'hot', 'id' => $item->id];
                                break;
                            case self::TYPE_LIKE:
                                $cartoon_data = CartoonModel::getRecommendPic('like', $item-$this->show_max);
                                $more_api_params = ['sort' => 'like', 'id' => $item->id];
                                break;
                            case self::TYPE_SALE:
                                $cartoon_data = CartoonModel::getRecommendPic('sale', $item-$this->show_max);
                                $more_api_params = ['sort' => 'sale', 'id' => $item->id];
                                break;
                            case self::TYPE_NEW:
                                $cartoon_data = CartoonModel::getRecommendPic('new', $item-$this->show_max);
                                $more_api_params = ['sort' => 'new', 'id' => $item->id];
                                break;
                            default:
                                $more_api_params = null;
                                break;
                        }

                        return [
                            'id'            => $item->id,
                            'title'         => $item->title,
                            'sub_title'     => $item->sub_title,
                            'show_style'    => $item->show_style,
                            'show_max'      => $item->show_max,
                            'more_api'      => '/api/cartoon/list',
                            'api_params'    => $more_api_params,
                            'list'          => $cartoon_data,
                            'type'          => $item->type,
                        ];
                    });
            });
    }
}
