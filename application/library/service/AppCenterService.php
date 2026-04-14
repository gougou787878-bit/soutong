<?php
/**
 *
 * @copyright kaiser
 * @todo 应用中心及广告位获取、数据上报处理
 *
 */

namespace service;

use Tbold\Serv\biz\BizOrder;
use Tbold\Serv\biz\BizUser;
use tools\CurlService;

/**
 * Class AppCenterService
 * @package service
 */
class AppCenterService
{
    const APP_EXPIRED = 4000;//每半天穿透一次
    const API_APP = 'https://un.hao123apps.info/api.php/api/ads/appCenter';//应用中心
    const API_ADS = 'https://un.hao123apps.info/api.php/api/ads/ads';//相关广告
    const API_ORIGIN_ADD_USER = 'https://union-api.hao123apps.info/api.php/api/originData/addUser';//数据源-新增用户
    const API_ORIGIN_ADD_ORDER = 'https://union-api.hao123apps.info/api.php/api/originData/addOrder';//数据源-新增订单
    const API_ORIGIN_UPDATE_ORDER = 'https://union-api.hao123apps.info/api.php/api/originData/updateOrder';//数据源-更新订单
    const API_ORIGIN_KEEP_DATA = 'https://union-api.hao123apps.info/api.php/api/originData/reportKeepV2';//每日留存安装数据上报 异步处理

    const PRODUCT_GOLD_ID = 0;//砖石产品编号
    const POS_START = 'blue-start';//定义广告位
    const CACHE_APP = 'un:app:';//联盟应用缓存
    const CACHE_ADS = 'un:ads:';//联盟广告缓存
    const WHITE_LIST = [];//渠道白名单 加入的渠道只展示默认官方广告
    const REPORT_QUEUE_KEY = 'channel.queue';//上报队列key
    const ROUT_ADD_USER = 'adduser';
    const ROUT_ADD_ORDER = 'addorder';
    const ROUT_UPD_ORDER = 'updorder';
    const ROUT_KEEP = 'keep';
    const ROUTE = [
        self::ROUT_ADD_USER,
        self::ROUT_ADD_ORDER,
        self::ROUT_UPD_ORDER
    ];

    const IS_QUEUE = true;


    /**
     * 定义要使用的加密类
     *
     * @return \LibCrypt
     */
    protected function crypt()
    {
        $crypt = new \LibCrypt();
        $crypt->setKey(config('channel.sign_key'), config('channel.encrypt_key'));
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
     * 联盟中心 获取渠道应用配置
     * @param string $channel
     * @return array
     * @example
     *
     * 应用中心返回
     * array(
     *  [1] => Array
     * (
     * [id] => 9
     * [name] => 蚂蚁VPN
     * [url] => https://ss.lanshuapi.com
     * [aff_domain] => https://b.lausera.com/
     * [tips] => 放眼看世界
     * [logo] => https://new_img.ycomesc.com/new/ads/20200514/2020051416093136033.png
     * [big_image] => https://new_img.ycomesc.com/new/ads/20200411/2020041121214739337.png
     * [demo_image] => https://new_img.ycomesc.com/new/ads/20200507/2020050716124921726.png
     * [rate_vip] => 0.5000
     * [pay_rate_vip] => 0.9200
     * [parent_id] => 0
     * [flag] =>
     * [channel_url] => https://b.lausera.com/c-2/a-amdQC
     * )
     * )
     *
     * ----------------- 开屏图返回格式
     *
     * Array
     * (
     * [K-START] => Array
     * (
     * [0] => Array
     * (
     * [id] => 24
     * [title] => 汤不热-起屏图
     * [product_id] => 1
     * [image] => https://new_img.ycomesc.com/new/ads/20200413/2020041320410757119.jpeg
     * [link_url] => https://tbr.tangbr.com/chan/a1000/Vm
     * [positon] => K-START
     * [positon_name] => 起屏图
     * )
     *
     * [1] => Array
     * (
     * [id] => 41
     * [title] => 蚂蚁VPN-起屏图
     * [product_id] => 9
     * [image] => https://new_img.ycomesc.com/new/ads/20200413/2020041313162752406.jpeg
     * [link_url] => https://b.lausera.com/c-2/a-amdQC
     * [positon] => K-START
     * [positon_name] => 起屏图
     * )
     *
     * [2] => Array
     * (
     * [id] => 43
     * [title] => 91av-起屏图
     * [product_id] => 4
     * [image] => https://new_img.ycomesc.com/new/ads/20200508/2020050819403875365.png
     * [link_url] => http://web.i91av.org/chan-1048/aff-THCr
     * [positon] => K-START
     * [positon_name] => 起屏图
     * )
     *
     * )
     *
     * )
     *
     *
     * @test $channel = 'k1001'
     */
    protected function getUnionRemoteData($channel, $url)
    {
        if (empty($channel) || (self::WHITE_LIST && in_array($channel, self::WHITE_LIST))) {
            return [];
        }
        $postData = [
            'channel'    => $channel,
            'product_id' => config('channel.product_id')
        ];
        $returnData = $this->sendRemoteRequest($url, $postData);
        return $returnData;
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
                if ($result && $result['status'] == 1) {//成功返回
                    $returnData = $result['data'] ?? [];
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


    /**#################################数据源上报-part start #############################**/

    /**
     * 数据源 上报  新增渠道用户
     *
     * @param string $uid
     * @param string $uuid
     * @param string $channel
     * @param string $invite_by
     * @param int $created_at
     * @return array
     */
    public function addUser(
        string $uid,
        string $uuid,
        string $device,
        string $channel = '',
        string $invite_by = '0',
        int $created_at = TIMESTAMP
    ) {
        if (empty($channel)) {
            return null;
        }
        $postData = [
            'product_id' => config('channel.product_id'),
            'uid'        => $uid,
            'uuid'       => $uuid,
            'invite_by'  => $invite_by,
            'channel'    => $channel,
            'created_at' => $created_at,
            'device'     => $this->oauthType($device)
        ];
        //新渠道用户新建
        $biz = BizUser::make([]);
        $biz->setAgentChannel($channel);
        $biz->setCreatedAt($created_at);
        $biz->setUid($uid);
        $biz->setUuid($uuid);
        //$biz->push();
        if (self::IS_QUEUE) {
            return $this->addQueue(self::ROUT_ADD_USER, $postData);
        }
        return $this->sendRemoteRequest(self::API_ORIGIN_ADD_USER, $postData, 8);
    }

    public function oauthType($oauth_type){
        $arr = [
            'android' => 'and',
            'pwa' => 'pwa',
            'ios' => 'pwa',
            'web' => 'web',
            'pc' => 'web'
        ];
        return $arr[$oauth_type] ?? 'and';
    }

    /**
     * 数据源 上报  新增渠道的订单
     *
     * @param string $order_sn
     * @param string $uuid
     * @param string $order_amount 元
     * @param int $order_type 订单类型 默认0 vip 订单  | 1 其他订单（砖石|金币|汤币）
     * @param string $channel
     * @param string $invite_by
     * @param int $status 订单状态 默认 0 未支付
     * @param int $created_at
     * @param string $phone
     * @return array|null
     */
    public function addOrder(
        string $order_sn,
        string $uuid,
        string $order_amount,
        string $device,
        int $order_type = 0,
        string $channel = '',
        string $invite_by = '0',
        int $status = 0,
        int $created_at = TIMESTAMP,
         $phone = ''
    ) {
        if (empty($channel)) {
            return null;
        }
//        if (1 == $order_type) {
//            $product_id = self::PRODUCT_GOLD_ID;
//        } else {
        $product_id = config('channel.product_id');
//        }
        if (!$product_id) {
            return null;
        }

        $postData = [
            'product_id'   => $product_id,
            'order_sn'     => $order_sn,
            'order_type'   => $order_type,
            'uuid'         => $uuid,
            'invite_by'    => $invite_by,
            'channel'      => $channel,
            'order_amount' => $order_amount,
            'status'       => $status,
            'created_at'   => $created_at,
            'phone'        => $phone ? $phone : '',
            'device'     => $this->oauthType($device)
        ];
        if (self::IS_QUEUE) {
            return $this->addQueue(self::ROUT_ADD_ORDER, $postData);
        }
        return $this->sendRemoteRequest(self::API_ORIGIN_ADD_ORDER, $postData, 8);

    }

    /**
     * 数据源 上报  更新渠道的订单状态
     * @param string $order_sn
     * @param string $pay_amount
     * @param int $status
     * @param int $updated_at
     * @return array
     */
    public function updateOrder(string $order_sn, string $pay_amount, int $status = 1, int $updated_at = TIMESTAMP,$order=null)
    {
        if($order && is_array($order)){
            /** @var \OrdersModel $order */
            $order = \OrdersModel::make($order);
        }
        $postData = [
            'product_id' => config('channel.product_id'),
            'order_sn'   => $order_sn,
            'pay_amount' => $pay_amount,
            'status'     => $status,
            'updated_at' => $updated_at,
        ];
        if ($order && $uid = $order->withMember()->value('uid')) {
            //新渠道订单统计
            $biz = BizOrder::make([]);
            $biz->setAgentChannel($order->build_id);
            $biz->setCreatedAt($order->created_at);
            $biz->setUid($uid);
            $biz->setUuid($order->uuid);
            $biz->setOrderSn($order->order_id);
            $biz->setOrderPrice($order->amount / 100);
            $biz->setPayPrice($pay_amount);//元
            //$biz->push();
        }
        if(self::IS_QUEUE){
            return $this->addQueue(self::ROUT_UPD_ORDER,$postData);
        }
        return $this->sendRemoteRequest(self::API_ORIGIN_UPDATE_ORDER, $postData, 8);

    }


    /**
     *
     * 加入队列
     *
     * @param $route  队列路由
     * @param $data   队列数据
     */
    public function addQueue($route, $data)
    {
        $json = json_encode(['route' => $route, 'data' => $data]);
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
        $url = '';
        if (self::ROUT_ADD_USER == $queueData['route']) {
            $url = self::API_ORIGIN_ADD_USER;
        } elseif (self::ROUT_ADD_ORDER == $queueData['route']) {
            $url = self::API_ORIGIN_ADD_ORDER;
        } elseif (self::ROUT_UPD_ORDER == $queueData['route']) {
            $url = self::API_ORIGIN_UPDATE_ORDER;
        }elseif (self::ROUT_KEEP == $queueData['route']) {
            $url = self::API_ORIGIN_KEEP_DATA;
        }
        if ($url) {
            return $this->sendRemoteRequest($url, $queueData['data'], 8);
        }
        return;
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
            if (!$this->sendQueue($buffer)) {
                errLog("retry send data: {$buffer} ");
                redis()->lPush(self::REPORT_QUEUE_KEY, $buffer);
            }
        }
    }

    /**
     * @param int $uid
     * @param string $channel
     * @param int $invited_aff
     * @param string $register_date //eg 2022-10-10
     * @param string $last_visit_date // eg 2022-10-10
     * @param array $extendData
     * @return array|null
     *
     * 留存 安装数据上报处理
     * 'product_id'     => self::PRODUCT_ID,//必须 联盟产品编号
     * 'channel'        => '渠道标识',// 推广链接上那个渠道标识 业务后台用户一般是build_id字段 方便定位用户所属渠道
     * 'uid'            => '5566',//必须 用户编号
     * 'invited_aff'    => '123',//必须 邀请人invited_by 被谁邀请的
     * 'register_date'  => '2022-10-10'//必须 用户注册日期
     * 'last_visit_date'=> '2022-10-10'//必须 用户最后更新活跃日期
     *
     * //$extendData
     * //agent_id｜agent_username 必传一个 方便定位用户渠道关系
     * 'agent_id'       => ''//渠道的联盟唯一编号 有的话最好传
     * 'agent_username' => ''//代理登陆账号 非必须 看项目情况
     */
    public function keepData(int $uid, string $channel, int $invited_aff, string $register_date, string $last_visit_date, array $extendData = [])
    {
        return [];
        $postData = [
            'product_id'      => config('channel.product_id'),
            'channel'         => $channel,//一般为推广链接上的渠道标识 或用户表字段 build_id
            'uid'             => $uid,//用户的编号
            'invited_aff'     => $invited_aff,//用户被谁邀请
            'register_date'   => $register_date,//注册日期
            'last_visit_date' => $last_visit_date,//最后更新日期
        ];
        if ($extendData) {
            $postData = array_merge($postData, $extendData);
        }
        if (self::IS_QUEUE) {
            return $this->addQueue(self::ROUT_KEEP, $postData);
        }
        return $this->sendRemoteRequest(self::API_ORIGIN_KEEP_DATA, $postData, 8);
    }
}