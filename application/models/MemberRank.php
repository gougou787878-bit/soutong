<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class MemberRankModel
 *
 * @property int $day 日期 yyyymmdd
 * @property int $id 
 * @property int $receive 求片推片数量
 * @property string $uuid 用户
 * @property int $profit 社区收益
 * @property int $output 提现
 * @property int $followed 粉丝
 * @property int $praize  社区获赞
 * @property int $play  mv播放量
 * @property int $upload  社区作品上传数
 *
 * @author xiongba
 * @date 2020-07-09 23:38:21
 *
 * @mixin \Eloquent
 */
class MemberRankModel extends Model
{
    use \traits\EventLog;
    protected $table = "member_rank";

    protected $primaryKey = 'id';

    protected $fillable = ['play','praize','followed','output','profit','day', 'receive', 'uuid','upload'];

    protected $guarded = 'id';

    public $timestamps = false;

    const FIELD_PRAIZE = 'praize';//帖子获赞
    const FIELD_FOLLOWED = 'followed';//被多少人关注
    const FIELD_OUTPUT = 'output';//提现
    const FIELD_PROFIT = 'profit';//帖子收益
    const FIELD_RECEIVE = 'receive';//接单
    const FIELD_PLAY = 'play';//mv播放量
    const FIELD_UPLOAD = 'upload';//社区作品上传数
    const RANK_PRAIZE = 'rank:praize:%d';
    const RANK_UPLOAD = 'rank:upload:%d';
    const RANK_PROFIT = 'rank:profit:%d';

    const RANK_NUM = 10;

    /**
     * 求片用户关联
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function member()
    {
        return self::hasOne(MemberModel::class, 'uuid', 'uuid');
    }

    /**
     * @param $uuid
     * @param $day
     * @return self|null|object
     */
    static function getRowData($uuid, $day = null)
    {
        if($day === null){
            $day = date('Ymd',TIMESTAMP);
        }
        return self::where([
            'uuid' => $uuid,
            'day'  => $day,
        ])->first();
    }

    /**
     * @param $uuid
     * @return self|null|object
     * @author xiongba
     * @date 2020-10-05 10:02:47
     */
    public static function getMonth($uuid){
        $date = date('Ymd',strtotime('first day of this month'));
       return self::where(
            [
                ['uuid' , '=' , $uuid],
                ['day','>=' , $date]
            ]
        )->first();
    }

    /**
     * @param $uuid
     * @param $field
     * @param int $increase
     * @param null $day
     * @return bool
     * @throws RedisException
     */
    public static function addMemberRank($uuid, $field, int $increase = 1, $day = null): bool
    {
        if (0 == $increase) {
            return false;
        }
        ($day == null) && $day = date('Ymd');
        $where = ['uuid' => $uuid, 'day' => $day];
        $data = [$field => DB::raw("{$field}+{$increase}")];
        $row = self::query()->where($where)->first();
        if($row){
            $res = self::where('id',$row->id)->update($data);
            if ($res){
                self::updateRedisRank($uuid,$field,$increase);
            }
            return $res;
        }
        $data = [$field => DB::raw("{$field}+{$increase}")];
        $res = self::insert(array_merge($where, $data));
        if ($res){
            self::updateRedisRank($uuid,$field,$increase);
        }
        return $res;
    }

    /**
     * @throws RedisException
     */
    public static function reduceMemberRank($uuid, $field, int $increase = 1, $day = null)
    {
        if (0 == $increase) {
            return false;
        }
        ($day == null) && $day = date('Ymd');
        $where = ['uuid' => $uuid, 'day' => $day];
        $data = [$field => DB::raw("{$field}-{$increase}")];
        $row = self::query()->where($where)->first();
        if($row->$field > 0){
            $res = self::where('id',$row->id)->update($data);
            if ($res){
                self::updateRedisRank($uuid,$field,$increase * (-1));
            }
            return $res;
        }
    }

    /**
     * @throws RedisException
     */
    public static function updateRedisRank($uuid, $field, $increase){
        $increase = floatval($increase);
        $day = date('Ymd');
        $week = date('W');
        $month = date('Ym');
        $key_day = "";
        $key_week = "";
        $key_month = "";
        switch ($field){
            case self::FIELD_PRAIZE:
                $key_day = sprintf(self::RANK_PRAIZE,$day);
                $key_week = sprintf(self::RANK_PRAIZE,$week);
                $key_month = sprintf(self::RANK_PRAIZE,$month);
                break;
            case self::FIELD_UPLOAD:
                $key_day = sprintf(self::RANK_UPLOAD,$day);
                $key_week = sprintf(self::RANK_UPLOAD,$week);
                $key_month = sprintf(self::RANK_UPLOAD,$month);
                break;
            case self::FIELD_PROFIT:
                $key_day = sprintf(self::RANK_PROFIT,$day);
                $key_week = sprintf(self::RANK_PROFIT,$week);
                $key_month = sprintf(self::RANK_PROFIT,$month);
                break;
            default:
                break;
        }
        if ($key_day && $key_week && $key_month){
            redis()->zIncrBy($key_day,$increase,$uuid);
            self::keyTtl('day',$key_day);
            redis()->zIncrBy($key_week,$increase,$uuid);
            self::keyTtl('week',$key_week);
            redis()->zIncrBy($key_month,$increase,$uuid);
            self::keyTtl('month',$key_month);
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
    public static function getRankByRedis($rank_by, $rank_time,$num){
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
            case self::FIELD_PRAIZE:
                $redis_key = sprintf(self::RANK_PRAIZE,$date_key);
                break;
            case self::FIELD_UPLOAD:
                $redis_key = sprintf(self::RANK_UPLOAD,$date_key);
                break;
            case self::FIELD_PROFIT:
                $redis_key = sprintf(self::RANK_PROFIT,$date_key);
                break;
            default:
                break;
        }
        return redis()->zRevRange($redis_key, 0, $num - 1,['withscores' => true]);
    }

    /**
     * 按天 获取数据  默认 top10
     * @param int $day
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection
     */
    static function getRankByDay($day = null, $limit = 10)
    {
        is_null($day) && $day = date('Ymd', TIMESTAMP);
        $where[] = ['day','=',$day];
        //DB::enableQueryLog();
        $data =  self::where($where)->orderByDesc('receive')
            ->limit($limit)
            ->with('member:uuid,nickname,thumb,followed_count,auth_status,uid')
            ->get()
            ->map(function ($item) {
            $item->member && $item->member->thumb = url_avatar($item->member->thumb);
            return $item;
        })->filter();
        //\Factory\Log\Log::debug(DB::getQueryLog());
        return $data;
    }

    /**
     * 数据总榜单
     * @param int $limit
     * @return mixed
     */
    static function getAllReplyRank($limit = 10)
    {
        return self::orderByDesc('receive')->limit($limit)->with('member:uuid,uid,nickname,thumb,followed_count,auth_status')->get()->map(function (
            $item
        ) {
            $item->member && $item->member->thumb = url_avatar($item->member->thumb);
            return $item;
        })->filter();
    }



}
