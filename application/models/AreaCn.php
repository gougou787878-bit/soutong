<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class AreaCnModel
 *
 * @property int $id ID
 * @property string $areaname 栏目名
 * @property int $parentid 父栏目
 * @property string $shortname
 * @property string $lng
 * @property string $lat
 * @property int $level 1.省 2.市 3.区 4.镇
 * @property string $position
 * @property int $sort 排序
 * @property int $status
 * @property int $is_hot
 *
 *
 * @date 2022-02-23 16:17:23
 *
 * @mixin \Eloquent
 */
class AreaCnModel extends Model
{

    protected $table = "area_cn";

    protected $primaryKey = 'id';

    protected $fillable = ['areaname', 'parentid', 'shortname', 'lng', 'lat', 'level', 'position', 'sort', 'status', 'is_hot'];

    protected $guarded = 'id';

    public $timestamps = false;
    const STATUS_SUCCESS = 1;
    const STATUS_FAIL = 0;
    const STATUS = [
        self::STATUS_FAIL    => '禁用',
        self::STATUS_SUCCESS => '启用',
    ];
    const HOT = [
        self::STATUS_FAIL    => '--',
        self::STATUS_SUCCESS => '热门',
    ];
    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    static function queryBase()
    {
        return self::query()->where('status', '=', self::STATUS_SUCCESS);
    }
    static function queryHot(){
        return self::queryBase()->where('is_hot', '=', self::STATUS_SUCCESS);
    }

    const AREA_KEY = 'area';//前端全城市
    const AREA_KEY_HOT = 'area:hot';//前端热门城市

    public static function clearRedisCache()
    {
       redis()->del(self::AREA_KEY);
       redis()->del(self::AREA_KEY_HOT);
    }
}
