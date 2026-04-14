<?php

use tools\RedisService;

/**
 * class MemberCoinrecordModel
 * 用户金币消费日志
 *
 * @property int $id
 * @property int $uid 用户ID
 * @property string $action 收支行为
 * @property int $game_action 游戏类型
 * @property int $game_banker 庄家ID
 * @property int $giftcount 数量
 * @property int $giftid 行为对应ID
 * @property int $live_updated_at 直播时间
 * @property int $mark 标识，1表示热门礼物，2表示守护礼物
 * @property int $showid 直播标识
 * @property int $totalcoin 总价
 * @property int $touid 对方ID
 * @property string $type 收支类型
 * @property int $addtime 添加时间
 *
 * @author xiongba
 * @date 2020-03-02 12:32:23
 *
 * @mixin \Eloquent
 */
class UsersCoinrecordModel extends EloquentModel
{
    protected $table = 'member_coinrecord';

    protected $primaryKey = 'id';


    protected $guarded = [];

    const SPRING = [
        500     => 20,
        1000    => 50,
        5000    => 100,
        30000   => 500,
        80000   => 600,
        150000  => 800,
        200000  => 1000,
        300000  => 2222,
        500000  => 3333,
        800000  => 4444,
        1200000 => 5555,
        1500000 => 6666,
    ];


    protected $appends = ['add_time_str'];

    public function getAddTimeStrAttribute($key)
    {
        return date('Y-m-d H:i:s', $this->attributes['addtime'] ?? 0);
    }


    public static function createForExpendBySys(
        $action,
        $uid,
        $totalCoin,
        $showId,
        $mark = null,
        $addTime = null
    ) {
        return self::createForExpend($action, $uid, 0, $totalCoin, 0, 0, $showId, $mark, $addTime);
    }

    public static function addIncome($action, $uid, $toUid, $totalCoin, $giftid, $showId, $desc = '')
    {
        $add_log = [
            "type"      => 'income',
            "action"    => $action,
            "uid"       => $uid,
            "touid"     => $toUid ?? $uid,
            "giftid"    => $giftid,
            "giftcount" => 0,
            "totalcoin" => $totalCoin,
            "showid"    => $showId,
            "addtime"   => time(),
            'desc'      => $desc
        ];
        return \UsersCoinrecordModel::create($add_log);
    }

    public static function addMvExpend($uid, $mv, $totalCoin,$is_kou = 0)
    {
        $insert = [
            "type"      => 'expend',
            "action"    => 'buymv',
            "uid"       => $uid,
            "touid"     => $mv['uid'],
            "giftid"    => 0,
            "giftcount" => 0,
            "totalcoin" => $totalCoin,
            "showid"    => $mv['id'],
            "mark"      => $is_kou ? 1 : 0,
            "addtime"   => time(),
            "desc"      => "购买视频[{$mv['title']}]",
        ];

        //今日金币消耗
        \SysTotalModel::incrBy('total_gold_consume',$totalCoin);

        return self::create($insert);
    }

    public static function addTopicExpend($uid, $title, $totalCoin)
    {
        $insert = [
            "type"      => 'expend',
            "action"    => 'topic',
            "uid"       => $uid,
            "touid"     => 0,
            "giftid"    => 0,
            "giftcount" => 0,
            "totalcoin" => $totalCoin,
            "showid"    => 0,
            "mark"      => 0,
            "addtime"   => time(),
            "desc"      => "创建合集[{$title}]",
        ];

        //今日金币消耗
        \SysTotalModel::incrBy('total_gold_consume',$totalCoin);

        return self::create($insert);
    }

    public static function createForExpend(
        $action,
        $uid,
        $toUid,
        $totalCoin,
        $giftId,
        $giftCount,
        $showId,
        $mark = null,
        $addTime = null,
        $desc = null
    ) {
        $insert = [
            "type"      => 'expend',
            "action"    => $action,
            "uid"       => $uid,
            "touid"     => $toUid,
            "giftid"    => $giftId,
            "giftcount" => $giftCount,
            "totalcoin" => $totalCoin,
            "showid"    => $showId,
            "mark"      => $mark,
            "addtime"   => $addTime ?? time(),
            "desc"      => $desc?$desc:$action,
        ];

        foreach ($insert as $k => $v) {
            if ($v === null) {
                unset($insert[$k]);
            }
        }

        $model = self::create($insert);
        if ($model) {
            //redis()->del(self::getContribute($toUid, $uid, 'day'));
            //redis()->del(self::getContribute($toUid, $uid, 'week'));
            //redis()->del(self::getContribute($toUid, $uid, 'moon'));
        }

        //今日金币消耗
        \SysTotalModel::incrBy('total_gold_consume',$totalCoin);

        return $model;
    }

    public function withMember()
    {
        return $this->hasOne(MemberModel::class, 'uid', 'uid');
    }

    public function withUser()
    {
        return $this->withMember();
    }

    public function withLiveMember()
    {
        return $this->hasOne(MemberModel::class, 'uid', 'touid');
    }

    public function withMv()
    {
        return $this->hasOne(MvModel::class, 'id', 'showid');
    }

    /* 获取用户本场贡献 */
    public function getContribut($uid, $liveuid)
    {
        $sum = $this->where('action', 'sendgift')
            ->where('uid', $uid)
            ->where('touid', $liveuid)
            ->sum('totalcoin');
        if (!$sum) {
            $sum = 0;
        }
        return $sum;
    }

    public function getWeekContribute($uid, $starttime = 0, $endtime = 0)
    {
        $contribute = '0';
        if ($uid > 0) {
            $query = $this->query();
            $query->whereIn('action', ['sendgift', 'buyguard'])->where('uid', $uid);
            if ($starttime > 0) {
                $query->where('addtime', '>', $starttime);
            }
            if ($endtime > 0) {
                $query->where('addtime', '<', $endtime);
            }
            $contribute = $query->sum('totalcoin');
            if (!$contribute) {
                $contribute = 0;
            }
        }

        return $contribute;
    }

    /* 贡献榜 */
    public function getContributeList($touid, $p)
    {
        $pnum = 50;
        $start = ($p - 1) * $pnum;

        $rs = $this->select(["uid", DB::raw('SUM(totalcoin) as total')])
            ->where('touid', $touid)->groupBy("uid")->orderBy("total", 'desc')
            ->offset($start)->limit($pnum)->get();
        if ($rs) {
            $rs = $rs->toArray();
        }
        foreach ($rs as $k => $v) {
            $rs[$k]['userinfo'] = getUserInfo($v['uid']);
        }

        return $rs;
    }

    // 给主播榜
    public function getContributeTopList($touid, $crypt = false)
    {
        $rs = $this->select("uid")
            ->where('touid', $touid)->where('uid', '!=', $touid)->groupBy("uid")->limit(3)->get();

        if (!$rs) {
            return [];
        }
        $rs = $rs->toArray();
        $return = [];
        foreach ($rs as $k => $v) {
            $touid = getUserInfo($v['uid'], 0, $crypt);
            $return[] = $touid['avatar'];
        }
        return $return;
    }

    public function getSendGiftList($uid, $type = 0, $p)
    {
        if ($type == 1) {
            $where = [
                'touid'  => $uid,
                'action' => 'sendgift'
            ];
        } else {
            $where = [
                'uid'    => $uid,
                'action' => 'sendgift'
            ];
        }
        $list = $this->_getGiftList($where, $p);

        $retrun = [];
        foreach ($list as $k => $value) {
            $user_info = getUserInfo($value['uid']);
            $retrun[$k]['nickname'] = $user_info['nickname'];
            $retrun[$k]['gift_name'] = $value->withGift->giftname;
            $retrun[$k]['send_gift_count'] = $value->giftcount;
            $retrun[$k]['total_coin'] = $value->totalcoin;
        }
        return $retrun;
    }


    private function _getGiftList($where, $p = 1)
    {
        $start = $p * 50;
        $list = $this->select("uid", 'giftid', 'giftcount', 'totalcoin')
            ->with('withGift')->orderBy("addtime", 'desc')->where($where)
            ->offset($start)->limit(50)->get();

        return $list;
    }

    public function withGift()
    {
        return $this->hasOne(GiftModel::class, 'id', 'giftid');
    }

    public function withToUser()
    {
        return $this->hasOne(MemberModel::class, 'id', 'touid');
    }

    protected static function getRedisKey($toUid, $myUid, $dayType)
    {
        return __FUNCTION__ . ':' . $myUid . ':' . $toUid . ':' . $dayType;
    }

    public static function getContribute($toUid, $myUid, $dayType)
    {
        $where = ['type' => 'expend', 'action' => 'sendgift', 'touid' => $toUid, 'uid' => $myUid];
        switch ($dayType) {
            case 'moon':
                $fistTime = strtotime(date('Y-m-01'));
                break;
            case 'week':
                $fistTime = strtotime(date('Y-m-d 00:00:00', \Carbon\Carbon::now()->weekday(1)->timestamp));
                break;
            case 'day':
            default:
                $fistTime = strtotime(date('Y-m-d'));
                break;
        }

        $val = cached(self::getRedisKey($toUid, $myUid, $dayType))
            ->expired(600)
            ->fetch(function () use ($where, $fistTime) {
                return self::where($where)
                    ->where('addtime', '>', $fistTime)
                    ->sum('totalcoin');
            });
        return (int)$val;
    }

    /**
     * 获取前三贡献榜
     * @param $uid
     * @param int $limit
     * @return mixed
     * @author xiongba
     */
    static function getTopContribute($uid, $limit = 3)
    {
        $key = sprintf('%s:%s:%s', __FUNCTION__, $uid, $limit);
        return cached($key)
            ->expired(300)
            ->serializerJSON()
            ->fetch(function ($uid, $limit) {
                $where = ['type' => 'expend', 'action' => 'sendgift', 'touid' => $uid];
                return self::with('withUser:uid,thumb,nickname')
                    ->select(["uid", DB::raw('SUM(totalcoin) as total')])
                    ->where($where)
                    ->groupBy(['uid'])
                    ->orderByDesc('total')
                    ->limit($limit)
                    ->get()
                    ->pluck('withUser');
            }, [$uid, $limit]);
    }

    static function formateContributeApi($item)
    {
        $member = MemberModel::query()->where('uid', $item['uid'])->first();
        $thumb = $member ? $member['thumb'] : '';
        return $item ? [
            'uid'   => $item['uid'],
            'thumb' => $thumb,
            'date'  => date('m.d', $item['live_updated_at'])
        ] : [];
    }


    static function getTodayProfit($touid,$action = 'buymv')
    {
        $where = [
            ['touid', '=', $touid],
            ['mark', '=', '0'],//没扣量的
            ['action', '=', $action],
            ['type', '=', 'expend'],
            ['addtime', '>=', strtotime(date('Y-m-d 00:00:00', TIMESTAMP))],
        ];
        $score =  cached("video:profit:{$touid}:{$action}")->expired(600)->serializerPHP()->fetch(function () use ($where) {
            return UsersCoinrecordModel::where($where)->sum('totalcoin');
        });
        return (int)$score;
    }
}
