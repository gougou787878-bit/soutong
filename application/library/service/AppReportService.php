<?php
/**
 *
 * @copyright kaiser
 * @todo 数据上报服务控制
 *
 */

namespace service;

use tools\CurlService;

/**
 * Class AppReportService
 * @package service
 */
class AppReportService
{

    const SIGN_KEY = '132f1537f85scxpcm59f7e318b9por51';//签名key
    const ENCRYPT_KEY = 'e79465cfbb39ckcusimcupor3b066a6e';//加密key
    const API_GATEWAY = 'https://report.hao123apps.org/index/report';//gateway
    const REPORT_QUEUE_KEY = 'jtuan.queue';//上报队列key
    const PRODUCT_ID = 0;//当前产品-中心-编号
    const IS_QUEUE = true;
    const IS_DEBUG = false;//优先级高
    const IS_ENABLE = true;//是否启用


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
     *  定义上报中心通讯远程请求
     *
     * @param $url
     * @param array $postData
     * @param int $timeout
     * @return array
     */
    protected function sendRemoteRequest($url, $postData = [], $timeout = 30)
    {
        $result = null;
        try {
            $data = $this->crypt()->replyData($postData);
            $result = $this->getCurlService()->curlPost($url, json_decode($data, true), $timeout);
            // success 代表上报成功  其他一律为失败
            //不用解密 解密要日本人
            //$result = $this->crypt()->checkInputData(json_decode($result, true), false);
            $reqTime = date('Y-m-d H:i:s');
            $msg = PHP_EOL . "req: {$reqTime}" . PHP_EOL;
            $msg .= var_export($postData, true) . PHP_EOL;
            $msg .= "rep:" . PHP_EOL;
            $msg .= var_export($result, true) . PHP_EOL;
            $msg .= PHP_EOL . str_repeat('#', 10) . PHP_EOL;
            self::IS_DEBUG && errLog($msg);
        } catch (\Throwable $exception) {
            errLog("sendDataCenterRemoteRequestError: \r\n " . var_export([
                    $url,
                    $postData,
                    $result,
                    $exception->getMessage()
                ],
                    true));
        }
        return $result;
    }


    /**
     *
     * 数据中心  新增用户
     * @param $reportData
     * @return array|void
     *
     * @example $reportData
     *
     * mod    是    string    users
     * pid    是    int    产品ID，报表中心获取
     * uid    是    int    用户uid
     * uuid    是    string    用户uuid
     * oauth_id    是    string    设备oauth_id
     * oauth_type    是    string    设备类型（android,ios,pc,web）
     * version    是    string    版本号 ‘1.0.0’
     * regdate    是    int    注册时间戳
     * regip    是    string    注册IP
     * invited_by    是    int    被谁邀请
     *
     */
    public function addUser($reportData)
    {
        if (!self::IS_ENABLE) {
            return false;
        }

        $postData = $reportData;
        $postData['mod'] = 'users';
        $postData['pid'] = self::PRODUCT_ID;

        if (self::IS_DEBUG == false && self::IS_QUEUE) {
            return $this->addQueue($postData);
        }
        return $this->sendRemoteRequest(self::API_GATEWAY, $postData, 8);
    }

    /** 数据源 上报 邀请更新 用户信息
     * @param $reportData
     * @return array|void
     * @example $reportData
     * mod    是    string    updateUser
     * pid    是    int    产品ID，报表中心获取
     * uid    是    int    用户uid
     * invited_by    是    int    被谁邀请 如未修改传0
     * channel    是    string    渠道标示，如未修改，传空字符串
     *
     */
    public function updateUser($reportData){
        if (!self::IS_ENABLE) {
            return false;
        }
        $postData = $reportData;
        $postData['mod'] = 'updateUser';
        $postData['pid'] = self::PRODUCT_ID;
        if (self::IS_DEBUG == false && self::IS_QUEUE) {
            return $this->addQueue($postData);
        }
        return $this->sendRemoteRequest(self::API_GATEWAY, $postData, 8);
    }

    /**
     * 数据源 上报  新增订单
     * @param $reportData
     * @return array|void
     *
     * @example $reportData params
     *
     * mod    是    string    orders
     * pid    是    int    产品ID，报表中心获取
     * order_id    是    string    订单号
     * uid    是    int    用户uid
     * oauth_type    是    string    设备类型（android,ios,pc,web）
     * amount    是    int    订单金额 （单位元）
     * product    是    int    订单类型 1:vip 2:金币
     * way    是    string    支付方式（alipay,wechat,agent）
     * created_at    是    int    创建时间戳
     *
     *
     */
    public function addOrder($reportData)
    {
        if (!self::IS_ENABLE) {
            return false;
        }
        $postData = $reportData;
        $postData['mod'] = 'orders';
        $postData['pid'] = self::PRODUCT_ID;
        if (self::IS_DEBUG == false && self::IS_QUEUE) {
            return $this->addQueue($postData);
        }
        return $this->sendRemoteRequest(self::API_GATEWAY, $postData, 8);

    }

    /**
     * 数据源 上报  更新订单状态
     * @param $reportData
     * @return array|void
     * @example $reportData
     *
     * mod    是    string    updateOrder
     * pid    是    int    产品ID，报表中心获取
     * order_id    是    string    订单号
     * third_id    是    string    第三方订单号
     * pay_amount    是    int    支付金额（单位元）
     * payed_at    是    int    订单支付时间戳
     *
     */
    public function updateOrder($reportData)
    {
        if (!self::IS_ENABLE) {
            return false;
        }
        $postData = $reportData;
        $postData['mod'] = 'updateOrder';
        $postData['pid'] = self::PRODUCT_ID;
        if (self::IS_DEBUG == false && self::IS_QUEUE) {
            return $this->addQueue($postData);
        }
        return $this->sendRemoteRequest(self::API_GATEWAY, $postData, 8);

    }

    /**
     * @param $reportData
     * @return array|bool|void
     *
     * @example $reportData
     * mod    是    string    exchange
     * pid    是    int    产品ID，报表中心获取
     * order_id    是    string    订单号
     * third_id    是    string    第三方订单号
     * uid    是    int    用户uid
     * oauth_type    是    string    设备类型（android,ios,pc,web）
     * name    是    string    提现姓名
     * card_number    是    string    提现卡号
     * amount    是    int    订单金额 （单位元）
     * pay_amount    是    int    实际提现金额 （单位元）
     * product    是    int    提现类型 1:代理提现 2:金币提现
     * way    是    string    提现方式（alipay,bankcard）
     * created_at    是    int    创建时间戳
     * payed_at    是    int    提现成功时间
     * status    是    int    订单状态，默认1 1成功 0失败
     */
    public function exchangeReport($reportData){
        if (!self::IS_ENABLE) {
            return false;
        }
        $postData = $reportData;
        $postData['mod'] = 'exchange';
        $postData['pid'] = self::PRODUCT_ID;
        if (self::IS_DEBUG == false && self::IS_QUEUE) {
            return $this->addQueue($postData);
        }
        return $this->sendRemoteRequest(self::API_GATEWAY, $postData, 8);
    }


    /**
     *
     * 加入队列
     *
     * @param $data   队列数据
     */
    public function addQueue($data)
    {
        $json = is_array($data) ? json_encode($data) : $data;
        return redis()->lPush(self::REPORT_QUEUE_KEY, $json);
    }

    /**
     * 发送队列
     *
     * @param $queueJson
     * @return array|void
     */
    public function sendQueue($queueJson)
    {
        $queueData = json_decode($queueJson, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            errLog("queueMsg # data: {$queueJson}  error:" . json_last_error_msg());
            return;
        }
        return $this->sendRemoteRequest(self::API_GATEWAY, $queueData, 8);
    }

    /**
     * 启动队列上报
     */
    public function startDeamonReportData()
    {
        while (true) {
            $buffer = redis()->rPop(self::REPORT_QUEUE_KEY);
            if ($buffer === false) {
                usleep(5000);
                continue;
            }
            if ($this->sendQueue($buffer) != 'success') {
                errLog("retry send data: {$buffer} ");
                redis()->lPush(self::REPORT_QUEUE_KEY, $buffer);
            }
        }
    }

}