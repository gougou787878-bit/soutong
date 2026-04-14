<?php


use Illuminate\Database\Eloquent\Model;
use service\MvService;

/**
 * class DailyVideoModel
 *
 * @property int $id
 * @property string $day
 * @property int $status 0 默认 ，1 开启
 * @property string $vids
 *
 * @author xiongba
 * @date 2020-09-22 19:41:05
 *
 * @mixin \Eloquent
 */
class DailyVideoModel extends EloquentModel
{

    protected $table = "daily_video";

    protected $primaryKey = 'id';

    protected $fillable = ['day', 'status', 'vids'];

    protected $guarded = 'id';

    public $timestamps = false;

    const STAT_ENABLE = 1;
    const STAT_DISABLE = 0;

    const STAT = [
        self::STAT_ENABLE  => '启用',
        self::STAT_DISABLE => '禁用',
    ];


    protected $appends = ['title', 'number'];//虚拟属性列表

    public function getTitleAttribute()
    {
        //2020-06-16
        $titleDate = substr($this->day, 5);
        return "{$titleDate}推荐";
    }

    public function getNumberAttribute()
    {
        $vidArr = explode(',', trim($this->vids, ','));
        return count($vidArr);
    }


    public static function getVideoByDate($date, $limit = 6,&$vidArray=[])
    {
        $vidStr = self::where('day', $date)->value('vids');
        if (empty($vidStr)) {
            return null;
        }
        $vidArr = explode(',', $vidStr);
        if(count($vidArr)>=$limit){
            $vidArray = array_chunk($vidArr,$limit)[0];
        }else{
            $vidArray = $vidArr;
        }
        $data = [];
        if ($vidArray) {
            $data = \MvModel::queryBase()->whereIn('id', $vidArray)
                ->limit($limit)
                ->with('user:uid,nickname,thumb,uid,expired_at,vip_level,uuid,sexType')
                ->orderByDesc('id')
                ->get();
        }
        return $data;
    }


    public function getVideos($limit = 6)
    {
        $vidArr = explode(',', $this->vids);
        $vidArr && $vidArr = array_unique($vidArr);
        $data = [];
        if ($vidArr) {
            $data = \MvModel::queryBase()->whereIn('id', $vidArr)
                ->limit($limit)
                ->with('user:uid,nickname,thumb,uid,expired_at,vip_level,uuid,sexType')
                ->orderByDesc('id')
                ->get();
        }
        return $data;
    }


    public static function queryBase()
    {
        return self::where('status', self::STAT_ENABLE);
    }


}
