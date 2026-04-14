<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class StorySeriesModel
 *
 * @property int $id
 * @property int $story_id
 * @property int $series 章节
 * @property string $title 名称
 * @property int $is_free 是否限免 0 免费 1 vip 2钻石
 * @property int $views_count 总历史点击数
 * @property int $status 1上架0下架
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property string $url 小说cdn路径
 *
 * @author xiongba
 * @date 2022-06-28 20:56:02
 *
 * @mixin \Eloquent
 */
class StorySeriesModel extends Model
{

    protected $table = "story_series";

    protected $primaryKey = 'id';

    protected $fillable = [
        'story_id',
        'series',
        'title',
        'is_free',
        'views_count',
        'status',
        'created_at',
        'updated_at',
        'url'
    ];

    protected $guarded = 'id';

    public $timestamps = false;
    protected $hidden = ['is_free','views_count','status'];
    protected $appends = ['url_full'];
    public function getUrlFullAttribute()
    {
        return $this->getAttribute('url')? url_story($this->getAttribute('url')) . "?v=1":'';
    }
    /**
     * @param $pid
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    static function getManHuaSeries($pid){
        return self::where(['story_id'=>$pid])->get();
    }
    /**
     * @param $comics_id
     * @param $series_id
     * @return array
     */
    public static function getSeriesSrc($comics_id, $series_id)
    {
        return self::where(['story_id' => $comics_id, 'series' => $series_id])->first();
    }

}
