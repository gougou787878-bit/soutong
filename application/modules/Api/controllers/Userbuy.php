<?php


/**
 * 用户买东西
 * Class UserTransactionController
 * @author xiongba
 * @date 2020-03-25 20:04:37
 */
class UserbuyController extends BaseController
{



    /**
     * 购买视频。自动从余额中扣除用户的金币
     * @return bool
     * @author xiongba
     */
    public function videoAction()
    {
        $id = intval($_POST['id'] ?? null);
        if (empty($id)) {
            return $this->errorJson('参数错误');
        }
        if (request()->getMember()->isBan()){
            return $this->errorJson('您已被禁言');
        }
        try {
            //频率控制
            \helper\Util::PanicFrequency(sprintf("video-%d-%d",$this->member['uid'],$id),1,10,'操作太频繁,5秒后重试');
            $service = new \service\MvService();
            $member = MemberModel::find($this->member['uid']);
            $data = $service->buyMv($member, $id);
            return $this->showJson($data);
        } catch (\Throwable $e) {
            return $this->errorJson($e->getMessage(), $e->getCode());
        }
    }
    /**
     * 使用观影卷观看视频
     * @return bool
     */
    public function checkByTicketAction()
    {
        $mv_id = $this->post['mv_id'] ?? 0;
        try {
            $service = new \service\MvService();
            //var_dump(request()->getMember());die;
            $result = $service->checkByTicket($mv_id, request()->getMember());
            return $this->showJson($result);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage(), $e->getCode());
        }
    }



}