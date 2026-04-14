<?php


use helper\QueryHelper;
use service\MvService;

class VideoincomeController extends BaseController
{

    /**
     * 视频收益明细
     * @author xiongba
     * @date 2020-07-04 10:45:44
     */
    public function videoIncomeListAction()
    {
        $touid = $this->member['uid'];
        $vid = $this->post['vid']??0;
        $where = [
            'touid'  => $touid,
            'action' => 'buymv',
            'type'   => 'expend',
        ];
        $vid && $where['showid'] = $vid;//购买时已经写入
        list($limit, $offset,$page) = \helper\QueryHelper::restLimitOffset();
        $data = cached("vinlist:{$vid}:{$page}")->serializerJSON()
            ->setSaveEmpty(true)
            ->expired(500)->fetch(function ()use($where,$limit,$offset){
            $data = UsersCoinrecordModel::where($where)->where('mark','=',0)
                ->select(['id', 'uid', 'touid', 'showid', 'addtime', 'totalcoin', 'type', 'action','desc'])
                ->with('withUser:uid,nickname,thumb,vip_level,sexType,expired_at')
                ->orderByDesc('id')
                ->limit($limit)
                ->offset($offset)
                ->get();
            return $data?$data->toArray():[];
        });
        $this->showJson($data);
    }

    /**
     * 我的视频金币可提现收益
     */
    public function videoProfitAction()
    {
        /** @var MemberModel $member */
        $member = MemberModel::onWriteConnection()
            ->where('oauth_id', $this->post['oauth_id'])
            ->where('oauth_type', $this->post['oauth_type'])
            ->first();
        $data['score'] = $member->score;
        $data['score_total'] = $member->score_total;
        $data['today_score'] = UsersCoinrecordModel::getTodayProfit($member->uid);  // 累计收入;
        $data['rate']= (string)(UserWithdrawModel::USER_WITHDRAW_MONEY_RATE_MV-UserWithdrawModel::USER_WITHDRAW_MONEY_RATE_MV_CHANNEL);
        if ($member->auth_status) {
            $data['rate'] = MemberMakerModel::getMakerRate($member->uuid);
        }
        //$data['can_withdraw'] = number_format($member->score,2,'.','');
        $data['can_withdraw'] = $member->score;
        $data['is_fee'] = 1;//包含 通道手续费
        $data['rule'] = setting('user.withdraw.!desc', '1、可到账金额不低于100元时可以发起提现；
2、每周一至周二可发起提现申请，每周可以发起2次（点击“立即提现”出现“提现成功”提示1次即可，切勿多点，此操作会消耗次数）；
3、平台在每周一上午10点后对已经收到的提现申请进行审核，通过后申请的提现金额到账，完成提现');
        $this->showJson($data);
    }

    /**
     * 我的帖子金币可提现收益
     */
    public function postProfitAction()
    {
        /** @var MemberModel $member */
        $member = MemberModel::onWriteConnection()
            ->where('oauth_id', $this->post['oauth_id'])
            ->where('oauth_type', $this->post['oauth_type'])
            ->first();
        $data['post_coins'] = $member->post_coins;
        $data['total_post_coins'] = $member->total_post_coins;
        $data['today_post_coins'] = UsersCoinrecordModel::getTodayProfit($member->uid,'buyPost');  // 累计收入;
        $data['rate']= (string)(UserWithdrawModel::USER_WITHDRAW_MONEY_RATE_MV-UserWithdrawModel::USER_WITHDRAW_MONEY_RATE_MV_CHANNEL);
        if ($member->auth_status) {
            $data['rate'] = MemberMakerModel::getMakerRate($member->uuid);
        }
        //$data['can_withdraw'] = number_format($member->post_coins,2,'.','');
        $data['can_withdraw'] = $member->post_coins;
        $data['is_fee'] = 1;//包含 通道手续费
        $data['rule'] = setting('user.withdraw.!desc', '1、可到账金额不低于100元时可以发起提现；
2、每周一至周二可发起提现申请，每周可以发起2次（点击“立即提现”出现“提现成功”提示1次即可，切勿多点，此操作会消耗次数）；
3、平台在每周一上午10点后对已经收到的提现申请进行审核，通过后申请的提现金额到账，完成提现');
        $this->showJson($data);
    }

    /**
     * 我的收益视频列表
     */
    public function videoListAction()
    {
        $type = $this->post['type'] ?? 'new';
        $order = 'new' == $type ? 'id' : 'count_pay';
//        $return = (new MvService())->getUserWorks(request()->getMember(), [
//            ['status', '=', MvModel::STAT_CALLBACK_DONE],
//            ['is_hide', '=', MvModel::IS_HIDE_NO],
//            ['coins', '>',0]
//            ], $order);
        $member = request()->getMember();
        $query = MvModel::queryBase()
            ->with('user_topic')
            ->select('mv.*')
            ->leftJoin('mv_pay as mp' , 'mv.id' , 'mp.mv_id')
            ->where('mp.uid' , $member->uid)
            ->forPage($this->page , $this->limit)
            ->orderByDesc('is_top')
            ->orderByDesc($order);
        $items = MvModel::setGrammar($query)->get();
        $return = (new MvService())->v2format($items);

        $this->showJson($return);

    }



    /**
     * 最热
     * @author xiongba
     * @date 2020-09-24 15:09:04
     */
    public function hottestAction()
    {
        $where = [
            'uid' => $this->member['uid'],
        ];
        $list = MvModel::queryBase()
            ->where($where)
            ->orderByDesc('count_pay')
            ->orderByDesc('id')
            ->forPage($this->page, $this->limit)
            ->selectRaw('id,uid,duration,title,coins,cover_thumb,count_pay,`like`,rating')
            ->get();
        $result = [
            'list'     => $list,
            'last_idx' => "0",
        ];
        return $this->showJson($result);
    }

    /**
     * 热销
     * @return bool
     * @author xiongba
     * @date 2020-09-24 15:10:10
     */
    public function newestAction()
    {
        $where = [
            'uid' => $this->member['uid'],
        ];
        $list = MvModel::queryBase()
            ->where($where)
            ->orderByDesc('id')
            ->forPage($this->page, $this->limit)
            ->selectRaw('id,uid,duration,title,coins,cover_thumb,count_pay,`like`,rating')
            ->get();
        $result = [
            'list'     => $list,
            'last_idx' => "0",
        ];
        return $this->showJson($result);
    }


}