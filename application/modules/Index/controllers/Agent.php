<?php
/**
 * 邀请记录
 */

class AgentController extends IndexController
{

    public function indexAction()
    {
        @header('Content-Type: application/json');
        $data = [
            'status'  => 0,
            'data'    => null,
            'message' => "非法请求",
        ];
        if (!$this->getRequest()->isPost()) {
            echo json_encode($data);
            return;
        }
        $code = $_REQUEST['code'] ?? '';
        if (empty($code)) {
            $data['message'] = '非法数据';
            echo json_encode($data);
            return;
        }
        $uid = ActiveInviteModel::getCode2ID($code);
        //$uid = 2;
        $list = ActiveInviteModel::getData();
        $row = ActiveInviteModel::getRow($uid);
        $key = 0;
        if ($list && $row) {
            foreach ($list as $k => $_t) {
                if ($row['id'] == $_t['id']) {
                    $key = $k + 1;
                }
            }
        }
        $return = [
            'list' => $list,
            'row'  => $row?$row:null,
            'rank' => $key,
        ];
        $data['status'] = 200;
        $data['data'] = $return;
        $data['message'] = "成功返回";
        if ($_REQUEST['_cache'] ?? '') {
            ActiveInviteModel::clearCache($uid);
        }
        echo json_encode($data);
    }


}