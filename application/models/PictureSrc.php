<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class PictureSrcModel
 *
 * @property int $id 
 * @property int $picture_id 图集ID
 * @property string $img_url 图片地址
 * @property string $img_width 图片宽
 * @property string $img_height 图片高
 *
 * @author xiongba
 * @date 2022-06-28 20:54:32
 *
 * @mixin \Eloquent
 */
class PictureSrcModel extends Model
{

    protected $table = "picture_src";

    protected $primaryKey = 'id';

    protected $fillable = ['picture_id', 'img_url', 'img_width', 'img_height'];

    protected $guarded = 'id';

    public $timestamps = false;

    protected $appends = ['img_url_full'];

    public function getImgUrlFullAttribute()
    {
        if ($thumb = $this->attributes['img_url']) {
            return url_cover($thumb);
        }
        return '';
    }

    /**
     * @param $comics_id
     * @param $series_id
     * @return array
     */
    public static function getSeriesSrc($comics_id, $series_id)
    {
        return self::where(['m_id' => $comics_id, 's_id' => $series_id])->get()
            ->map(function (PictureSrcModel $item) {
                return $item;
            })->toArray();
    }


}
