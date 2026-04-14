<?php

class EggController extends H5BaseController
{

    //初始化接口
    public function indexAction()
    {
        try {
            $id = $_GET['id'];
            $token = $_GET['token'];
            test_assert($id && $token, '参数错误');

            $uid = getCode2ID($token);
            test_assert($uid, '参数错误');
            $member = MemberModel::find($uid);
            test_assert($member, '用户不存在');
            $lottery = EggModel::info($id);
            test_assert($lottery, '活动不存在');
            test_assert($lottery->lottery_status, '活动已下架');
            $service = new \service\EggService();
            $result = $service->init($member, $lottery->id);
            return $this->showJson($result);
        }catch (Throwable $e){
            return $this->errorJson($e->getMessage());
        }
    }


    //抽奖接口
    public function lotteryAction()
    {
        try {
            $type = $_GET['num'] ?? 0;
            $id = $_GET['id'];
            $token = $_GET['token'];
            test_assert($id && $token, '参数错误');

            $uid = getCode2ID($token);
            test_assert($uid, '参数错误');
            $member = MemberModel::find($uid);
            test_assert($member, '用户不存在');
            $lottery = EggModel::info($id);
            test_assert($lottery, '活动不存在');
            test_assert($lottery->lottery_status, '活动已下架');
            test_assert(\Carbon\Carbon::now()->gte($lottery->lottery_begin), '活动未开始');
            test_assert(\Carbon\Carbon::now()->lte($lottery->lottery_end), '活动已结束');
            test_assert($type == 1, '抽奖数据异常');
            $service = new \service\EggService();
            $result = $service->draw($member, $lottery, $type);
            return $this->showJson($result);
        } catch (Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function lottery_logAction()
    {
        try {
            $id = $_GET['id'];
            $token = $_GET['token'];
            test_assert($id && $token, '参数错误');
            $uid = getCode2ID($token);
            test_assert($uid, '参数错误');
            $member = MemberModel::find($uid);
            test_assert($member, '用户不存在');
            $lottery = EggModel::info($id);
            test_assert($lottery, '活动已下架');
            test_assert($lottery->lottery_status, '活动不存在');
            $list = EggLogModel::list($member->uid, $lottery->id);
            return $this->showJson($list);
        }catch (Throwable $exception){
            return $this->errorJson($exception->getMessage());
        }
    }
}