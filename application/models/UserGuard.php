<?php

use tools\RedisService;

/**
 * 守护模型
 * class GuardUsersModel
 *
 * @property int $id
 * @property int $uid 用户ID
 * @property int $liveuid 主播ID
 * @property int $type 守护类型,1普通守护，2尊贵守护
 * @property int $endtime 到期时间
 * @property int $addtime 添加时间
 *
 * @author xiongba
 * @date 2020-02-28 14:49:30
 *
 * @mixin \Eloquent
 */
class UserGuardModel extends EloquentModel
{
    use  \repositories\LiveRepository,\repositories\UsersRepository;
    protected $table = 'guard_users';

    const  USER_GUARD = 'user_guards_';

    protected $guarded = [];

    public static function getGuardNums($live_uid)
    {
        return self::where('liveuid', $live_uid)->where('endtime', '>=', time())->count();
    }

    /**
     * 获取用户守护信息
     * @param $uid
     * @param $liveUid
     * @return array
     * @author xiongba
     * @date 2020-02-28 13:40:26
     */
    public static function getUserGuard($uid, $liveUid)
    {
        $rs =[
            'type' => '0',
            'endtime' => '0',
        ];
        $key = 'user_guard_' . $uid . '_' . $liveUid;
        $guardInfo = cached($key)
            ->expired(7200)
            ->serializerJSON()
            ->fetch(function () use ($uid, $liveUid) {
                $guardInfo = UserGuardModel::select(['type', 'endtime'])
                    ->where('uid', $uid)
                    ->where('liveuid', $liveUid)
                    ->first();
                return empty($guardInfo) ? null :$guardInfo->toArray();
            });
        if (empty($guardInfo)) {
            return $rs;
        }
        $nowTime = time();
        if ($guardInfo['endtime'] > $nowTime) {
            $rs = array(
                'type' => $guardInfo['type'],
                'endtime' => date("Y.m.d", $guardInfo['endtime']),
            );
        }
        return $rs;
    }


    /* 守护用户列表 */
    public function getGuardList($data, $page = 1, $limit = 20)
    {
        $liveUid = $data['live_uid'];
        $liveUUid = $data['live_uuid'];
        $list = \tools\RedisService::hGet(self::USER_GUARD."{$liveUid}","{$page}_{$limit}");

        if (!$list) {
            $list = self::query()
                ->select("members.uuid","members.uid", "members.nickname", "members.level", "members.sexType", "members.thumb",  "members.consumption", "guard_users.type as guard_type")
                ->rightJoin('members', 'guard_users.uid', '=', 'members.uid')
                ->where("guard_users.endtime", '>=', time())
                ->where("guard_users.liveuid", '=', $liveUid)
                ->orderBy('type', 'desc')
                ->offset(($page - 1) * $limit)
                ->limit($limit)
                ->get()
                ->toArray();
            \tools\RedisService::hSet(self::USER_GUARD."{$liveUid}","{$page}_{$limit}", serialize($list));
            RedisService::expire(self::USER_GUARD."{$liveUid}",3600);
        } else {
            $list = unserialize($list);
        }

        $key = singleton(StatisticsModel::class)->realWeek . $liveUUid;
        foreach ($list as $k => $v) {
            $list[$k]['thumb'] = $this->fetchUserThumb($v['thumb']);
            $list[$k]['level'] =  getLevel($list[$k]['consumption']??0);
            $value = \tools\RedisService::redis()->zScore($key,$v['uuid']);
            $list[$k]['contribute'] = $value ? $value : 0;
        }
        $last_names = array_column($list,'contribute');
        array_multisort($last_names,SORT_DESC,$list);
        return $list;
    }


    /**
     * 购买守护
     * @param $data
     * @return array
     */
    public function buyGuard($data)
    {
        // 返回格式
        $rs = [
            'code' => 0, 'msg' => '购买成功', 'info' => []
        ];

        $uid = $data['uid'];
        $liveUid = $data['live_uid'];
        $guardId = $data['guardid'];

        $guard = singleton(GuardModel::class);

        $guardInfo = $guard->where('id', $guardId)->first();

        if (!$guardInfo) {
            $rs['code'] = 1001;
            $rs['msg'] = '守护信息不存在';
            return $rs;
        }

        $addTime = time();
        $isExist = $this
            ->where('liveuid', $liveUid)
            ->where('uid', $uid)
            ->first();
        if ($isExist) {
            $isExist = $isExist->toArray();
        }
        if ($isExist && $isExist['endtime'] > $addTime && $isExist['type'] > $guardInfo['type']) {
            $rs['code'] = 1004;
            $rs['msg'] = '已经是尊贵守护了，不能购买普通守护';
            return $rs;
        }

        $type = 'expend';
        $action = 'buyguard';
        $giftId = $guardInfo['id'];
        $total = $guardInfo['coin'];

        /* 更新用户余额 消费 */

        $users = singleton(MemberModel::class);
        $itOK = MemberModel::incrMustGTPk($uid , ['coins' => -$total, 'consumption' => $total]);
        if (!$itOK) {
            $rs['code'] = 1002;
            $rs['msg'] = '余额不足';
            return $rs;
        }

        $users->where('uid', $liveUid)
            ->update([
                'votes' => DB::raw("votes + {$total}"),
                'votes_total' => DB::raw("votes_total + {$total}"),
            ]);

        $insert_votes = [
            'type' => 'income',
            'action' => $action,
            'uid' => $liveUid,
            'votes' => $total,
            'addtime' => time(),
        ];
        $users_voterecord = singleton(UserVoterecordModel::class);
        $users_voterecord->create($insert_votes);

        $showid = $liveUid;

        $insert = ["type" => $type, "action" => $action, "uid" => $uid, "touid" => $liveUid, "giftid" => $giftId, "giftcount" => $total, "totalcoin" => $total, "showid" => $showid, "addtime" => $addTime];
        $users_coinrecord = singleton(UsersCoinrecordModel::class);
        $users_coinrecord->create($insert);

        $endtime = $addTime + $guardInfo['length_time'];

        if ($isExist) {
            if ($isExist['type'] == $guardInfo['type'] && $isExist['endtime'] > $addTime) {
                /* 同类型未到期 只更新到期时间 */
                $this->where('id', $isExist['id'])->update([
                    'endtime' => DB::raw("endtime + {$guardInfo['length_time']}")
                ]);
                $rs['msg'] = '续费成功';
            } else {
                $data = array(
                    'type' => $guardInfo['type'],
                    'endtime' => $endtime,
                    'addtime' => $addTime,
                );

                $this->where('id', $isExist['id'])->update($data);
            }
        } else {
            $data = array(
                'uid' => $uid,
                'liveuid' => $liveUid,
                'type' => $guardInfo['type'],
                'endtime' => $endtime,
                'addtime' => $addTime,
            );

            $this->create($data);

        }
        $userinfoNew = $users->select('uuid','consumption', 'coins' , 'oauth_type' , 'oauth_id')->where('uid', $uid)->first()->toArray();

        $level = getLevel($userinfoNew['consumption']);

        $guard_one = $this
            ->select('type', 'endtime')
            ->where('uid', $uid)
            ->where('liveuid', $liveUid)
            ->first()->toArray();
        $key = 'getUserGuard_' . $uid . '_' . $liveUid;

        setCaches($key, $guard_one);

        $liveUidInfo = MemberModel::find($liveUid);

        $guard_nums = $this->getGuardNums($liveUid);

        $info = array(
            'coins' => $userinfoNew['coins'],
            'votes_total' => $liveUidInfo['votes_total'],
            'guard_nums' => $guard_nums,
            'level' => (string)$level,
            'guard_type' => $guard_one['type'],
            'endtime' => date("Y.m.d", $guard_one['endtime']),
        );

        $rs['info'] = $info;

        /* 更新缓存 */
        changeMemberCache(MemberModel::hashByAry($userinfoNew),[
            'coins'=>$userinfoNew['coins'],
            'consumption'=>$userinfoNew['consumption'],
        ]);
        changeMemberCache($liveUidInfo->getDeviceHash(),[
            'votes'=>$liveUidInfo['votes'],
            'votes_total'=>$liveUidInfo['votes_total'],
        ]);
        // 删除列表缓存
        \tools\RedisService::redis()->del(self::USER_GUARD."{$liveUid}");
        \tools\RedisService::redis()->del( 'user_guard_' . $uid . '_' . $liveUid);

        return $rs;

    }


    /* 用户基本信息 */
    function getUserInfo($uid, $type = 0, $crypt = false)
    {
        $info = getCaches("userinfo1_" . $uid);
        if (!$info) {
            $query = singleton(MemberModel::class);
            $field = [
                'uid', 'nickname', 'thumb as avatar',
                // 'avatar_thumb', 'sexType',
                'person_signnatrue', 'coins', 'consumption', 'votes_total',
                //'province',
                //   'city',
                'birthday',
                //'user_status', 'issuper', 'openid'
            ];
            // $field = ['*'];
            $info = $query->select($field)->where('uid', $uid)->first();
            if ($info) {
                $info = $info->toArray();
                // $info['uid'] = $info['id'];
                $info['level'] = (string)getLevel($info['consumption']);
                $info['level_anchor'] = (string)getLevelAnchor($info['votes_total']);

                $info['avatar'] = url_avatar($info['avatar']);
                $info['avatar_thumb'] = url_avatar($info['avatar']);
            } else if ($type == 1) {
                $info = $info->toArray();
                return $info;

            } else {
                //  $info['id'] = $uid;
                $info['uid'] = $uid;
                $info['nickname'] = '匿名用户';
                $info['avatar'] = url_avatar('');
                $info['avatar_thumb'] = $info['avatar'];
                $info['coin'] = '0';
                $info['sex'] = '0';
                $info['signature'] = '';
                $info['consumption'] = '0';
                $info['votes_total'] = '0';
                $info['province'] = '';
                $info['city'] = '';
                $info['birthday'] = '';
                $info['issuper'] = '0';
                $info['level'] = '1';
                $info['level_anchor'] = '1';
                $info['openid'] = '';
            }
            if ($info) {
                setCaches("userinfo_" . $uid, $info);
            }

        }
        if ($info) {
            $info['uid'] = $info['uid'] ?? $uid;
            $info['free_look'] = $this->getUserShare($uid);
            $info['isVV'] = $this->getUserVip($uid)['type'];
            $info['beauty_no'] = $this->getUserLiang($uid)['name'];
        }
        return $info;
    }


    function getUserShare($uid)
    {
        $user_share_time = \tools\RedisService::hGet('share_time', $uid);

        if (empty($user_share_time)) {
            return 0;
        } else {
            return $user_share_time;
        }
    }
}