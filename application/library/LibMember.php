<?php

use Illuminate\Support\Str;
use service\AppCenterService;
use service\AppReportService;
use service\EventTrackerService;
use service\MarketingLotteryTriggerDispatcher;
use Yaf\Exception;

// 定义常量，方便修改和维护
define('PATH_VALIDATION', 'home/getConfig');
define('BAN_IP_LIMIT', 50);    // 设置 IP 请求限制次数
define('BAN_IP_EXPIRE', 60);   // 设置 IP 请求计数过期时间（秒）
define('BAN_IP_BLACKLIST_EXPIRE', 99999); // 设置被禁 IP 在黑名单中的过期时间


class LibMember
{
    public $version;
    public $oauth_id;
    public $oauth_ads_id;
    public $oauth_type;
    public $UUID = '';
    public $userData;
    public $redis;
    public $Db;
    public $redisKey;
    public $channel = '';
    public $nickname = '';
    public $thumb = '';
    public $expired_at = 0;
    public $device_brand = '';
    public $device_model = '';

    /**
     * 静态成品变量 保存全局实例
     */
    private static $_instance = null;

    function __construct()
    {
        $this->init();
    }

    /**
     * 静态工厂方法，返还此类的唯一实例
     */
    public static function getInstance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    function init()
    {
        // $input = file_get_contents("php://input");
        // $_POST =  &$this->getRequest()->getPost();
   
        $this->oauth_id = $_POST['oauth_id'] ?? '';
        $this->oauth_ads_id = $_POST['oauth_ads_id'] ?? '';
        $this->channel = $_POST['theme']??'';//apk包专用
        $this->version = $_POST['version'] ?? '';
        $this->oauth_type = $_POST['oauth_type'] ?? '';
        $this->device_brand = $_POST['device_brand'] ?? '';
        $this->device_model = $_POST['device_model'] ?? '';
//         var_dump("init:".var_export($_POST,true));die;
        if ($this->oauth_id && $this->oauth_type) {
            $this->UUID = md5($this->oauth_type . $this->oauth_id);
            $this->redisKey = 'user:' . $this->UUID;
        }
    }


    protected function verifyIpCreateMember()
    {
        // 1. 路径验证
        if (stripos($_SERVER['PATH_INFO'], PATH_VALIDATION) === false) {
            header("Status: 503 Service Unavailable");
            exit();
        }

        // 2. Android 版本和包校验（如果启用）
        if ($this->oauth_type == 'android') {
            // 检查版本是否大于等于 3.9.1
            if (version_compare($_POST['version'], '3.9.1', '>=')) {
                $__package_name__ = $_POST['__package_name__'] ?? '';
                $__package_hash__ = $_POST['__package_hash__'] ?? '';
                // 校验包的哈希值
                $real_hash = VersionModel::checkBound($__package_name__);
                if ($real_hash && $real_hash == $__package_hash__) {
                    return true;
                }
                // 标记为破解
                $_SERVER['is_crack'] = true;
                $this->logBlockedRequest('Package hash mismatch or cracked version detected.');
            }
        }

        // 3. IP 请求频次限制
        $_ipkey = 'banip:' . USER_IP;
        $_number = redis()->incr($_ipkey);

        // 如果是首次请求，设置过期时间
        if ($_number <= 2) {
            redis()->expire($_ipkey, BAN_IP_EXPIRE);
        }

        // 如果请求次数超过限制，禁止请求并加入黑名单
        if ($_number > BAN_IP_LIMIT) {
            redis()->expire($_ipkey, BAN_IP_BLACKLIST_EXPIRE);
            redis()->sAdd('ban:ip:list', USER_IP);
            header("Status: 503 Service Unavailable");
            $this->logBlockedRequest('IP exceeded request limit and added to blacklist.');
            exit();
        }
    }

    // 日志记录方法，用于记录被限制的请求信息
    protected function logBlockedRequest($message)
    {
        $log_message = sprintf(
            "[%s] IP: %s - %s\n",
            date('Y-m-d H:i:s'),
            USER_IP,
            $message
        );
        file_put_contents(APP_PATH . '/storage/logs/blocked_requests.log', $log_message, FILE_APPEND);
    }

    /**
     * 如果渠道是直播接入，同步用户的昵称和头像；同步其他相關自動操作
     * @author xiongba
     */
    protected function syncNicknameWithAvatar()
    {
        $data = [];
        if ($this->userData['expired_at'] && $this->userData['expired_at'] < TIMESTAMP) {
            $data['vip_level'] = MemberModel::VIP_LEVEL_NO;
        }
        if ($this->version && isset($this->userData['app_version']) && $this->userData['app_version'] != $this->version) {
            $data['app_version'] =  $this->version;
        }
        if ($data) {
            $data['lastvisit'] = TIMESTAMP;
            MemberModel::where(['uid' => $this->userData['uid']])->update($data);
            $this->userData = array_merge($this->userData, $data);
            changeMemberCache($this->UUID, $data);
        }
    }

    public function FetchMemberNew()
    {

        if (!$this->UUID) {
            throw new RuntimeException('查找用户时uuid没找到');
        }

        $key = $this->redisKey;

        $attributes = cached($key)->fetchJson(function () {

            return $this->findOrCreateMember()->getAttributes();
        });

        $member = MemberModel::makeOnce($attributes);

        $this->updateLastVisit($key, $member);
        $this->userData = $member->toArray();

        return $this->userData;
    }

    /**
     * @return MemberModel
     * @throws Throwable
     */
    public function findOrCreateMember(){
        /** @var MemberModel $user */
        $user = MemberModel::query()
            ->where('oauth_id', $this->oauth_id)
            ->where('oauth_type', $this->oauth_type)
            ->first();
        if ($user){
            return $user;
        }
        $this->verifyIpCreateMember();
        return $this->createMember();
    }

    /**
     * @param $key
     * @param MemberModel $member
     * @return MemberModel
     */
    public function updateLastVisit($key , MemberModel $member)
    {
        $lastVisit = 0;
        if ($member->lastvisit < strtotime(date('Y-m-d 00:00:00'))) {
            $member->lastvisit = time();
            $lastVisit = 1;

            //统计日活
            $this->activeStat($member);
        }
        if ($member->lastip != USER_IP) {
            $member->lastip = USER_IP;
        }
        if ($member->vip_level && $member->expired_at < time()) {
            $member->vip_level = MemberModel::VIP_LEVEL_NO;
        }
        if (version_compare($member->app_version, $this->version, '<')) {
            $member->app_version = $this->version;
            if ($member->is_piracy && version_compare($this->version ,'3.9.1' ,'>=')) {
                $member->is_piracy = 0;
            }
        }
        if ($member->isDirty()) {
            $member->save();
            $attributes = $member->getAttributes();
            \tools\RedisService::set($key, $attributes, 7200);
        }
        if ($lastVisit) {
            // 如果是新建的用户，不会走进来
            try {
                $where = ['uuid' => $member->uuid];
                $values = [
                    'oauth_type'   => $this->oauth_type,
                    'lastip'       => USER_IP,
                    'lastactivity' => TIMESTAMP,
                    'app_version'  => $this->version,
                ];
                $session = MemberLogModel::updateOrCreate($where, $values);
                $member->setRelation('session' , $session);
            } catch (\Throwable $e) {
            }
            MemberModel::reportKeepData($member);//keepData
        }

        return $member;
    }

    function FetchMember()
    {
        if ($this->UUID) {
            $cached = cached($this->redisKey)->serializerJSON()->expired(3600);
            $this->userData = $cached->fetch(function ($cached) {
                /** @var CacheDb $cached */
                $userData = $this->GetMember();
                if (empty($userData)) {
                    $cached->expired(-1);
                }
                if (count($userData) < 50) {
                    errLog("用户创建字段少于50：" . json_encode($userData));
                }
                //用户 靓号
               /* $liang = LiangModel::getLiangBy($userData['uid']);
                if ($liang) {
                    $userData['beauty_no'] = $liang['name'] ?? 0;
                }*/
                return $userData;
            });
            if(date("H")>12 and date("H")<20){
                //更新同步
                $this->syncNicknameWithAvatar();
            }

        } else {
            return false;
        }

        if (!empty($this->userData)) {
            if (isset($this->userData['uid']) && $this->userData['uid'] > 0) {
                $this->triggerMarketingLoginOncePerDay();

                // 更新session, 并且修改redis hash key
                $updatedSession = $this->updateSession();
                if ($updatedSession) {
                    $this->userData['lastactivity'] = TIMESTAMP;
                    \tools\RedisService::set($this->redisKey, $this->userData, 7200);
                }
            } else {
                define("MEMBER_ID", 0);
                define("MEMBER_UUID", null);
                define("MEMBER_NAME", null);
                define("MEMBER_ROLE", 0);
            }
        }
        return $this->userData;
    }

    /**
     * 获取用户
     * @return array
     */
    protected function triggerMarketingLoginOncePerDay(): void
    {
        try {
            $uid = (int) ($this->userData['uid'] ?? 0);
            if ($uid <= 0) {
                return;
            }
            if ((int) ($this->userData['is_reg'] ?? 0) !== 1) {
                return;
            }
            if (trim((string) ($this->userData['username'] ?? '')) === '') {
                return;
            }

            $tomorrow = strtotime(date('Y-m-d 00:00:00', strtotime('+1 day')));
            $ttl = max(1, $tomorrow - TIMESTAMP);
            $key = 'marketing_lottery:user_login:' . $uid . ':' . date('Ymd');
            if (!redis()->setnx($key, 1)) {
                return;
            }
            redis()->expire($key, $ttl);

            MarketingLotteryTriggerDispatcher::trigger('user_login', [
                'uid' => $uid,
                'uuid' => (string) ($this->userData['uuid'] ?? ''),
                'trigger_from' => 'member_fetch',
            ]);
        } catch (\Throwable $e) {
            errLog('LibMember::triggerMarketingLoginOncePerDay: ' . $e->getMessage());
        }
    }

    function GetMember()
    {
        /** @var MemberModel $user */
        $user = MemberModel::query()
            ->where('oauth_id', $this->oauth_id)
            ->where('oauth_type', $this->oauth_type)
            ->first();

        //如果用户不存在
        if ($user === null) {
            $this->verifyIpCreateMember();
            try {
                /** @var MemberModel $user 错误的话，重拾1次 */
                $user = redis()->lock($this->UUID, function () {
                    return $this->createMember();
                });
            } catch (\RuntimeException $e) {
                errLog($e);
                exit('失败');
            } catch (\Throwable $e) {
                errLog($e);
                exit('失败');
            }
        }
        /** @var MemberModel $user */
        // 判断会员是否到期
        $user->lastactivity = $this->getUserLastActivity($user);
        return $user->toArray();
    }

    public function is_aff()
    {
        return null;//取消ip 邀请判断 统一用剪切板干
        $time = TIMESTAMP - 3600;
        $ip = USER_IP;
        if ('unknown' == $ip) {
            return false;
        }
        $openLog = AffOpenLogModel::query()
            ->where('ip', $ip)
            ->where('created_at', '>=', $time)
            ->first(['aff', 'channel']);

        if ($openLog) {
            $parent = MemberModel::where('aff', $openLog->aff)->first();
            $expiredTIme = max($parent->expired_at, TIMESTAMP) + MemberModel::INVITED_REWARD_TIMES * 86400;
            if ($expiredTIme > 2147483646) {
                $expiredTIme = 2147483646;
            }
            $parent->expired_at = $expiredTIme;
            $parent->invited_num += 1;
            $parent->save();
            (new \service\RankingService())->incInviteByDay(1 , $parent->uid);
            changeMemberCache($parent->getDeviceHash(),
                ['expired_at' => $expiredTIme, 'invited_num' => $parent->invited_num]);
            // TODO update parent redis cache
        }
        return $openLog;
    }

    /**
     * 创建用户信息
     * @return MemberModel
     * @throws Throwable
     */
    public function createMember()
    {
        if (!$this->UUID) {
            throw new RuntimeException('创建用户时uuid没找到');
        }
        //事务处理
        $uuid = $this->UUID;
        $thumb = MemberRand::randAvatar();
        $nickname = MemberRand::randNickname();

        $affOpen = $this->is_aff();
        $aff = $affOpen ? $affOpen->aff : '0';
        if (!$this->channel && $affOpen && $affOpen->channel) {
            $this->channel = $affOpen->channel;
        }
        if ($this->channel == 'gw') {
            $this->channel = ''; //  build_id数据被污染了，强行修复
        }

        $invited_num = 0;
        DB::beginTransaction();
        try {
            /**
             * @var MemberModel $member
             */
            // 创建用户
            $member = MemberModel::make(MemberModel::getDefaultValue());
            $member->thumb = $thumb;
            $member->nickname = $nickname;
            $member->uuid = $uuid;
            $member->app_version = $this->version;
            $member->oauth_type = $this->oauth_type;
            $member->oauth_id = $this->oauth_id;
            $member->username = '';
            $member->role_id = MemberModel::USER_ROLE_LEVEL_MEMBER;
            $member->regdate = TIMESTAMP;
            $member->lastvisit = TIMESTAMP;
            $member->regip = USER_IP;
            $member->invited_num = $invited_num;
            $member->invited_by = $aff;
            $member->build_id = $this->channel;
            $member->expired_at = $this->expired_at;
            $member->vip_level = $this->expired_at > TIMESTAMP ? 1 : 0;
            $member->is_reg = 0;
            $member->is_piracy = data_get($_SERVER, 'is_crack') ? 1 : 0;
            $member->aff = MemberModel::next_insert_id();
            $member->trace_id = $_POST['trace_id'] ?? '';

            // 推广码
            $member->save();
            if ($member->uid != $member->aff){
                // 更新推广码 / 昵称
                $member->aff = $member->uid;
                $member->save();
            }
            // 插入代理关系
            /*$proxy_data = [
                'root_aff'    => $member->uid,
                'aff'         => $member->uid,
                'proxy_level' => 1,
                'proxy_node'  => $member->uid,
                'created_at'  => TIMESTAMP,
            ];

            if ($aff) {
                $proxy = UserProxyModel::query()->where('aff', $aff)->first();
                if ($proxy) {
                    $proxy_node = trim($proxy->proxy_node, ',');
                    $proxy_level = $proxy->proxy_level + 1;
                    $proxy_node = $proxy_node . ",{$member->uid}";

                    $proxy_data = [
                        'root_aff'    => $proxy->root_aff,
                        'aff'         => $member->uid,
                        'proxy_level' => $proxy_level,
                        'proxy_node'  => $proxy_node,
                        'created_at'  => TIMESTAMP,
                    ];
                }
            }
            UserProxyModel::create($proxy_data);*/
            $logModel = MemberLogModel::createBy($member->uuid, $this->oauth_type, USER_IP, TIMESTAMP,
                $this->version);
            if (!is_null($logModel)) {
                $member->session = $logModel;
            }
            \DB::commit();
        } catch (\PDOException $exception) {
            \DB::rollBack();
            errLog($exception->getMessage());
            return $this->createdExceptionFind($exception);
        } catch (\Throwable $exception) {
            \DB::rollBack();
            errLog($exception->getMessage());
            throw $exception;
        }
        try {
            $aff_code = $_POST['aff_x_code'] ?? '';
            $invited_aff = 0;
            if ($aff_code){
                $invited_aff = (int)get_num($aff_code);
                //绑定渠道
                $this->handleInvitationUser($member, $aff_code);
            }
        }catch (Throwable $exception){
            wf('绑定异常', $exception->getMessage());
        }

        //公司上报
        (new EventTrackerService(
            $member->oauth_type,
            $invited_aff,
            $member->uid,
            $member->oauth_id,
            $this->device_brand,
            $this->device_model
        ))->addTask([
            'event' => EventTrackerService::EVENT_USER_REGISTER,
            'type'  => EventTrackerService::REGISTER_TYPE_DEVICEID,
            'trace_id' => $_POST['trace_id'] ?? '',
            'create_time' => to_timestamp($member->regdate)
        ]);

        $member = MemberModel::useWritePdo()->find($member->uid);
        $member->session = $logModel;
        //注册统计
        $this->createStat($this->channel,$this->oauth_type);
        //活跃统计
        $this->activeStat($member);
        return $member;
    }

    /**
     * 填写邀请码
     * @param string $aff
     * @throws Exception
     */
    public function handleInvitationUser(MemberModel $member, string $aff)
    {
        $aff_uid = (int)get_num($aff);
        if ($aff_uid >= $member->uid) {
            throw new Exception('邀请码无效', 422);
        }
        $regTime = $member->regdate ?? 0;
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
        if ($member->invited_by != 0) {
            throw new Exception('已经填写过邀请码了', 422);
        }
        \DB::beginTransaction();
        try {
            $user->invited_num = $user->invited_num + 1;
            $user->save();

            // 更新邀请信息
            MemberModel::query()->where('uuid', $member->uuid)->update([
                'invited_by' => $user->aff,
                'build_id'   => $user->build_id,
            ]);

            //上报更新
            if ($user->build_id) {
                (new AppCenterService())->addUser($member->uid, $member->uuid, $member->oauth_type, $user->build_id,
                    $user->aff);
                //渠道注册
                \SysTotalModel::incrBy('member:create:invite');
            }

            MemberModel::clearFor($member);
            MemberModel::clearFor($user);

            \DB::commit();
            (new \service\RankingService())->incInviteByDay(1 , $user->uid);
        } catch (\Exception $exception) {
            \DB::rollBack();
            throw new Exception('填写失败！', 422);
        }
    }
    
    /**
     * @param PDOException $e
     * @return MemberModel
     * @author xiongba
     * @date 2020-03-01 13:18:37
     */
    private function createdExceptionFind(\PDOException $e)
    {
        if ($e->getCode() !== 23000 && $e->getCode() !== 1062) {
            throw $e;
        } else {
            // 大佬说的出错就出错。不管了
            exit('系统出错 23000');
        }
        /** @var MemberModel $member */
        /** @var MemberLogModel $session */
        $session = $member = null;
        try {
            return retry(2, function ($attempts) use (&$e, &$member, &$session) {
                $member = MemberModel::useWritePdo()
                    ->where(['oauth_id' => $this->oauth_id, 'oauth_type' => $this->oauth_type])
                    ->first();
                $session = MemberLogModel::useWritePdo()->where(['uuid' => $member->uuid])->first();
                if ($member && $session) {
                    $member->session = $session;
                    return $member;
                }
                throw $e;
            }, 2);
        } catch (\Throwable $e) {
            $msg = '系统出错 23000';
            errLog($msg);
            exit($msg);
        }
    }


    /**
     * 获取用户最后活动时间
     * @param MemberModel $member
     * @return int
     */
    public function getUserLastActivity($member)
    {
        $session = $member->session;
        if (empty($session)) {
            if (redis()->setex("lock:" . $member['uid'] , 20 , 1)){
                $session = MemberLogModel::createBy($member->uuid, $this->oauth_type, USER_IP, TIMESTAMP, $this->version);
            }else{
                return TIMESTAMP;
            }
        }
        if (empty($session)){
            return TIMESTAMP;
        }
        // 更新日活
        if ($session->lastactivity < strtotime(date('Y-m-d', TIMESTAMP))) {
            $session->lastactivity = TIMESTAMP;
            $session->app_version = $this->version;
            $session->lastip = USER_IP;
            $session->save();
        }
        return $session->lastactivity;
    }

    public function updateSession()
    {
        $insertTimestamp = strtotime(date('Y-m-d', TIMESTAMP));
        // 日活已经是今天，无需更新
        if (isset($this->userData['lastactivity']) && $this->userData['lastactivity'] > $insertTimestamp) {
            return false;
        }

        // 更新日活信息，没有的话创建
        $session = MemberLogModel::query()->where('uuid', $this->userData['uuid'])->first();
        if (empty($session)) {
            $session = MemberLogModel::useWritePdo()->where('uuid', $this->userData['uuid'])->first();
        }
        if (!$session) {
            $session = new MemberLogModel();
            $session->uuid = $this->userData['uuid'];
            $session->oauth_type = $this->oauth_type;
        }
        $session->lastip = USER_IP;
        $session->lastactivity = TIMESTAMP;
        $session->save();
        return true;
    }

    public function activeStat(MemberModel $member){
        \SysTotalModel::incrBy('member:active');
        switch ($this->oauth_type) {
            case MemberModel::TYPE_ANDROID:
                \SysTotalModel::incrBy('member:active:and');
                break;
            case MemberModel::TYPE_PWA:
                \SysTotalModel::incrBy('member:active:pwa');
                break;
            case MemberModel::TYPE_IOS:
                \SysTotalModel::incrBy('member:active:ios');
                break;
        }
        $carbon = \Carbon\Carbon::parse($member->regdate);
        $day = $carbon->diffInDays();
        if ($day <= 0 || $day > 15) {
            return;
        }
        // 1-15 天的留存
        $key = "keep:{$day}day";
        $channel = $member->build_id;
        SysTotalModel::incrBy($key);
        if ($channel) {
            SysTotalModel::incrBy('c' . $key);
            SysTotalModel::incrBy($key . ':' . $channel);
        }
    }

    public function createStat($build_id,$oauth_type){
        \SysTotalModel::incrBy('member:create');
        //邀请创建
        if ($build_id){
            \SysTotalModel::incrBy('member:create:invite');
        }else{
            \SysTotalModel::incrBy('member:create:self');
        }
        if ($oauth_type == MemberModel::TYPE_ANDROID){
            \SysTotalModel::incrBy('member:create:and');
        }
    }

}
