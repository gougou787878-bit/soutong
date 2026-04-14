<?php

use helper\Util;
use helper\Validator;
use service\LotteryService;

class LotteryController extends IndexController
{

    // 获取抽奖活动首页
    public function indexAction()
    {
        $code = $_GET['code'];
        if (!$code){
            return $this->redirect(replace_share('{share.xlp}'));
        }
        $uid = ActiveInviteModel::getCode2ID($code);
        $member = MemberModel::firstAff($uid);
        $service = new LotteryService();

        //时间
        $begin_time = strtotime(setting('lottery_begin_time', '2023-12-22'));
        $end_time = strtotime(setting('lottery_end_time', '2024-01-04'));
        $between_time = date('Y年m月d日', $begin_time) . '-' . date('m月d日', $end_time);
        $this->view->assign('between_time', $between_time);
        //剩余抽奖次数
        $this->view->assign('lottery_times', UserLotteryModel::getRemianTimes($member->aff));
//        $this->view->assign('turntable', $service->listConfig());
        $this->view->assign('top_list', $service->topList());
//        $this->view->assign('award_list', $service->awardList($member));
        $this->view->assign('user_award', $service->user_award($member));
        $this->view->assign('code', $code);
        $this->display('index');
    }

    // 抽奖
    public function drawAction()
    {
        try {
            $code = $_POST['code'];
            test_assert($code, '非法请求');
            $uid = ActiveInviteModel::getCode2ID($code);
            $key = sprintf('lottery:draw:%d', $uid);
            Util::PanicFrequency($key, 1, 5, '操作太频繁~');
            $member = MemberModel::firstAff($uid);
            test_assert($member, '用户不存在');
            $service = new LotteryService();
            $rs = $service->draw($member);
            $this->ej(['code' => 0, 'data' => $rs]);
        } catch (Throwable $e) {
            $this->ej(['code' => 1, 'data' => $e->getMessage()]);
        }
    }

    //领取抽奖次数奖励
    public function receiveAction(){
        try {
            $code = $_POST['code'];
            test_assert($code, '非法请求');
            $id = $_POST['id'];
            test_assert($id, '非法请求');
            $uid = ActiveInviteModel::getCode2ID($code);
            $key = sprintf('lottery:receive:%d', $uid);
            Util::PanicFrequency($key, 1, 5, '操作太频繁~');
            $member = MemberModel::firstAff($uid);
            test_assert($member, '用户不存在');

            $service = new LotteryService();
            $service->receive($member, $id);
            $this->ej(['code' => 0, 'data' => '领取成功']);
        } catch (Throwable $e) {
            $this->ej(['code' => 1, 'data' => $e->getMessage()]);
        }
    }

    public function userAwardAction(){
        try {
            $code = $_POST['code'];
            test_assert($code, '非法请求');
            $uid = ActiveInviteModel::getCode2ID($code);
            $member = MemberModel::firstAff($uid);
            test_assert($member, '用户不存在');

            $service = new LotteryService();
            $rs = $service->user_award($member);
            $this->ej(['code' => 0, 'data' => $rs]);
        } catch (Throwable $e) {
            $this->ej(['code' => 1, 'data' => $e->getMessage()]);
        }
    }
}