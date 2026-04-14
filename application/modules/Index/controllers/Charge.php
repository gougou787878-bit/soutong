<?php

/**
 * 充值记录
 */

class ChargeController extends IndexController
{

    public static $pay_type_name = [
        '1' => '花呗',
        '2' => '支付宝',
        '3' => '微信',
        '4' => '银行卡'
    ];
    public static $pay_type_names = [
        '1' => '支付宝',
        '2' => '微信',
        '3' => '银行卡'
    ];



    /**
     *
     */
    function indexAction()
    {
        $uid = $_REQUEST["uid"]??'';
        $uuid = $_REQUEST["uuid"]??'';
        $token = $_REQUEST["token"]??'';

        $this->view->assign("uid", $uid);
        $this->view->assign("token", $token);

        $list = [
            'agent' => [],
            'online' => [],
        ];
        $offset = 0;
        $limit = 20;

        $charge = OrdersModel::where(['uuid' => $uuid])
            ->orderBy("created_at", "desc")
            ->offset($offset)
            ->limit($limit);
        $chargeClone = clone $charge;
        //online线上充值/agent代理充值
        $onlineList = $charge->where('pay_type', 'online')->get();
        $agentList = $chargeClone->where('pay_type', 'agent')->get();

        /* foreach ($list['online'] as $k => $v) {
             $list['online'][$k]['addtime'] = date('Y-m-d H:i:s', $v['addtime']);
             $list['online'][$k]['pay_cate'] = '在线充值';
             if ($v['status'] == 0) {
                 $list['online'][$k]['status'] = '未支付';
             } elseif ($v['status'] == 1) {
                 $list['online'][$k]['status'] = '支付成功';
             } else {
                 $list['online'][$k]['status'] = '支付失败';
             }
             $list['online'][$k]['type'] = self::$pay_type_names[$v['type']];
         }*/


        /*$fields = 'touid,orderno,money,addtime,status,type,pay_cate,uid,id';
        $limit = '0,20';
        $list = [];
        $list['online'] = $charge->select($fields)->where(['touid' => $uid, 'pay_cate' => 1])->order("addtime desc")->limit($limit)->select();
        foreach ($list['online'] as $k => $v) {
            $list['online'][$k]['addtime'] = date('Y-m-d H:i:s', $v['addtime']);
            $list['online'][$k]['pay_cate'] = '在线充值';
            if ($v['status'] == 0) {
                $list['online'][$k]['status'] = '未支付';
            } elseif ($v['status'] == 1) {
                $list['online'][$k]['status'] = '支付成功';
            } else {
                $list['online'][$k]['status'] = '支付失败';
            }
            $list['online'][$k]['type'] = self::$pay_type_names[$v['type']];
        }
        $list['agent'] = $charge->field($fields)->where(['touid' => $uid, 'pay_cate' => 2, 'status' => array('elt', '1')])->order("addtime desc")->limit($limit)->select();
//        var_dump($data);die;
        foreach ($list['agent'] as $k => $v) {
            $list['agent'][$k]['addtime'] = date('Y-m-d H:i:s', $v['addtime']);
            $list['agent'][$k]['pay_cate'] = '在线充值';
            if ($v['status'] == 0) {
                $list['agent'][$k]['status'] = '未支付';
            } elseif ($v['status'] == 1) {
                $list['agent'][$k]['status'] = '支付成功';
            } else {
                $list['agent'][$k]['status'] = '支付失败';
            }
            $list['agent'][$k]['type'] = self::$pay_type_name[$v['type']];
        }*/

        $onlineResults = $onlineList->map(function ($item) {
            $item->amount = sprintf("%.2f", $item->amount / 100);
            return $item;
        })->toArray();

        $agentResults = $agentList->map(function ($item) {
            $item->amount = sprintf("%.2f", $item->amount / 100);
            return $item;
        })->toArray();

        $this->view->assign("onlineList", $onlineResults);
        $this->view->assign("agentList", $agentResults);
        $this->view->assign("status_name", \OrdersModel::STATUS);

        $this->show('charge');

    }

    /**
     * 代理充值
     * @return json
     */
    public function AgentUserLoginAction()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());
        $uid = $_REQUEST["uid"];
        $pay_type = $_REQUEST["pay_type"];
        $money = $_REQUEST["pay_type"];
        $token = $_REQUEST["token"];

        $checkToken = checkToken($uid, $token);
        if ($checkToken == 701) {
            $rs['code'] = $checkToken;
            $rs['msg'] = '游客没有权限，请登录！';
            return $rs;
        }
        if ($checkToken == 700) {
            $rs['code'] = $checkToken;
            $rs['msg'] = '您的登录状态失效，请重新登录！';
            return $rs;
        }
        require API_ROOT . '/public/Rsa/Rsa.php';
        $private_key = API_ROOT . '/public/Rsa/lumeier/private_key.pem'; // 私钥路径
        $public_key = API_ROOT . '/public/Rsa/lumeier/rsa_public_key.pem'; // 公钥路径
        $rsa = new Rsa($private_key, $public_key);
        $orderno = $uid . '_' . date('YmdHis') . rand(100, 999);

        $domain = new Domain_Charge();

        $orderInfo = $domain->getUserOrder($uid, 2, $money, $pay_type);


        $time = time();
        $data = [
            'uid' => $uid,
            'touid' => $uid,
            'money' => $money,
            'orderno' => $orderno,
            'addtime' => $time,
            'pay_cate' => 2,
            'type' => $pay_type
        ];

        // 订单存在
        if ($orderInfo) {
            if ($money == $orderInfo['money'] && $orderInfo['type'] == $pay_type && $orderInfo['pay_cate'] == 2) {
                $orderInfo = $orderInfo;
            } else {
                $orderInfo = $domain->createOrder($data);
            }
        } else {
            $orderInfo = $domain->createOrder($data);
        }

        $arr = [
            'pay_type' => $pay_type,
            'platform' => '64255',
            'money' => $money,
            'uid' => $uid,
            'orderno' => $orderInfo['orderno'],
        ];
        ksort($arr);
        $ecryption_data = $rsa->publicEncrypt(json_encode($arr));
        $config = getConfigPri();

        $url = $config['agent_pay_url'] . '/index/Login/doLogin';


        $domain = new Domain_User();
        $info = $domain->getBaseInfo($uid);
        $param = array_merge($arr, [
            'sign' => $ecryption_data,
            'avatar' => $info['avatar'],
            'name' => $info['user_nicename'],
            'orderno' => $orderInfo['orderno'],
            'state' => 1
        ]);
        $ch = curl_init();
        @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        @curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        @curl_setopt($ch, CURLOPT_URL, $url);
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        @curl_setopt($ch, CURLOPT_POST, 1);
        @curl_setopt($ch, CURLOPT_POSTFIELDS, $param);

        @$result = curl_exec($ch);
        if (curl_errno($ch)) {
            file_put_contents('./agent_pay.txt', date('y-m-d H:i:s') . ' 提交参数信息 ch:' . json_encode(curl_error($ch)) . "\r\n", FILE_APPEND);
        }

        curl_close($ch);
        $result = json_decode($result, true);

        $rs['code'] = $result['code'];
        $rs['msg'] = $result['msg'];
        if ($result && $result['code'] == 0) {
            $rs['info'][] = $result['data']['redirect_url'];
        }
        return $rs;
    }

    public function moreAction()
    {
        $uid = $_REQUEST['uid']??'';
        $token = $_REQUEST['token']??'';
        $pay_cate = (int)$_REQUEST['pay_type'];

        $result = array(
            'data' => array(),
            'nums' => 0,
            'isscroll' => 0,
        );

        if (checkToken($uid, $token) == 700) {
            echo json_encode($result);
            exit;
        }

        $p = $_REQUEST['page'];
        $pnums = 20;
        $start = ($p - 1) * $pnums;

        $charge = M('UsersCharge');
        $fields = 'touid,orderno,money,addtime,status,type,pay_cate,uid,id';
        $list = $charge->field($fields)->where(['touid' => $uid, 'pay_cate' => $pay_cate])->order("addtime desc")->limit($start, $pnums)->select();
        foreach ($list as $k => $v) {
            $list[$k]['addtime'] = date('Y-m-d H:i:s', $v['addtime']);
            if ($v['pay_cate'] == 1) {
                $list[$k]['pay_cate'] = '在线充值';
            } else {
                $list[$k]['pay_cate'] = '代理充值';
            }
            if ($v['status'] == 0) {
                $list[$k]['status'] = '未支付';
            } elseif ($v['status'] == 1) {
                $list[$k]['status'] = '支付成功';
            } else {
                $list[$k]['status'] = '支付失败';
            }
            $list[$k]['type'] = self::$pay_type_name[$list[$k]['type']];
        }

        $nums = count($list);
        if ($nums < $pnums) {
            $isscroll = 0;
        } else {
            $isscroll = 1;
        }

        $result = array(
            'data' => $list,
            'nums' => $nums,
            'isscroll' => $isscroll,
        );

        echo json_encode($result);
        exit;
    }

    function message_urlAction()
    {
        if ($_REQUEST["id"]) {
            $users_charge = M("users_charge")->where(array("id" => $_REQUEST["id"]))->find();
            $data = $this->agentPayUrl($users_charge);
            if ($data) {
                echo json_encode(array('code' => 200, 'msg' => '获取成功', 'data' => $data));
            } else {
                echo json_encode(array('code' => 101, 'msg' => '获取失败', 'data' => $data));
            }

        }
    }

    private function agentPayUrl($orderInfo)
    {
        // 订单存在
        $arr = [
            'pay_type' => $orderInfo['type'],
            'platform' => '64255',
            'money' => $orderInfo['money'],
            'uid' => $orderInfo['uid'],
            'orderno' => $orderInfo['orderno'],
        ];
        require_once(__ROOT__ . 'api/public/Rsa/Rsa.php');
        $private_key = __ROOT__ . 'api/public/Rsa/lumeier/private_key.pem'; // 私钥路径
        $public_key = __ROOT__ . 'api/public/Rsa/lumeier/rsa_public_key.pem'; // 公钥路径

        $rsa = new \Rsa($private_key, $public_key);
        ksort($arr);
        $ecryption_data = $rsa->publicEncrypt(json_encode($arr));
        $config = getConfigPri();

        $url = $config['agent_pay_url'] . '/index/Login/doLogin';


        $info = $userinfo = getUserInfo($orderInfo['uid']);
        $param = array_merge($arr, [
            'sign' => $ecryption_data,
            'avatar' => $info['avatar'],
            'name' => $info['user_nicename'],
            'orderno' => $orderInfo['orderno'],
            'state' => 1
        ]);
        $ch = curl_init();
        @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        @curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        @curl_setopt($ch, CURLOPT_URL, $url);
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        @curl_setopt($ch, CURLOPT_POST, 1);
        @curl_setopt($ch, CURLOPT_POSTFIELDS, $param);

        @$result = curl_exec($ch);
        if (curl_errno($ch)) {
            file_put_contents('./agent_pay.txt', date('y-m-d H:i:s') . ' 提交参数信息 ch:' . json_encode(curl_error($ch)) . "\r\n", FILE_APPEND);
        }

        curl_close($ch);
        $result = json_decode($result, true);

        $rs['code'] = $result['code'];
        $rs['msg'] = $result['msg'];
        if ($result && $result['code'] == 0) {
            return $result['data']['redirect_url'];
        } else {
            return '';
        }
    }


}