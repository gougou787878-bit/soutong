<?php

namespace service;

use AgentsUserModel;
use MemberModel;
use Yaf\Exception;

class EventTrackerService
{
    protected $event;
    protected $channel;
    protected $event_id;
    protected $app_id;
    protected $uid;
    protected $sid;
    protected $client_ts;
    protected $device;
    protected $device_id;
    protected $device_brand;
    protected $device_model;
    protected $user_agent;
    protected $ip;

    //落地页展示日志<客服端>
    const EVENT_LANDING_PAGE_VIEW = 'landing_page_view';
    //落地页点击<客服端>
    const EVENT_LANDING_PAGE_CLICK = 'landing_page_click';
    //安装日志<客服端>
    const EVENT_APP_INSTALL = 'app_install';
    //注册日志<服务端>
    const EVENT_USER_REGISTER = 'user_register';
    //注册日志<服务端>
    const EVENT_USER_LOGIN = 'user_login';
    //订单创建<服务端>
    const EVENT_ORDER_CREATED = 'order_created';
    //订单支付成功<服务端>
    const EVENT_ORDER_PAID = 'order_paid';
    //金币消耗<服务端>
    const EVENT_COIN_CONSUME = 'coin_consume';
    //导航路径行为<客服端>
    const EVENT_NAVIGATION = 'navigation';
    //应用页面展示<客服端>
    const EVENT_APP_PAGE_VIEW = 'app_page_view';
    //应用页面点击<客服端>
    const EVENT_PAGE_CLICK = 'page_click';
    //APP广告行为<客服端>
    const EVENT_ADVERTISING = 'advertising';
    //页面存活<客服端>
    const EVENT_PAGE_LIFECYCLE = 'page_lifecycle';
    //视频事件<客服端>
    const EVENT_VIDEO_EVENT = 'video_event';
    //视频点赞<服务端>
    const EVENT_VIDEO_LIKE = 'video_like';
    //视频评论<服务端>
    const EVENT_VIDEO_COMMENT = 'video_comment';
    //视频收藏<服务端>
    const EVENT_VIDEO_COLLECT = 'video_collect';
    //视频购买<服务端>
    const EVENT_VIDEO_PURCHASE = 'video_purchase';
    //关键词搜索<服务端>
    const EVENT_KEYWORD_SEARCH = 'keyword_search';
    //关键词搜索点击<客户端>
    const EVENT_KEYWORD_CLICK = 'keyword_click';
    //广告展示<客户端>
    const EVENT_AD_IMPRESSION = 'ad_impression';
    //广告点击<客户端>
    const EVENT_AD_CLICK = 'ad_click';

    const DEVICE_ANDROID = 'android';
    const DEVICE_IOS = 'ios';
    const DEVICE_PC = 'pc';

    const REGISTER_TYPE_PHONE = 'phone';
    const REGISTER_TYPE_DEVICEID = 'deviceid';
    const REGISTER_TYPE_EMAIL = 'email';
    const REGISTER_TYPE_USERNAME = 'username';

    const EVENT_TRACKING_REPORT_KEY = 'event:tracking:report';

    public function __construct(
        $device = '',
        $channel = 0,
        $uid = '',
        $device_id = '',
        $device_brand = '',
        $device_model = '',
        $user_agent = ''
    ) {
        if (!empty($channel)){
            $channel = AgentsUserModel::getUsernameByAff($channel);
        }else{
            $channel = '';
        }
        switch ($device){
            case MemberModel::TYPE_ANDROID:
                $device = self::DEVICE_ANDROID;
                break;
            case MemberModel::TYPE_PWA:
                $device = self::DEVICE_IOS;
                break;
            default:
                $device = self::DEVICE_PC;
                break;
        }

        if (!$user_agent){
            $user_agent = $device == self::DEVICE_ANDROID ? '' : ($_SERVER['HTTP_USER_AGENT'] ?? '');
        }
        if (filter_var(USER_IP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false){
            $this->ip = USER_IP;
        }else{
            $this->ip = '';
        }

        $this->channel      = $channel;
        $this->app_id       = config('click.report.app_id');
        $this->uid          = (string)$uid;
        $this->sid          = $this->uuid();
        $this->client_ts    = TIMESTAMP;
        $this->device       = $device;
        $this->device_id    = $device_id;
        $this->device_brand = $device_brand;
        $this->device_model = $device_model;
        $this->user_agent   = $user_agent;
    }

    /**
     * 公共参数，不含 event_id
     */
    protected function buildBaseParams()
    {
        return [
            'event'        => $this->event,
            'channel'      => $this->channel,
            'app_id'       => $this->app_id,
            'uid'          => $this->uid,
            'sid'          => $this->sid,
            'client_ts'    => $this->client_ts,
            'device'       => $this->device,
            'device_id'    => $this->device_id,
            'user_agent'   => $this->user_agent,
            'device_brand' => $this->device_brand,
            'device_model' => $this->device_model,
            'ip'           => $this->ip,
        ];
    }

    /**
     * ⭐ event_id 生成规则：
     * 排除 event_id → 排序 → key=value 拼接 → md5
     */
    protected function generateEventId($params)
    {
        unset($params['event_id']);
        ksort($params);
        $query = implode('', $params);
        return md5($query);
    }

    protected function make_register_event_id($event): string
    {
        return md5($event['app_id'] . $event['uid'] . $event['create_time']);
    }

    protected function make_order_created_event_id($event): string
    {
        return md5($event['app_id'] . $event['uid'] . $event['order_id'] . $event['create_time']);
    }

    protected function make_order_paid_event_id($event): string
    {
        return md5($event['app_id'] . $event['uid'] . $event['order_id'] . $event['create_time']);
    }

    protected function make_coin_consume_event_id($event): string
    {
        return md5($event['app_id'] . $event['uid'] . $event['order_id'] . $event['create_time']);
    }

    protected function make_event_id($event_params): string
    {
        switch ($event_params['event']) {
            case EventTrackerService::EVENT_USER_REGISTER:
                return $this->make_register_event_id($event_params);
            case EventTrackerService::EVENT_ORDER_CREATED:
                return $this->make_order_created_event_id($event_params);
            case EventTrackerService::EVENT_ORDER_PAID:
                return $this->make_order_paid_event_id($event_params);
            case EventTrackerService::EVENT_COIN_CONSUME:
                return $this->make_coin_consume_event_id($event_params);
            default:
                return $this->generateEventId($event_params);
        }
    }

    /**
     * 埋点：落地页访问
     */
    public function addTask($data)
    {
        $params = $this->buildBaseParams();
        $event_params = array_merge($params, $data);
        $params['event_id'] = $this->make_event_id($event_params);
        $params['event'] = $data['event'];
        unset($data['event']);
        $params['payload'] = $data;

        $key = self::EVENT_TRACKING_REPORT_KEY;
        //提测时恢复
        redis()->lPush($key, json_encode($params));
       // jobs3([self::class, 'postForm'], [$params]);
    }

    /**
     * POST 提交
     */
    public static function postForm($data)
    {
        $header = ['Content-Type:application/json'];
        $http = new \tools\HttpCurl();
        $result = $http->post(config('click.report.url'), json_encode($data), $header, false);
        $result = json_decode($result, true);
        if ($result['code'] != 0){
            //jobs3([self::class, 'postForm'], [$data]);
            wf('失败的参数:', $data, '', '/storage/logs/report-error-data.log');
            wf('点击失败:', $result, '', '/storage/logs/report-error.log');
        }
        return true;
    }

    /**
     * UUID 生成器
     */
    protected function uuid()
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
