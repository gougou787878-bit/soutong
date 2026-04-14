<?php

use helper\OperateHelper;
use Carbon\Carbon;


/**
 * class MemberModel
 * @property int $uid
 * @property string $oauth_type 设备'ios','android'
 * @property string $oauth_id
 * @property string $uuid
 * @property string $username
 * @property string $password
 * @property int $is_reg 是否注册， 0为游客  1为注册用户
 * @property int $role_id
 * @property string $regip
 * @property int $regdate
 * @property string $lastip
 * @property int $lastvisit
 * @property int $expired_at 会员到期时间
 * @property int $aff 邀请码md5( md5(uuid) )
 * @property int $invited_by 被谁 aff 邀请
 * @property int $invited_num 已邀请安装个数
 * @property string $app_version app版本号
 * @property string $nickname 用户昵称
 * @property string $thumb 用户头像
 * @property int $coins 用户金币余额
 * @property int $coins_total 累计金币
 * @property int $score 用户视频收益
 * @property int $score_total 累计视频收益
 * @property int $votes 直播收益余额
 * @property int $votes_total 直播收益总额
 * @property int $tui_coins
 * @property int $total_tui_coins
 * @property int $post_coins 社区收益
 * @property int $total_post_coins 社区总收益
 * @property int $fans_count 用户粉丝数
 * @property int $followed_count 关注数
 * @property int $videos_count 作品数
 * @property int $fabulous_count 获赞数
 * @property int $likes_count 喜欢数
 * @property int $live_count 直播次数
 * @property int $sexType 0未设置1男2女
 * @property int $vip_level 0 非会员/过期 1月卡 2季卡 3 年卡
 * @property string $person_signnatrue 个人签名
 * @property string $build_id build_id 超级签名标识
 * @property int $auth_status 0 未认证 1 认证通过
 * @property string $birthday 生日
 * @property int $live_supper 直播超管  0 1
 * @property int $is_live_super 直播角色 30 普通，50 房间管理员 60 超管
 * @property string $phone
 * @property MemberLogModel $session
 * @property MvModel[] $mvList
 * @property int $is_piracy
 * @property string $extra 操作备注
 * @property int $short_videos_count 短视频作品数
 * @property string $trace_id trace_id
 *
 *
 * @property int $is_vip 是否是vip
 * @property string $avatar_url
 * @property string $expired_str
 * @property int $is_attention
 * @property int $doubleFollowed
 * @property boolean $isVV
 * @property boolean $vvLevel
 *
 * @property MemberMakerModel $maker
 * @property MemberTalkModel $talk
 * @property int $exp
 *
 * @author xiongba
 * @date 2020-02-26 12:07:25
 *
 * @mixin \Eloquent
 */
class MemberModel extends EloquentModel
{
    // const
    const USER_ROLE_LEVEL_MEMBER = 8; // 普通用户
    const USER_ROLE_LEVEL_BANED = 9; // 禁言用户
    const USER_ROLE_LEVEL_ADS = 2; // 广告推广用户
    const USER_ROLE_BLACK = 4;//黑名单

    const REDIS_USER_LIKING_LIST = 'user_like_list:'; // 用户喜欢列表

    const REDIS_USER_UID = 'user_uid:'; // 用户缓存（根据uid查找）

    const REDIS_USER_UID_OTHER = 'other_user_uid:'; // 他人用户缓存

    const USER_DEFAULT_NICKNAME_PREFIX = '游客账号_';

    const INVITED_REWARD_TIMES = 3; // 邀请奖励天数
    const USER_WATCH_COUNT_DEFAULT = 10; // 默认用户可观看次数

    const RECOMMEND_USER_LIST = 'recommend.list';
    //看过的所有视屏
    const REDIS_USER_WATCH_LIST_ALL = 'user_watch_list_all:%d';

    const POSTS_PAID = "member_posts_paied:member_aff:%s"; //用户购买的帖子
    const USER_REIDS_PREFIX = 'user:info:';//用户信息


    const VIP_LEVEL_NO = 0,
        VIP_LEVEL_MOON = 1,
        VIP_LEVEL_JIKA = 2,
        VIP_LEVEL_YEAR = 3,
        VIP_LEVEL_LONG = 4,
        VIP_LEVEL_BN = 5,
        VIP_LEVEL_AW_MON = 6,
        VIP_LEVEL_AW_YEAR = 7,
        VIP_LEVEL_AW_LONG = 8,
        VIP_LEVEL_SUPREME = 9;
    const USER_VIP_TYPE = [
        self::VIP_LEVEL_NO   => '非VIP',
        self::VIP_LEVEL_MOON => '月卡',
        self::VIP_LEVEL_JIKA => '季卡',
        self::VIP_LEVEL_YEAR => '年卡',
        self::VIP_LEVEL_LONG => '永久卡',
        self::VIP_LEVEL_BN   => '半年卡',
        self::VIP_LEVEL_AW_MON   => '暗网月卡',
        self::VIP_LEVEL_AW_YEAR   => '暗网年卡',
        self::VIP_LEVEL_AW_LONG   => '暗网永久卡',
        self::VIP_LEVEL_SUPREME   => '至尊永久卡',
    ];

    const AW_VIP_TYPE = [
        self::VIP_LEVEL_AW_MON,
        self::VIP_LEVEL_AW_YEAR,
        self::VIP_LEVEL_AW_LONG,
        self::VIP_LEVEL_SUPREME
    ];

    const LIVE_SUPER_GUEST = 0;
    const LIVE_SUPER_ORDINARY = 30;
    const LIVE_SUPER_ADMIN = 50;
    const LIVE_SUPER_SUPER = 60;
    const LIVE_SUPER = [
        self::LIVE_SUPER_GUEST    => '游客',
        self::LIVE_SUPER_ORDINARY => '普通',
        self::LIVE_SUPER_ADMIN    => '房间管理',
        self::LIVE_SUPER_SUPER    => '超管',
    ];

    //role_id  设置 8 正常 9 禁言 10 封号 20 渠道（主）
    const ROLE_NORMAL = 8;
    const ROLE_FORBIDDEN = 9;
    const ROLE_BAN = 10;
    const ROLE_CHANNEL = 20;
    const ROLE_BROKER = 30;
    const ROLE = [
        self::ROLE_NORMAL    => '普通',
        self::ROLE_FORBIDDEN => '禁言',
        self::ROLE_BAN       => '封号',
        self::ROLE_CHANNEL   => '渠道',
        self::ROLE_BROKER    => '商家',
    ];

    const TYPE_ANDROID = 'android';
    const TYPE_IOS = 'ios';
    const TYPE_PWA = 'pwa';
    const TYPE_PC = 'pc';
    const TYPE = [
        self::TYPE_ANDROID => '安卓',
        self::TYPE_IOS     => '苹果',
        self::TYPE_PWA     => 'pwa',
        self::TYPE_PC      => 'pc',
    ];

    // 社区发帖是否需要审核
    const NEED_TRIAL = 0;
    const EXEMPT_TRIAL = 1;

    const CHANGE_COLUMN = [
        'uuid',
        'username',
        'password',
        'role_id',
        'role_type',
        'gender',
    ];

    // 数据表
    const AUTH_STATUS_NO = 0;
    const AUTH_STATUS_YES = 1;
    const AUTH_STATUS = [
        self::AUTH_STATUS_NO  => '未认证',
        self::AUTH_STATUS_YES => '已认证',
    ];

    const SEX_NULL = 0;
    const SEX_MAN = 1;
    const SEX_WOMAN = 2;
    const SEX_TYPE_TIPS = [
        self::SEX_NULL => '未设置',
        self::SEX_MAN => '男',
        self::SEX_WOMAN => '女',
    ];

    const IS_REG_NO = 0;
    const IS_REG_YES = 1;
    const IS_REG_TOPS = [
        self::IS_REG_NO => '否',
        self::IS_REG_YES  => '是',
    ];

    protected $table = 'members';

    protected $primaryKey = 'uid';

    protected $hidden = ['session', 'password'];
    // 可填充字段
    protected $fillable = [
        'oauth_type',
        'oauth_id',
        'uuid',
        'username',
        'password',
        'is_reg',
        'role_id',
        'regip',
        'regdate',
        'lastip',
        'lastvisit',
        'expired_at',
        'aff',
        'invited_by',
        'invited_num',
        'app_version',
        'nickname',
        'thumb',
        'coins',
        'coins_total',
        'score',
        'score_total',
        'votes',
        'votes_total',
        'tui_coins',
        'total_tui_coins',
        'fans_count',
        'followed_count',
        'videos_count',
        'fabulous_count',
        'likes_count',
        'live_count',
        'sexType',
        'vip_level',
        'person_signnatrue',
        'build_id',
        'auth_status',
        'exp',
        'birthday',
        'live_supper',
        'is_live_super',
        'phone',
        'is_piracy',
        'post_coins',
        'total_post_coins',
        'post_auth',
        'girl_auth',
        'extra',
        'short_videos_count',
        'trace_id'

    ];


    protected $appends = [
        'avatar_url',
        'expired_str',
        'is_vip',
        'is_attention',
//        'doubleFollowed',
//        'isVV',
        'vvLevel',
        'vip_icon',
    ];

    public static function virtualByForDelele()
    {
        $member = self::make();
        $member->nickname = "用户已注销";
        $member->is_null = true;
        return $member;
    }


    public function getLevelAnchorAttribute()
    {
        return 0; //return getLevel($this->attributes['votes_total']);
    }

    public function getLevelAttribute()
    {
        return 0;  //return getLevel($this->attributes['consumption']);
    }

    public function getLiveLogAttribute()
    {
        return [];  //UserLiveLogModel::getTopLog($uid);
    }

    public function getIsLiveAttribute()
    {
        return 0; // LiveModel::instance()->getRoomInfo($this->post['to_uid']) ? 1 : 0;
    }

    public function getAvatarUrlAttribute()
    {
        return url_avatar($this->attributes['thumb'] ?? '');
    }
    public function getAffCodeAttribute()
    {
        return generate_code($this->attributes['aff'] ?? 0);
    }

    public function getIsVipAttribute()
    {
        if (!isset($this->attributes['expired_at']) || $this->attributes['expired_at'] < TIMESTAMP){
            return 0;
        }
        return 1;
    }

    public function getIsVVAttribute()
    {
        if (!isset($this->attributes['expired_at'])){
            return false;
        }
        return $this->attributes['expired_at'] > time();
    }

    public function getVvLevelAttribute()
    {
        if (!isset($this->attributes['vip_level'])){
            return 0;
        }
        return $this->attributes['vip_level'];
    }

    public function getExpiredStrAttribute()
    {
        $expired_at = $this->attributes['expired_at'] ?? 0;
        if ($expired_at && is_numeric($expired_at)) {
            return date('Y-m-d H:i:s', $expired_at);
        }
        return '';
    }

    public function getVipIconAttribute()
    {
        $vip_level= $this->getAttributeValue('vip_level');
        $expired_at = $this->getAttributeValue('expired_at');
        $aff = (int)$this->getAttributeValue('aff');
        $uid = (int)$this->getAttributeValue('uid');
        $aff = $aff > 0 ? $aff : $uid;
        if($vip_level > self::VIP_LEVEL_NO){
            if ($expired_at > time()){
                /** @var ProductUserModel $product */
                $product = ProductUserModel::getUserProduct($aff);
                if (isset($product->product) && $product->product->vip_icon){
                    return url_cover($product->product->vip_icon);
                }
            }
        }
        return '';
    }

    public function getIsAttentionAttribute()
    {
        $watchUser = self::$watchUser;
        if (!isset($this->attributes['aff']) || empty($watchUser)) {
            return 0;
        }
        if (!isset($watchUser->aff)){
            return 0;
        }
        static $ids = null;
        if (null === $ids) {
            $key = UserAttentionModel::REDIS_USER_FOLLOWED_LIST . $watchUser->aff;
            $ids = redis()->sMembers($key);
        }
        return in_array($this->attributes['aff'], $ids) ? 1 : 0;
    }

    public function getDoubleFollowedAttribute()
    {
        $watchUser = self::$watchUser;
        if (!isset($this->attributes['aff']) || empty($watchUser)) {
            return 0;
        }
        if (!isset($watchUser->aff)){
            return 0;
        }
        if (!$this->getIsAttentionAttribute()){
            return 0;
        }
        static $ids = null;
        if (null === $ids) {
            $key = UserAttentionModel::REDIS_USER_FANS_LIST . $watchUser->aff;
            $ids = redis()->sMembers($key);
        }
        return in_array($this->attributes['aff'], $ids) ? 1 : 0;
    }

    /**
     * @param $val
     * @return static
     * @author xiongba
     * @date 2020-02-27 20:35:26
     */
    public static function firstByName($val)
    {
        return self::where('username', $val)->first();
    }


    public static function findByUuid($uuid)
    {
        return self::where('uuid', $uuid)->first();
    }

    public static function findByUsername($username)
    {
        return self::where('username', $username)->first();
    }

    /**
     * 使用uid查找用户的uuid
     * @param $uid
     * @return string|null
     * @author xiongba
     * @date 2020-04-28 17:21:19
     */
    public static function getUuidByUid($uid)
    {
        return cached('member:live:uuid:')
            ->suffix($uid)
            ->expired(1800)
            ->fetch(function () use ($uid) {
                $member = MemberModel::find($uid);
                if (empty($member)) {
                    return false;
                }
                return $member->uuid;
            });
    }

    public function mvList()
    {
        return self::hasMany(MvModel::class, 'uid', 'uid');
    }



    // 追加一列 user_thumb 用户头像全路径
    // protected $appends = ['user_thumb'];

    /**
     * 用户对应的log members_log
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function session()
    {
        return $this->hasOne(MemberLogModel::class, 'uuid', 'uuid');
    }

    public static function getOfficial()
    {
        static $member = null;
        if ($member === null) {
            $member = \MemberModel::where('uid', setting('official.uid', 4888000))->first();
        }
        return $member;
    }


    public static function getDefaultValue()
    {
        return [
            "validate"          => 0,
            "share"             => 0,
            "fans_count"        => 0,
            "followed_count"    => 0,
            "videos_count"      => 0,
            "live_count"        => 0,
            "fabulous_count"    => 0,
            "likes_count"       => 0,
            "sexType"           => 0,
            "auth_status"       => 0,
            "exp"               => 0,
            "live_supper"       => 0,
            "consumption"       => 0,
            "level_anchor"      => 1,
            "coins_total"       => 0,
            "new_topic_reply"   => 0,
            "coins"             => (int)setting('register.coins', 0),
            "vip_level"         => (int)setting('register.vip_level', 0),
            "level"             => (int)setting('register.level', 1),
            "is_recommend"      => (int)setting('register.is_recommend', 0),
            "login_count"       => (int)setting('register.login_count', 1),
            "score"             => (int)setting('register.score', 0),
            "gender"            => (int)setting('register.gender', 1),
            "thumb"             => setting('register.thumb', "91_ads_20200111FeTEqY.png"),
            "role_type"         => setting('register.role_type', 'normal'),
            "person_signnatrue" => setting('register.person_signature', ""),
            "build_id"          => setting('register.build_id', ''),
            "chat_uid"          => "",
            "birthday"          => "",
            "votes"             => "0.00",
            "votes_total"       => "0.00",
            "is_live_super"     => self::LIVE_SUPER_ORDINARY,
            "phone"             => null,
            "lastip"            => USER_IP,
        ];
    }
    

    /**
     * 获取登录token
     * @return string
     */
    public function token(): string
    {
        $signKey = config('token.login', '');
        return md5($signKey . $this->uuid . $signKey);
    }

    public static function dynamicToken($uid = null)
    {
        if (empty($uid)) {
            return null;
        }
        $token = substr(md5(microtime(true) . '-' . $uid), 16);
        redis()->set('g-tok:' . $token, $uid, 7200);
        return $token;
    }

    /**
     * 获取设备hash
     * @return string
     * @author xiongba
     * @date 2020-03-14 17:33:31
     */
    public function getDeviceHash()
    {
        return self::hashByAry($this);
    }


    /**
     * @param $member
     * @return string
     * @author xiongba
     * @date 2020-03-15 15:22:35
     */
    public static function hashByAry($member)
    {
        return md5(($member['oauth_type'] ?? '') . ($member['oauth_id'] ?? ''));
    }

    /**
     * @throws RedisException
     */
    public function clearCached(){
        self::clearFor($this);
    }


    /**
     * @param $member
     * @throws RedisException
     * @author xiongba
     * @date 2020-03-15 15:22:39
     */
    public static function clearFor($member)
    {
        if (empty($member)) {
            return;
        }
        if (is_object($member)) {
            $member = $member->toArray();
        }
        $hash = self::hashByAry($member);
        $uuid = $member['uuid'] ?? '';
        redis()->del('user:' . $hash);
        //redis()->del('user:' . $hash .':v1');
        if ($uuid != $hash) {
            redis()->del('user:' . $uuid);
            //redis()->del('user:' . $uuid.':v1');
        }
        \MemberModel::unbindDevice($member);
    }

    /**
     * 绑定uuid和设备关系
     * @param $member
     * @author xiongba
     * @date 2020-03-16 10:28:29
     */
    public static function bindUuidWithDevice($member)
    {
        if (empty($member)) {
            return;
        }
        if (isset($member['uuid']) && !empty($member['uuid'])) {
            redis()->set('user:uuid:' . $member['uuid'], MemberModel::hashByAry($member), 3600);
        }
    }

    /**
     * 使用指定的uuid获取设备
     * @param $uuid
     * @return bool|string
     * @author xiongba
     * @date 2020-03-16 10:28:43
     */
    public static function getDeviceByUuid($uuid)
    {
        return redis()->get('user:uuid:' . $uuid);
    }

    /**
     * 解除uuid和设备的关系
     * @param $member
     * @throws RedisException
     * @author xiongba
     * @date 2020-03-16 10:29:04
     */
    public static function unbindDevice($member)
    {
        if (empty($member)) {
            return;
        }
        redis()->del('user:uuid:' . ($member['uuid'] ?? ''));
    }


    public static function queryVideoCount($videoCount)
    {
        return self::where('videos_count', '>=', $videoCount);
    }


    public function formatDiamond()
    {
        $coins = $this->coins;
        if ($coins >= 10000) {
            $str = sprintf('%.2fw钻', $coins / 10000);
        } elseif ($coins >= 1000) {
            $str = sprintf('%.2fk钻', $coins / 1000);
        } else {
            $str = $coins . '钻';
        }
        if (strpos($str, '.00') !== false) {
            $str = str_replace('.00', '', $str);
        } elseif (strpos($str, '.') !== false && mb_strpos($str, '0钻') !== false) {
            $str = str_replace('0钻', '钻', $str);
        }

        return $str;
    }

    public function getMessageCount()
    {
        return MessageModel::getMessageCount($this->uuid);
    }


    public function isBan(): bool
    {
        $role_id = $this->attributes['role_id'] ?? 8;
        return in_array($role_id, [
            self::USER_ROLE_BLACK,
            self::USER_ROLE_LEVEL_BANED,
        ]);
    }

    public function isFeeMonthVip(): bool
    {
        return $this->vip_level > self::VIP_LEVEL_MOON && !Carbon::parse($this->expired_at)->lt(Carbon::now());
    }

    public function maker()
    {
        return $this->hasOne(MemberMakerModel::class, 'uuid', 'uuid');
    }

    public function talk()
    {
        return $this->hasOne(MemberTalkModel::class, 'uid', 'uid');
    }

    public function imToken(): string
    {
        if (empty($this->phone)) {
            throw new \Exception('请先绑定手机号码');
        }
        $salt = 'qeypxETJYTa9zQl4e9AblizBq3Cg0XHq';
        $format = sprintf("%s%s%s", $this->uuid, $this->phone, $salt);
        return sha1($format);//40位
    }

    public function realIos(){
        return strtolower($this->oauth_type) == 'ios' && strpos($this->oauth_id, '-') !== false;
    }

    /**
     * 活跃留存数据上报
     * @param MemberModel $memberModel
     * @return array|null
     */
    static function reportKeepData(MemberModel $memberModel)
    {
        if (!$memberModel->build_id) {
            return;
        }
        $extend = [];
        //尝试找到用户关联联盟渠道信息上报 方便定位关系
        /** @var \AgentsUserModel $agentUser */
        $agentUser = \AgentsUserModel::where(['channel' => $memberModel->build_id, 'aff' => $memberModel->invited_by])->first();
        if (is_null($agentUser)) {
            $agentUser = \AgentsUserModel::where(['channel' => $memberModel->build_id])->first();
        }
        if (!is_null($agentUser)) {
            $extend['agent_id'] = $agentUser->root_id;
        }
        return (new \service\AppCenterService())->keepData($memberModel->uid, $memberModel->build_id, $memberModel->invited_by, date('Y-m-d', $memberModel->regdate), date('Y-m-d'), $extend);
    }


    /**
     * @param string $aff
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|MemberModel|object
     * @author xiongba
     * @date 2020-06-13 20:56:44
     */
    public static function firstAff(string $aff):MemberModel
    {
        return self::where('aff', '=', $aff)->first();
    }


    public function isExemptTrial(): bool
    {
        $auth = $this->exempt_trial ?? self::NEED_TRIAL;
        $exempt_trial_at = $this->exempt_trial_at ?? date('Y-m-d H:i:s', time());
        //trigger_log('用户' . $this->aff . '的免审权限为:' . $auth . ' 用户的权限过期时间为:' . $exempt_trial_at);
        if ($auth == self::EXEMPT_TRIAL && !Carbon::parse($exempt_trial_at)->lt(Carbon::now())) {
            return true;
        }
        return false;
    }

    public static function isAwVip(\MemberModel $member){
        if ($member->is_vip && in_array($member->vip_level,[self::VIP_LEVEL_AW_MON,self::VIP_LEVEL_AW_YEAR])){
            return 1;
        }
        return 0;
    }

    static function createAccountByPc($oauthType, $oauthId, $version, $username, $password, $invitedAff): array
    {
        $exists = MemberModel::onWriteConnection()->where('username', $username)
            ->lockForUpdate()
            ->first();
        test_assert(!$exists, '您输入的用户名已经被注册，请更换用户名重新尝试注册');

        list($invitedByAff, $invitedByChannel) = self::getInviteBy($invitedAff);
        $member = MemberModel::make(MemberModel::getDefaultValue());
        $member->thumb = MemberRand::randAvatar();
        $member->nickname = MemberRand::randNickname();
        $member->uuid = md5($oauthType . $oauthId);
        $member->app_version = $version;
        $member->oauth_type = $oauthType;
        $member->oauth_id = $oauthId;
        $member->username = $username;
        $member->password = $password;
        $member->role_id = MemberModel::USER_ROLE_LEVEL_MEMBER;
        $member->regdate = TIMESTAMP;
        $member->lastvisit = TIMESTAMP;
        $member->regip = USER_IP;
        $member->invited_num = 0;
        $member->invited_by = $invitedByAff;
        $member->build_id = $invitedByChannel;
        $member->expired_at = TIMESTAMP;
        $member->vip_level = 0;
        $member->is_reg = 1;
        $member->is_piracy = 0;
        $member->aff = MemberModel::next_insert_id();
        // 推广码
        $isOk = $member->save();
        if ($member->uid != $member->aff){
            // 更新推广码 / 昵称
            $member->aff = $member->uid;
            $isOk = $member->save();
        }
        test_assert($isOk, '系统异常');
        //日志
        $logModel = MemberLogModel::createBy($member->uuid, $member->oauth_type, USER_IP, TIMESTAMP,
            $member->app_version);
        if (!is_null($logModel)) {
            $member->session = $logModel;
        }

        SysTotalModel::incrBy('member:active');
        SysTotalModel::incrBy('member:active:pc');

        $crypt = new LibCryptPwa();
        $token = $crypt->encryptToken($member->aff, $member->oauth_id, $member->oauth_type);
        return ['token' => $token];
    }

    static function getInviteBy($invitedAff): array
    {
        $invitedBy = 0;
        if (!$invitedAff) {
            return [$invitedBy, ''];
        }

        $invited_by = get_num($invitedAff);
        $invitedMember = MemberModel::firstAff($invited_by);
        if (!$invitedMember) {
            return [$invitedBy, ''];
        }

        $invitedMember->increment('invited_num');
        jobs([SysTotalModel::class, 'incrBy'], ['share_reg']);
        $key = 'user:info:' . $invitedMember->aff;
        if (redis()->exists($key)) {
            redis()->setWithSerialize($key, $invitedMember, 7200);
        }

        return [$invited_by, $invitedMember->build_id];
    }

    const CK_PC_USER_INFO = 'ck:pc:user:info:%s';
    const GP_PC_USER_INFO = 'gp:pc:user:info';
    const CN_PC_USER_INFO = 'PC_他人用户信息';
    public static function getUserInfo($aff)
    {
        $cacheKey = sprintf(self::CK_PC_USER_INFO, $aff);
        return cached($cacheKey)
            ->group(self::GP_PC_USER_INFO)
            ->chinese(self::CN_PC_USER_INFO)
            ->fetchPhp(function () use ($aff) {
                return MemberModel::select(['aff', 'nickname', 'thumb', 'vip_level', 'followed_count'])
                    ->where('aff', $aff)
                    ->first();
            });
    }

    public function updateSession(): bool
    {
        $insertTimestamp = date('Y-m-d 00:00:00', TIMESTAMP);
        // 日活已经是今天，无需更新
        if (isset($this->lastvisit) and $this->lastvisit > strtotime($insertTimestamp)) {
            return false;
        }
        SysTotalModel::incrBy('member:active');
        switch ($this->oauth_type) {
            case MemberModel::TYPE_ANDROID:
                SysTotalModel::incrBy('member:active:and');
                break;
            case MemberModel::TYPE_PWA:
                SysTotalModel::incrBy('member:active:pwa');
                break;
            case MemberModel::TYPE_IOS:
                SysTotalModel::incrBy('member:active:ios');
                break;
            case MemberModel::TYPE_PC:
                SysTotalModel::incrBy('member:active:pc');
                break;
        }

        // 更新日活信息，没有的话创建
        $session = MemberLogModel::onWriteConnection()->where('uuid', $this->uuid)->first();
        if (!$session) {
            $session = new MemberLogModel();
            $session->uuid = $this->uuid;
            $session->oauth_type = $this->oauth_type;
        }
        $this->lastip = $session->lastip = USER_IP;
        $this->lastvisit = $session->lastactivity = TIMESTAMP;
        $session->save();
        $this->save();

        $this->clearCached();
        return true;
    }

    public static function ban($aff){
        $member = self::find($aff);
        if ($member){
            $member->role_id = MemberModel::USER_ROLE_LEVEL_BANED;
            if ($member->is_vip){
                $member->vip_level = MemberModel::VIP_LEVEL_NO;
                $member->expired_at = TIMESTAMP;
            }
            if ($member->isDirty()){
                $member->save();
            }
            //通卡
            $tk = FreeMemberModel::where('uid', $member->uid)->first();
            if ($tk){
                $tk->delete();
                redis()->del(sprintf(FreeMemberModel::REDIS_FREE_DAY_TYPE, $member->uid, FreeMemberModel::FREE_DAY_MV));
                redis()->del(sprintf(FreeMemberModel::REDIS_FREE_DAY_TYPE, $member->uid, FreeMemberModel::FREE_DAY_MV_ADD_COMMUNITY));
            }
        }
    }

    //查找两层就好了，不搞递归
    public static function info($uid){
        return cached('channel:member:info:' . $uid)
            ->fetchJson(function () use ($uid){
                /** @var AgentsUserModel $channel */
                $channel = AgentsUserModel::where('aff')->first();
                if (!empty($channel)){
                    return $channel->username;
                }
                return '';
            });
    }

}