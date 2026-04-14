<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class AreaModel
 *
 * @property string $adcode 地区码
 * @property string $citycode 电话区号
 * @property int $id
 * @property int $is_hot
 * @property int $level
 * @property string $name
 * @property string $parent 上级区域码
 * @property string $pinyin
 * @property string $short_pinyin
 * @property string $first_py
 *
 * @author xiongba
 * @date 2021-07-10 11:18:36
 *
 * @mixin \Eloquent
 */
class AreaModel extends Model
{

    protected $table = "area";

    protected $primaryKey = 'id';

    protected $fillable = ['adcode', 'citycode', 'is_hot', 'level', 'name', 'parent', 'pinyin', 'short_pinyin'];

    protected $hidden = ['citycode', 'is_hot', 'level', 'parent'];

    protected $appends = ['first_py'];

    protected $guarded = 'id';

    public $timestamps = false;


    public function getFirstPyAttribute(): string
    {
        $pinyin = $this->attributes['pinyin'] ?? '';
        if (strlen($pinyin) <= 0) {
            return '';
        }
        return substr($this->attributes['pinyin'] ?? '', 0, 1);
    }

    /**
     * @return self|object|null
     */
    public static function getPosByIp(){
        $pos = \tools\IpLocation::getLocation(USER_IP);
        $province = $position['province'] ?? '未知';
        if(empty($province)){
            $province = '未知';
        }
        $province = str_replace(['省' , '市'] , '' , $province);
        /** @var AreaModel $area */
        return AreaModel::where('name' , $province)->where('parent', '')->first();
    }

}
