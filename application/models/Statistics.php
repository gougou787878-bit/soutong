<?php

/**
 * 榜单redis实现
 * Class StatisticsModel
 */

use tools\RedisService;

class StatisticsModel
{
    public $actorContributeRankKey = 'actor_contribute_rank';
    public $userContributeRankKey = 'user_contribute_rank';
    public $liveTimeRankKey = 'user_contribute_rank';

    // 主播日榜
    public $actorDay = 'actor_statistics_day';
    // 主播周榜
    public $actorWeek = 'actor_statistics_week';
    // 主播月榜
    public $actorMonth = 'actor_statistics_month';
    // 主播总榜
    public $actorTotal = 'actor_statistics_total';

    // 用户日榜
    public $userDay = 'user_statistics_day';
    // 用户周榜
    public $userWeek = 'user_statistics_week';
    // 用户月榜
    public $userMonth = 'user_statistics_month';
    // 用户总榜
    public $userTotal = 'user_statistics_total';


    // 主播对观众1对1日榜
    public $realDay = 'real_statistics_day_';
    // 主播对观众1对1周榜
    public $realWeek = 'real_statistics_week_';
    // 主播对观众1对1月榜
    public $realMonth = 'real_statistics_month_';
    // 主播对观众1对1总榜
    public $realTotal = 'real_statistics_total_';

    // 今日主播开播数
    public $openToday = 'open_today_live';
    // 今日观看人数
    public $seeToday = 'see_today_live';


    // 榜单数量
    private $itemNums = 10;

    protected static $_instance = [];

    /**
     * @return static
     * @author xiongba
     * @date 2020-02-26 16:07:21
     */
    public static function instance()
    {
        $class = get_called_class();
        if (!isset(self::$_instance[$class])) {
            self::$_instance[$class] = new static();
        }
        return self::$_instance[$class];
    }

    /**
     * 时间节点
     * @return array
     */
    private function getTime()
    {
        //php获取今日开始时间戳和结束时间戳
        $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $endToday = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
        //php获取本周起始时间
        $beginWeek = mktime(0, 0, 0, date("m"), date("d") - date("w") + 1, date("Y"));
        $endWeek = mktime(23, 59, 59, date("m"), date("d") - date("w") + 7, date("Y"));

        //php获取本月起始时间戳和结束时间戳
        $beginThisMonth = mktime(0, 0, 0, date('m'), 1, date('Y'));
        $endThisMonth = mktime(23, 59, 59, date('m'), date('t'), date('Y'));

        return [
            'beginToday' => $beginToday,
            'endToday' => $endToday,
            'beginWeek' => $beginWeek,
            'endWeek' => $endWeek,
            'beginThisMonth' => $beginThisMonth,
            'endThisMonth' => $endThisMonth,
        ];
    }

    /**
     * 更新日榜
     * statics_time 更新时间
     * @param $uuid
     * @param int $score
     */
    protected function updateActorDay($uuid, $score = 0)
    {
        $expireTime = RedisService::redis()->ttl($this->actorDay);
        RedisService::redis()->zIncrby($this->actorDay, $score, $uuid);

        // 如果key时间永久或key不存在
        if (($expireTime == -1) || ($expireTime == -2)) {
            $expireTime = $this->getTime()['endToday'] - time();
        }
        RedisService::redis()->expire($this->actorDay, $expireTime);
    }

    /**
     * 更新日榜
     * statics_time 更新时间
     * @param $uid
     * @param int $score
     */
    protected function updateActorHour($uid, $score = 0)
    {
        $key = 'ranking:coin:hour:' . date('H', TIMESTAMP);
        $has = RedisService::redis()->zCard($key);
        RedisService::redis()->zIncrby($key, $score, $uid);
        if ($has === 0) {
            RedisService::redis()->expire($key, 3900);
        }
    }


    /**
     * 更新周榜
     * statics_time 更新时间
     * @param $uuid
     * @param int $score
     */
    protected function updateActorWeek($uuid, $score = 0)
    {
        $expireTime = RedisService::redis()->ttl($this->actorWeek);
        RedisService::redis()->zIncrby($this->actorWeek, $score, $uuid);

        // 如果key时间永久或key不存在
        if (($expireTime == -1) || ($expireTime == -2)) {
            $expireTime = $this->getTime()['endWeek'] - time();
        }
        RedisService::redis()->expire($this->actorWeek, $expireTime);
    }


    /**
     * 更新月榜
     * statics_time 更新时间
     * @param $uuid
     * @param int $score
     */
    protected function updateActorMonth($uuid, $score = 0)
    {
        $expireTime = RedisService::redis()->ttl($this->actorMonth);
        RedisService::redis()->zIncrby($this->actorMonth, $score, $uuid);

        // 如果key时间永久或key不存在
        if (($expireTime == -1) || ($expireTime == -2)) {
            $expireTime = $this->getTime()['endThisMonth'] - time();
        }
        RedisService::redis()->expire($this->actorMonth, $expireTime);
    }


    /**
     * 更新总榜
     * statics_time 更新时间
     * @param $uuid
     * @param int $score
     */
    protected function updateActorTotal($uuid, $score = 0)
    {
        RedisService::redis()->zIncrby($this->actorTotal, $score, $uuid);
    }

    ////////////////////////////////////////////////
    /**
     * 更新日榜
     * statics_time 更新时间
     * @param $uuid
     * @param int $score
     */
    protected function updateUserDay($uuid, $score = 0)
    {
        $expireTime = RedisService::redis()->ttl($this->userDay);
        RedisService::redis()->zIncrby($this->userDay, $score, $uuid);

        // 如果key时间永久或key不存在
        if (($expireTime == -1) || ($expireTime == -2)) {
            $expireTime = $this->getTime()['endToday'] - time();
        }
        RedisService::redis()->expire($this->userDay, $expireTime);
    }

    /**
     * 更新周榜
     * statics_time 更新时间
     * @param $uuid
     * @param int $score
     */
    protected function updateUserWeek($uuid, $score = 0)
    {
        $expireTime = RedisService::redis()->ttl($this->userWeek);
        RedisService::redis()->zIncrby($this->userWeek, $score, $uuid);

        // 如果key时间永久或key不存在
        if (($expireTime == -1) || ($expireTime == -2)) {
            $expireTime = $this->getTime()['endWeek'] - time();
        }
        RedisService::redis()->expire($this->userWeek, $expireTime);
    }

    /**
     * 更新月榜
     * statics_time 更新时间
     * @param $uuid
     * @param int $score
     */
    protected function updateUserMonth($uuid, $score = 0)
    {
        $expireTime = RedisService::redis()->ttl($this->userMonth);
        RedisService::redis()->zIncrby($this->userMonth, $score, $uuid);

        // 如果key时间永久或key不存在
        if (($expireTime == -1) || ($expireTime == -2)) {
            $expireTime = $this->getTime()['endThisMonth'] - time();
        }
        RedisService::redis()->expire($this->userMonth, $expireTime);
    }

    /**
     * 更新总榜
     * statics_time 更新时间
     * @param $uuid
     * @param int $score
     */
    protected function updateUserTotal($uuid, $score = 0)
    {
        RedisService::redis()->zIncrby($this->userTotal, $score, $uuid);
    }

    //////////////////////////
    /**
     * 更新一对一榜单
     * @param $liveUid
     * @param $uuid
     * @param int $score
     */
    protected function updateRealDay($liveUid, $uuid, $score = 0)
    {
        $expireTime = RedisService::redis()->ttl($this->realDay . $liveUid);
        RedisService::redis()->zIncrby($this->realDay . $liveUid, $score, $uuid);
        // 如果key时间永久或key不存在
        if (($expireTime == -1) || ($expireTime == -2)) {
            $expireTime = $this->getTime()['endToday'] - time();
        }
        RedisService::redis()->expire($this->realDay . $liveUid, $expireTime);
    }

    /**
     * 更新一对一榜单
     * @param $liveUUID
     * @param $uuid
     * @param int $score
     */
    protected function updateRealWeek($liveUUID, $uuid, $score = 0)
    {
        $expireTime = RedisService::redis()->ttl($this->realWeek . $liveUUID);
        RedisService::redis()->zIncrby($this->realWeek . $liveUUID, $score, $uuid);
        // 如果key时间永久或key不存在
        if (($expireTime == -1) || ($expireTime == -2)) {
            $expireTime = $this->getTime()['endWeek'] - time();
        }
        RedisService::redis()->expire($this->realWeek . $liveUUID, $expireTime);
    }

    /**
     * 更新一对一榜单
     * @param $liveUUID
     * @param $uuid
     * @param int $score
     */
    protected function updateRealMonth($liveUUID, $uuid, $score = 0)
    {
        $expireTime = RedisService::redis()->ttl($this->realMonth . $liveUUID);
        RedisService::redis()->zIncrby($this->realMonth . $liveUUID, $score, $uuid);
        // 如果key时间永久或key不存在
        if (($expireTime == -1) || ($expireTime == -2)) {
            $expireTime = $this->getTime()['endThisMonth'] - time();
        }
        RedisService::redis()->expire($this->realMonth . $liveUUID, $expireTime);
    }

    /**
     * 更新一对一榜单
     * @param $liveUUID
     * @param $uuid
     * @param int $score
     */
    protected function updateRealTotal($liveUUID, $uuid, $score = 0)
    {

        RedisService::redis()->zIncrby($this->realTotal . $liveUUID, $score, $uuid);
    }

    ////////////////////////////////////////

    /**
     * 更新门面方法
     * @param $liveUUID
     * @param $uuid
     * @param int $score
     * @return bool
     */
    public function updateFace($liveUUID, $uuid, $score)
    {

        $this->updateUserDay($uuid, $score);
        $this->updateUserWeek($uuid, $score);
        $this->updateUserMonth($uuid, $score);
        //$this->updateUserTotal($uuid, $score);

        $this->updateActorHour($liveUUID, $score);
        $this->updateActorDay($liveUUID, $score);
        $this->updateActorWeek($liveUUID, $score);
        $this->updateActorMonth($liveUUID, $score);
        //$this->updateActorTotal($liveUUID, $score);

        $this->updateRealDay($liveUUID, $uuid, $score);
        $this->updateRealWeek($liveUUID, $uuid, $score);
        $this->updateRealMonth($liveUUID, $uuid, $score);
        //$this->updateRealTotal($liveUUID, $uuid, $score);
        return true;

    }


    /**
     * 1对1 读取门面方法
     * @param $liveUUID
     * @return array
     */
    public function readFace1vs1($liveUUID)
    {
        $data['day'] = RedisService::zRevrange($this->realDay . $liveUUID, 0, $this->itemNums);
        $data['week'] = RedisService::zRevrange($this->realWeek . $liveUUID, 0, $this->itemNums);
        $data['month'] = RedisService::zRevrange($this->realMonth . $liveUUID, 0, $this->itemNums);
        //$data['total'] = RedisService::zRevrange($this->realTotal . $liveUid, 0, $this->itemNums);
        $data['total'] = [];

        return $data;

    }

    /**
     * 金主榜门面模式
     * @return bool
     */
    public function userFace()
    {
        $data['day'] = RedisService::zRevrange($this->userDay, 0, $this->itemNums);
        $data['week'] = RedisService::zRevrange($this->userWeek, 0, $this->itemNums);
        $data['month'] = RedisService::zRevrange($this->userMonth, 0, $this->itemNums);
        //$data['total'] = RedisService::zRevrange($this->userTotal, 0, $this->itemNums);
        $data['total'] = [];
        return $data;
    }

    /**
     * 获取排行榜的key
     * @param string $type 获取什么类型的排行榜 enum(month=月,week=周,day=天,hour=小时)
     * @return string
     */
    public function getActorKey($type){
        if ($type == 'month') {
            $key = $this->actorWeek;
        } elseif ($type == 'week') {
            $key = $this->actorMonth;
        } elseif ($type == 'hour') {
            $key = 'ranking:coin:hour:' . date('H', TIMESTAMP);
        } else {
            $key = $this->actorDay;
        }
        return $key ;
    }

    /**
     * 获取主播排行榜
     * @param string $type 获取什么类型的排行榜 enum(month=月,week=周,day=天,hour=小时)
     * @param int $start
     * @param int $end
     * @param bool $withscore 要不要返回分数
     * @return array 返回uuid
     */
    public function getLiveRange($type , $start , $end , $withscore = false)
    {
        $key = $this->getActorKey($type);
        return redis()->zRevrange($key, $start, $end,$withscore);
    }

    /**
     * 主榜门面模式
     */
    public function actorFace()
    {
        $data['day'] = RedisService::zRevrange($this->actorDay, 0, $this->itemNums);
        $data['week'] = RedisService::zRevrange($this->actorWeek, 0, $this->itemNums);
        $data['month'] = RedisService::zRevrange($this->actorMonth, 0, $this->itemNums);
        //$data['total'] = RedisService::zRevrange($this->actorTotal, 0, $this->itemNums);
        $data['total'] = [];
        return $data;
    }

    /**
     * 记录今日开播数
     */
    public function updateOpenLiveToday() {
        $v = redis()->incrBy($this->openToday, 1);
        if ($v < 100) {
            redis()->expireAt($this->openToday, $this->getTime()['endToday']);
        }
    }

    /**
     * 记录今日观看人数
     */
    public function updateSeeLiveToday() {
        if (redis()->incr($this->seeToday) < 100) {
            redis()->expireAt($this->seeToday, strtotime(date('Y-m-d 23:59:50', TIMESTAMP)));
        }
    }

    public function updateSeeLiveTpm($isVip = false)
    {
        $key1 = 't:bp';//白票
        $key2 = 't:vp';
        if (!$isVip) {
            if (redis()->incr($key1) < 100) {
                redis()->expireAt($key1, strtotime(date('Y-m-d 23:59:50', TIMESTAMP)));
            }
        } else {
            if (redis()->incr($key2) < 100) {
                redis()->expireAt($key2, strtotime(date('Y-m-d 23:59:50', TIMESTAMP)));
            }
        }

    }

}