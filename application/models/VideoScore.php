<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class VideoScoreModel
 *
 * @property float $avgEndRate 平均完播率
 * @property float $avgEndTime 平均播放时长
 * @property int $created_at 此测试评分创建时间
 * @property int $duration 本视频总时长
 * @property int $id
 * @property float $likesRate 完成当前测试平均点赞率
 * @property int $needCoin 是否是金币视频0-不是，1-是
 * @property int $published_at 用户发布视频时间
 * @property float $replyRate 完成当前测试平均评论率
 * @property int $test_times 视频被测试次数500,1000,5000,10000..金币视频测试数区别对待50.ext
 * @property int $vid 视频id
 * @property int $vtype 视频类型-兼容字段，视频放在一张表里面的不用考虑
 * @property float $rate  综合评分
 * @property float $rate_time  综合评分
 *
 *
 * @property-read MvModel $mv
 *
 * @author xiongba
 * @date 2020-06-24 16:51:49
 *
 * @mixin \Eloquent
 */
class VideoScoreModel extends Model
{

    protected $table = "video_score";

    protected $primaryKey = 'id';

    protected $fillable = [
        'avgEndRate',
        'avgEndTime',
        'created_at',
        'duration',
        'likesRate',
        'needCoin',
        'published_at',
        'replyRate',
        'test_times',
        'vid',
        'rate',
        'rate_time',
        'vtype'
    ];

    protected $guarded = 'id';

    public $timestamps = false;


    public function mv(){
        return self::hasOne(MvModel::class , 'id' , 'vid');
    }

    /**
     * 使用视频初始化评分
     * @param MvModel $model
     * @return Model|VideoScoreModel
     */
    public static function createInit(MvModel $model)
    {
        return self::create([
            'replyRate'    => 0,
            'likesRate'    => 0,
            'test_times'   => 0,
            'avgEndRate'   => 0,
            'avgEndTime'   => 0,
            'created_at'   => time(),
            'duration'     => $model->duration,
            'needCoin'     => $model->coins > 0 ? 1 : 0,
            'published_at' => $model->created_at,
            'vid'          => $model->id,
            'rate'         => 1,
            'vtype'        => 1
        ]);
    }


    /**
     * 今日头条的查询Query
     * @return VideoScoreModel
     * @author xiongba
     * @date 2020-09-06 01:03:05
     */
    public static function todayHottestQuery()
    {
        list($begin, $end) = explode('-', setting('today:hottest:between', '24-72'));
        return VideoScoreModel::with('mv:id,status,is_delete,is_hide')
            //->where('vtype', '=', \MvModel::TYPE_FEATURED)
            ->where('duration', '>', 30)
            ->where('test_times', '!=', 0)
            ->whereBetween('published_at', [TIMESTAMP - $end * 3600, TIMESTAMP - $begin * 3600])
            ->orderByDesc("rate")
            ->select(['vid', 'avgEndRate', 'rate']);

    }



    public static function queryScoreJoin($vType){
        return \VideoScoreModel::query()
            ->leftJoin('mv', 'video_score.vid', '=', 'mv.id')
            //->where('mv.is_delete', '=', \MvModel::IS_DELETE_NO)
            ->where('mv.status', '=', \MvModel::STAT_CALLBACK_DONE)
            ->where('mv.is_hide', '=', \MvModel::IS_HIDE_NO)
            ->where('video_score.duration','>', setting('recommend:duration:min-limit', 20))
            //->where('video_score.vtype', '=', $vType)
            ->where('video_score.published_at', '>', time() - 86400 * 2)
            ->orderByDesc("video_score.rate")
            ->select(['video_score.vid', 'video_score.avgEndRate', 'video_score.rate']);
    }

}
