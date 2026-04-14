<?php

// 用户模块
use helper\QueryHelper;
use helper\Util;
use service\ProxyService;
use service\UserVideoService;
use service\EventTrackerService;
use Tbold\Serv\biz\BizAppVisit;
use Yaf\Exception;
use tools\HttpCurl;

class UsersController extends BaseController
{
    use \repositories\UsersRepository, \repositories\SmsRepository, \repositories\MvRepository;

    /**
     * 得到个人信息
     */
    public function getUserHomeAction()
    {
        $uid = $this->post['to_uid'] ?? '';
        if (empty($uid)) {
            return $this->errorJson('非法请求');
        }
        // 头像，粉丝，关注，妹币 id 级别 主播 用户等级 个性签名;
        $query = MemberModel::where('uid', $uid);
        /** @var MemberModel $info */
        $info = $query->first([
            'uid',
            'uuid',
            'thumb',
            'nickname',
            'expired_at',
            'vip_level',
            'fans_count',
            'followed_count',
            'fabulous_count',
            'likes_count',
            'videos_count',
            //'coins',
            //'coins_total',
            'thumb',
            'auth_status',
            'aff',
            'person_signnatrue',
        ]);
        if (empty($info)) {
            return $this->errorJson('用户信息不存在');
        }
        $info->auth_level = 0;
        if ($info->auth_status) {
            $info->auth_level = MemberMakerModel::getMakerLevel($info->uuid);
        }
        $info->watchByUser(request()->getMember());

        return $this->showJson($info);
    }

    /**
     * 验证手机号是否存在
     * @throws \Yaf\Exception
     */
    public function hasPhoneAction()
    {
        $phone = trim($this->post['phone'] ?? '');
        
        if ($phone == '') {
            throw new \Yaf\Exception('手机号不能为空', 422);
        }
        try {
            $user = $this->getUserByPhone($phone);
        } catch (\Throwable $e) {
            $this->errLog((string)$e);
        }
        
        $this->showJson(['has' => !empty($user)]);
    }

    /**
     * 注册（绑定手机号）
     * @throws \Yaf\Exception
     */
    public function registerAction()
    {
        $phone = $this->post['phone'] ?? '';
        $password = $this->post['password'] ?? '';
        $identify = $this->post['identify'] ?? '';
        $aff = $this->post['aff'] ?? '';
        if ($phone == '' or $password == '' or $identify == '') {
            throw new \Yaf\Exception('参数不全', 422);
        }
        if (!empty($aff)) {
            $this->handleInvitationUser($aff, $inviteUser);
        }
        
        $this->validatorSMS($phone, $identify);
        $token = $this->handleRegister($phone, $password);

        $this->showJson([
            'success' => true,
            'msg'     => '绑定手机号成功',
            'token'   => $token,
            'uid'     => $this->member['uid'],
            'uuid'    => $this->member['uuid']
        ]);
    }

    /**
     * 登录账号
     */
    public function loginAction()
    {
        try {
            $phone = $this->post['phone'] ?? '';
            test_assert($phone , '参数不全');
            $password = $this->post['password'] ?? '';
            test_assert($password , '参数不全');
            test_assert(isset($password[5]) , '密码长度不够');

            $user = $this->getUserByPhone($phone);
            test_assert($user , '用户不存在');
            test_assert($user->password == md5($password) , '密码错误');

            if ($this->member['username'] == $phone) { // 登录
                $token = $this->token($user->uuid);
            } else { // 交换设备
                $token = $this->handleChange($user);
            }
            
            $this->showJson([
                'success' => true,
                'msg'     => '登录成功',
                'token'   => $token,
                'uid'     => $user->uid,
                'uuid'    => $user->uuid
            ]);
        }catch (\Throwable $e){
            $this->errorJson($e->getMessage());
        }
    }

    /**
     * 填写邀请码
     * @throws \Yaf\Exception
     */
    public function invitationAction()
    {
        $aff = $this->post['aff'] ?? '';
        $aff = trim($aff);
        if (!$aff) {
            return $this->errorJson('参数错误',422);
        }
        $inviteUser = null;
        $rs = $this->handleInvitationUser($aff, $inviteUser);
        $data = [];
        $msg = '邀请绑定成功';
        $this->showJson($data, 1, $msg);
    }

    /**
     * 找回密码
     * @throws \Yaf\Exception
     */
    public function findPasswordAction()
    {
        $phone = $this->post['phone'] ?? '';
        $password = $this->post['password'] ?? '';
        $identify = $this->post['identify'] ?? '';

        if ($phone == '' or $password == '' or $identify == '') {
            return $this->errorJson('参数错误');
        }

        if (strlen($password) < 5) {
            return $this->errorJson('密码长度不够，至少5个字符');
        }

        $user = $this->getUserByPhone($phone);
        if (empty($user)) {
            return $this->errorJson('手机号不存在');
        }

        $this->validatorSMS($phone, $identify);

        $user->password = md5($password);
        $user->save();
        $this->showJson([
            'success' => true,
            'msg'     => '修改密码成功'
        ]);
    }

    /**
     * `修改密码
     * @return bool
     */
    public function changePasswordAction()
    {
        $password = $this->post['password'] ?? '';
        $oldPassword = $this->post['oldPassword'] ?? '';
        if ($password == '' or $oldPassword == '') {
            return $this->errorJson('参数不全');
        }

        if (strlen($password) < 5) {
            return $this->errorJson('密码长度不够，至少5个字符');
        }

        /** @var MemberModel $member */
        $member = MemberModel::where('uuid', '=', $this->member['uuid'])->first();
        if (empty($member)) {
            return $this->errorJson('用户不存在');
        }

        if ($member->password != md5($oldPassword)) {
            return $this->errorJson('原密码不正确');
        }
        MemberModel::query()->where('uuid', $this->member['uuid'])->update(['password' => md5($password)]);
        $this->showJson(['success' => true, 'msg' => '修改成功']);
    }

    /**
     * 修改头像
     */
    public function thumbAction()
    {
        $thumb = $this->post['thumb'] ?? '';
        if (!$this->member['vip_level']) {
            return $this->errorJson('仅允许会员用户设置头像');
        }
        if ($thumb == '') {
            return $this->errorJson('请先上传图片');
        }

        if ($this->member['role_id'] == 9) {
            return $this->errorJson('您不能修改图片');
        }
        MemberModel::query()->where('uuid', $this->member['uuid'])->update(['thumb' => $thumb]);
        // $this->showJson(['success' => true, 'msg' => '修改成功']);
        $this->clearUserInfo();
        $this->showJson(['success' => true, 'msg' => $this->fetchUserThumb($thumb)]);
    }

    /**
     * 检查输入的关键字
     * @param $content
     * @return bool
     */
    private function _checkInputWord($content)
    {
        $ban_word = ['代充', '会员', '金币', 'VIP', '微信', '联系', '电话'];
        $flag = false;
        foreach ($ban_word as $t) {
            if (stripos($content, $t) !== false) {
                $flag = true;
                break;
            }
        }
        return $flag;
    }

    /**
     * @author xiongba
     * @date 2020-11-02 15:58:08
     */
    public function personalAction()
    {
        $nickname = $this->post['nickname'] ?? '';
        $gender = $this->post['gender'] ?? '';
        $birthday = $this->post['birthday'] ?? '';
        $memo = $this->post['person_signnatrue'] ?? '';
        if (!$this->member['vip_level']) {
            return $this->errorJson('仅允许会员用户修改');
        }
        if ($this->member['role_id'] == MemberModel::USER_ROLE_LEVEL_BANED) {
            return $this->errorJson('您已被禁言');
        }
        $words = collect(explode("\n", setting('user:nickname', '搜同')))
            ->merge(explode(",", setting('filter:words', '搜同')))
            ->map('trim')->unique()->values()->all();

        if (check_keywords($nickname, $words)) {
            return $this->errorJson('信息敏感，系统拒绝');
        }
        if (check_keywords($memo, $words)) {
            return $this->errorJson('信息敏感，系统拒绝');
        }

        // 验证用户名
        $data = [];
        if ($nickname != '') {
            $str = preg_replace('/[^a-zA-Z0-9]/', '', $nickname);
            $affStr = generate_code($this->member['aff']);
            if (stristr($str, $affStr) !== false) {
                return $this->errorJson('昵称不合法');
            }
            if ($this->_checkInputWord($nickname)) {
                $nickname = 'Guest_' . $this->member['uid'];
            }
            $data['nickname'] = $nickname;
        }
        if ($memo) {
            if ($this->_checkInputWord($memo)) {
                $r = [
                    '成功的秘密，就是每天淘汰自己!',
                    '从不为已经不属于自己的东西而惋惜',
                    '打开一扇窗',
                    '假如我要，我就一定能',
                    '不会哭未算幸运!',
                    '前进、前进、向前进!',
                    '抬头面对明天的太阳!',
                    '人生好歹都要拼!',
                    '等待就是浪费青春!',
                    '时光飞逝，唯有实力永存',
                    '没有欢笑的时光，是虚度的光阴',
                ];
                $memo = $r[array_rand($r)];
            }
        }

        $gender != '' and $data['sexType'] = $gender;
        $birthday != '' and $data['birthday'] = (is_integer($birthday) ? $birthday : strtotime($birthday));
        $memo != '' and $data['person_signnatrue'] = $memo;
        if (!empty($data)) {
            MemberModel::where('uuid', $this->member['uuid'])->update($data);
            if (isset($data['nickname'])) {
                $update = [
                    'nickname' => $data['nickname']
                ];
                MemberMakerModel::where('uuid', $this->member['uuid'])->update($update);
            }
        }
        $this->clearUserInfo();
        $this->showJson(['success' => true, 'msg' => '更新成功']);
    }

    /**
     * 关注用户，取消关注
     * @throws Throwable
     */
    public function followingAction()
    {
        $uid = $this->post['to_uid'] ?? '';
        if (empty($uid) || $uid == $this->member['uid']) {
            return $this->errorJson('参数错误');
        }
        \helper\Util::PanicFrequency($this->member['uid'], 4, 20);
        if ($this->member['role_id'] == MemberModel::USER_ROLE_LEVEL_BANED) {
            return $this->errorJson('您已被禁言');
        }
        try {
            $result = (new \service\FollowedService())->handleFollowingUser($this->member['uid'], $uid);
            $this->showJson(['success' => true, 'msg' => $result['msg'], 'is_attention' => $result['is_attention']]);
        } catch (Throwable $e) {
            $this->errorJson('操作失败，请重试', 0, '');
        }

    }

    /**
     * 查看用户的关注列表
     */
    public function followedAction()
    {
        $uid = $this->post['uid'] ?? '';
        if (empty($uid)) {
            $uid = $this->member['uid'];
        }
        $member = request()->getMember();
        $items = $this->getUserFollowedList($uid, $member);
        $this->showJson($items);
    }

    /**
     * 用户粉丝列表
     */
    public function fansAction()
    {
        try {
            $uid = $this->post['uid'] ?? '';
            $member = request()->getMember();
            if (empty($uid)) {
                $uid = $member->uid;
            }
            list($page, $limit) = QueryHelper::pageLimit();
            $items = (new \service\FollowedService())->getUserFansList($member, $uid, $page, $limit);
            return $this->showJson($items);
        }catch (Throwable $exception){
            return $this->errorJson($exception->getMessage());
        }
    }

    /**
     * 用户的视频列表
     */
    public function videosAction()
    {
        $uid = $this->post['uid'] ?? '';
        $kwy = $this->post['kwy'] ?? '';
        $show_type = $this->post['show_type'] ?? 0;
        if (empty($uid)) {
            $uid = $this->member['uid'];
        }
        $member = request()->getMember();
        $items = (new UserVideoService())->getVideosByUid($member, $uid, $show_type, $kwy);
        $this->showJson($items);
    }

    /**
     * 用户点赞的视频列表
     */
    public function likesAction()
    {
        $uid = $this->post['uid'] ?? '';
        $kwy = $this->post['kwy'] ?? '';
        $show_type = $this->post['show_type'] ?? 0;
        if (empty($uid)) {
            $uid = $this->member['uid'];
        }
        $member = request()->getMember();

        $items = (new UserVideoService())->getUserLikesVideoList($member, (int)$uid, $kwy, $show_type);
        $this->showJson($items);
    }

    /**
     * 获取用户信息
     */
    public function getBaseInfoAction()
    {
        $member = request()->getMember();
        $member->append('level', 'level_anchor', 'aff_code', 'expired_str', 'vip_icon');
        $build_id = $member->build_id;
        $member->addHidden(
            'password', 'oauth_type', 'oauth_id', 'regip', 'regdate',
            'uuid', 'lastip', 'lastvisit', 'app_version', 'app_version', 'gender',
            'is_live_super', 'phone', 'level_anchor', 'live_supper', 'thumb',
            'login_count'
        );
        $member['invite_by_code'] = $member['invited_by'] ? generate_code($member['invited_by']) : '';
        $aff_code = generate_code($member['aff']);
        $sharData = getShareDataByAff($aff_code, $this->channel);
        $member['share_url'] = $sharData['share_link'];
        $member['share_text'] = $sharData['share_url'];
        $member['token'] = $this->token($this->member['uuid']);
        $canMaxWatch = (int)setting("site.can_watch_count", 6);

        if ($member->isVV) {
            $member['watch_count'] = 1024;
            $member['can_watch'] = 1024;//剩余次数
        } else {
            $member['watch_count'] = $canMaxWatch;
            $canWatch = $canMaxWatch - $this->getUserTodayWatchCount($this->member['uid']);
            $member['can_watch'] = max($canWatch, 0);
        }
        $member['auth_level'] = 0;
        /** @var MemberMakerModel $creatorInfo */
        $creatorInfo = MemberMakerModel::getMakeRowInfo($this->member['uuid']);
        $auth_status = $member->auth_status;
        if ($creatorInfo) {
            $member['auth_status'] = $creatorInfo->status;
            //认证 重写auth_status 状态
            if ($creatorInfo->status == MemberMakerModel::CREATOR_STAT_YES) {
                $member['auth_level'] = $creatorInfo->level_num;
            }
        }
        $member['message_tip'] = MessageModel::getMessageCount($this->member['uuid']);//我的通知
        $member['today_post_score'] = UsersCoinrecordModel::getTodayProfit($member->uid,'buyPost');  // 累计收入;
        $member['today_mv_score'] = UsersCoinrecordModel::getTodayProfit($member->uid,'buymv');  // 累计收入;
        $member['today_tui_coins'] = ProxyService::getMyProxyAmount($member->uid, [
            ['created_at', '>=', strtotime(date('Y-m-d 00:00:00'))],
            ['type', '=', UserProxyCashBackDetailModel::TYPE_TUI]
        ]);
        if ($build_id && isChannel($build_id)) {
            $member['today_tui_coins'] = 0.00;
        }

        $result = $member->toArray();
        //临时代吗  后面删除
        //$result['vip_level'] = $member['vip_level'] == 4 ? 3 : $member['vip_level'];

        $result['is_vip'] = $member->expired_at >= time() ? 1 : 0;
        $topicCfg = [
            'total_free' => intval(setting('user-topic.count', 5)), //系统允许免费创建次数
            'amount'     => intval(setting('user-topic.uint', 5)),  //收费创建的单价
            'over_free'  => 0, //用户剩余免费创建次数
            'allow'      => 0, //是否允许用户创建
        ];
        if ($auth_status == MemberModel::AUTH_STATUS_YES) {
            $topic_count = 0;
            if ($topic_count <= $topicCfg['total_free']) {
                $topicCfg['over_free'] = $topicCfg['total_free'] - $topic_count;
            }
            $topicCfg['allow'] = 1;
        }
        $result['topic_cfg'] = $topicCfg;
        $result['chat_token'] = getChatToken($this->member['uuid']);
        $result['chat_tips'] = "请勿发送广告等违规信息，谨防私下交易上当受骗。违规用户将被永久禁言处理。";
        $result['my_ticket_number'] = MvTicketModel::myInitMvTicketNumber(request()->getMember());
        $result['can_aw'] = UsersProductPrivilegeModel::hasAwMvPerm();
        $result['can_aw_tips'] = setting('can_aw_tips','');
        $result['build_id'] = AgentsUserModel::getUsernameByAff($member->invited_by);
        return $this->showJson($result);
    }

    public function verify_urlAction()
    {
        $member = request()->getMember();
        if ($member->isBan()) {
            return $this->errorJson('封禁用户不允许操作');
        }
        $serverService = new service\ServerService();
        $data = [
            'verifyUrl' => $serverService->verifyUrl($member->aff).'?t=' . time()
        ];
        return $this->showJson($data);
    }

    /**
     * 发送短信
     * @throws \Yaf\Exception
     */
    public function smsAction()
    {
        $mobile_prefix = $this->post['mobile_prefix'] ?? '86';
        $phone = $this->post['phone'] ?? '';
        $member = request()->getMember();
        $verify_code = $this->post['verify_code'] ?? '';
        try {
            $this->seed($phone, $mobile_prefix, $member, $verify_code);
        } catch (Exception $e) {
            return $this->errorJson($e->getMessage(), $e->getCode());
        }
        $this->showJson('发送成功');
    }

    /**
     * 得到短信国家
     */
    public function getSMSCountryAction()
    {
        $result = (new SMSCountryModel)->getList();
        $this->showJson($result);
    }

    /**
     * 用户榜
     */
    public function topUserAction()
    {
        $validator = simpleValidate($this->post, [
            'type' => 'filled|string|in:day,week,month,total',
        ]);

        if (!$validator['success']) {
            throw new \Yaf\Exception('参数不全', 422);
        }

        $dbType = '';

        $dataRaw = StatisticsModel::instance()->userFace();
        switch ($this->post['type']) {
            case 'day' :
                $result = $dataRaw['day'];
                $dbType = 'user_statistics_day';
                break;
            case 'week' :
                $result = $dataRaw['week'];
                $dbType = 'user_statistics_week';
                break;
            case 'month' :
                $result = $dataRaw['month'];
                $dbType = 'user_statistics_month';
                break;
            case 'total' :
                $result = $dataRaw['total'];
                $dbType = 'user_statistics_total';
                break;
        }

        $memberModel = singleton(MemberModel::class);
        $timeStart = 0;
        $key = "top_user_" . $dbType;
        $resultFix = getCaches($key);
        if (!$resultFix && $result) {
            foreach ($result as $k => $v) {
                $timeStart += 1;
                $i = $memberModel->where('uuid', $v)->first();
                $i && $i = $i->toArray();
                $coins = \tools\RedisService::redis()->zScore($dbType, $v);
                $expend = $i ? $i['consumption'] : '0';
                $resultFix[] = [
                    'order'             => intval($k) + 1,
                    'uid'               => $i['uid'],
                    'nickname'          => isset($i['nickname']) ? $i['nickname'] : $i['username'],
                    'thumb'             => $this->config->img->img_head_url . $i['thumb'],
                    'person_signnatrue' => $i['person_signnatrue'],
                    'is_attention'      => $this->isAttentionNew($this->member['uid'], $i['uid']),
                    'level'             => getLevel($expend),
                    'sexType'           => $i['sexType'],
                    'coins'             => $coins
                ];
            }
            setCaches($key, $resultFix, 600);
        }

        $this->showJson($resultFix ?: []);
    }

    /**
     * 主播榜
     */
    public function topActorAction()
    {
        $validator = simpleValidate($this->post, [
            'type' => 'filled|string|in:day,week,month,total',
        ]);

        if (!$validator['success']) {
            return $this->showJson([], 0, $validator['message'][0]);
        }

        $dbType = '';

        $dataRaw = singleton(StatisticsModel::class)->actorFace();
        switch ($this->post['type']) {
            case 'day' :
                $result = $dataRaw['day'];
                $dbType = 'actor_statistics_day';
                break;
            case 'week' :
                $result = $dataRaw['week'];
                $dbType = 'actor_statistics_week';
                break;
            case 'month' :
                $result = $dataRaw['month'];
                $dbType = 'actor_statistics_month';
                break;
            case 'total' :
                $result = $dataRaw['total'];
                $dbType = 'actor_statistics_total';
                break;
        }

        $memberModel = singleton(MemberModel::class);
        $timeStart = 0;
        $key = "top_actor" . $dbType;
        $resultFix = getCaches($key);
        if (!$resultFix) {
            foreach ($result as $k => $v) {
                $timeStart += 1;
                $i = $memberModel->where('uuid', $v)->first();
                $i && $i = $i->toArray();
                $coins = tools\RedisService::redis()->zScore($dbType, $v);
                $expend = $i ? $i['votes_total'] : '0';
                $resultFix[] = [
                    'order'             => intval($k) + 1,
                    'uid'               => $i['uid'],
                    'nickname'          => isset($i['nickname']) ? $i['nickname'] : $i['username'],
                    'thumb'             => $this->config->img->img_head_url . $i['thumb'],
                    'person_signnatrue' => $i['person_signnatrue'],
                    'is_attention'      => $this->isAttentionNew($this->member['uid'], $i['uid']),
                    'level_anchor'      => getLevelAnchor($expend),
                    'sexType'           => $i['sexType'],
                    'coins'             => $coins
                ];
            }
            setCaches($key, $resultFix, 600);
        }

        $this->showJson($resultFix ?: []);
    }


    /**
     * 我的提现明细列表
     */
    function myWithDrawListAction()
    {
        $uuid = request()->getMember()->uuid;
        $uid = request()->getMember()->uid;
        list($limit, $offset, $page) = QueryHelper::restLimitOffset();
        $key = "withdraw:{$uid}:{$page}";
        $list = cached($key)
            ->expired(600)->serializerJSON()->fetch(function () use ($uuid, $limit, $offset) {
                return UserWithdrawModel::where('uuid', $uuid)
                    ->select(['id', 'name', 'trueto_amount', 'amount', 'status', 'created_at', 'withdraw_from'])
                    ->orderBy('id', 'desc')
                    ->offset($offset)
                    ->limit($limit)
                    ->get()->map(function (UserWithdrawModel $item) {
                        $item->created_at_str = date('Y-m-d H:i', $item->created_at);
                        $item->status_str = UserWithdrawModel::STATUS_TEXT[$item->status];
                        return $item;
                    })
                    ->values();
            });
        $this->showJson($list);
    }

    /**
     * 我的订单
     * @return bool
     */
    public function ordersAction()
    {
        $member = request()->getMember();
        $service = new \service\UserRechargeLogsService($member);
        $data = $service->getLogs($lastIndex, $total);

        return $this->showJson([
            'lastIndex' => $lastIndex,
            'list'      => $data,
            'total'     => $total
        ]);
    }

    /**
     * 我的邀请赚钱
     */
    public function yqzqAction()
    {
        /** @var MemberModel $member */
        $member = request()->getMember();
        //邀请注册用户数
        $yaoqingTotal = cached('yaoqin:' . $member->uid)->expired(1800)->fetch(function () use ($member) {
            return MemberModel::where(['invited_by' => $member->aff, 'is_reg' => 1])->count('uid');
        });
        //邀请规则
        $yaoqinGuize = url_ads('/new/ads/20201114/2020111411095820968.png');
        //选择我们
        $xuanzeWomen = url_ads('/new/ads/20201114/2020111411113685005.png');
        //收益明细
        $shouyiMingxi = url_ads('/new/ads/20201114/2020111411124349498.png');
        //推广记录
        $tuiguangJilu = url_ads('/new/ads/20201114/2020111411140177414.png');
        //去推广
        $quTuiguang = url_ads('/new/ads/20201114/2020111411142172768.png');
        $total_tui_coins = $member->total_tui_coins;
        if ($member->build_id && isChannel($member->build_id)) {
            $total_tui_coins = 0;
        }
        $return = [
            'total_invited_num'     => $member->invited_num,
            'total_invited_reg_num' => $yaoqingTotal,
            'total_tui_coins'       => $total_tui_coins,
            'image_yqgz'            => $yaoqinGuize,
            'image_symx'            => $shouyiMingxi,
            'image_tgjl'            => $tuiguangJilu,
            'image_qtg'             => $quTuiguang,
            'image_xzwm'            => $xuanzeWomen,
        ];
        return $this->showJson($return);
    }

    /**
     * 用户金币日志
     * @author xiongba
     */
    public function consume_logAction()
    {
        $uid = $this->member['uid'];
        list($page, $limit) = \helper\QueryHelper::pageLimit();
        $list = cached("coin:log:{$uid}:{$page}")
            ->fetchJson(function () use ($uid, $limit, $page) {
                return UsersCoinrecordModel::where('uid', $uid)
                    ->orderByDesc('id')
                    ->forPage($page, $limit)
                    ->get(['uid', 'addtime', 'id', 'touid', 'desc', 'type', 'totalcoin'])->toArray();
            }, 60);
        $this->showJson($list);
    }


    /**
     * 创作教程
     * @return bool|void
     * @author xiongba
     * @date 2020-10-14 19:08:59
     */
    public function creatorStudyAction()
    {
        $result = [];
        $result[] = [
            'name' => '创作攻略',
            'type' => 'raiders',
            'item' => [
                [
                    'bg'   => url_live('/new/xiao/20201014/2020101420125257235.png'),
                    'name' => '创作技巧解析',
                    'url'  => url_h5('h5/learn/jqjx.html')
                ],
                [
                    'bg'   => url_live('/new/xiao/20201014/2020101420133627178.png'),
                    'name' => '剪辑工具推荐',
                    'url'  => url_h5('h5/study/skill.html')
                ],
            ]
        ];

        $result[] = [
            'name' => '创作教程',
            'type' => 'study',
            'item' => [
                [
                    'bg'   => url_live('/new/xiao/20201014/2020101420141278143.png'),
                    'name' => '如何利用视频盈利',
                    'url'  => url_h5('h5/learn/spyl.html')
                ],
                [
                    'bg'   => url_live('/new/xiao/20201014/2020101420144757301.png'),
                    'name' => '怎样创作高质量的',
                    'url'  => url_h5('h5/learn/jxsp.html')
                ],
                [
                    'bg'   => url_live('/new/xiao/20201014/2020101420151717076.png'),
                    'name' => '月入上万不是梦',
                    'url'  => url_h5('h5/learn/yrsw.html')
                ],
            ]
        ];

        return $this->showJson($result);

    }

    /**
     * 获取用户的观影卷
     * @return bool
     */
    public function myMvTicketAction()
    {
        $type = $this->post['type'] ?? 'all';
        $member = request()->getMember();
        //print_r($member->aff);
        //errLog("aff:".$member->aff);
        $result = MvTicketModel::myMvTicket($member, $type);
        return $this->showJson($result);
    }


    /**
     * =============新登陆注册流程 （统一）
     *
     * 现在就一个登录页面输入用户名和密码，
     * 如果用户名存在 判断密码是否正确，正确的话走账号切换流程
     * 如果账号不存在的话。 就是注册
     * 不提供密码找回及修改功能
     * 注册页面需要描述：不提供密码找回的功能
     *
     * @throws \Yaf\Exception
     */
    public function login_accountAction()
    {
        $username = $this->post['username'] ?? '';
        $password = $this->post['password'] ?? '';
        if (empty($username) || empty($password)) {
            return $this->errorJson('参数不全');
        }
        if (mb_strlen($username) < 6 || strlen($password) < 6) {
            return $this->errorJson('用户名或密码长度不小于6位，推荐使用字母数字下划线组合或手机号码');
        } elseif (!preg_match("/^[\w]{6,}$/", $username)) {
            return $this->errorJson('用户名格式不正确，推荐使用字母数字下划线组合或手机号码.');
        }
        if (redis()->get("ban15:{$username}")) {
            errLog("登陆异常账号:{$username} 用户：{$this->member['uid']} IP:" . USER_IP);
            return $this->errorJson('系统检测到该账号登陆异常，过一会重试~');
        }
        $password = md5($password);
        /** @var MemberModel $member */
        $member = MemberModel::where(['username'=>$username,'password'=>$password])->first();
        if(is_null($member)){
            if (!Util::frequency($username, 3, 300)) {
                redis()->setex("ban15:{$username}", 900, 1);
                return $this->errorJson('用户名或密码错误,触发登陆异常~');
            }
            return $this->errorJson('用户名或密码错误.');
        }

        //公司上报
        (new EventTrackerService(
            $member->oauth_type,
            $member->invited_by,
            $member->uid,
            $member->oauth_id,
            $this->post['device_brand'] ?? '',
            $this->post['device_model'] ?? ''
        ))->addTask([
            'event' => EventTrackerService::EVENT_USER_LOGIN,
            'type'  => EventTrackerService::REGISTER_TYPE_USERNAME,
        ]);

        //$this->channel && BizAppVisit::behavior(BizAppVisit::ID_LOGIN);
        if($member->uid == $this->member['uid']){//当前自己人
            $token = $this->token($this->member['uuid']);
            return $this->showJson([
                'success' => true,
                'msg'     => '登录成功',
                'token'   => $token,
                'uid'     => $this->member['uid'],
                'uuid'    => $this->member['uuid']
            ]);
        }else{
            // 交换设备
            $token = $this->handleChange($member);
            return $this->showJson([
                'success' => true,
                'msg'     => '设备登陆成功~',
                'token'   => $token,
                'uid'     => $member->uid,
                'uuid'    => $member->uuid
            ]);
        }
        return $this->errorJson('设备异常，登录失败.');
    }

    public function register_accountAction()
    {
        $username = $this->post['username'] ?? '';
        $password = $this->post['password'] ?? '';
        if (empty($username) || empty($password)) {
            return $this->errorJson('参数不全');
        }
        if (mb_strlen($username) < 6 || strlen($password) < 6) {
            return $this->errorJson('用户名或密码长度不小于6位，推荐使用字母数字下划线组合或手机号码');
        } elseif (!preg_match("/^[\w]{6,}$/", $username)) {
            return $this->errorJson('用户名格式不正确，推荐使用字母数字下划线组合或手机号码.');
        }
        if ($this->member['is_reg']) {//已经绑定
            return $this->errorJson('当前设备注册绑定了~');
        }
        $password = md5($password);
        $user = $this->getUserByPhone($username);
        if (is_null($user)) {
            //注册
            $data = [
                'username' => $username,
                'phone'    => $username,
                'password' => $password,
                'is_reg'   => 1,
            ];
            MemberModel::where('uuid', $this->member['uuid'])->update($data);
            //邀请赠送的VIP产品ID
            if ($this->member['invited_by']){
                $this->checkReward($this->member['uid'], $this->member['invited_by']);
            }

            $token = $this->token($this->member['uuid']);
            \MemberModel::clearFor($this->member);
            //$this->channel && BizAppVisit::behavior(BizAppVisit::ID_REG);

            //公司上报
            (new EventTrackerService(
                $this->member['oauth_type'],
                $this->member['invited_by'],
                $this->member['uid'],
                $this->member['oauth_id'],
                $this->post['device_brand'] ?? '',
                $this->post['device_model'] ?? ''
            ))->addTask([
                'event' => EventTrackerService::EVENT_USER_LOGIN,
                'type'  => EventTrackerService::REGISTER_TYPE_USERNAME,
            ]);

            return $this->showJson([
                'success' => true,
                'msg'     => '注册绑定成功~',
                'token'   => $token,
                'uid'     => $this->member['uid'],
                'uuid'    => $this->member['uuid']
            ]);
        }
        if (!Util::frequency('reg'.$this->member['uid'], 3, 300)) {
            return $this->errorJson('设备注册太频繁~');
        }
        return $this->errorJson('当前账号已注册，登陆试试.');
    }

    public function checkReward($uid, $invite_by)
    {
        if (empty($uid) || empty($invite_by)) {
            return null;
        }

        /** @var MemberModel $member */
        $member = MemberModel::find($invite_by);
        if (is_null($member)) {
            return null;
        }
        //注册IP相同
        $user = MemberModel::find($uid);
        if ($user->regip == $member->regip){
            return null;
        }

        //邀请赠送的VIP产品ID
        $invite_send_product_id = setting('invite_send_product_id', 0);
        if (!$invite_send_product_id){
            return null;
        }
        //15 送的VIP-产品ID 15 固定死
        /** @var \ProductModel $product */
        $product = \ProductModel::query()->where('id', $invite_send_product_id)->first();
        if (empty($product)){
            return null;
        }
        // 更新上级信息
        $reward = MemberModel::INVITED_REWARD_TIMES;
        $expired = max($member->expired_at , TIMESTAMP) + $reward * 86400;
        if (!empty($product) && $expired < strtotime("+30 days")){
            $member->expired_at = $expired;
            $member->vip_level = max($user->vip_level, $product->vip_level);
            $product->valid_date = $reward;
            //vip 商品卡片
            \ProductUserModel::buyVIPProduct($member, $product);
        }

        $member->save();
        MemberModel::clearFor($member);
        return true;
    }

    private function _checkLogin($userName)
    {
        $userName = md5($userName);
        $userNameKey = mb_substr_replace(md5($userName), '', 6, 26);
        $date = date("Ymd");
        $ip = md5(USER_IP);
        $ipKey = mb_substr_replace($ip, '', 6, 26);
        if ($_data = redis()->get($userNameKey)) {
            list($_ip, $_date) = explode('|', $_data);
            if ($_ip != $ipKey && $_date == $date) {
                return false;
            }
        } else if ($_data = redis()->get($ipKey)) {
            list($_name, $_date) = explode('|', $_data);
            if ($_name != $userNameKey && $_date == $date) {
                return false;
            }
        }
        redis()->set($userNameKey, "{$ipKey}|{$date}", 86400);
        redis()->set($ipKey, "{$userNameKey}|{$date}", 86400);
        return true;
    }

    public function customer_confAction()
    {
        try {
            $url = config('customer.api_url') . '/app/customer/user/getUrl';
            $app_id = config('customer.app_id');
            $key = config('customer.key');
            $member = request()->getMember();
            $systemType = $member->oauth_type == MemberModel::TYPE_PWA ? 'h5' : $member->oauth_type;
            $deviceType = $member->oauth_type == MemberModel::TYPE_ANDROID ? 'android' : 'iPhone';
            $encrypt_data = [
                'appId'         => $app_id,
                'userId'        => $member->aff,     //用户Id
                'deviceType'    => $deviceType,     //设备类型
                'systemType'    => $systemType,     //系统类型,ios、h5等
                'systemVersion' => $_POST['version'],     //系统版本
            ];
            $sign = customerCoreAesEncrypt($encrypt_data, $key);
            $data = [
                'appId'     => $app_id,
                'sign'      => $sign
            ];
            $url = $url . '?' . html_entity_decode(http_build_query($data), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $res = cached(sprintf('customer:url:%s', $member->aff))
                ->clearCached()
                ->fetchPhp(function () use ($url){
                   return (new HttpCurl())->get($url);
                });
            test_assert($res, '客服系统异常');
            $res = json_decode($res, true);
            if ($res['code'] != 200){
                test_assert(false, $res['tips']);
            }
            $url = $res['data']['url'] ?? '';
            test_assert($url, $res['tips']);

            return  $this->showJson(['url' => $url]);
        }catch (Throwable $exception){
            return $this->errorJson($exception->getMessage());
        }
    }
}
