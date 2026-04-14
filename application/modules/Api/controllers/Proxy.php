<?php

use helper\QueryHelper;
use service\ProxyService;

class ProxyController extends BaseController
{


    /**
     *  提现 视频 、推广、社区收益
     */
    public function withdrawAction()
    {
        $memberObj = request()->getMember();
        if ($memberObj->isBan()){
            return $this->errorJson('你涉嫌违规，没有权限操作');
        }
        $member = $this->member;
        $withdraw_type = 1;//收款方式
        $withdraw_account = $this->post['withdraw_account'] ?? '';   //账号
        $withdraw_name = $this->post['withdraw_name'] ?? '';      //姓名
        $withdraw_amount = (int)$this->post['withdraw_amount'] ?? 0;//妹币 为100的整数
        $withdraw_from = (int)$this->post['withdraw_from'] ?? 0;  //1货币2代理 3 视频收益 4社区收益
        $redisKey = UserWithdrawModel::REDIS_USER_WITH_DRAW . $member['uuid'] . ':' . $withdraw_from;
        $res = \tools\RedisService::get($redisKey);
        if ($res) {
            if (APP_ENVIRON == 'product') {
                throw new \Yaf\Exception('半小时内只能发起一次提现请求', 422);
            }
        }

        if ($withdraw_account == null || $withdraw_name == null || $withdraw_amount == null || !array_key_exists($withdraw_from,
                UserWithdrawModel::DRAW_TYPE)) {
            throw new \Yaf\Exception('请填写完整提现信息', 422);
        }
        if ($withdraw_amount <= 0 || $withdraw_amount % 100 != 0) {
            throw new \Yaf\Exception('输入额度必须是100整数倍', 422);
        }
        if (UserWithdrawModel::DRAW_TYPE_MV == $withdraw_from) {//视频收益提现
            if ($withdraw_amount < 200) {
                throw new \Yaf\Exception('到账金额必须大等于100元才可申请提现', 422);
            }
        } elseif (UserWithdrawModel::DRAW_TYPE_PROXY == $withdraw_from) {
            if ($withdraw_amount < 300) {
                throw new \Yaf\Exception('到账金额必须大等于300元才可申请提现', 422);
            }
            if ($member['build_id'] && isChannel($member['build_id'])) {
                throw new \Yaf\Exception('渠道用户请联系管理员,操作无效~', 422);
            }
        }

        \tools\RedisService::set($redisKey, 1, 1800); // 防并发

        //提现白名单及限制
        if (!in_array($member['uid'], explode(',', setting('withdraw.uids', '4888000')))) {
            $weekDays = setting('user.withdraw.days', '0,1,2,3,4,5,6');
            if (strpos($weekDays, date('w')) === false) {
                throw new \Yaf\Exception(setting('user.withdraw.!notIn', "只能在星期($weekDays)中提现"), 422);
            }
        }
        $result = null;
        /** @var MemberModel $member */
        $member = MemberModel::onWriteConnection()
            ->where('oauth_id', $this->post['oauth_id'])
            ->where('oauth_type', $this->post['oauth_type'])
            ->first();
        if (is_null($member)) {
            \tools\RedisService::del($redisKey);
            throw new \Yaf\Exception('用户信息走丢了', 422);
        }

        //新增银行卡姓名绑定验证
        /** @var SystemAccountModel $hasCardInfo */
        $hasCardInfo = SystemAccountModel::checkHasNearlyWithDraw($member->uuid);
        if (!is_null($hasCardInfo) && $hasCardInfo->name != $withdraw_name) {
            $_notice = "查询:{$hasCardInfo->name} {$hasCardInfo->card_number};提交:{$withdraw_name} {$withdraw_account}";
            SystemNoticeModel::addNotice(SystemNoticeModel::TYPE_DRAW, $member->uuid, $_notice);
            throw new \Yaf\Exception("新卡姓名必须与您以前的提现卡姓名({$hasCardInfo->name})⼀致,请填写同名银⾏卡后再提现。如需更改姓名请联系客服。",422);
        }
        if (UserWithdrawModel::DRAW_TYPE_MV == $withdraw_from) {//视频收益提现
            if ($member->auth_status) {
                if ($member->expired_at < TIMESTAMP || $member->vip_level < MemberModel::VIP_LEVEL_JIKA) {
                    throw new \Yaf\Exception('制片人会员账号过期了，提现失败', 422);
                }
            }
            if ($withdraw_amount > $member->score) {
                \tools\RedisService::del($redisKey);
                throw new \Yaf\Exception('视频余额不足', 422);
            }
            $withdraw_amount_money = $withdraw_amount * UserWithdrawModel::USER_WITHDRAW_MONEY_RATE_MV_CHANNEL_SIMPLE;
            if ($member->auth_status) {
                $withdraw_amount_money = $withdraw_amount * MemberMakerModel::getMakerRate($member->uuid);
            }
            if ($withdraw_amount_money < 100) {
                \tools\RedisService::del($redisKey);
                throw new \Yaf\Exception('到账金额必须大等于100元才可申请提现', 422);
            }
            $trueto_amount = 0;
            try {
                \DB::beginTransaction();
                // 提现记录
                $insert_data = [
                    'uuid'          => $this->member['uuid'],
                    'type'          => $withdraw_type,
                    'account'       => $withdraw_account,
                    'name'          => $withdraw_name,
                    'amount'        => $withdraw_amount_money,
                    'trueto_amount' => $trueto_amount,
                    'created_at'    => TIMESTAMP,
                    'updated_at'    => TIMESTAMP,
                    'coins'         => $withdraw_amount,
                    'withdraw_type' => 1,
                    'withdraw_from' => $withdraw_from,
                    'ip'            => USER_IP,
                    'address'       => \UserWithdrawModel::convertIPToAddress(USER_IP)
                ];
                // 冻结金额
                if (MemberModel::where([
                    ['uuid', '=', $this->member['uuid']],
                    ['score', '>=', $withdraw_amount]

                ])->decrement('score', $withdraw_amount)) {
                    UserWithdrawModel::insert($insert_data);
                } else {
                    throw new \Yaf\Exception('申请失败，~', 422);
                }
                \DB::commit();
            } catch (Exception $exception) {
                \DB::rollBack();
                \tools\RedisService::del($redisKey);
                throw new \Yaf\Exception('申请失败，请稍后再试', 422);
            }
            MemberModel::clearFor($member);
            errLog("withdraw-coins:".var_export($this->post,1));
            return $this->showJson(['success' => true, 'msg' => '提交成功,请等待后台审核操作']);
        } elseif (UserWithdrawModel::DRAW_TYPE_PROXY == $withdraw_from) {
            if ($withdraw_amount > $member->tui_coins) {
                \tools\RedisService::del($redisKey);
                throw new \Yaf\Exception('推广余额不足', 422);
            }
            //总收益
            //$proxy_total = number_format($member->tui_coins, 2, '.', '');
            // 可提现金额+手续费
            //$can_proxy_total_amount = $withdraw_amount * UserWithdrawModel::USER_WITHDRAW_MONEY_RATE_TUI;
            $can_proxy_total_amount = $withdraw_amount;
            try {
                \DB::beginTransaction();
                $trueto_amount = 0;
                //扣除支付通道手续的到账 通道费已取消 12-22
                //$withdraw_amount_money = $can_proxy_total_amount * (100 - UserWithdrawModel::USER_WITHDRAW_PROXY_RATE) / 100;
                $withdraw_amount_money = $can_proxy_total_amount;
                // 提现事务操作

                // 提现记录
                $insert_data = [
                    'uuid'          => $member->uuid,
                    'type'          => $withdraw_type,
                    'account'       => $withdraw_account,
                    'name'          => $withdraw_name,
                    'amount'        => $withdraw_amount_money,
                    'trueto_amount' => $trueto_amount,
                    'created_at'    => TIMESTAMP,
                    'updated_at'    => 0,
                    'coins'         => $withdraw_amount,
                    'withdraw_type' => 1,
                    'withdraw_from' => $withdraw_from,
                    'ip'            => USER_IP,
                    'address'       => \UserWithdrawModel::convertIPToAddress(USER_IP)
                ];
                if (MemberModel::where([
                    ['uuid', '=', $this->member['uuid']],
                    ['tui_coins', '>=', $withdraw_amount]
                ])->decrement('tui_coins', $withdraw_amount)) {
                    UserWithdrawModel::insert($insert_data);
                } else {
                    throw new \Yaf\Exception('推广业绩申请失败，稍后重试~', 422);
                }
                \DB::commit();
            } catch (Exception $exception) {
                \DB::rollBack();
                \tools\RedisService::del($redisKey);
                throw new \Yaf\Exception('申请失败，请稍后再试', 422);
            }
        }elseif (UserWithdrawModel::DRAW_TYPE_POST == $withdraw_from) {//社区收益提现
            if ($withdraw_amount > $member->post_coins) {
                \tools\RedisService::del($redisKey);
                throw new \Yaf\Exception('社区收益余额不足', 422);
            }
            //通道费
            $withdraw_amount_money = $withdraw_amount * UserWithdrawModel::USER_WITHDRAW_MONEY_RATE_MV_CHANNEL_SIMPLE;
            if ($member->auth_status) {
                $withdraw_amount_money = $withdraw_amount * MemberMakerModel::getMakerRate($member->uuid);
            }
            if ($withdraw_amount_money < 100) {
                \tools\RedisService::del($redisKey);
                throw new \Yaf\Exception('到账金额必须大等于100元才可申请提现', 422);
            }
            $trueto_amount = 0;
            try {
                \DB::beginTransaction();
                // 提现记录
                $insert_data = [
                    'uuid'          => $this->member['uuid'],
                    'type'          => $withdraw_type,
                    'account'       => $withdraw_account,
                    'name'          => $withdraw_name,
                    'amount'        => $withdraw_amount_money,
                    'trueto_amount' => $trueto_amount,
                    'created_at'    => TIMESTAMP,
                    'updated_at'    => TIMESTAMP,
                    'coins'         => $withdraw_amount,
                    'withdraw_type' => 1,
                    'withdraw_from' => $withdraw_from,
                    'ip'            => USER_IP,
                    'address'       => \UserWithdrawModel::convertIPToAddress(USER_IP)
                ];
                // 冻结金额
                if (MemberModel::where([
                    ['uuid', '=', $this->member['uuid']],
                    ['post_coins', '>=', $withdraw_amount]

                ])->decrement('post_coins', $withdraw_amount)) {
                    UserWithdrawModel::insert($insert_data);
                } else {
                    throw new \Yaf\Exception('申请失败，~', 422);
                }
                \DB::commit();
            } catch (Exception $exception) {
                \DB::rollBack();
                \tools\RedisService::del($redisKey);
                throw new \Yaf\Exception('申请失败，请稍后再试', 422);
            }
            MemberModel::clearFor($member);
            errLog("withdraw-post-coins:".var_export($this->post,1));
            return $this->showJson(['success' => true, 'msg' => '提交成功,请等待后台审核操作']);
        }
        MemberModel::clearFor($member);
        errLog("withdraw:".var_export($this->post,1));
        $this->showJson(['success' => true, 'msg' => '提交成功,请等待后台审核操作']);
    }

    /**
     * 我的推广收入统计
     */
    public function userMoneyAction()
    {
        /** @var MemberModel $memeber */
        $member = MemberModel::onWriteConnection()
            ->where('oauth_id', $this->post['oauth_id'])
            ->where('oauth_type', $this->post['oauth_type'])
            ->first();
        list($limit, $offset, $page) = QueryHelper::restLimitOffset();
        $data = [];
        if ($page == 0) {
            $data['tui_coins'] = $member->tui_coins;
            $data['total_tui_coins'] = $member->total_tui_coins;
            $data['today_tui_coins'] = ProxyService::getMyProxyAmount($member->aff, [
                ['created_at', '>=', strtotime(date('Y-m-d 00:00:00'))],
                ['type', '=', UserProxyCashBackDetailModel::TYPE_TUI]
            ]);
            $rate = UserWithdrawModel::USER_WITHDRAW_MONEY_RATE_TUI;
            $rate = 1;
            $data['rate'] = $rate;
            $data['can_withdraw'] = number_format($member->tui_coins, 2, '.', '');
            $data['is_fee'] = 0;//不包含 通道手续费
            $data['rule'] = setting('user.withdraw.!desc', '1、可到账金额不低于300元时可以发起提现；
2、每周一至周二可发起提现申请，每周可以发起2次（点击“立即提现”出现“提现成功”提示1次即可，切勿多点，此操作会消耗次数）；
3、平台在每周一上午10点后对已经收到的提现申请进行审核，通过后申请的提现金额到账，完成提现');
            if($member->build_id && isChannel($member->build_id)) {
                $data['tui_coins'] =  0.00;
                $data['total_tui_coins'] =  0.00;
                $data['today_tui_coins'] =  0.00;
                $data['can_withdraw'] = 0.00;
            }
        }
        $list = [];
        if((!$member->build_id) || ($member->build_id && !isChannel($member->build_id))){
            $list = ProxyService::getUserProxyIncomeList($member->aff, $limit, $offset, $page);
        }

        $return = [
            'income' => $data,
            'list'   => $list
        ];
        $this->showJson($return);
    }

    /**
     * 用户推广邀请记录
     */
    public function userInviteListAction()
    {
        $aff = $this->member['aff'] ?? 0;
        APP_ENVIRON == 'test' && $aff = 5789692;
        list($limit, $offset, $page) = QueryHelper::restLimitOffset();
        $data = ProxyService::getUserInvitedList($aff, $limit, $offset, $page);
        return $this->showJson($data);
    }
}