<?php


namespace repositories;


use Carbon\Traits\Timestamp;
use DB;
use Illuminate\Database\Query\JoinClause;
use MemberModel;
use ProductModel;
use service\ActivityInviteService;
use service\AppCenterService;
use service\AppReportService;
use service\BaipiaoService;
use service\EventTrackerService;
use service\FollowedService;
use service\UserLevelService;
use tools\RedisService;
use Yaf\Exception;

trait UsersRepository
{
    /**
     * 通过手机号获取用户
     * @param string $phone
     * @return MemberModel|object|null
     */
    public function getUserByPhone(string $phone)
    {
        $user = MemberModel::query()->where('username', $phone)->first();

        return $user;
    }

    /**
     * 通过UUID获取用户
     * @param string $uuid
     * @return array
     */
    public function getUserByUUID(string $uuid)
    {
        $hash = cached('user:uuid:' . $uuid)
            ->serializerJSON()
            ->expired(7200)
            ->fetch(function () use ($uuid) {
                $member = MemberModel::where('uuid', $uuid)->first(['oauth_id', 'oauth_type']);
                if ($member) {
                    return $member->getDeviceHash();
                }
                return null;
            });
        if (empty($hash)) {
            return [];
        }
        return cached('user:' . $hash)->expired(86400)->serializerJSON()->fetch(function () use ($uuid) {
            return MemberModel::where('uuid', $uuid)->first()->toArray();
        });
    }

    /**
     * 获取用户头像  不见意使用 改方法  使用    url_avatar（）代替
     * @param $thumb
     * @return string
     */
    public function fetchUserThumb(string $thumb)
    {
        return url_avatar($thumb);
        if (empty(trim($thumb))) {
            return \Yaf\Registry::get('config')->img->default_jd_thumb;
        }
        return \Yaf\Registry::get('config')->img->img_head_url . $thumb;
    }

    /**
     * 随机获取分享链接
     * @param $aff
     * @param $channel
     * @return mixed
     */
    public function getShareUrl(string $aff = '',string $channel='')
    {
        $aff_code = '';
        if ($aff) {
            $aff_code = generate_code($aff);
            return getShareLink($aff_code, $channel);
        } else {
            $aff_code = generate_code($this->member['aff']);
            $channel = $this->member['build_id'];
        }
        return getShareLink($aff_code, $channel);
    }

    /**
     * 绑定账号
     * @param string $phone
     * @param string $password
     * @return string
     * @throws Exception
     */
    public function handleRegister(string $phone, string $password): string
    {
        $password = md5($password);
        $hasPhone = $this->getUserByPhone($phone);
        if (!is_null($hasPhone)) {
            throw new Exception('该手机号已被注册', 422);
        }
       if ($this->member['username'] != '') {
            throw new Exception('该账号已经绑定过手机了，不能重复绑定', 422);
        }
        $data = [
            'username' => $phone,
            'phone'    => $phone,
            'password' => $password,
            'is_reg'   => 1,
        ];
        MemberModel::query()->where('uuid', $this->member['uuid'])->update($data);
        if ($this->member['invited_by'] > 0 && $this->member['regdate'] > \UserInviteReceiveLogModel::START_TIME) {
            //$inviteInfo = \UserInviteStatModel::query()->where('uid', $this->member['invited_by'])->first();
            // (new ActivityInviteService($this->member['invited_by']))->incrRewardRemainder();
            //if ($inviteInfo) {
            //    \UserInviteStatModel::query()->where('uid', $this->member['invited_by'])->increment("nums");
            //} else {
            //    \UserInviteStatModel::query()->insert(['uid' => $this->member['invited_by'], "nums" => 1]);
            //}
        }
        $this->member = array_merge($this->member, $data);
        MemberModel::clearFor($this->member);
        return $this->token($this->member['uuid']);
    }

    /**
     * 切换账号
     *
     * @param MemberModel $member
     *
     * @return string
     */
    public function handleChange(MemberModel $member): string
    {
        $findMember = MemberModel::find($this->member['uid']);
        test_assert($findMember , '找不到用户');
        if ($member->uid == $findMember->uid) {
            return $this->token($findMember->uuid);
        }
        $changeUUID = $member->uuid;
        $memberTemp = [];
        $userTemp = [];
        transaction(function () use ($findMember, $member, $changeUUID, &$memberTemp, &$userTemp) {
            // 保存交换记录
            $log = [
                'uid'             => $findMember->uid,
                'oauth_type'      => $findMember->oauth_type,
                'oauth_id'        => $findMember->oauth_id,
                'old_uuid'        => $findMember->uuid,
                'new_uuid'        => $member->uuid,
                'old_invited_num' => $findMember->invited_num,
                'new_invited_num' => $member->invited_num,
                'created_at'      => TIMESTAMP,
            ];
            $ok = \UuidLogModel::create($log);
            test_assert($ok , '记录日志失败');

            // 交换用户
            $tempOauth = ['oauth_id' => 'temp_' . \TIMESTAMP . rand(1000, 9999), 'oauth_type' => 'ios'];
            $memberTemp = ['oauth_id' => $member->oauth_id, 'oauth_type' => $member->oauth_type];
            $userTemp = ['oauth_id' => $findMember->oauth_id, 'oauth_type' => $findMember->oauth_type];
            $ok = $findMember->update($tempOauth); // 联合索引
            test_assert($ok , '记录日志失败1');
            $ok = $member->update($userTemp);
            test_assert($ok , '记录日志失败2');
            $ok = $findMember->update($memberTemp);
            test_assert($ok , '记录日志失败3');
        });
        // 如果被交换的用户也交换过，uuid 不是自己的
        MemberModel::clearFor($memberTemp);
        MemberModel::clearFor($userTemp);
        MemberModel::clearFor($this->member);
        redis()->del('user:' . $changeUUID);
        redis()->del('user:' . $changeUUID.':v1');
        return $this->token($findMember->uuid);
    }

    /**
     * 填写邀请码
     * @param string $aff
     * @throws Exception
     */
    public function handleInvitationUser(string $aff,&$inviteUser)
    {
        //$msg = 'handleInvitationUser:'.date('Y-m-d').'#'.$aff.'#'.PHP_EOL;
        $aff_uid = (int)get_num($aff);
        if ($aff_uid >= $this->member['uid']) {
            throw new Exception('邀请码无效', 422);
        }
        $regTime = $this->member['regdate']??0;
        $now = time();
        $gap = 48 * 3600;
        if(($now-$regTime)>$gap){
            throw new Exception('已超过48小时,你不能被邀请~', 422);
        }
        /** @var MemberModel $user */
        $user = MemberModel::query()->where('aff', $aff_uid)->first();
        if (empty($user)) {
            throw new Exception('邀请码不正确', 422);
        }
        //过滤非法渠道
        if ($user->build_id && stripos($user->build_id, 'xl') !== false) {
            $user->build_id = '';
        }
        if ($user->build_id == 'xl') {
            $user->build_id = '';
        }

        /*if(BaipiaoService::checkJoin(['uid'=>$aff_uid])){
            $inviteUser = $user;
        }*/
        if ($this->member['invited_by'] != 0) {
            return ['code'=>422,'msg'=>'已经填写过邀请码了'];
            //throw new Exception('已经填写过邀请码了', 422);
        }
        //$msg .='user:'.var_export($user->toArray(),true);
        //$msg .='nowU:'.var_export($this->member,true);
        \DB::beginTransaction();
        try {
            /**
             * 邀请调整：
             * 1.1、邀请人获赠免费看天数，限制最多可获得30天
             * 1.2、被邀请人填写邀请人的邀请码，且账号已注册，才赠送邀请人免费看天数
             * 登录限制：
             * 2.1、每个账号每天只能在同一IP登录。（限制在北京时间0点重置）
             * 2.2、每个IP每天只能登录一个账号。（限制在北京时间0点重置*/
//            $reward = 86400;//\MemberModel::INVITED_REWARD_TIMES;
//            $expired = max($user->expired_at , TIMESTAMP) + $reward;
//            $expired_date = date("Ymd",$expired);
//            $max = date("Ymd",strtotime("+30 days"));
//            // 更新上级信息
//            if($expired_date-$max<=30 && $this->member['is_reg']){
//                $user->expired_at = $expired;
//            }
            if($this->member['is_reg']){
                //邀请赠送的VIP产品ID
                $invite_send_product_id = setting('invite_send_product_id', 0);
                if ($invite_send_product_id){
                    /** @var ProductModel $product */
                    $product = ProductModel::query()->where('id', $invite_send_product_id)->first();
                    // 更新上级信息
                    $reward = MemberModel::INVITED_REWARD_TIMES;
                    $expired = max($user->expired_at , TIMESTAMP) + $reward * 86400;
                    if (!empty($product) && $expired < strtotime("+30 days")){
                        $user->expired_at = $expired;
                        $user->vip_level = max($user->vip_level, $product->vip_level);
                        $product->valid_date = $reward;
                        //vip 商品卡片
                        \ProductUserModel::buyVIPProduct($user, $product);
                    }
                }
            }

            $user->invited_num = $user->invited_num + 1;
            $user->save();

            // 更新邀请信息
            MemberModel::query()->where('uuid', $this->member['uuid'])->update([
                'invited_by' => $user->aff,
                'build_id'   => $user->build_id,
            ]);

            //上报更新
            if ($user->build_id) {
                (new AppCenterService())->addUser($this->member['uid'], $this->member['uuid'], $this->member['oauth_type'], $user->build_id,
                    $user->aff);
                //渠道注册
                \SysTotalModel::incrBy('member:create:invite');

                //公司上报
                (new EventTrackerService(
                    $this->member['oauth_type'],
                    $user->uid,
                    $this->member['uid'],
                    $this->member['oauth_id'],
                    $_POST['device_brand'] ?? '',
                    $_POST['device_model'] ?? ''
                ))->addTask([
                    'event' => EventTrackerService::EVENT_USER_REGISTER,
                    'type'  => EventTrackerService::REGISTER_TYPE_DEVICEID,
                    'trace_id' => $_POST['trace_id'] ?? '',
                    'create_time' => to_timestamp($this->member['regdate'])
                ]);
            }
            //数据中心 邀请上报
            (new AppReportService())->updateUser([
                'uid'        => $this->member['uid'],
                'invited_by' => $user->aff,
                'channel'    => $user->build_id ? $user->build_id : '',
            ]);

            MemberModel::clearFor($this->member);
            MemberModel::clearFor($user);
            // 更新代理表

            \DB::commit();
            $this->member['invited_by'] = $user->aff;
            $this->member['build_id'] = $user->build_id;
            (new \service\RankingService())->incInviteByDay(1 , $user->uid);
        } catch (\Exception $exception) {
            \DB::rollBack();
            errLog('invite:'.$exception->getMessage());
            throw new Exception('填写失败！', 422);
        }
        //errLog($msg);
        return ['code'=>200,'msg'=>'yes'];

    }


    /**
     * 用户关注列表
     * @param string $uid
     * @param $member
     * @return array|bool|mixed|string
     */
    public function getUserFollowedList(string $uid, $member)
    {
        MemberModel::setWatchUser($member);
        $key = \UserAttentionModel::REDIS_USER_FOLLOWED_ITEM . $uid;
        return cached($key)
            ->expired(7200)
            ->hash($this->page)
            ->serializerPHP()
            ->fetch(function () use ($uid, $member) {
                $members = \UserAttentionModel::query()
                    ->where('uid', $uid)
                    ->with('followed:uid,aff,nickname,thumb,person_signnatrue,vip_level,expired_at')
                    ->offset($this->offset)
                    ->limit($this->limit)
                    ->get()
                    ->pluck('followed');
                $results = [];
                foreach ($members as $key => $item) {
                    if ($item === null){
                        $item = MemberModel::virtualByForDelele();
                    }
                    $results[$key] = $item;
                }

                return $results;
            });
    }




    /**
     * @param int $type 1 当天登录奖励
     */
    public function isGetLoginDimonds($type = 1)
    {
        $result = \AwardModel::query()
            ->where('uid', $this->member['uid'])
            ->where('date', date('Y-m-d'))
            ->where('type', $type)
            ->first();
        return $result ? true : false;
    }

    public function addDimonds($type = 1, $dimonds = 0)
    {
        $awardKey = "user_awards:" . $this->member['uid'];
        if (\tools\RedisService::get($awardKey)) {
            return false;
        }

        \DB::beginTransaction();
        try {
            $insert_dimonds = [
                'uid'         => $this->member['uid'],
                'type'        => $type,
                'date'        => date('Y-m-d'),
                'description' => "登录奖励:{$dimonds}金币"
            ];

            \AwardModel::query()->insert($insert_dimonds);

            $add_log = [
                "type"      => 'income',
                "action"    => 'loginAward',
                "uid"       => $this->member['uid'],
                "touid"     => $this->member['uid'],
                "giftid"    => 0,
                "giftcount" => 0,
                "totalcoin" => $dimonds,
                "showid"    => 0,
                "addtime"   => time()
            ];
            $log = \UsersCoinrecordModel::query()->insert($add_log);
            if (empty($log)) {
                throw new \Exception('添加日志失败');
            }
            $updateValue = ['coins' => $dimonds, 'coins_total' => $dimonds,];
            $d = MemberModel::incrPk($this->member['uid'], $updateValue);
            if (empty($d)) {
                throw new \Exception('更新用户信息失败');
            }
            \DB::commit();
            $todayLastSecond = strtotime(date('Y-m-d 23:59:59'));
            RedisService::set($awardKey, 1, $todayLastSecond - TIMESTAMP);
            changeMemberCache(MemberModel::hashByAry($this->member), $updateValue, 'add');
            return true;
        } catch (\Exception $exception) {
            \DB::rollBack();
            return false;
        }
    }

    public function clearUserInfo($uuid = '')
    {
        if (empty($uuid) || $uuid == $this->member['uuid']) {
            MemberModel::clearFor($this->member);
        } else {
            $member = MemberModel::where('uuid', $uuid)->first();
            MemberModel::clearFor($member);
        }
    }

    /* 判断是否关注(新) */
    function isAttentionNew($uid, $toUid)
    {
        static $array = null;
        if ($array === null) {
            $tmp = redis()->sMembers(\UserAttentionModel::REDIS_USER_FOLLOWED_LIST . $uid);
            $array = array_flip($tmp);
        }
        //不要修改类型，否则app可能会崩溃
        return isset($array[$toUid]) ? 1 : 0;
    }

    /**
     * 得到关注人是否有开播
     * @param $uid
     * @return bool
     */
    function getFollowLive($uid)
    {
        return false;
    }
}