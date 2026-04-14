<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class PictureModel
 *
 * @property int $id
 * @property string $p_id 资源中心id
 * @property string $title 标题
 * @property string $desc 描述
 * @property string $thumb 封面图
 * @property int $category_id 图集分类ID
 * @property string $tags 标签
 * @property int $is_free 0 免费 1 vip 2  钻石（金币）
 * @property int $rating 浏览数
 * @property int $favorites 收藏人数
 * @property string $refresh_at 刷新时间
 * @property int $recommend 是否推荐
 * @property int $coins 金币
 * @property int $status 1上架0下架
 * @property int $total  总图片数
 *
 * @author xiongba
 * @date 2022-06-28 20:53:41
 *
 * @mixin \Eloquent
 */
class PictureModel extends EloquentModel
{

    protected $table = "picture";

    protected $primaryKey = 'id';

    protected $fillable = [
        'p_id',
        'title',
        'desc',
        'thumb',
        'category_id',
        'tags',
        'is_free',
        'rating',
        'favorites',
        'refresh_at',
        'recommend',
        'coins',
        'total',
        'status'
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

    protected $appends = ['thumb_full', 'is_pay', 'is_like', 'tags_list'];


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
        return PictureFavoritesModel::hasLike($watchUser->getAttributeValue('uid'),
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
            $resource_type = PrivilegeModel::RESOURCE_TYPE_COINS_PICTURE;
        }else{
            $resource_type = PrivilegeModel::RESOURCE_TYPE_VIP_PICTURE;
        }
        $hasPrivilege = UsersProductPrivilegeModel::hasPrivilege(USER_PRIVILEGE, $resource_type, PrivilegeModel::PRIVILEGE_TYPE_VIEW);
        if ($hasPrivilege){
            return 1;
        }

        if ($coins > 0){
            return PicturePayModel::hasBuy($watchUser->getAttributeValue('uid'), $this->getAttributeValue('id')) ? 1 : 0;
        }

        return 0;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function series()
    {
        return self::hasMany(PictureSrcModel::class, 'picture_id', 'id');
    }
   /* public function getTotalAttribute()
    {
        return PictureSrcModel::where(['picture_id'=>$this->getAttributeValue('id')])->count('id');
    }*/
    public function getThumbFullAttribute()
    {
        if ($thumb = $this->attributes['thumb']) {
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



}
