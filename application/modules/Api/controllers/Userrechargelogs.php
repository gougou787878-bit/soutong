<?php

/**
 * 用户充值记录
 * Class UserTransactionController
 * @author xiongba
 * @date 2020-03-25 20:04:37
 */
class UserrechargelogsController extends BaseController
{


    public function logsAction()
    {
        $lastIndex = (int)request()->getPost('lastIndex', null);
        $member = request()->getMember();
        $service = new \service\UserRechargeLogsService($member);
        $data = $service->getLogs($lastIndex, $total);

        return $this->showJson([
            'lastIndex' => $lastIndex,
            'list'      => $data,
            'total'     => $total
        ]);
    }


}