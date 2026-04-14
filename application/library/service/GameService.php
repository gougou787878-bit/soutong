<?php
/**
 *
 * @date 游戏接入控制
 * @author
 */


namespace service;


use helper\OperateHelper;
use tools\CurlService;
use Yaf\Exception;

class GameService
{

    private static $apiUrl = 'http://game.hyys.info/api.php';
    private static $channel = 'xblue_';
    const  SIGN_KEY = '132f1537f85scxpcm59f7e318newgaMe';
    const  ENCRYPT_KEY = 'e79465cfbb39ckcusimcuekd3b06gaMe';

    /**
     * 进入游戏
     * @param string $uid '用户id'
     * @param string $id 'game id'
     * @param string $channel_of_agent_username '用户所属渠dao 账号'
     * @return string
     */
    public function enterGame($uid, $id,$channel_of_agent_username='self')
    {
        $data = [
            'mod'  => 'game',
            'code' => 'index',
            'uid'  => self::$channel . $uid,
            'game' => $id,
            'channel'=>$channel_of_agent_username
        ];

        $result = $this->sendRemoteRequest(self::$apiUrl, $data);
        if (isset($result['gameUrl']) && !empty($result['gameUrl'])) {
            return $result['gameUrl'];
        }
        return '';
    }

    /**
     * 用户上下分
     * @param string $uid '用户id'
     * @param float $amount '转换金额 正数代表上分 负数代表下分 单位元'
     * @param string $action add reduce
     * @param string $msg
     * @param null $type //类型
     * @param null $gift //类型对应值
     * @return bool
     */
    public function transfer($uid, $amount, $action = 'add', $msg = '账户余额操作', $type = null, $gift = null, $is_admin = 0)
    {
        $data = [
            'mod'    => 'game',
            'code'   => 'transfer',
            'uid'    => self::$channel . $uid,
            'amount' => (string)$amount,
            'action' => $action,
            'from'   => 'outside',
        ];
        if ($is_admin) {
            $data['admin'] = $is_admin;
            $data['from'] = 'inside';
        }
        $result = $this->sendRemoteRequest(self::$apiUrl, $data);
        /**{
         * "status": "2",
         * "reason":"余额不足"
         * }*/
        $flag = false;
        $reson = $result['reason'] ?? '操作失败~';
        if (isset($result['status']) && $result['status'] == 1) {
            $flag = true;

            if ($gift == 7) {//send vip once
                $type = \GameDetailModel::TYPE_VIP;
                //\GameDetailModel::sendVip($uid, $gift);
            } elseif ($gift == 30) {//send vip once
                $type = \GameDetailModel::TYPE_VIP;
                //\GameDetailModel::sendVip($uid, $gift);
            }
        }
        $type = $type ?? \GameDetailModel::TYPE_DEFAULT;
        \GameDetailModel::addData($uid, $action, $amount, $msg, [$data, $result], $flag, $type);
        $date = date('Y-m-d H:i:s');
        errLog("gameTransfer:{$date} " . var_export([$data, $result, $type, $gift], 1));
        return [$flag, $reson];
    }

    /**
     * 查询游戏余额
     * @param string $uid '用户id'
     * @return array [balance 用户余额 transferable 用户可下分余额]
     */
    public function getBalance($uid)
    {
        $data = [
            'mod'  => 'game',
            'code' => 'balance',
            'uid'  => self::$channel . $uid,
        ];
        $balance = $this->sendRemoteRequest(self::$apiUrl, $data);
        if (!isset($balance['transferable']) || $balance['transferable'] <= 0) {
            return "0";
        }
        $b = $balance['transferable'] ?? 0;
        return "{$b}";
    }


    /**
     * 定义要使用的加密类
     *
     * @return \LibCrypt
     */
    protected function crypt()
    {
        $crypt = new \LibCrypt();
        $crypt->setKey(self::SIGN_KEY, self::ENCRYPT_KEY);
        return $crypt;
    }

    /**
     * 定义要使用的curl
     *
     * @return CurlService
     */
    protected function getCurlService()
    {
        return (new CurlService());
    }

    /**
     *  定义联盟通讯远程请求 (暂只对联盟通讯有用)
     *
     * @param $url
     * @param array $postData
     * @param int $timeout
     * @return array
     */
    protected function sendRemoteRequest($url, $postData = [], $timeout = 30)
    {
        $returnData = [];
        try {
            $data = $this->crypt()->replyData($postData);
            $result = $this->getCurlService()->curlPost($url, json_decode($data, true), $timeout);
            if ($result) {
                $result = $this->crypt()->checkInputData(json_decode($result, true), false);
                if (isset($result['data']) && is_array($result['data'])) {
                    $returnData = $result['data'];
                } elseif (!isset($result['code']) || $result['code'] != 200) {
                    throw new Exception('Game请求失败，数据' . json_encode($data) . ' 返回：' . json_encode($result));
                } else {
                    $returnData = $result['data'] ?? false;
                }
            }
        } catch (\Throwable $exception) {
            errLog("sendRemoteRequestError: \r\n " . var_export([
                    $url,
                    $postData,
                    $returnData,
                    $exception->getMessage()
                ],
                    true));
        }
        return $returnData;
    }

    /**
     * 游戏产品定义
     * @return array
     */
    static function gameProductList()
    {
        //小额快捷支付宝 微信  30 50 100 200
        //大额支付宝转卡 50 - 10000
        $gamePriceList = [50, 100, 200, 300, 400, 500, 1000, 5000, 10000];
        return array_map(function ($item) {
            $rs = [
                'name'        => "¥{$item}",//商品名
                //'tips'        => $item >= 1000 ? '首冲送7天VIP' : '',//商品tips
                'tips'        => '',//商品tips
                'gift_type'   => '0',//赠送类型  1 vip 2 观影券  默认 0
                //'gift_number' => $item >= 1000 ? '0' : '0',//赠送类型对应的礼物数量  默认 0
                'gift_number' => '0',//赠送类型对应的礼物数量  默认 0
                'price'       => (string)$item,//商品价值
            ];

            $rs['way'][] = [
                'payway' => 'agent',
                'tips'   => '额外赠送2%',
                'name'   =>  '人工充值',
                'icon'   =>  url_cover(\ProductModel::PAY_WAY_ICON['payway_agent']),
            ];
            $rs['way'][] = [
                'payway' => 'alipay',
                'tips'   => '',
                'name'   =>  '支付宝',
                'icon'   =>  url_cover(\ProductModel::PAY_WAY_ICON['payway_alipay']),
            ];
            if (true || $item <= 200) {

                $rs['way'][] = [
                    'payway' => 'wechat',
                    'tips'   => '',//额外赠送0%
                    'name'   =>  '微信',
                    'icon'   =>  url_cover(\ProductModel::PAY_WAY_ICON['payway_wechat']),
                ];
            }
            if ($item >= 500 && $item<=5000) {
                $rs['way'][] = [
                    'payway' => 'bankcard',
                    'tips'   => '',//额外赠送0%
                    'name'   =>  '银联',
                    'icon'   =>  url_cover(\ProductModel::PAY_WAY_ICON['payway_bank']),
                ];
            }

            return $rs;

        }, $gamePriceList);
    }

}