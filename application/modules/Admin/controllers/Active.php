<?php

/**
 * 开播
 * Class UserController
 */

class ActiveController extends AdminController
{
    var $type = array(
        '0' => '平均',
        '1' => '手气',
    );
    var $type_grant = array(
        '0' => '立即',
        '1' => '延迟',
    );
    var $coin_type = [
        'income' => '收入',
        'expend' => '支出'
    ];
    var $gift_type = array(
        "loginAward"         => "登陆奖励",
        "sendgift"           => "赠送礼物",
        "sendbarrage"        => "弹幕",
        "loginbonus"         => "登录奖励",
        "buyvip"             => "购买VIP",
       // "buycar"             => "购买坐骑",
       // "buyliang"           => "购买靓号",
        //        'game_bet'=>'游戏下注',
        //        'game_return'=>'游戏退还',
        //        'game_win'=>'游戏获胜',
        //        'game_banker'=>'庄家收益',
        //        'set_deposit'=>'上庄扣除',
        //        'deposit_return'=>'下庄退还',
        'recharge'           => '购买充值',
        'roomcharge'         => '房间扣费',
        'timecharge'         => '计时扣费',
       // 'sendred'            => '发送红包',
        //'robred'             => '抢红包',
        //'buyguard'           => '开通守护',
        'reg_reward'         => '注册奖励',
        //'spring'             => '春鸡活动',
        'buyvipsend'         => '冲vip金币',
        'turntable'          => '转盘日志',
        'turntable_activity' => '转盘活动',
        'transfer'           => '转账',
        'buyMessage'         => '买私信',
        'buyTalkTime'        => '购买匹配时长',
        'buyMh'        => '漫画购买',
        'buyPost'        => '社区解锁',
        'lotteryFrq'    => '抽奖次数奖励',
        'lottery'    => '抽奖奖励',
        //        'buy_gold_egg'=>'购买金蛋',
        //        'buy_silver_egg'=>'购买银蛋',
        //        'buy_copper_egg'=>'购买铜蛋'
    );

    public function init()
    {
        parent::init();

    }

    public function coin_recordAction()
    {
        $query_link = 'd.php?mod=active&code=coin_record';
        $query = UsersCoinrecordModel::orderBy('id', 'desc')->offset($this->pageStart)->limit($this->perPageNum);
        $uid = $this->get['uid'] ?? '';
        if ($uid) {
            $query_link .= '&uid=' . $uid;
            $query->where('uid', $uid);
        }
        $touid = $this->get['touid'] ?? '';
        if ($touid) {
            $query_link .= '&touid=' . $touid;
            $query->where('touid', $touid);
        }
        $start_time = $this->get['start_time'] ?? date('Y-m-d');
        if ($start_time) {
            $query_link .= '&start_time=' . $start_time;
            $query->where('addtime', '>=', strtotime($start_time));
        }
        $end_time = $this->get['end_time'] ?? '';
        if ($end_time) {
            $query_link .= '&end_time=' . $end_time;
            $query->where('addtime', '<=', strtotime($end_time . " 23:59:59"));
        }
        $action = $this->get['action'] ?? '';
        if ($action) {
            $query_link .= '&action=' . $action;
            $query->where('action', $action);
        }
        $type = $this->get['type'] ?? '';
        if ($type) {
            $query_link .= '&coin_type=' . $type;
            $query->where('type', $type);
        }
        $giftid = $this->get['giftid'] ?? '';
        if ($giftid) {
            $query_link .= '&giftid=' . $giftid;
            $query->whereIn('giftid', explode(',', $giftid));
        }
        $data = $query->with(['withMember', 'withLiveMember'])->get()->map(function ($item) {
            /** @var UsersCoinrecordModel $item */
            $item->totalcoin = $item->totalcoin / HT_JE_BEI;
            return $item;
        });

        foreach ($data as $key => $val) {
            if ($val['action'] == 'sendgift') {
                $data[$key]->giftname = '';
            } elseif ($val['action'] == 'loginbonus') {
                $data[$key]->giftname = '领取金币';
            } elseif ($val['action'] == 'sendbarrage') {
                $data[$key]->giftname = '弹幕';
            } elseif ($val['action'] == 'roomcharge') {
                $data[$key]->giftname = '房间扣费';
            } elseif ($val['action'] == 'timecharge') {
                $data[$key]->giftname = '计时扣费';
            } elseif ($val['action'] == 'buycar') {
                $data[$key]->giftname = '';
            } elseif ($val['action'] == 'buyliang') {
                $data[$key]->giftname = '';
            } elseif ($val['action'] == 'sendred') {
                $data[$key]->giftname = '发送红包';
            } elseif ($val['action'] == 'robred') {
                $data[$key]->giftname = '抢红包';
            } elseif ($val['action'] == 'buyguard') {
                $data[$key]->giftname = '';
            } elseif ($val['action'] == 'reg_reward') {
                $data[$key]->giftname = '注册奖励';
            } elseif ($val['action'] == 'transfer') {
                $data[$key]->giftname = '转账';
            } elseif ($val['action'] == 'buyTalkTime') {
                $data[$key]->giftname = '购买匹配时长';
            } else {
                $data[$key]->giftname = '未知';
            }
        }
        $page_arr['html'] = sitePage($query_link);
        $this->getView()
            ->assign('data', $data)
            ->assign('coin_type', $this->coin_type)
            ->assign('gift_type', $this->gift_type)
            ->assign('uid', $uid)
            ->assign('giftid', $giftid)
            ->assign('touid', $touid)
            ->assign('type', $type)
            ->assign('action', $action)
            ->assign('start_time', $start_time)
            ->assign('end_time', $end_time)
            ->assign('page_arr', $page_arr)
            ->display('member/coin_recode.phtml');
    }


    /**
     * 金币日志统计汇总
     */
    public function tongjiAction()
    {
        if (!$this->getRequest()->isXmlHttpRequest()) {
            return $this->showJson([], 0);
        }
        $where = [];
        if ($this->post['type']) {
            $where[] = ['type', '=', $this->post['type']];
        }
        if ($this->post['action']) {
            $where[] = ['action', '=', $this->post['action']];
        }
        if ($this->post['uid']) {
            $where[] = ['uid', '=', $this->post['uid']];
        }
        if ($this->post['touid']) {
            $where[] = ['touid', '=', $this->post['touid']];
        }
        if ($this->post['start_time']) {
            $where[] = ['addtime', '>=', strtotime($this->post['start_time'])];
        }
        if ($this->post['end_time']) {
            $where[] = ['addtime', '<=', strtotime($this->post['end_time'] . " 23:59:59")];
        }
        if (!$where) {
            return $this->showJson([], 0);
        }
        /**[mod] => active
         * [code] => coin_record
         * [type] =>
         * [action] => buyvip
         * [start_time] => 2020-05-20
         * [end_time] =>
         * [uid] =>
         * [touid] => */
        $giftid = [];
        if ($this->post['giftid'] ?? false) {
            $giftid = explode(',', $this->post['giftid']);
        }
        $query = UsersCoinrecordModel::where($where);
        if (!empty($giftid)) {
            $query->whereIn('giftid', $giftid);
        }
        $data = [
            'number' => $query->count('id'),
            'total'  => $query->sum('totalcoin'),
        ];
        return $this->showJson($data);

    }
}



