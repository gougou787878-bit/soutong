<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class MhSrcModel
 *
 * @property int $id
 * @property int $m_id 漫画ID，剧集ID
 * @property int $s_id 章节ID，单本默认1
 * @property string $img_url 图片地址
 * @property string $img_width
 * @property string $img_height
 * @property int $from
 *
 * @author xiongba
 * @date 2022-05-17 17:35:43
 *
 * @mixin \Eloquent
 */
class MhSrcModel extends Model
{

    protected $table = "mh_src";

    protected $primaryKey = 'id';

    protected $fillable = ['m_id', 's_id', 'img_url', 'img_width', 'img_height', 'from'];

    protected $guarded = 'id';

    public $timestamps = false;

    protected $appends = ['img_url_full'];

    public function getImgUrlFullAttribute()
    {
        if ($thumb = $this->attributes['img_url']) {
            if (!$this->from) {
                // $thumb = 'images/mh/' . ltrim($thumb, '/');
                $thumb =  ltrim($thumb, '/');
                $moduleName =  Yaf\Application::app()->getDispatcher()->getRequest()->getModuleName();
                if (strcasecmp($moduleName , 'admin') === 0){
                    return 'https://imgpublic.ycomesc.live/'.$thumb;
                }
            }
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
            ->map(function (MhSrcModel $item) {
                return $item;
            })->toArray();
    }

}
