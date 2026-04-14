<?php


namespace service;


use Carbon\Carbon;
use MvModel;

class RankingService
{
    const KEY_NAME_INVITE = 'invite';
    const KEY_NAME_PRAISED = 'praised';


    /**
     * @param string $type
     * @param $start
     * @param $stop
     * @return array
     * @author xiongba
     */
    public function getPraised(string $type, $start, $stop)
    {
        $uid = $this->revRange($type, $start, $stop, self::KEY_NAME_PRAISED);
        if (empty($uid)) {
            $uid = [];
            \MemberModel::orderBy('fabulous_count', 'desc')
                ->limit($stop)
                ->get(['uid', 'fabulous_count'])
                ->map(function ($item) use (&$uid) {
                    /** @var \MemberModel $item */
                    $this->incPraised($item->fabulous_count, $item->uid);
                    $uid[] = $item->uid;
                });
        }
        return $uid;
    }

    /**
     * 添加获赞榜单
     * @param $score
     * @param $uid
     * @return float
     * @author xiongba
     * @date 2020-06-01 15:17:27
     */
    public function incPraised($score, $uid)
    {
        return $this->incr($score, $uid, self::KEY_NAME_PRAISED);
    }


    /**
     * 邀请用户榜单
     * @param string $type
     * @param $start
     * @param $stop
     * @return array
     * @author xiongba
     */
    public function getInvite(string $type, $start, $stop)
    {
        $uid = $this->revRange($type, $start, $stop, self::KEY_NAME_INVITE);
        if (empty($uid)) {
            $uid = [];
            \MemberModel::orderBy('invited_num', 'desc')
                ->limit($stop)
                ->get(['uid', 'invited_num'])
                ->map(function ($item) use (&$uid) {
                    /** @var \MemberModel $item */
                    $this->incInvite($item->invited_num, $item->uid);
                    $uid[] = $item->uid;
                });
        }
        return $uid;
    }

    /**
     * 添加邀请用户榜单
     * @param $score
     * @param $uid
     * @return float
     * @author xiongba
     */
    public function incInvite($score, $uid)
    {
        return $this->incr($score, $uid, self::KEY_NAME_INVITE);
    }


    public function getMemberInfo($memberIds, $member , $appendColumn = [])
    {
        $userService = new \service\UserService();
        $follows = redis()->sMembers(\UserAttentionModel::REDIS_USER_FOLLOWED_LIST . $member['uid']);
        $results = \MemberModel::whereIn('uid', $memberIds)->where('oauth_type','!=','channel')->get()->map(function ($item) use ($userService, $follows,$appendColumn) {
            /** @var \MemberModel $item */
            $result = $userService->baseInfoForColumn($item, false,
                array_merge(['nickname', 'thumb', 'level', 'uid', 'aff', 'isVV', 'vvLevel'] , $appendColumn));
            $result['is_attention'] = intval($result['isFollowed'] = in_array($item->uid, $follows));
            return $result;
        });
        $members = array_reindex($results->toArray(), 'uid');
        $members = array_keep_column($members, $memberIds);
        return array_values($members);
    }


    /**
     * @param string $type
     * @param $start
     * @param $stop
     * @param $keyName
     * @return array
     * @author xiongba
     */
    public function revRange(string $type, $start, $stop, $keyName)
    {
        $key = null;
        if ($type == 'day') {
            $key = "ranking:$keyName:day:" . date('Ymd');
        } elseif ($type == 'month') {
            $key = "ranking:$keyName:month:" . date('Ym');
        } elseif ($type == 'week') {
            $key = "ranking:$keyName:week:" . date('YW');
        }
        return redis()->zRevRange($key, $start, $stop);
    }


    public function incr($score, $uid, $keyName)
    {
        $dayKey = "ranking:$keyName:day:" . date('Ymd');
        $monthKey = "ranking:$keyName:month:" . date('Ym');
        $weekKey = "ranking:$keyName:week:" . date('YW');
        redis()->zIncrBy($monthKey, $score, $uid);
        redis()->zIncrBy($weekKey, $score, $uid);
        $value = redis()->zIncrBy($dayKey, $score, $uid);
        if ($value == $score) {
            redis()->expire($dayKey, 87400);//有效期87400秒
            redis()->expireAt($weekKey, strtotime('next monday +1 days')); //有效时间到下个礼拜二失效
            redis()->expireAt($monthKey, strtotime('first day of next month') + 86400); // 有效时间到下个月第二号
        }
        return $value;
    }


    /**
     * @param string $type
     * @param $start
     * @param $stop
     * @return array
     * @author xiongba
     */
    public function getPraisedByDay(string $type, $start, $stop)
    {
        $expire_at = 43200;
        if ($type != 'day') {
            $expire_at = 18 * 3600 * 1.5;
        }
        $uid = cached("ranking:praise:data:" . $type)->expired($expire_at)->serializerPHP()
            ->fetch(function () use ($type, $start, $stop) {
                $day = $this->getType2Day($type);
                $uid = $this->revRangeByDay(self::KEY_NAME_PRAISED, $start, $stop, $day);
                if (empty($uid)) {
                    $uid = [];
                    \MemberModel::orderBy('fabulous_count', 'desc')
                        ->limit($stop)
                        ->get(['uid', 'fabulous_count'])
                        ->map(function ($item) use (&$uid) {
                            /** @var \MemberModel $item */
                            $this->incPraisedByDay($item->fabulous_count, $item->uid);
                            $uid[] = $item->uid;
                        });
                }
                return $uid;
            });
        return $uid;
    }

    /**
     * 添加获赞榜单
     * @param $score
     * @param $uid
     * @return float
     * @author xiongba
     * @date 2020-06-01 15:17:27
     */
    public function incPraisedByDay($score, $uid)
    {
        /*cached("ranking:praise:data:day")->clearCached();
        cached("ranking:praise:data:month")->clearCached();
        cached("ranking:praise:data:week")->clearCached();*/
        return $this->incrByDay($score, $uid, self::KEY_NAME_PRAISED);
    }

    /**
     * 邀请用户榜单
     * @param string $type
     * @param $start
     * @param $stop
     * @return array
     * @author xiongba
     */
    public function getInviteByDay(string $type, $start, $stop)
    {
        $expire_at = 43200;
        if ($type != 'day') {
            $expire_at = 18 * 3600 * 1.5;
        }
        $uid = cached("ranking:invite:data:" . $type)->expired($expire_at)->serializerPHP()
            ->fetch(function () use ($type, $start, $stop) {
                $day = $this->getType2Day($type);
                $uid = $this->revRangeByDay(self::KEY_NAME_INVITE, $start, $stop, $day);
                if (empty($uid)) {
                    $uid = [];
                    \MemberModel::orderBy('invited_num', 'desc')
                        ->where('oauth_type','!=','channel')
                        ->limit($stop)
                        ->get(['uid', 'invited_num'])
                        ->map(function ($item) use (&$uid) {
                            /** @var \MemberModel $item */
                            $this->incInviteByDay($item->invited_num, $item->uid);
                            $uid[] = $item->uid;
                        });
                }
                return $uid;
            });
        return $uid;
    }


    /**
     * 添加获赞榜单
     * @param $score
     * @param $uid
     * @return float
     * @author xiongba
     * @date 2020-06-01 15:17:27
     */
    public function incInviteByDay($score, $uid)
    {
        /*cached("ranking:invite:data:day")->clearCached();
        cached("ranking:invite:data:month")->clearCached();
        cached("ranking:invite:data:week")->clearCached();*/
        return $this->incrByDay($score, $uid, self::KEY_NAME_INVITE);
    }

    protected function getType2Day($type = 'day'){
        $day = 1;
        if ($type == 'month') {
            $day = 7;
        } elseif ($type == 'week') {
            $day = 30;
        }
        return $day;
    }


    public function incrByDay($score, $uid, $keyName, $timestamp = null)
    {
        if ($timestamp === null) {
            $timestamp = time();
        }
        $dayKey = "ranking:$keyName:day:" . date('Ymd', $timestamp);
        $value = redis()->zIncrBy($dayKey, $score, $uid);
        if ($value <= $score * 2) {
            redis()->expire($dayKey, 87400 * 14);
        }
        return $value;
    }


    /**
     * 对指定时间内进行一个
     * @param $start
     * @param $stop
     * @param $key
     * @param int $day
     * @param bool $withScore
     * @param null $timestamp
     * @return array
     * @author xiongba
     */
    public function revRangeByDay($key, $start, $stop, $day = 1, $withScore = false, $timestamp = null)
    {
        if ($timestamp === null) {
            $timestamp = time();
        }
        $dayKey = "ranking:$key:day:";
        $keys = [];
        for ($i = 0; $i < $day; $i++) {
            $keys[] = $dayKey . date('Ymd', $timestamp - $i * 86400);
        }
        $ary = [];
        foreach ($keys as $key) {
            $tmp = redis()->zRevRange($key, $start, $stop, true);
            foreach ($tmp as $member => $score) {
                $ary[$member] = floatval($ary[$member] ?? 0) + floatval($score);
            }
        }
        arsort($ary);
        $result = array_slice($ary, $start, $stop, true);
        return $withScore ? $result : array_keys($result);
    }

    /**
     * @throws \RedisException
     */
    public function listTotalRank($is_aw, $rankBy, $page, $limit){
        $list = cached(sprintf('mv:rank:total:bak:%s:%s:%s:%s', $is_aw, $rankBy, $page, $limit))
            ->group('mv:rank:list')
            ->chinese('视频排行榜')
            ->fetchPhp(function () use ($is_aw, $rankBy, $page, $limit){
                //默认like点赞 sell销量 play热播
                $sort = '';
                switch ($rankBy){
                    case 'like':
                        $sort = 'like';
                        break;
                    case 'sell':
                        $sort = 'count_pay';
                        break;
                    case 'play':
                        $sort = 'rating';
                        break;
                    default:
                        test_assert(false, '类型错误');
                }
                return MvModel::queryBase()
                    ->where('is_aw', $is_aw)
                    ->where('type', MvModel::TYPE_LONG)
                    ->orderByDesc($sort)
                    ->forPage($page, $limit)
                    ->get()
                    ->map(function (MvModel $item) use ($sort){
                        $item->val = max($item->$sort, 0);
                        return $item;
                    });
            },86400);
        $list = (new MvService())->v2format($list);
        if (!is_array($list)){
            $list = $list->toArray();
        }
        return $list;
    }

    /**
     * @throws \RedisException
     */
    public function listRank($is_aw, $rankBy, $rankTime, $page, $limit){
        //默认长视频排行榜
        $list = cached(sprintf('mv:rank:bak:%s:%s:%s:%s:%s', $is_aw, $rankBy, $rankTime, $page, $limit))
            ->group('mv:rank:list')
            ->chinese('视频排行榜')
            ->fetchPhp(function () use ($is_aw, $rankBy, $rankTime, $page, $limit){
                $rankInfo = \MvTotalModel::getRankByRedis($is_aw, MvModel::TYPE_LONG, $rankBy, $rankTime, 200);
                if (!$rankInfo){
                    return [];
                }
                $mvIdArr = array_keys($rankInfo);
                $ids = collect($mvIdArr)->forPage($page, $limit)->values()->toArray();
                if (empty($ids)){
                    return [];
                }
                return MvModel::queryBase()
                    ->whereIn('id', $ids)
                    ->get()
                    ->map(function (MvModel $item) use ($rankInfo){
                        $item->val = max($rankInfo[$item->id], 0);
                        return $item;
                    });
            },600);
        if (empty($list)){
            return [];
        }
        $list = (new MvService())->v2format($list);
        if (!is_array($list)){
            $list = $list->toArray();
        }
        array_multisort(array_column($list, 'val'), SORT_DESC, $list);
        return $list;
    }
}