<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class MvTotalModel
 *
 * @property int $id
 * @property int $vid 视频id
 * @property string $date_at 日期
 * @property int $view_num 观看量
 * @property int $like_num 点赞数
 * @property int $sale_num 销量
 *
 * @property MvModel $mv
 *
 * @author xiongba
 * @date 2020-03-16 17:04:41
 *
 * @mixin \Eloquent
 */
class MvTotalModel extends EloquentModel
{

    protected $table = "mv_total";

    protected $primaryKey = 'id';

    protected $fillable = ['vid', 'date_at', 'view_num', 'like_num', 'sale_num','c_view_num'];

    protected $guarded = 'id';


    public $timestamps = false;


    public function mv()
    {
        return self::hasOne(MvModel::class, 'id', 'vid');
    }

    public static function incrView(int $vid, int $view = 1, $date = null)
    {
        if (empty($date)) {
            $date = date('Y-m-d');
        }

       $uid =  MvModel::query()->where('id',$vid)->value('uid');
        if($uid){
            $auth_status =  MemberModel::query()->where('uid',$uid)->value('auth_status');
            if($auth_status){
                 self::_insertOrUpdate($vid , $date , 'c_view_num' , $view);
            }
        }

        //效率比updateOrCreate更高
        return self::_insertOrUpdate($vid , $date , 'view_num' , $view);
    }

    public static function incrBuy(int $vid, int $buy = 1, $date = null)
    {
        if (empty($date)) {
            $date = date('Y-m-d');
        }
        //效率比updateOrCreate更高
        $itOk =  self::_insertOrUpdate($vid , $date , 'sale_num' , $buy);
        if ($itOk){
            cached('search:hot:sale')->clearCached();
        }
        return $itOk;
    }

    public static function incrLike(int $vid, int $like = 1, $date = null)
    {
        if (empty($date)) {
            $date = date('Y-m-d');
        }
        return self::_insertOrUpdate($vid , $date , 'like_num' , $like);
    }

    private static function _insertOrUpdate($vid, $date, $field, $score)
    {
        if ($score >= 0) {
            $itOk = DB::update("insert into ks_mv_total (vid, date_at, $field) values ($vid,'$date',$score) on duplicate key update  $field=$field+$score");
        } else {
            $abs = abs($score);
            $itOk = DB::update("insert into ks_mv_total (vid, date_at, $field) values ($vid,'$date',0) on duplicate key update  $field=if($field > $abs, $field - $abs , 0)");
        }
        return $itOk;
    }


    public static function getViewScoreMv($start, $end = 0)
    {
        return self::getScoreMv('view_num', $start, $end);
    }
    public static function getCreatorViewScoreMv($start, $end = 0)
    {
        return self::getScoreMv('c_view_num', $start, $end);
    }


    public static function getLikeScoreMv($start, $end = 0)
    {
        return self::getScoreMv('like_num', $start, $end);
    }

    public static function getSaleScoreMv($start, $end = 0)
    {
        return self::getScoreMv('sale_num', $start, $end);
    }

    public static function getVideoId($sortField, $start, $end = 0)
    {
        if ($end <= 0) {
            list($start, $end) = [0, $start];
        }
        $dateArr = [date('Y-m-d'), \Carbon\Carbon::yesterday()->format('Y-m-d')];
        'test' == APP_ENVIRON && $dateArr = ['2020-11-26', '2020-11-25', '2020-11-23'];//测试环境

        return cached("rk:{$sortField}:{$start}")
            ->expired(800)
            ->serializerJSON()
            ->fetch(function () use ($sortField, $dateArr, $start, $end) {
                return self::whereIn('date_at', $dateArr)
                    ->orderByDesc('date_at')
                    ->orderByDesc($sortField)
                    ->orderByDesc('id')
                    ->offset($start)
                    ->limit($end)
                    ->pluck('vid')
                    ->toArray();
            });
    }


    /**
     * @param $totalField
     * @param $start
     * @param int $end
     * @return \Illuminate\Support\Collection
     * @author xiongba
     * @date 2020-03-16 19:39:05
     */
    public static function getScoreMv($totalField, $start, $end = 0)
    {
        if ($end <= 0) {
            list($start, $end) = [0, $start];
        }
        return self::query()
            ->with([
                'mv' => function ($query) {
                    return $query->with('user:uid,nickname,thumb,vip_level,sexType,expired_at,uuid')->with('user_topic');
                }
            ])
            ->whereIn('date_at', [date('Y-m-d'), \Carbon\Carbon::yesterday()->format('Y-m-d')])
            ->orderByDesc('date_at')
            ->orderByDesc($totalField)
            ->orderByDesc('id')
            ->offset($start)
            ->limit($end)
            ->get([$totalField, 'vid', 'date_at'])
            ->map(function ($item)use($totalField) {
                if (empty($item->mv)) {
                    return null;
                }
                $item->mv->score = $item->{$totalField};
                return $item->mv;
            })->filter();
    }

    const FIELD_LIKE = 'like';//视频获赞
    const FIELD_SALE = 'sell';//视频销售
    const FIELD_VIEW = 'play';//视频观看

    const MV_RANK_LIKE = 'mv:rank:like:new:%d:%d:%s';
    const MV_RANK_SALE = 'mv:rank:sale:new:%d:%d:%s';
    const MV_RANK_VIEW = 'mv:rank:view:new:%d:%d:%s';

    public static function addCacheData($mv_id, $is_aw, $field, $type, $increase){
        try {
            $increase = floatval($increase);
            $day = date('Ymd');
            $week = date('W');
            $month = date('Ym');
            $key_day = "";
            $key_week = "";
            $key_month = "";
            switch ($field){
                case self::FIELD_LIKE:
                    $key_day = sprintf(self::MV_RANK_LIKE, $is_aw, $type, $day);
                    $key_week = sprintf(self::MV_RANK_LIKE, $is_aw, $type, $week);
                    $key_month = sprintf(self::MV_RANK_LIKE, $is_aw, $type, $month);
                    break;
                case self::FIELD_SALE:
                    $key_day = sprintf(self::MV_RANK_SALE, $is_aw, $type, $day);
                    $key_week = sprintf(self::MV_RANK_SALE, $is_aw, $type, $week);
                    $key_month = sprintf(self::MV_RANK_SALE, $is_aw, $type, $month);
                    break;
                case self::FIELD_VIEW:
                    $key_day = sprintf(self::MV_RANK_VIEW, $is_aw, $type, $day);
                    $key_week = sprintf(self::MV_RANK_VIEW, $is_aw, $type, $week);
                    $key_month = sprintf(self::MV_RANK_VIEW, $is_aw, $type, $month);
                    break;
                default:
                    break;
            }
            if ($key_day && $key_week && $key_month){
                redis()->zIncrBy($key_day, $increase, $mv_id);
                self::keyTtl('day', $key_day);
                redis()->zIncrBy($key_week, $increase, $mv_id);
                self::keyTtl('week', $key_week);
                redis()->zIncrBy($key_month, $increase, $mv_id);
                self::keyTtl('month', $key_month);
            }
        }catch (RedisException $e){
            trigger_log("视频排行榜异常：" . $e->getMessage());
        }
    }

    /**
     * @throws RedisException
     */
    public static function keyTtl($type, $key){
        switch ($type){
            case 'day':
                if (redis()->ttl($key) == -1){
                    redis()->expire($key,25 * 3600);
                }
                break;
            case 'week':
                if (redis()->ttl($key) == -1){
                    redis()->expire($key,8 * 24 * 3600);
                }
                break;
            case 'month':
                if (redis()->ttl($key) == -1){
                    redis()->expire($key,32 * 24 * 3600);
                }
                break;
            default:
                break;
        }
    }

    /**
     * @throws RedisException
     */
    public static function getRankByRedis($is_aw, $type, $rank_by, $rank_time, $num){
        $date_key = '';
        switch ($rank_time){
            case 'day':
                $date_key = date('Ymd');
                break;
            case 'week':
                $date_key = date('W');
                break;
            case 'month':
                $date_key = date('Ym');
                break;
            default:
                break;
        }
        $redis_key = '';
        switch ($rank_by){
            case self::FIELD_LIKE:
                $redis_key = sprintf(self::MV_RANK_LIKE, $is_aw, $type, $date_key);
                break;
            case self::FIELD_SALE:
                $redis_key = sprintf(self::MV_RANK_SALE, $is_aw, $type, $date_key);
                break;
            case self::FIELD_VIEW:
                $redis_key = sprintf(self::MV_RANK_VIEW, $is_aw, $type, $date_key);
                break;
            default:
                break;
        }
        return redis()->zRevRange($redis_key, 0, $num - 1,['withscores' => true]);
    }

}

