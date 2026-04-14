<?php


use tools\RedisService;

/**
 * class AdsModel
 *
 * @property string $android_url andeoid下载地址
 * @property int $apply_type 应用场景：1.专题策划 2. 直播间广告 3.直播间广告2， 4.启动页 5 首页活动广告
 * @property string $channel 渠道广告
 * @property int $created_at 创建时间
 * @property string $description 广告词
 * @property int $id
 * @property int $img_type 1:上传的图片;2:网络图片
 * @property string $img_url 图片地址
 * @property string $ios_url ios下载地址
 * @property string $mv_m3u8 视频m3u8
 * @property int $position 位置:配置查看adscontroller
 * @property int $show_user 0全部1 48小时前248小时后
 * @property int $status 0-禁用，1-启用
 * @property int $click_number
 * @property string $title 广告标题
 * @property int $type 广告类型 1：下载链接 2：跳转qq 3:跳转微信
 * @property string $url 广告跳转地址/QQ号/微信号
 * @property int $value 目标ID,分类id|系列id|标签id|视频id
 * @property string $expired_date 广告过期时间  eg：2022-10-10 00:00:00
 *
 * @author xiongba
 * @date 2020-03-07 21:09:30
 *
 * @mixin \Eloquent
 */
class AdsModel extends EloquentModel
{
    const STATUS_SUCCESS = 1;
    const STATUS_FAIL = 0;
    const STATUS = [
        self::STATUS_FAIL    => '禁用',
        self::STATUS_SUCCESS => '启用',
    ];

    const REDIS_ADS_KEY = 'ads:pos_';

    const POSITION_SCREEN = 1; // 启动页广告
    const POSITION_LIST = 2; // 视频列表
    const POSITION_MANHUA = 3; // 直播列表 漫画列表
    const POSITION_PIC = 20; // 图集广告
    const POSITION_STORY = 21; // 小说广告
    const POSITION_DIAMOND_PLAZA = 4; //金币商城
    const POSITION_MEMBER_RECHARGE = 5; //会员充值
    const POSITION_DIAMOND_VIDEO_PLAZA = 6; //金币视频广场
    const POSITION_ACTIVE_POP = 7; //活动弹窗
    const POSITION_GLOABLE_FULI = 8; //会员福利
    const POSITION_SEARCH_INDEX = 9; //金币视频广场
    const POSITION_PRODUCT_LIST = 10; //产品列表
    const POSITION_INDEX_HOME = 11; // 视频主页
    const POSITION_APP_CENTER = 12; // 应用中心
    const POSITION_JINGXUAN = 13; // 精选视频广告
    const POSITION_AW = 66; // 暗网banner
    const POSITION_TAB_FEATURE = 14; // tab 精选列表
    const POSITION_SITE_TOP = 15; // 进站必涮
    const POSITION_WEEK = 16; // 每周精选
    //const POSITION_BANNER = 301; // banner广告
    const POSITION_PLAY = 201; // 直播播放
    const POSITION_GAME = 17; // 游戏
    const POSITION_LANPRON_HOME = 99; //
    const POSITION_LANPRON_MID = 100; //
    const POSITION_LANPRON_END = 101; //
    const POSITION_LANPRON_POP = 102; //

    const POSITION_LANPRON_DETAIL_1 = 103; //
    const POSITION_LANPRON_DETAIL_2 = 104; //
    const POSITION_LANPRON_DETAIL_3 = 105; //

    const POS_COMMUNITY_BANNER = 106; // 社区广告
    const POS_FIND_BANNER = 107; // 求片广告
    //const POS_HOT_SEARCH_BANNER = 108; // 热搜广告

    const POS_GIRL_BANNER = 109; // 约炮广告
    const POS_ORIGINAL_BANNER = 110; // 原创/视频详情

    const POSITION_HOT_RANK = 120;//热榜
    const POSITION_SEED_LIST = 130;//种子banner

    const POSITION_PORN_GAME_BANNER = 141;//黄游banner
    const POSITION_PORN_GAME_DETAIL = 142;//黄游详情

    const POSITION_CARTOON_BANNER = 151;//动漫banner
    const POSITION_CARTOON_DETAIL = 152;//动漫详情

    const POSITION_LIVE_BANNER = 161;//直播banner
    const POSITION_LIVE_DETAIL = 162;//直播详情
    const POSITION_AI_HL_BANNER = 171;//AI换脸banner
    const POSITION_COMMUNITY_DETAIL = 180;//帖子详情
    const POSITION_FIND_DETAIL = 181;//求片详情
    const POSITION_SEED_DETAIL = 182;//种子详情
    const POSITION_MV_LIST_MIX = 183;//视频列表
    const POSITION_TEM_LIST = 184;//临时广告

    const POSITION = [ // 广告位置
        self::POSITION_SCREEN              => '启动页广告',
        self::POSITION_LIST                => 'tab-最新列表',
        self::POSITION_MANHUA              => '漫画主页',
        self::POSITION_PIC                 => '图集主页',
        self::POSITION_STORY               => '小说主页',
        self::POS_COMMUNITY_BANNER         => '社区主页',
        self::POS_FIND_BANNER              => '求片主页',
        //self::POS_HOT_SEARCH_BANNER        => '热搜banner',
        self::POSITION_PLAY                => 'tab-其他列表广告',
        self::POSITION_DIAMOND_PLAZA       => '金币商城',
        self::POSITION_MEMBER_RECHARGE     => '会员充值',
        self::POSITION_DIAMOND_VIDEO_PLAZA => '金币视频广场',
        //self::POSITION_PRODUCT_LIST        => '产品列表',
        self::POSITION_INDEX_HOME          => '发现页',
        self::POSITION_APP_CENTER          => '应用中心',
        self::POSITION_JINGXUAN            => 'tab-最热列表',
        self::POSITION_AW                  => '暗网广告',
        self::POSITION_TAB_FEATURE         => 'tab-推荐列表',
        self::POSITION_SITE_TOP            => '进站必涮',
        self::POSITION_WEEK                => '每周精选',
        self::POSITION_SEARCH_INDEX        => '搜索主页',
        self::POSITION_ACTIVE_POP          => '活动弹窗',
        //self::POSITION_GLOABLE_FULI        => '会员福利',
        self::POSITION_GAME                => '游戏',
        self::POSITION_LANPRON_HOME        => 'Pron-首页广告',
        self::POSITION_LANPRON_MID         => 'Pron-中部',
        self::POSITION_LANPRON_END         => 'Pron-尾部',
        self::POSITION_LANPRON_POP         => 'Pron-pop挂窗广告',
        self::POSITION_LANPRON_DETAIL_1    => 'Pron-详情left广告',
        //self::POSITION_LANPRON_DETAIL_2    => 'Pron-详情left2广告',
        self::POSITION_LANPRON_DETAIL_3    => 'Pron-详情播放下面广告',
        self::POS_GIRL_BANNER              => '约炮主页',
        self::POS_ORIGINAL_BANNER          => '原创/视频详情',
        self::POSITION_HOT_RANK            => '热榜banner', //热榜
        self::POSITION_SEED_LIST           => '种子banner', // 种子列表
        self::POSITION_PORN_GAME_BANNER    => '黄游banner', //黄游banner
        self::POSITION_PORN_GAME_DETAIL    => '黄游详情', // 黄游详情
       self::POSITION_CARTOON_BANNER       => '动漫banner', //动漫banner
       self::POSITION_CARTOON_DETAIL       => '动漫详情', // 动漫详情
       self::POSITION_LIVE_BANNER          => '直播banner', // 直播banner
       self::POSITION_LIVE_DETAIL          => '直播详情', // 直播详情
       self::POSITION_AI_HL_BANNER         => 'AI-banner', // AI banner
       self::POSITION_COMMUNITY_DETAIL     => '帖子详情', // 帖子详情
       self::POSITION_FIND_DETAIL          => '求片详情', // 求片详情
       self::POSITION_SEED_DETAIL          => '种子详情', // 种子详情
       self::POSITION_MV_LIST_MIX          => '视频列表混合', // 视频列表混合
       self::POSITION_TEM_LIST             => '临时广告', // 临时广告
    ];

    //应用场景：1.专题策划 2. 直播间广告 3.直播间广告2， 4.启动页 5 首页活动广告
    const APPLY_TYPE_THEMATIC = 1,
        APPLY_TYPE_LIVE_ROOM_ONE = 2,
        APPLY_TYPE_LIVE_ROOM_TWO = 3,
        APPLY_TYPE_START_PAGE = 4,
        APPLY_TYPE_HOME_ACTIVITY = 5;
    const APPLY_TYPE = [
        self::APPLY_TYPE_THEMATIC      => '专题策划',
        self::APPLY_TYPE_LIVE_ROOM_ONE => '直播间广告',
        self::APPLY_TYPE_LIVE_ROOM_TWO => '直播间广告2',
        self::APPLY_TYPE_START_PAGE    => '启动页',
        self::APPLY_TYPE_HOME_ACTIVITY => '首页活动广告',
    ];


    //  展示用户群体
    const SHOW_USER = [
        0 => '全部用户',
        1 => '注册时间48小时内用户',
        2 => '注册时间48小时后用户'
    ];

    // 广告类型
    const ADS_TYPE = [
        0 => '默认处理',
        1 => '外部跳转连接',
        2 => '内部跳转标签',
        3 => '内部跳转连接',
        4 => '内部跳转视频详情',
       // 5 => '直接安装App',
        6 => '跳转到VIP',
        7 => '跳转金币商城',
        8 => '跳转到游戏',
        9 => '图文漫画',
        10 => '用户分享',
        11 => '跳转社区帖子',
        12 => '跳转约炮',
        13 => '跳转原创',
    ];

    // 广告位置
    const POSITION_REMOTE = [
        self::POSITION_SCREEN               =>  '1',//'启屏页',
        self::POSITION_ACTIVE_POP           =>  '2',//'活动弹窗',
        self::POSITION_TAB_FEATURE          =>  '3',//视频banner,
        self::POS_ORIGINAL_BANNER           =>  '4',//视频/原创视频详情',
        self::POSITION_APP_CENTER           =>  '5',//'应用中心',
        self::POSITION_SEARCH_INDEX         =>  '6',//'搜索主页',
        self::POSITION_LIVE_BANNER          =>  '7',//'直播banner',
        self::POSITION_LIVE_DETAIL          =>  '8',//'直播详情',
        self::POSITION_AI_HL_BANNER         =>  '9',//'AI-banner',
        self::POS_COMMUNITY_BANNER          =>  '10',//'社区banner广告',
        self::POS_FIND_BANNER               =>  '11',//'求片banner'
        self::POSITION_AW                   =>  '12',//'暗网视频banner',
        self::POSITION_MANHUA               =>  '13',//'漫画banner',
        self::POSITION_PIC                  =>  '14',//'图集banner',
        self::POSITION_STORY                =>  '15',//小说广告,
        self::POSITION_PORN_GAME_BANNER     =>  '16',//'黄游banner',
        self::POSITION_PORN_GAME_DETAIL     =>  '17',//'黄游详情',
        self::POSITION_CARTOON_BANNER       =>  '18',//'动漫banner',
        self::POSITION_CARTOON_DETAIL       =>  '19',//'动漫详情',
        self::POSITION_SEED_LIST            =>  '20',//'种子banner',
        self::POSITION_DIAMOND_PLAZA        =>  '21',//'金币商城广告',
        self::POSITION_COMMUNITY_DETAIL     =>  '22',//'帖子详情',
        self::POSITION_FIND_DETAIL          =>  '23',//'求片详情',
        self::POSITION_SEED_DETAIL          =>  '24',//'种子详情',
        self::POSITION_MV_LIST_MIX          =>  '25',//'视频列表混合',
        self::POSITION_TEM_LIST             =>  '26',//'临时广告',
    ];

    // 广告类型 前面远程 => 原系统
    const ADS_TYPE_REMOTE = [
        0 => '0',//默认处理
        1 => '1',//外部跳转连接
        2 => '2',//内部跳转标签
        3 => '3',//内部跳转连接
        4 => '4',//内部跳转视频详情
        // 5 => '直接安装App',
        6 => '6',//跳转到VIP
        7 => '7',//跳转金币商城
//        8 => '跳转到游戏',
        9 => '9',//图文漫画
        10 => '10',//用户分享
        11 => '11',//跳转社区帖子
//        12 => '跳转约炮',
//        13 => '13',//跳转原创
    ];

    const ADS_TYPE_DOWNLOAD = 5;

    protected $table = 'ads';

    protected $fillable = [
        'title',
        'img_url',
        'img_type',
        'url',
        'position',
        'ios_url',
        'android_url',
        'type',
        'value',
        'status',
        'created_at',
        'show_user',
        'click_number',
        'expired_date'
    ];

    protected $appends = [
        'img_url_full',
        'is_expired'
    ];

    /**
     * 替换图片地址
     * @param $value
     * @return string
     */
    public function getImgUrlFullAttribute()
    {
        return $this->img_url ? url_ads($this->img_url) : '';
    }
    /**
     * @return bool
     */
    public function getIsExpiredAttribute()
    {
        $expired_date = $this->getAttribute('expired_date');
        if ($expired_date) {
            return strtotime($expired_date) > time() ? false : true;
        }
        return false;
    }

    public static function clearRedisCache($position, $channel = '')
    {
        $key = self::REDIS_ADS_KEY . $position . $channel;
        $position && RedisService::del($key);
    }

    // 处理一个跳转链接
    static function handleUrlStr(&$ad, $aff, $attr)
    {
        $shareDomain = \service\UserService::getShareURL();
        $task = yac()->fetch('task-' . TaskModel::TASK_TYPE_CLICK_AD, function () {
            return TaskModel::where('task_type', TaskModel::TASK_TYPE_CLICK_AD)->first();
        }, rand(300, 1800));
        $tag = '{{ADTASK}}';
        $tag2 = 'url=' . urlencode('{{ADTASK}}');

        if ($task && $ad[$attr]) {
            $args = [
                'task_id' => $task['id'],
                'aff'     => $aff,
                't'       => time()
            ];
            $curUrl = $shareDomain . '/index.php/notify/ad_jump?' . http_build_query($args, '', '&');
            if (strpos($ad[$attr], $tag2) !== false) {
                $ad[$attr] = str_replace($tag2, 'url=' . $curUrl . '&url=', $ad[$attr]);
                return;
            }
            if (strpos($ad[$attr], $tag) !== false) {
                $ad[$attr] = str_replace($tag, $curUrl . '&url=', $ad[$attr]);
                return;
            }
        } else {
            if (strpos($ad[$attr], $tag2) !== false) {
                $ad[$attr] = str_replace($tag2, 'url=', $ad[$attr]);
                return;
            }
            if (strpos($ad[$attr], $tag) !== false) {
                $ad[$attr] = str_replace($tag, '', $ad[$attr]);
                return;
            }
        }
        //'{{ADTASK}}https://www.naidu.com'
        //'coinRecharge??url={{ADTASK}}https://www.naidu.com'
    }

    //广告系统APPkey
    const ADS_APP_REPORT_KEY = 'ads:remote:report';
    const TYPE_COMMON = 0;
    const TYPE_APP_CENTER = 1;
    const TYPE_APP_NOTICE = 2;

    const NT_APP_IN = 1;
    const NT_APP_OUT = 0;

    const ADS_VERSION = '1.0.0';

    const NEW_TIPS = [
        self::POSITION_COMMUNITY_DETAIL,
        self::POSITION_FIND_DETAIL,
        self::POSITION_SEED_DETAIL,
        self::POSITION_MV_LIST_MIX,
        self::POSITION_SCREEN,
        self::POSITION_ACTIVE_POP,
        self::POSITION_APP_CENTER
    ];

    const CK_ADS_REMOTE_LIST = 'ck:ads:remote:list:%d';
    const GP_ADS_REMOTE_LIST = 'gp:ads:remote:list';
    const CN_ADS_REMOTE_LIST = '远程广告列表';
    public static function getRemoteAdsList($type = self::TYPE_COMMON){
        return cached(sprintf(self::CK_ADS_REMOTE_LIST, $type))
            ->group(self::GP_ADS_REMOTE_LIST)
            ->chinese(self::CN_ADS_REMOTE_LIST)
            ->fetchJson(function () use ($type){
                $http = new \tools\HttpCurl();
                $params = [
                    'hash' => config('ads.key'),
                    'type' => $type,
                ];
                // wf("远程广告列表 params:",$params);
                //$data = $http->get(config('ads.app_list_url'), $params);
                $data = $http->get(config('ads.app_list_new_url'), $params);
                //  wf("远程广告列表 banner:",$data);
                $data = json_decode($data, true);
                if (!$data['status'] == 1){
                    return [];
                }
                $list = [];
                if ($type == self::TYPE_COMMON){
                    if ($data['data']){
                        $list = array_reduce($data['data'], function($result, $item) {
                            $result[$item['position_val']][] = $item;
                            return $result;
                        }, []);
                    }
                }else{
                    $list = $data['data'];
                }

                return $list;
            }, 300);
    }

    public static function getPositionByRemote($position){
        static $list = null;
        if ($list === null){
            $list = self::getRemoteAdsList();
        }
        $key = self::POSITION_REMOTE[$position];
        if (!array_key_exists($key, $list)){
            return [];
        }
        $data = $list[$key];
        if (!$data){
            return [];
        }
        array_multisort(array_column($data, 'sort'), SORT_DESC, $data);
        return collect($data)->map(function ($item){
            $img = parse_url($item['image'], PHP_URL_PATH);
            $is_expired = false;
//            if ($item['end_at']) {
//                $is_expired = strtotime($item['end_at']) > time() ? false : true;
//            }
            $m3u8 = '';
            if ($item['m3u8']){
                $m3u8 = getPlayUrl(parse_url($item['m3u8'], PHP_URL_PATH), false);
            }
            return [
                'id'                        => $item['id'],
                'title'                     => $item['title'],
                'img_url'                   => $img,
                'url'                       => $item['address'],
                'type'                      => intval(self::ADS_TYPE_REMOTE[$item['type_val']] ?? 0),  
                'value'                     => 0,
                'expired_date'              => $item['end_at'],
                'mv_m3u8'                   => $m3u8,
                'img_url_full'              => url_cover($img),
                'is_expired'                => $is_expired,
                'advertise_code'            => $item['_id'],
                'advertise_location_code'   => $item['position_val'] ?: '-1_null',
                'ad_type'                   => $item['ad_type'] ?: '',
                'ad_slot_name'              => $item['position_name'] ?: '',
            ];
        })->filter()->values();
    }

    public static function getAppByRemote(){
        $list = self::getRemoteAdsList(self::TYPE_APP_CENTER);
        array_multisort(array_column($list, 'sort'), SORT_DESC, $list);
        return collect($list)->map(function ($item){
            $img = parse_url($item['image'], PHP_URL_PATH);
            return [
                'id'          => $item['id'],
                'title'       => $item['title'],
                'short_name'  => '',
                'description' => $item['desc'],
                'img_url_2'   => $img ?: '',
                'img_url'     => $img ? url_cover($img) : '',
                'link_url'    => replace_share($item['address']),
                'clicked'     => rand(100000, 1000000),
                'created_at'  => date('Y/m/d'),
                'advertise_code'            => $item['_id'],
                'advertise_location_code'   => $item['position_val'] ?: '-1_null',
                'ad_type'                   => $item['ad_type'] ?: '',
                'ad_slot_name'              => $item['position_name'] ?: '应用中心',
            ];
        })->filter()->values();
    }

    public static function getNoticeAppByRemote(){
        $list = self::getRemoteAdsList(self::TYPE_APP_NOTICE);
        array_multisort(array_column($list, 'sort'), SORT_DESC, $list);
        return collect($list)->map(function ($item){
            $img = parse_url($item['image'], PHP_URL_PATH);
            return [
                'id'          => $item['id'],
                'title'       => $item['title'],
                'short_name'  => '',
                'description' => $item['desc'],
                'img_url'     => $img ? url_cover($img) : '',
                'link_url'    => $item['address'],
                'clicked'     => 0,
                'created_at'  => date('Y/m/d'),
                'app_type'    => $item['app_type'],
                'advertise_code'            => $item['_id'],
                'advertise_location_code'   => $item['position_val'] ?: '-1_null',
                'ad_type'                   => $item['ad_type'] ?: '',
                'ad_slot_name'              => $item['position_name'] ?: '弹窗APP',
            ];
        })->filter()->values();
    }

    public static function reportRemote($id, $time){
        //写入队列
        redis()->lPush(self::ADS_APP_REPORT_KEY, json_encode(['id' => $id, 'time' => $time]));
        if (redis()->lLen(self::ADS_APP_REPORT_KEY) >= 100){
            $data = [];
            $list = [];
            // 循环弹出前 100 条数据
            for ($i = 0; $i < 100; $i++) {
                $value = redis()->rPop(self::ADS_APP_REPORT_KEY);
                if ($value === false) {
                    break; // 队列为空时停止
                }
                $list[] = json_decode($value, true);
                $data[] = $value;
            }
            if (empty($list)){
                echo "空的",PHP_EOL;
                return;
            }
            $http = new \tools\HttpCurl();
            $params = ['hash' => config('ads.key'), 'list' => $list];
            $header = ['Content-Type:application/json'];
            //error_log(var_export($params, true). PHP_EOL, 3, APP_PATH . '/storage/logs/report.log');
            $result = $http->post(config('ads.app_report_url'), json_encode($params), $header);
            $result = json_decode($result, true);
            if ($result['status'] != 1){
                collect($data)->map(function ($item){
                    redis()->lPush(self::ADS_APP_REPORT_KEY, $item);
                });
            }
        }
    }

    //循环广告
    public static function formatMixAds($list, $page){
        $count = count($list);
        if ($count == 0){
            return [];
        }
        $result = [];
        $pageSize = 2;
        $start = ($pageSize * ($page - 1)) % $count;
        // 取出 $pageSize 个广告（循环）
        for ($i = 0; $i < $pageSize; $i++) {
            $index = ($start + $i) % $count;
            $result[] = $list[$index];
        }
        return $result;
    }
}