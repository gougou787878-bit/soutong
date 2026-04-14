<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class MhSeriesModel
 *
 * @property int $id 
 * @property int $pid 漫画编号 id
 * @property int $episode 章节编号
 * @property string $thumb 封面url
 * @property int $from 
 *
 * @author xiongba
 * @date 2022-05-17 17:35:53
 *
 * @mixin \Eloquent
 */
class MhSeriesModel extends Model
{

    protected $table = "mh_series";

    protected $primaryKey = 'id';

    protected $fillable = ['pid', 'episode', 'thumb', 'from'];

    protected $guarded = 'id';

    public $timestamps = false;
    protected $hidden = ['thumb'];

    /**
     * @param $pid
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    static function getManHuaSeries($pid){
        return self::where(['pid'=>$pid])->orderBy('episode')->get();
    }

}
