<?php

use service\AdService;
use service\GameService;
use service\VerifyService;
use tools\CurlService;
use Yaf\Exception;

/**
 * 游戏接入-
 * Class GameController
 *https://showdoc.hyys.info/web/#/47?page_id=2917
 */
class GameController extends BaseController
{
    //const WEI_CHAT = '*微信号：JIUYI88_';
    const WEI_CHAT = '*您的充值已达到加VIP资格,Enigma账号：JIUYI_VIP1 有问题反馈立马解决,提现30分钟秒到账';
    public function indexAction()
    {
        $member = $this->member;
        $base = config('img.img_ads_url');
        $imgUrl = str_ireplace('/img.ads/', '', $base);
        // 兼容老版
        $ad = AdService::getADsByPosition(AdsModel::POSITION_GAME);
        //$ad = [];
        $gameList = [
            ['id' => 100, 'icons' => $imgUrl . '/new/ads/20221119/2022111910340134164.png', 'name' => '激情世界杯'],
            ['id' => 1, 'icons' => $imgUrl . '/new/game/1.jpg', 'name' => '捕鱼'],
            ['id' => 2, 'icons' => $imgUrl . '/new/game/2.jpg', 'name' => '斗地主'],
            ['id' => 3, 'icons' => $imgUrl . '/new/game/3.jpg', 'name' => '炸金花'],
            ['id' => 5, 'icons' => $imgUrl . '/new/game/5.jpg', 'name' => '抢庄牛牛'],
            ['id' => 6, 'icons' => $imgUrl . '/new/game/6.jpg', 'name' => '二人麻将'],
            ['id' => 7, 'icons' => $imgUrl . '/new/game/7.jpg', 'name' => '红黑大战'],
            ['id' => 10, 'icons' => $imgUrl . '/new/game/10.jpg', 'name' => '跑得快'],
            ['id' => 12, 'icons' => $imgUrl . '/new/game/12.jpg', 'name' => '龙虎大战'],
            ['id' => 14, 'icons' => $imgUrl . '/new/game/14.jpg', 'name' => '视讯百家乐'],
            ['id' => 18, 'icons' => $imgUrl . '/new/game/18.jpg', 'name' => '奔驰宝马'],
            ['id' => 19, 'icons' => $imgUrl . '/new/game/19.jpg', 'name' => '飞禽走兽'],
            ['id' => 25, 'icons' => $imgUrl . '/new/game/25.jpg', 'name' => '21点'],
            ['id' => 26, 'icons' => $imgUrl . '/new/game/26.jpg', 'name' => '抢庄三公'],
            ['id' => 27, 'icons' => $imgUrl . '/new/game/27.jpg', 'name' => '看三张'],
            ['id' => 28, 'icons' => $imgUrl . '/new/game/28.jpg', 'name' => '抢庄牌九'],
            ['id' => 29, 'icons' => $imgUrl . '/new/game/29.jpg', 'name' => '抢庄二八杠'],
        ];

        $hot = [
            ['id' => 2, 'icons' => $imgUrl . '/new/game/hot/2.png', 'name' => '斗地主'],
            ['id' => 3, 'icons' => $imgUrl . '/new/game/hot/3.png', 'name' => '炸金花'],
            ['id' => 14, 'icons' => $imgUrl . '/new/game/hot/14.png', 'name' => '视讯百家乐'],
            ['id' => 5, 'icons' => $imgUrl . '/new/game/hot/5.png', 'name' => '抢庄牛牛'],
            ['id' => 12, 'icons' => $imgUrl . '/new/game/hot/12.png', 'name' => '龙虎大战'],
        ];

        $randGame = array_rand($gameList);
        $f = random_int(1, 9);
        $e = random_int(1, 9);
        $t = $gameList[$randGame]['name'];
        $tips = '恭喜 ' . $f . '****' . $e . ' 在' . $t . '中赢得' . random_int(1, 3000) . '元';
        $tips2 = '恭喜 ' . random_int(1, 9) . '****' . random_int(1, 9) . ' 在' . $gameList[array_rand($gameList)]['name'] . '中赢得' . random_int(1, 3000) . '元';

        // 活动逻辑  大厅才送
        //GameDetailModel::sendOver24HourActive($this->member,3);//超24小时活动赠送
        //GameDetailModel::sendActive($this->member,3);//注册绑定 送3元
        // 活动逻辑  enterAction
//        $notice = '绑定手机*送3元~';
        $notice = '#温馨提示：游戏已经下线，游戏用户一周内将账户内金额提走。';
//        $notice = '为回馈新老用户对我们自营游戏的支持,即日起开放首充奖励及累充奖励活动,详细规则请点击下方详情查看
//-白天充值成功率高，请尽量在白天8点-晚上8点之间充值。';
        //GameDetailModel::sendActive($this->member,3);
        $hasPhone = $member['phone'] ?? '';
        //$hasGift = GameDetailModel::checkHasActive($member['aff'],3,GameDetailModel::TYPE_GAVE);
        $hasGift = true;
        $hasPhone = '1';
        $this->showJson([
            'advert' => $ad,
            //'hotGame' => $hot,
            'hotGame' => [],
            'moreGame' => $gameList,
            'notice' => $notice,
            //'notice' => "",
            'tips' => $tips . '。'  . ' ' . $tips2,
            'vipIcon' => false,
            'vipText' => '',
            'iconIcon' => $hasGift ? false : true,
            'iconText' => $hasGift ? '' : '注册送3元',
            'hasPhone' => $hasPhone ? 1 : 0,
            'balance' => (new GameService())->getBalance($member['aff']),
            'active_bar' => true,//控制是否展示 活动按钮和详情
            'active_url' => 'https://h5game.microservices.vip/',//控制跳转h5连接
        ]);
    }

    /**
     * 生成支付签名
     * @param $array
     * @param string $signKey
     * @return string
     */
    public function make_sign_pay($array, $signKey = '')
    {
        if (empty($array)) {
            return '';
        }
        $string = '';
        foreach ($array as $key => $val) {
            $string .= $val;
        }

        $string = md5($string . $signKey);
        return $string;
    }
    public function payAction()
    {  $oauth_type = $this->post['oauth_type'];
        $amount = $this->post['amount'];
        $way = $this->post['way'] ?? 'alipay';
        $type = $this->post['type']??'game';
        $verify_code = $this->post['verify_code'] ?? '';
        $member = $this->member;
        if (empty($amount) || $amount < 50) {
            return $this->showJson([], 0, '金额至少50元~');
        }
        if(empty($type) || ($type!='game' || $type!='agent')){
            $type = 'game';
        }
        if($way == 'agent'){
            $type = 'agent';
            $way = 'alipay';
        }
        if (MemberModel::USER_ROLE_BLACK == $member['role_id']) {
            return $this->showJson([], 0, '订单创建失败，请稍后重试爸爸~');
        }

        $fdk=[];
        $fdk['uid'] = USER_IP;
        if (!frequencyLimit(600, 8, $fdk)) {
            $ip = USER_IP;
            $pos = $this->position['area'] ?? '';
            if (empty($pos)) {
                $pos = $this->position['city'] ?? '';
                $pos .= $this->position['isp'] ?? '';
            }
            SystemNoticeModel::addNotice(SystemNoticeModel::TYPE_GAME, $member['uuid'],
                "单ip{$ip}($pos) 10分钟内游戏单拉起超过8次");
            //return $this->errorJson('短时间内操作太頻繁了,或联系专属客服人员~');
        } elseif (!frequencyLimit(300, 5, $member)) {
            SystemNoticeModel::addNotice(SystemNoticeModel::TYPE_GAME, $member['uuid'], '5分钟内游戏单拉起超过5次');
            //return $this->errorJson('短时间内操作太頻繁了,稍后再试试,或联系专属客服人员~');
        }
        //check order no pay stat
        list($totalOrderToday,$totalOrderTodayNoPay,$totalOrderTodayNoPayIn1Hour) = OrdersModel::limitGameOrder($member['uuid']);
        if ($totalOrderTodayNoPay >= 10 || $totalOrderTodayNoPayIn1Hour >= 5) {
            SystemNoticeModel::addNotice(SystemNoticeModel::TYPE_GAME, $member['uuid'],
                "当日总订单：{$totalOrderToday} 未付游戏单{$totalOrderTodayNoPay};1小时未付游戏单{$totalOrderTodayNoPayIn1Hour}");
            //return $this->errorJson('今天太多未付款单了,稍后再试试~');
        }

        if (true || in_array($member['uid'], ['866032', '4757036'])) {
            $vfquency = OrdersModel::verifyFrequency([
                ['uuid', '=', $member['uuid']],
                ['order_type', '=', OrdersModel::TYPE_GAME],
                ['created_at', '>=', strtotime("-1 hours")],//1 小时未支付非游戏单大于等于4笔
            ],4);
            //$vfquency = true;
            if($vfquency ){
                if($verify_code){
                    //verifycode
                    if(!(new VerifyService())->verifyCheck($member['aff'],$verify_code)){
                        return $this->errorJson(VerifyService::VERIFY_CODE_TEXT);
                    }
                }else{
                    if ($oauth_type != 'ios') {
                        return $this->errorJson('多次下单未支付,请更新到最新版本试试', VerifyService::VERIFY_CODE);
                    }
                }
            }
        }

        $data['app_name'] = SYSTEM_ID;
        $data['app_type'] = $member['oauth_type'] == 'ios' ? 'ios' : 'android';
        $data['aff'] = $member['aff'];
        //$data['aff'] = "{$member['aff']}:9999";//区分 '用户:产品'
        $data['amount'] = (string)$amount;

        $sign = $this->make_sign_pay($data, config('pay.pay_signkey'));
        $data['pay_type'] = $way;
        $data['ip'] = USER_IP;
        $data['type'] = $type;
        $data['sign'] = $sign;
        $data['product'] = 'game';
        $curl = new CurlService();
        $result = $curl->curlPost(config('pay.pay_url'), $data);
        $result = json_decode($result, true);
        errLog("gameOrder:".var_export($result,1));
        if (isset($result['success']) && $result['success'] == true) {
            $order = array(
                'uuid'       => $member['uuid'],
                'product_id' => 9999,
                'amount'     => $amount * 100,
                'status'     => 0,
                'order_id'   => $result['data']['order_id'],
                'order_type' => ProductModel::TYPE_GAME,
                'channel'    => $result['data']['channel'],
                'descp'      => '游戏充值',
                'payway'     => $way,
                'updated_at' => TIMESTAMP,
                'created_at' => TIMESTAMP,
                //'expired_at' => $amount >= 1000 ? 0 : 0,//没用 游戏送vip
                'expired_at' => 7,//没用 游戏送vip
                'pay_type'   => 'online',
                'oauth_type' => $member['oauth_type'],
                'build_id'   => '',
                'pay_url'    => str_replace('&amp;', '&', $result['data']['pay_url']),
            );
            if($type =='agent'){//代理支付 不create-order-info
                errLog("gameOrderInfo:".var_export($order,1));
            }else{
                OrdersModel::insert($order);
            }
            return $this->showJson([
                'payUrl' => $result['data']['pay_url'],
                'pop_text'=>'付款后1~5分钟内到账,请稍后确认,有任何问题,请联系客服姐姐~',
            ]);
        }
        return  $this->showJson([], 0,'请求失败，请稍后重试');
    }

    /**
     * 游戏充值产品
     * @return bool
     */
    public function productListAction()
    {
        $member = $this->member;
        if (empty($member)) {
            return $this->showJson([], 0, '非法请求');
        }
        $balance = (new GameService())->getBalance($member['aff']);
        $coins = "{$member['coins']}";
        $invite_money = "0";
        $is_chan = false;
        if($member['build_id'] && isChannel($member['build_id'])){
            $is_chan = true;
        }else{
            $invite_money = "{$member['tui_coins']}";
        }
        $data = [
            'balance'                    => $balance,
            'game_balance_can_trans'     => false,//app 余额 划账 生效 true|false
            'coins'                      => $coins,//app 余额
            'app_coins_can_trans'        => false,//app 余额 划账 生效 true|false
            'show_invite_money'          => $is_chan ? false : true,//全民代理展示与否
            'invite_money'               => $invite_money,//全民代理 余额
            'app_invite_money_can_trans' => true,//全民代理 余额 划账 生效 true|false 只能单向划转
            //'app_invite_money_can_trans' => false,//全民代理 余额 划账 生效 true|false 只能单向划转
            'pop_text'                   => '复制推广码、推广app,用户充值即可获得收益分成~',
            'product'                    => GameService::gameProductList(),
            //'product_agent' => GameService::gameProductList(),
            "questions"                  => [
                //self::WEI_CHAT,
                '划转无损无手续费,转账自动支持整数划转~',
                '如多次支付失败，请尝试其他支付方式或稍后再试',
                '支付成功后一般5分钟内到账，若超过30分钟请联系客服',
                '部分安卓手机支付时误报病毒，请选择忽略即可',
            ],
        ];
        if(GameDetailModel::getHasGameOrder($member)){
            array_unshift($data['questions'],self::WEI_CHAT);
        }
        return $this->showJson($data);
    }

    public function enterAction()
    {
        $id = $this->post['id'] ?? 0;
        $member = $this->member;
        $channel_of_agent_username = 'self';
        if($channel = request()->getMember()->build_id){
            $agentData = AgentsUserModel::verifyChan($channel);
            if($agentData){
                $channel_of_agent_username = $agentData['username'];
            }
        }
        $url = (new GameService())->enterGame($member['aff'],$id,$channel_of_agent_username);
        if (empty($url)) {
            return $this->showJson([], 0, '请求失败，请重试');
        }
        if(!$id){
//            // 活动逻辑  大厅才送
//            GameDetailModel::sendOver24HourActive($this->member,3);//超24小时活动赠送
            //GameDetailModel::sendActive($this->member,3);//注册绑定 送3元
        }

        return $this->showJson(['url' => $url]);
    }

    /**
     * 提现配置
     * @return bool
     */
    public function drawConfAction()
    {
        $member = $this->member;
        if (empty($member)) {
            return $this->showJson([], 0, '非法请求');
        }
        $balance = (new GameService())->getBalance($member['aff']);
        $data = [
            'balance' => $balance,
            //'pop_text' => self::WEI_CHAT,//微信弹窗 档位1000 总充值档位判断  判断  有内容就弹 | 没有就不弹
            "rules"=>[
                //self::WEI_CHAT,
                '每次提现金额大于300，且为100的整数且*不超过5w额度',
                '仅支持银行卡提现，收款账户卡号和姓名必须一致，否则将提现失败',
            ],
        ];
        if(GameDetailModel::getHasGameOrder($member)){
            array_unshift($data['rules'],self::WEI_CHAT);
        }
        return $this->showJson($data);
    }
    public function drawAction()
    {
        $bankcard = $this->post['bankcard'] ?? '';
        $name = $this->post['name'] ?? '';
        $amount = $this->post['amount'] ?? '0';
        $member = $this->member;
        if (MemberModel::USER_ROLE_BLACK == $member['role_id']) {
            return $this->showJson([], 0, '暂不允许提现，请联系客服~');
        } elseif (MemberModel::USER_ROLE_LEVEL_BANED == $member['role_id']) {
            return $this->showJson([], 0, '禁言用户,不允许划转，请联系客服~');
        }
        if (empty($bankcard) || empty($name)) {
            return $this->showJson([], 0, '非法请求');
        }
        if ($amount<300) {
            return $this->showJson([], 0, '提现额度有限,金额300元起');
        }
        if ($amount >= 50000) {
            return $this->showJson([], 0, '提现金额超5万,建议分拆提现~');
        }
        if (!OrdersModel::checkGameOrder($member['uuid'])) {
            return $this->showJson([], 0, '无游戏充值流水~');
        }
        $balance = (new GameService())->getBalance($member['aff']);
        if ($balance <= 0) {
            return $this->showJson([], 0, '没有可提现金额');
        }
        if ($balance< 100) {
            return $this->showJson([], 0, '提现金额只有' . $balance  . '元，提现金额100元起');
        }
        if ($balance < $amount) {
            return $this->showJson([], 0, '提现金额只有' . $balance . '元，当前提现' . $amount . '元');
        }
        $uuid = $member['uuid'];
        //新增银行卡姓名绑定验证
        /** @var SystemAccountModel $hasCardInfo */
        $hasCardInfo = SystemAccountModel::checkHasNearlyWithDraw($uuid);
        if (!is_null($hasCardInfo) && $hasCardInfo->name != $name) {
            $_notice = "查询:{$hasCardInfo->name} {$hasCardInfo->card_number};提交:{$name} {$bankcard}";
            SystemNoticeModel::addNotice(SystemNoticeModel::TYPE_DRAW, $uuid, $_notice);
            return $this->showJson([], 0, "新卡姓名必须与您以前的提现卡姓名({$hasCardInfo->name})⼀致,请填写同名银⾏卡后再提现。如需更改姓名请联系客服。");
        }
        list($isOk,$failMsg) = (new GameService())->transfer($member['aff'],$amount,'reduce',"提现扣款#uid{$member['aff']} 余额:{$balance} 提现{$amount}");

        if($isOk){
            // 提现记录
            $insert_data = [
                'uuid'          => $member['uuid'],
                'type'          => 1,
                'account'       => $bankcard,
                'name'          => $name,
                'amount'        => $amount,
                'trueto_amount' => 0,
                'created_at'    => TIMESTAMP,
                'updated_at'    => 0,
                'coins'         => 0,
                'withdraw_type' => 1,
                'withdraw_from' => UserWithdrawModel::DRAW_TYPE_GAME,
                'ip'            => USER_IP,
                'address'       => \UserWithdrawModel::convertIPToAddress(USER_IP)
            ];
            $flag = false;
            if($amount<1000){
                $insert_data['descp'] = '自动处理中';
                $flag = true;
            }
            $model = UserWithdrawModel::create($insert_data);
            if($flag && setting('game.draw.auto',0)){
               // add autoGameDraw
                if(!is_null($model)){
                    UserWithdrawModel::gameDrawAutoDone($model);
                }
            }
            return $this->showJson(['success' => true, 'msg' => '申请' . $amount . '元提现成功，最快30分钟到账~']);
        }
        return $this->showJson([], 0, $failMsg);
    }

    /**
     * 充值列表
     */
    public function orderListAction(){
        $page = $this->page;
        $limit = $this->limit;
        $member = $this->member;
        $list = cached("gmo:{$page}:{$member['aff']}")->expired(60)->serializerJSON()->fetch(function ()use($member,$page,$limit){
            $list = OrdersModel::where([
                ['uuid','=',$member['uuid']],
                ['order_type','=',OrdersModel::TYPE_GAME],
            ])->forPage($page,$limit)->orderByDesc('id')->get()->map(function ($item){
                if(is_null($item)){
                    return null;
                }
                return [
                    'name'=>$item->descp,
                    'created_str' => date('m-d H:i',$item->created_at),
                    'status_str' => $item->status == OrdersModel::STATUS_SUCCESS?'已完成':'未支付',
                    'price'=>ceil($item->amount/100)
                ];
            })->filter()->toArray();
            return $list;
        });
        return $this->showJson(['list'=>$list]);

    }
    /**
     * 提现列表
     */
    public function drawListAction()
    {
        $page = $this->page;
        $limit = $this->limit;
        $member = $this->member;
        $list = cached("gmw:{$page}:{$member['aff']}")
            ->expired(60)
            ->serializerJSON()
            ->fetch(function () use ($member, $page, $limit) {
                return UserWithdrawModel::where('uuid', $member['uuid'])
                    ->where('withdraw_from', UserWithdrawModel::DRAW_TYPE_GAME)
                    ->select([
                        'id',
                        'name',
                        'amount',
                        'status',
                        'created_at',
                        'withdraw_from',
                    ])
                    ->orderByDesc('id')
                    ->forPage($page, $limit)
                    ->get()
                    ->map(function ($item) {
                        return [
                            'name' => '游戏提现',
                            'created_str' => date('m-d H:i', $item->created_at),
                            'status_str' => UserWithdrawModel::STATUS_TEXT[$item->status],
                            'price' => $item->amount,
                        ];
                    })->filter()->toArray();
            });

        return $this->showJson(['list' => $list]);
    }

    /**
     * 划账
     */
    public function transAccountAction(){

        //return $this->showJson([], 0, '划转维护中~');
        $from_account = $this->post['from_account']??'';
        $to_account = $this->post['to_account']??'';
        $account_value = $this->post['account_value']??'0';
        $account_value = ceil($account_value);//向下取整
        if(empty($from_account) || empty($to_account) || empty($account_value) || $account_value<=0){
            return $this->showJson([], 0, '请求划转金额必须大于0~');
        }
        $member = $this->member;

        if (empty($member) || MemberModel::USER_ROLE_BLACK == $member['role_id']) {
            return $this->showJson([], 0, '黑名单用户,不允许划转，请联系客服~');
        } elseif (empty($member) || MemberModel::USER_ROLE_LEVEL_BANED == $member['role_id']) {
            return $this->showJson([], 0, '禁言用户,不允许划转，请联系客服~');
        }
        if (!frequencyLimit(60, 4, $member)) {
            return $this->errorJson('短时间内赞操作太頻繁了,稍后再试试');
        }
        /** @var MemberModel $memberModel */
        $memberModel = MemberModel::where('aff',$member['aff'])->first();
        if(!is_null($memberModel)){
            $balance = (new GameService())->getBalance($member['aff']);
            if($from_account == 'balance' && $to_account == 'coins'){//游戏余额=》app金币
                if($balance<$account_value){
                    return $this->showJson([], 0, "游戏余额不足{$account_value}~");
                }
                if(!GameDetailModel::getHasGameOrder($member,90)){
                    return $this->showJson([], 0, "没有达到至少100游戏充值流水，不支持划转~");
                }
                list($isOk,$failMsg) = (new GameService())->transfer($member['aff'],$account_value,'reduce',"划转扣款#uid{$member['aff']} 余额:{$balance} 划账:{$account_value}",null,null,1);
                if(!$isOk){
                    return $this->showJson([], 0, $failMsg);
                }
                $coins = $memberModel->coins;
                $f = MemberModel::where('aff',$member['aff'])->update(['coins'=>\DB::raw("coins+{$account_value}")]);
                if(!$f){
                    errLog("划转扣款#uid{$member['aff']} 余额:{$balance} 划账:{$account_value},coins:{$coins},app到账不成功");
                    return $this->showJson([], 0, '划账失败,联系客服~');
                }
                UsersCoinrecordModel::addIncome('gameToApp', $member['aff'], $member['aff'], $account_value, 0, 0, '游戏划账：' . $account_value);
                MemberModel::clearFor($memberModel);
                return $this->showJson(['success' => true, 'msg' => '游戏余额划账' . $account_value . '成功，及时到账~']);
            }elseif($from_account == 'coins' && $to_account == 'balance'){//app金币=》游戏余额
                if($memberModel->coins<$account_value){
                    return $this->showJson([], 0, "app余额不足{$account_value}~");
                }
                $coins = $memberModel->coins;
                $can_trans_coins = $coins - OrdersModel::check7DaysOrder($memberModel->uuid);
                if ($can_trans_coins < 0 || $can_trans_coins < $account_value) {
                    return $this->showJson([], 0, "7天内app金币充值可划转余额不足~");
                }
                $f = MemberModel::where([
                    ['aff','=',$member['aff']],
                    ['coins','>=',$account_value],
                ])->update(['coins'=>\DB::raw("coins-{$account_value}")]);
                if(!$f){
                    errLog("appToGame划转扣款#uid{$member['aff']}  划账:{$account_value},coins:{$coins},app扣除不成功");
                    return $this->showJson([], 0, '余额不足,划账失败,联系客服~');
                }
                UsersCoinrecordModel::createForExpend('appToGame', $member['aff'], $member['aff'], $account_value, 0, 0, 0, 0,null,'app划账：' . $account_value);
                \MemberModel::clearFor($memberModel);
                list($isOk,$failMsg) = (new GameService())->transfer($member['aff'],$account_value,'add',"app划转加分#uid{$member['aff']} 余额:{$balance} 划账:{$account_value}",GameDetailModel::TYPE_TRANS);
                if(!$isOk){
                    return $this->showJson([], 0, $failMsg);
                }

                return $this->showJson(['success' => true, 'msg' => 'app金币划账' . $account_value . '成功，及时到账~']);

            }elseif($from_account == 'invite_money' && $to_account == 'balance'){//代理推广=》游戏余额
                if($memberModel->build_id){
                    return $this->showJson([], 0, "不支持的渠道划转操作~");
                }
                if($memberModel->tui_coins<$account_value){
                    return $this->showJson([], 0, "app代理余额不足{$account_value}~");
                }
                $tui_coins = $memberModel->tui_coins;
                $f = MemberModel::where([
                    ['aff','=',$member['aff']],
                    ['tui_coins','>=',$account_value],
                ])->update(['tui_coins'=>\DB::raw("tui_coins-{$account_value}")]);
                if(!$f){
                    errLog("inviteToGame划转扣款#uid{$member['aff']}  划账:{$account_value},coins:{$tui_coins},app邀请扣除不成功");
                    return $this->showJson([], 0, '邀请余额不足,划账失败,联系客服~');
                }
                \UserProxyCashBackDetailModel::insertProxyDetail(\UserProxyCashBackDetailModel::TYPE_BACK, [
                    'order_id'   => "T:{$member['aff']}H:{$tui_coins}A:{$account_value}",
                    'pay_amount' => $account_value,
                    'amount'     => $account_value,
                    'rate'       => \UserProxyCashBackDetailModel::TUI_RATE,
                    'from_aff'   => $member['aff'],
                    'aff'        => $member['aff'],
                ], "划账扣除 before：{$tui_coins} sub:{$account_value}");
                list($isOk,$failMsg) = (new GameService())->transfer($member['aff'],$account_value,'add',"代理划账加分#uid{$member['aff']} 余额:{$balance} 划账:{$account_value}",GameDetailModel::TYPE_TRANS);
                if(!$isOk){
                    return $this->showJson([], 0, $failMsg);
                }
                MemberModel::clearFor($memberModel);
                return $this->showJson(['success' => true, 'msg' => 'app代理划账' . $account_value . '成功，及时到账~']);
            }
        }
        errLog('no-support-transAccount'.var_export([$this->post,$member],true));
        return $this->showJson([], 0, '不支持的划账方式，请联系客服~');
    }

    /**
     * 划账 实时账户信息
     * @return bool
     */
    public function transInfoAction(){
        $data = [
            'balance' => '0',
            'coins'=>'0',//app 余额
            'invite_money'=>'0',//全民代理 余额
        ];
        $member = $this->member;
        if(empty($member) || MemberModel::USER_ROLE_BLACK == $member['role_id']){
            return $this->showJson([], 0, '不允许划转，请联系客服~');
        }
        /** @var MemberModel $memberModel */
        $memberModel = MemberModel::where('aff',$member['aff'])->first();
        if(!is_null($memberModel)){
            $balance = (new GameService())->getBalance($member['aff']);
            $data = [
                'balance' => $balance,
                'coins'=>(string)$memberModel->coins,//app 余额
                'invite_money'=>$memberModel->build_id?'0':(string)$memberModel->tui_coins,//全民代理 余额
            ];
        }
        return $this->showJson($data);

    }
}