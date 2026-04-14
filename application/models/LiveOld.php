<?php

/*
 * 直播模块
 */

use \tools\RedisService;


/**
 *
 *
 * @property int $iv_vibrator
 * @property int $is_turntable
 * @property string $uuid
 *
 * Class LiveModel
 * @author xiongba
 */
class LiveOldModel extends RedisBizModel
{

    const DF_ROOM_IMG = '/static/file/actor_default.jpg';

    use \repositories\UsersRepository,
        \repositories\StreamRepository,
        \repositories\LiveRepository;


    protected $keyType = 'hash';

    protected $primaryKey = 'live_room';

    public $redisMainKey = 'live_room';
    public $redisLiveLogKey = 'l_log';
    public $redisKeyAdminPrefix = 'l_admin_';
    public $redisCloseFibWeekKey = 'live_close_fib_week';
    public $redisCloseDenyKey = 'live_close_deny';
    public $redisKickPrefix = 'live_kick_';
    public $redisLiveUserPrefix = 'live_user_';
    public $redisLiveUserSelectCachePrefix = 'live_user_select_';
    public $redisInfoWorkManPrefix = 'video_live_';

    public static $roomType = [
        'NORMAL'   => 0,
        'PASSWORD' => 1,
        'TICKET'   => 2,
        'TIMER'    => 3,
    ];

    public static $roomTypeLimit = [
        'MIN' => 0,
        'MAX' => 20,
    ];

    /**
     * 关播
     * @param $uid
     * @return array
     */
    public function stopRoom($uid)
    {
        // 记录开播信息
        $info = $this->stopInfo($uid);
        redis()->hDel($this->redisMainKey, $uid);
        redis()->del($this->redisLiveUserPrefix . $uid);
        redis()->del($this->redisLiveUserSelectCachePrefix . $uid);
        redis()->del($this->redisInfoWorkManPrefix . $uid);
        return $info;
    }


    /**
     * 离开房间
     * @param $uid
     * @return mixed
     */
    public function leaveRoom($liveUid, $uid)
    {
        $result = redis()->zRem($this->redisLiveUserPrefix . $liveUid, $uid);
        return $result;
    }

    /**
     * 直播记录
     * @param bool $liveUid
     * @return mixed
     */
    public function logInfo($liveUid = false)
    {
        if ($liveUid) {
            $liveLogInfo = $this->findLogByUid($liveUid);
        } else {
            $liveLogInfo = redis()->hGetAll($this->redisLiveLogKey);
            if (!$liveLogInfo) {
                $liveLogInfo = [];
            }
        }
        return $liveLogInfo;
    }

    /**
     * 得到房间信息
     * @param $uid
     * @return bool|mixed
     */
    public function getRoomInfo($uid)
    {
        $rawInfo = redis()->hGet($this->redisMainKey, $uid);
        if ($rawInfo) {
            return unserialize($rawInfo);
        }
        return false;
    }


    public function isLive($uid)
    {
        static $uids = null;
        if (null === $uids) {
            $uids = redis()->hKeys($this->redisMainKey);
            $uids = array_flip($uids);
        }
        return isset($uids[$uid]);
    }

    /*
     * 开播,创建房间
     */
    public function createRoom($uid, $data)
    {
        $isExist = redis()->hGet($this->redisMainKey, $uid);
        if ($isExist) {
            $this->stopRoom($uid);
            $this->addRoom($uid, $uid, $data, true);
        } else {
            $this->addRoom($uid, $uid, $data, true);
        }
        return 1;
    }

    /*
     * 记录开播,进入房间
     */
    public function addRoom($liveUid, $uid, $data, $isActor = false)
    {
        redis()->hSet($this->redisMainKey, $liveUid, serialize($data));
        if (!$isActor) {
            $this->enterRoom($liveUid, $uid, $data);
        }
        return true;
    }

    /*
    * 进入房间
    */
    public function enterRoom($liveUid, $uid, $data)
    {
        redis()->zAdd($this->redisLiveUserPrefix . $liveUid, $uid, $uid);
        // 人数加1
        $this->setRoom($liveUid, ['nums' => 1], true);
        return true;
    }

    /**
     * 设置更新房间状态
     * @param $uid
     * @param $data
     * @param bool $update
     * @return bool
     */
    public function setRoom($uid, $data, $update = false)
    {

        $rawData = redis()->hGet($this->redisMainKey, $uid);
        if (!$rawData) {
            return true;
        }

        $oldData = unserialize($rawData) ?? [];

        if ($update) {
            $newData = $oldData;
            foreach ($data as $k => $v) {
                if (isset($newData[$k])) {
                    $newData[$k] = $newData[$k] + $v;
                } else {
                    $newData[$k] = $v;
                }
            }
        } else {
            $newData = array_merge($oldData, $data);
        }

        redis()->hSet($this->redisMainKey, $uid, serialize($newData));
        return true;
    }

    /**
     * 所有直播列表
     * @return array
     */
    public function realList()
    {
        $return = [];
        if ($dataRaw = redis()->hGetAll($this->redisMainKey)) {
            $return = $dataRaw;
        }
        return $return;
    }

    /**
     * 所有直播列表key
     * @return array
     */
    public function realListIds()
    {
        $return = [];
        if ($dataRaw = redis()->hKeys($this->redisMainKey)) {
            $return = $dataRaw;
        }
        return $return;
    }

    /**
     * 通过uid查找直播
     * @param $uid
     * @return array
     */
    public function findByUid($uid)
    {
        $return = [];
        if ($dataRaw = redis()->hGet($this->redisMainKey, $uid)) {
            $return = unserialize($dataRaw) ?? [];
        }
        return $return;
    }

    /**
     * 直播状态
     * @param $uid
     * @param $liveUid
     * @return int
     */
    public function checkLive($uid, $liveUid)
    {
        $live = redis()->hGet($this->redisMainKey, $liveUid);
        if (!$live) {
            return 1005;
        }
        $live = unserialize($live);

        $rs['type'] = $live['type'] ?? 0;
        $rs['type_val'] = '0';
        $rs['type_msg'] = '';

        if ($live['type'] == 1) {
            $rs['type_msg'] = '本房间为密码房间，请输入密码';
            $rs['type_val'] = md5($live['type_val']);
        } elseif ($live['type'] == 2) {
            $rs['type_msg'] = '本房间为收费房间，需支付' . $live['type_val'] . '金币';
            $rs['type_val'] = $live['type_val'];
            $usersCoinrecord = singleton(UsersCoinrecordModel::class);
            $where = [
                'uid'             => $uid,
                'touid'           => $liveUid,
                'showid'          => $live['start_time'] ?? 0,
                'live_updated_at' => $live['updated_at'] ?? $live['start_time'],
                'type'            => 'expend',
                'action'          => 'roomcharge',
            ];
            $isExist = $usersCoinrecord
                ->where($where)
                ->value('id');
            if ($isExist) {
                $rs['type'] = '0';
                $rs['type_val'] = '0';
                $rs['type_msg'] = '';
            }
        } elseif ($live['type'] == 3) {
            $rs['type_val'] = $live['type_val'];
            $rs['type_msg'] = '本房间为计时房间，每分钟需支付' . $live['type_val'] . '金币';
        }

        return $rs;

    }


    public function findLogByUid($uid)
    {
        $liveLogInfo = redis()->hGet($this->redisLiveLogKey, $uid);
        if ($liveLogInfo) {
            $liveLogInfo = unserialize($liveLogInfo);
        } else {
            $liveLogInfo = [];
        }
        return $liveLogInfo;
    }

    /**
     * 关播信息
     * @param $liveUid
     * @return array|false
     */
    public function stopInfo($liveUid)
    {
        $liveRoomInfo = $this->findByUid($liveUid);
        if (!$liveRoomInfo) {
            return [];
        }

        $liveLogInfo = $this->findLogByUid($liveUid);

        $startTime = $liveRoomInfo['start_time'] ?? 0;
        $time = TIMESTAMP - $startTime;
        $rs = [
            'time'       => $time,
            'nums'       => 0,
            'start_time' => $startTime,
            'length'     => getSeconds($time, 3),
            'votes'      => 0,
            'end_time'   => time()
        ];

        // 取出前当信息
        $roomInfo = $this->getRoomInfo($liveUid);
        if (empty($roomInfo)) {
            return false;
        }

        $rs['votes'] = intval($roomInfo['votes'] ?? 0);
        $rs['nums'] = intval($roomInfo['nums'] ?? 0);

        $liveLogInfo[] = ['calc' => $rs, 'info' => $liveRoomInfo];

        //$newLiveInfo = serialize(array_merge($liveLogInfo, $rs, $liveRoomInfo));
        //redis()->hSet($this->redisLiveLogKey, $liveUid, $newLiveInfo);
        redis()->hSet($this->redisLiveLogKey, $liveUid, serialize($liveLogInfo));
        $userlivelog = [
            'created_at'   => $startTime,
            'updated_at'   => time(),
            'uid'          => $liveUid,
            'title'        => $roomInfo['title'] ?? ($roomInfo['nickname'] ?? 'nothing'),
            'num'          => $rs['nums'],
            'votes'        => $rs['votes'],
            'ticket_votes' => $roomInfo['ticket_votes'] ?? 0,
            'time_votes'   => $roomInfo['time_votes'] ?? 0,
            'end_time'     => time(),
        ];
        \UserLiveLogModel::insert($userlivelog);
        unset($rs['start_time']);
        return $rs;
    }

    /**
     * 直播中用户
     * @param $liveUid
     * @param int $p
     * @param bool $refreshCache
     * @return mixed
     */
    public function getUserList($liveUid, $p = 1, $refreshCache = false)
    {
        /* 用户列表 */
        $pnum = 20;
        $start = (intval($p) - 1) * $pnum;

        $key = $this->redisLiveUserSelectCachePrefix . $liveUid;
        $list = cached($key)
            ->expired(60)
            ->serializerPHP()
            ->hash($p)
            ->fetch(function () use ($liveUid, $start, $pnum) {
                $list = array();
                $ids = redis()->zRevRange($this->redisLiveUserPrefix . $liveUid, $start, $pnum, true);
                if ($ids) {
                    $memberInfo = MemberModel::whereIn('uid', $ids)
                        ->select('uid', 'uuid', 'username', 'nickname', "thumb", "level")
                        ->get()
                        ->toArray();
                    $guardInfo = UserGuardModel::whereIn('uid', $ids)->where('liveuid', $liveUid)->get();
                    foreach ($memberInfo as $k => $v) {
                        $info = $v;
                        $info['contribution'] = (string)($info[0] ?? 0);
                        $info['guard_type'] = $guardInfo[$k]['type'] ?? 0;
                        $info['thumb'] = $this->fetchUserThumb($info['thumb']);
                        $list[] = $info;
                    }
                }
                return $list;
            }, [], $refreshCache);

        if (!$list) {
            $list = array();
        }

        $nums = $this->findByUid($liveUid)['nums'] ?? 0;
        if (!$nums) {
            $nums = 0;
        }

        $rs['userlist'] = $list;
        $rs['nums'] = (string)$nums;

        /* 主播信息 */
        $rs['votes_total'] = MemberModel::find($liveUid)->votes_total;

        return $rs;
    }

    /**
     * 获取魅力值
     * @param $number 直播间人数
     * @param $sTime  播放时长
     * @param $coin   收到的妹币（礼物）
     * @return int
     */
    public function getPopularity($number, $sTime, $coin)
    {
        $time = intval((time() - $sTime) / 60);
        $time = 0;
        return intval(($number * 0.2) + ($time * 0.25) + ($coin * 0.8));
    }

    /**
     * 直播中管理员列表
     * @param $liveUid
     * @return mixed
     */
    public function getAdmin($liveUid)
    {
        return redis()->sMembers($this->redisKeyAdminPrefix . $liveUid);
    }

    /**
     * 设置直播中管理员
     * @param $liveUid
     * @param $uid
     * @param $isAdd
     * @param bool $auto
     * @return mixed
     */
    public function setAdmin($liveUid, $uid, $isAdd, $auto = false)
    {
        if ($auto) {
            $isAdd = redis()->sisMember($this->redisKeyAdminPrefix . $liveUid, $uid);
        }
        if ($isAdd) {
            return redis()->sRem($this->redisKeyAdminPrefix . $liveUid, $uid);
        } else {
            return redis()->sAdd($this->redisKeyAdminPrefix . $liveUid, $uid);
        }

    }

    /**
     * 判断是否直播中的管理员
     * @param $liveUid
     * @param $uid
     * @return bool
     */
    public function isAdmin($liveUid, $uid)
    {
        return redis()->sisMember($this->redisKeyAdminPrefix . $liveUid, $uid);
    }

    /**
     * 判断管理员人数
     * @param $liveUid
     * @return mixed
     */
    public function getAdminLen($liveUid)
    {
        return redis()->sCard($this->redisKeyAdminPrefix . $liveUid);
    }

    /**
     * 修改直播状态
     * @param $uid
     * @param $data
     * @return bool
     */
    public function changeLive($uid, $data)
    {
        return $this->setRoom($uid, $data);
    }


    /**
     * 计时房间扣费
     * @param $uid
     * @param $liveUid
     * @return int
     */
    public function roomCharge($uid, $liveUid)
    {

        $isLive = redis()->hGet($this->redisMainKey, $liveUid);
        if (!$isLive) {
            return 1005;
        }

        $isLive = unserialize($isLive);

        if ($isLive['type'] == 0 || $isLive['type'] == 1) {
            return 1006;
        }
        /** @var MemberModel $member */
        $member = MemberModel::where('uid', $uid)->first();
        $total = $isLive['type_val'];
        if ($total <= 0) { //房间费用有误
            return 1007;
        }
        $action = 'roomcharge';
        if ($isLive['type'] == 3) {
            $action = 'timecharge';
        }
        $showid = $isLive['start_time'];
        /** @var MemberModel $liveMember */
        $liveMember = MemberModel::where('uid', $liveUid)->first();
        try {
            DB::beginTransaction();
            /* 更新用户余额 消费 */
            $itOk = $member->incrMustGE_raw(['coins' => -$total, 'consumption' => $total]);
            if (!$itOk) {
                //余额不不足
                throw new \Exception('1008');
            }
            $itOk = UsersCoinrecordModel::createForExpend($action, $uid, $liveUid, $total, 0, 0, $showid);
            if (empty($itOk)) {
                throw new \Exception('1008');
            }
            /* 更新直播 映票 累计映票 */
            $itOk = $liveMember->incrMustGE_raw(['votes' => $total, 'votes_total' => $total]);
            if (empty($itOk)) {
                throw new \Exception('1008');
            }
            $itOk = UserVoterecordModel::addIncome($liveUid, $action, $total);
            if (empty($itOk)) {
                throw new \Exception('1008');
            }
            DB::commit();
            /* 更新缓存 */
            changeMemberCache($member->getDeviceHash(), [
                'coins'       => $member->coins,
                'consumption' => $member->consumption,
            ]);
            changeMemberCache($liveMember->getDeviceHash(), [
                'votes'       => $liveMember->votes,
                'votes_total' => $liveMember->votes_total,
            ]);
            if ($action == 'roomcharge') {
                //门票房间
                $this->setRoom($liveUid, ['ticket_votes' => $total], true);
            } else {
                //计时房
                $this->setRoom($liveUid, ['time_votes' => $total], true);
            }
            $rs['coins'] = $member->coins;
            $rs['votes_total'] = $liveMember->votes_total;

            return $rs;
        } catch (\Throwable $e) {
            DB::rollBack();
            return intval($e->getMessage());
        }
    }

    /**
     * 超管关闭直播间
     * @param $liveUid
     * @param $isLiveSuper
     * @param $type
     * @return int
     */
    public function superStopRoom($liveUid, $isLiveSuper, $type)
    {

        if ($isLiveSuper == 0) {
            return 1001;
        }
        if ($type == 1) {
            /* 关闭并禁用 */
            // todo nothing
        }
        $info = LiveModel::instance()->getRoomInfo($liveUid);
        $stream = "{$liveUid}_{$info['start_time']}";
        if ($info) {
            // 删除魅力值
            delCache('live_' . $stream . '_coin');
            $this->stopInfo($liveUid);
            $this->stopInfo($stream);
        }
        return 0;
    }

    /**
     * 判断是否禁止開播七天.
     * @param $uid
     * @return bool
     */
    public function closeFibWeek($uid)
    {
        $data = redis()->hGet($this->redisCloseFibWeekKey, $uid);
        if ($data) {
            $info = unserialize($data);
            if ($info['fib_time'] > time() - 86400 * 7) {
                return true;
            }
        }
        return false;
    }

    /**
     * 禁言一周，注意缓存过期.
     * @param $uid
     * @return bool
     */
    public function doCloseFibWeek($uid)
    {
        $data = serialize(['uid' => $uid, 'fib_time' => time()]);
        return redis()->hSet($this->redisCloseFibWeekKey, $uid, $data);
    }

    /**
     * 判断用户是否封禁.
     * @param $uid
     * @return bool
     */
    public function closeDeny($uid)
    {
        $data = redis()->hGet($this->redisCloseDenyKey, $uid);
        if ($data) {
            $info = unserialize($data);
            if (1 == ($data['type'] ?? 0)) {
                return true;
            }
            if ($info['fib_time'] > time() - 86400 * 7) {
                return true;
            }
        }
        return false;
    }

    /**
     * 封禁，注意缓存过期.
     * @param $uid
     * @param $type
     * @return mixed
     */
    public function doCloseDeny($uid, $type)
    {
        $data = ['uid' => $uid, 'fib_time' => time()];
        if (1 == $type) {
            $data['type'] = 1;
        } else {
            $data['type'] = 0;
        }
        $dataFix = serialize($data);
        return redis()->hSet($this->redisCloseDenyKey, $uid, $dataFix);
    }


    /**
     * 踢人
     * @param $liveUid
     * @param $uid
     * @return int
     */
    public function kick($liveUid, $uid)
    {
        // 被踢检查
        $isKick = redis()->hGet($this->redisKickPrefix . $liveUid, $uid);
        if ($surplus = $isKick - time()) {
            $rs['code'] = 1004;
            return $surplus;
        } else {
            redis()->hdel($this->redisKickPrefix . $liveUid, $uid);
        }
        return 0;
    }

    /**
     * 踢人动作
     * @param $liveUid
     * @param $uid
     * @param $time
     * @return int
     */
    public function doKick($liveUid, $uid, $time)
    {
        redis()->hSet($this->redisKickPrefix . $liveUid, $uid, $time);
        return 0;
    }

    /**
     * 得到热门主播
     * @param int $p
     * @return bool
     */
    public function getHot($p = 0)
    {

        $actorRaw = redis()->hGetAll($this->redisMainKey);
        $actor = [];
        foreach ($actorRaw as $item) {
            $tmp = unserialize($item);
            $is_recommend = $tmp['is_recommend'] ?? 0;
            $is_hot = $tmp['is_hot'] ?? 0;//主要是 苹果 安卓没有统一展示 热门
            $is_new = $tmp['is_new'] ?? 0;
            if ($is_hot && !$is_recommend) {
                $tmpArr = [
                    'uid'           => $tmp['uid'],
                    'nickname'      => $tmp['nickname'],
                    'thumb'         => $tmp['thumb'],//自己头像
                    'theme'         => $tmp['avatar'],//房间封面
                    'start_time'    => $tmp['start_time'],
                    'title'         => $tmp['title'],
                    'stream'        => $tmp['stream'],
                    'pull'          => $tmp['pull'],
                    'type'          => $tmp['type'],
                    'type_val'      => $tmp['type_val'],
                    'good_num'      => $tmp['good_num'],
                    'beauty_no'     => $tmp['good_num'],
                    'live_class_id' => $tmp['live_class_id'],
                    'hot_level'     => $tmp['hot_level'],
                    'nums'          => $tmp['nums'],
                    'length'        => $tmp['length'],
                    'votes'         => $tmp['votes'],
                    'is_live'       => 1,
                    'v_info'        => [
                        'level'        => $tmp['v_info']['level'],
                        'level_anchor' => $tmp['v_info']['level_anchor'],
                        'consumption'  => $tmp['v_info']['consumption'],
                        'votes_total'  => $tmp['v_info']['votes_total'],
                    ],
                    // 'popularity' => $tmp['popularity'],
                ];
                $actor[] = $tmpArr;
            }
        }
        return $this->liveList($actor);
    }

    /**
     * 得到所有主播
     * @param int $p
     * @param array $actors
     * @param int $limit
     * @return bool
     */
    public function getAll($p = 0, $actors = [], $limit = 20)
    {
        $actorRaw = redis()->hGetAll($this->redisLiveLogKey);
        $actor = [];
        foreach ($actorRaw as $item) {
            $tmp = unserialize($item);
            if ($actors) {
                if (in_array($tmp['uid'], $actors)) {
                    $actor[] = $tmp;
                }
            } else {
                $actor[] = $tmp;
            }
        }
        if ($p) {
            $actor = redisHashPage($actor, $p, $limit = 10, $order = 'uid');
        }
        return $this->liveList($actor);
    }


    /**
     * 得到推荐主播
     * @return bool
     */
    public function getRecommendLive()
    {
        $actorRaw = redis()->hGetAll($this->redisMainKey);
        $actor = [];
        foreach ($actorRaw as $item) {
            $tmp = unserialize($item);
            $is_recommend = $tmp['is_recommend'] ?? 0;
            if ($is_recommend) {
                $tmpArr = [
                    'uid'           => $tmp['uid'],
                    'nickname'      => $tmp['nickname'],
                    'thumb'         => $tmp['thumb'],
                    'theme'         => $tmp['avatar'],
                    'start_time'    => $tmp['start_time'],
                    'title'         => $tmp['title'],
                    'stream'        => $tmp['stream'],
                    'pull'          => $tmp['pull'],
                    'type'          => $tmp['type'],
                    'type_val'      => $tmp['type_val'],
                    'good_num'      => $tmp['good_num'],
                    'beauty_no'     => $tmp['good_num'],
                    'live_class_id' => $tmp['live_class_id'],
                    'hot_level'     => $tmp['hot_level'],
                    'nums'          => $tmp['nums'],
                    'length'        => $tmp['length'],
                    'votes'         => $tmp['votes'],
                    'is_live'       => 1,
                    'v_info'        => [
                        'level'        => $tmp['v_info']['level'],
                        'level_anchor' => $tmp['v_info']['level_anchor'],
                        'consumption'  => $tmp['v_info']['consumption'],
                        'votes_total'  => $tmp['v_info']['votes_total'],
                    ],
                    'popularity'    => getPopularity($tmp['stream']),
                ];
                $actor[] = $tmpArr;
            }
        }
        return $this->liveList($actor);
    }

    /**
     * 判断是否新秀
     * @param int $p
     * @return bool
     */
    public function getIsNew($p = 0)
    {
        $actorRaw = redis()->hGetAll($this->redisMainKey);

        $actor = [];
        foreach ($actorRaw as $item) {
            $tmp = unserialize($item);
            $is_recommend = $tmp['is_recommend'] ?? 0;
            $is_hot = $tmp['is_hot'] ?? 0;//主要是 苹果 安卓没有统一展示 热门
            $is_new = $tmp['is_new'] ?? 0;
            if ($is_new && !$is_recommend) {
                $tmpArr = [
                    'uid'           => $tmp['uid'],
                    'nickname'      => $tmp['nickname'],
                    'thumb'         => $tmp['thumb'],
                    'theme'         => $tmp['avatar'],
                    'start_time'    => $tmp['start_time'],
                    'title'         => $tmp['title'],
                    'stream'        => $tmp['stream'],
                    'pull'          => $tmp['pull'],
                    'type'          => $tmp['type'],
                    'type_val'      => $tmp['type_val'],
                    'good_num'      => $tmp['good_num'],
                    'beauty_no'     => $tmp['good_num'],
                    'live_class_id' => $tmp['live_class_id'],
                    'hot_level'     => $tmp['hot_level'],
                    'nums'          => $tmp['nums'],
                    'length'        => $tmp['length'],
                    'votes'         => $tmp['votes'],
                    'is_live'       => 1,
                    'v_info'        => [
                        'level'        => $tmp['v_info']['level'],
                        'level_anchor' => $tmp['v_info']['level_anchor'],
                        'consumption'  => $tmp['v_info']['consumption'],
                        'votes_total'  => $tmp['v_info']['votes_total'],
                    ]
                    // 'popularity' => $tmp['popularity'],
                ];
                $actor[] = $tmpArr;
            }
        }
        return $this->liveList($actor);
    }

    /*
     * 所有直播数据
     */
    public function getLiveAll($p = 0)
    {
        $actorRaw = redis()->hGetAll($this->redisMainKey);

        $actor = [];
        foreach ($actorRaw as $item) {
            $tmp = unserialize($item);
            $actor[] = $tmp;
        }
        return $this->liveList($actor);
    }

    public function enableVibrator($liveUid)
    {
        return $this->save($liveUid, ['iv_vibrator' => 1]);
    }

    public function stopVibrator($liveUid)
    {
        return $this->save($liveUid, ['iv_vibrator' => 0]);
    }

    public function save($liveUid, $data)
    {
        $str = redis()->hGet($this->redisMainKey, $liveUid);
        if (empty($str)) {
            return false;
        }
        $room = unserialize($str);
        $data = array_merge($room, $data);
        redis()->hSet($this->redisMainKey, $liveUid, serialize($data));
        return true;
    }

    /**
     * 处理直播数据  数据结构不兼容特别处理 fuck   直播|推荐|新秀 --ALL
     * @param $result
     * @param bool $if_sort
     * @return bool|array
     */
    public function liveList($result, $if_sort = true)
    {

        if ($if_sort) {
            $sort = [];
        }
        if (!$result) {
            return false;
        }
        $return = [];
        foreach ($result as $k => $v) {
            if (!isset($v['uid'])) {
                continue;
            }
            $vv['uid'] = $v['uid'];
            $vv['nickname'] = $v['nickname'];
            $vv['nums'] = $v['nums'];
            $vv['level'] = $v['v_info']['level'];
            $votes_total = $v['v_info']['votes_total'];
            $vv['level_anchor'] = getLevelAnchor($votes_total);
            $vv['votes_total'] = $v['v_info']['votes_total'];
            $vv['title'] = $v['title'];
            $vv['stream'] = $v['stream'];
            $vv['is_live'] = $v['is_live'];
            $vv['votes'] = $v['votes'];
            $vv['beauty_no'] = $v['beauty_no'];
            $vv['type'] = $v['type'];
            $vv['type_val'] = $v['type_val'];
            $vv['is_recommend'] = $v['is_recommend'] ?? 0;
            $vv['iv_vibrator'] = intval($v['iv_vibrator'] ?? 0); //是否是跳蛋房间
            $vv['is_turntable'] = intval($v['is_turntable'] ?? 0); //是否是转盘房间

            if ($vv['type'] == 1) {
                $vv['type_val'] = '';
            }
            $vv['thumb'] = url_avatar($v['thumb']);

            //兼容处理
            $target = $v['theme'] ?? '';
            if (strlen($target) < 10) {
                if (isset($v['avatar']) && $v['avatar']) {
                    $target = $v['avatar'];
                } else {
                    $target = self::DF_ROOM_IMG;
                }
            }
            $vv['theme'] = $target ? url_live($target) : $vv['thumb'];
            if (stripos($vv['thumb'], '91_ads_20200111FeTEqY.png') != false) {
                $vv['thumb'] = $vv['theme'];
            }
            //$vv['theme'] = $vv['thumb'];

            // 主播魅力值
            $sTime = explode('_', $v['stream'])[1];
            $coin = getCaches('live_' . $v['stream'] . '_coin');
            if (!$coin) {
                $coin = 0;
            }
            $popularity = $this->getPopularity($v['nums'], $sTime, $coin) ?? 0;
            $vv['popularity'] = $popularity ?? 0;
            $vv['pull'] = $this->PrivateKeyA('rtmp', $v['stream'], 0);
            $return[] = $vv;
            if ($if_sort) {
                // 排序规则
                $sort[] = $vv['popularity'];
            }
        }
        // 按魅力值排序
        if ($if_sort) {
            array_multisort($sort, SORT_DESC, SORT_NUMERIC, $return);
        }
        return $return;
    }

    /**
     * 分类下直播
     * @param $liveClassId
     * @param $p
     * @return mixed
     */
    public function getClassLive($liveClassId, $p)
    {
        $actorRaw = redis()->hGetAll($this->redisMainKey);
        $actor = [];
        foreach ($actorRaw as $item) {
            $tmp = unserialize($item);
            if ($tmp['live_class_id'] == $liveClassId) {
                $actor[] = $tmp;
            }
        }
        return $this->liveList($actor);
    }

    /**
     * 获取用户在房间中的级别
     * @param $uid
     * @param $liveId
     * @return int
     */
    public static function powerLevel($uid, $liveId)
    {
        return isAdmin($uid, $liveId);
    }

    /**
     * 获取主播累计今天在线时长
     * @param $uid
     * @return int
     */
    public function todayTotalTime($uid)
    {
        $data = $this->getRoomInfo($uid);
        $firstTime = strtotime(date('Y-m-d 00:00:00'));
        $where = [
            ['uid', '=', $uid],
            ['start_time', '>=', $firstTime],
        ];
        $models = UserLiveLogModel::where($where)->get(['start_time', 'end_time']);
        //今天之前开始后开播的
        if (empty($data)) {
            $total = 0;
        } elseif ($data['start_time'] < $firstTime) {
            $total = time() - $firstTime;
        } else {
            $total = time() - $data['start_time'];
        }
        foreach ($models as $model) {
            $total += $model->end_time - $model->start_time;
        }
        return $total;
    }

    /**
     * 主播持续直播在线时长
     * @param $uid
     * @return int|mixed
     * @author xiongba
     * @date 2020-03-06 15:42:25
     */
    public function keepTime($uid)
    {
        $data = $this->getRoomInfo($uid);
        if (empty($data)) {
            return 0;
        }
        return time() - $data['start_time'];
    }

    public function checkUserLimit($member, &$ci)
    {
        $exp = $member['expired_at'] > TIMESTAMP ? 1 : 0;//当前是否过期
        $vip_level = $member['vip_level'];//当前会员等级
        $xf = $member['coins'] > 1;
        $is_91 = $member['build_id'] == 'k91live';
        //会员
        //if ($exp && ($vip_level || in_array($member['build_id'], ['k91live']))) {
        if ($exp ) {
            return false;
        }
        //check has order
//        if (OrdersModel::hasChargeVip($member['uuid'])) {
//            return false;
//        }
        $limit = setting('enter.room.limit', '1,10');
        list($enable, $times) = explode(',', $limit);
        $ci = $times;
        if (!$enable) {
            return false;
        }
        $key = 'xz:' . $member['uid'];
        $number = redis()->incr($key);
        if ($number < 3) {
            redis()->expireAt($key, strtotime(date('Y-m-d 23:59:59', TIMESTAMP)));
        }
        if ($number <= $times + 1) {
            return false;
        }
        return true;
    }
}