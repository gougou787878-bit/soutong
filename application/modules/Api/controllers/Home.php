<?php

use service\AdService;
use service\AppCenterService;
use service\EventTrackerService;
use service\GameService;
use service\VerifyService;
use Tbold\Serv\biz\BizAppVisit;
use service\ObjectR2Service;
use helper\Validator;
/**
 * Class HomeController
 */
Class HomeController extends BaseController
{

    /**
     * 配置信息
     * @desc 用于获取配置信息
     * @return int status 操作码，1表示成功
     * @return array data
     * @return array data[0] 配置信息
     * @return string msg 提示信息
     */
    public function getConfigAction()
    {
        try {
            $aff = $this->post['aff']??'';
            $country = $this->position['country'] ?? '火星';
            if ($this->post['oauth_type'] == 'ios' && str_contains($country, '美国')) {
                if ('192.46.228.130' != USER_IP && empty($aff)){
                    if (empty($this->member['invited_by'])) {
                        redis()->sAdd('ban:ip', USER_IP);
                        return $this->showJson([], 911, '數據準備異常');
                    }
                }
            }
            $info = [];
//            $_version = null;
            $args = [VersionModel::TYPE_ANDROID, VersionModel::STATUS_SUCCESS];
            $_version = VersionModel::getleastVersion(...$args);
//            if ($this->post['oauth_type'] == 'android') {
//                $args = [VersionModel::TYPE_ANDROID, VersionModel::STATUS_SUCCESS];
//                $_version = VersionModel::getleastVersion(...$args);
//            } elseif ($this->post['oauth_type'] == 'pwa') {
//                $args = [
//                    VersionModel::TYPE_IOS,
//                    VersionModel::STATUS_SUCCESS,
//                    VersionModel::CHAN_PWA,
//                ];
//            } else {
//                $args = [
//                    VersionModel::TYPE_IOS,
//                    VersionModel::STATUS_SUCCESS,
//                    VersionModel::CHAN_TF,
//                ];
//            }
            $info['version'] = $_version;
            $domain = setting('global.domain' , "");
            $domain = collect(explode("," , $domain))->map(function ($v){return trim($v);})->filter()->unique()->toArray();
            shuffle($domain);
            //随机返回api池里面的2条域名
//        $domain = collect(explode("," , $domain))->map(function ($v){return trim($v);})->filter()->unique();
//        if ($domain->count() > 2){
//            $domain = $domain->random(2)->toArray();
//        }else{
//            $domain = $domain->toArray();
//        }
            $payName = explode('|', 'online'); // 代理充值关闭了的 explode('|', 'online|agent');
            $paySort = [];
            foreach ($payName as $v) {
                switch ($v) {
                    case 'online':
                        $paySort[] = [
                            'key' => $v,
                            'value' => '在线充值'
                        ];
                        break;
                    case 'agent':
                        $paySort[] = [
                            'key' => $v,
                            'value' => '人工充值'
                        ];
                        break;
                    case 'exclusive':
                        $paySort[] = [
                            'key' => $v,
                            'value' => '兑换码充值'
                        ];
                        break;
                    default:
                        break;
                }
            }
            $info['pay_sort'] = $paySort;
            //shuffle($domain);
            $info['domain_name'] = join(',', $domain); //域名检测
            $info['github_url'] = setting('github_url', 'https://raw.githubusercontent.com/ailiu258099-blip/master/main/soutong-app-ga.txt');
            $info['uploadKey'] = config('upload.mp4_key');// 视频上传key
            $info['imgUploadUrl'] = config('upload.img_upload');// 图片上传地址
            $info['videoUploadUrl'] = config('upload.mp4_upload'); // 视频上传地址

            //r2分片上传配置
            $info['r2URL'] = config('r2.url');
            $info['r2Key'] = config('r2.key');
            $info['r2CompleteURL'] = config('r2.complete_url');
            $info['pwa_download_url'] =  getShareURL();
            $info['pwa_apk'] = $_version ? $_version['apk'] : '';
            $info['seo_title'] = setting('pwa_title', '搜同社区');
            $info['seo_keywords'] = setting('pwa_keywords', '');
            $info['seo_description'] = setting('pwa_description', '');
            $info['watch_count'] = (int)config('site.can_watch_count', 3);
            $info['watch_is_fee_count'] = 1;//收费预览是否在后台统计
            //$info['watch_is_fee_count'] = intval(setting('config:fee-review:count', 1));//收费预览是否在后台统计
            $info['timestamp'] = strtotime(date('Y-m-d', TIMESTAMP));
            $systemNotice = getCaches('ks_config');
            $info['maintain_switch'] = 0;
            $info['maintain_tips'] = '搜同社区欢迎您~';
            $tg = setting('official.group', config('official.group'));//官方默认
            $info['tg'] = $tg;
            if($systemNotice){
                $info = array_merge($info,$systemNotice);
                /* if ($this->post['oauth_type'] == 'android') {
                     $info['maintain_tips'] = "游戏活动啦，充值任意金额游戏即送会员，还有流水反点活动哦。 快去看看吧！\n" . $info['maintain_tips'];
                 } elseif ($this->post['oauth_type'] == 'pwa' && version_compare($this->post['version'], '4.0.0', '<')) {
                     $info['maintain_tips'] = "全新轻量版已上线，99%的用户已更新(bluedmv.site )体验更滑更爽快去看看吧！\n" . $info['maintain_tips'];
                 }*/
            }

            // 活动
            $this->_homeAdsComplex($info);
            $info['player_cfg'] = [
                'x_auth'  => 'ca3a2848d4e4417eb6ebfbffdc1f3212',
                'refer'   => 'https://play.nbaidu.com',
                'dekey'   => 'e79465cfbbimgkcusimcuekd3b066aae',
                'use_new' => NEW_PLAY_CONF_ENABLE, // 中国的才使用m3u8加密
            ];
            $info['game_float'] = [
                'status' => 0,
                'icon'   => '',
                'route'  => '/api/game/index',
            ];
            $info['game_bottom_nav_show'] = version_compare(($_POST['version']??'3.8.0'), '4.2.0', '>=')?0:1;//游戏底部导航展示与否  1 展示  | 0 否
            $info['openBlack'] = 0;//ios 商店包 0 1
            if(IS_PWA){
                unset($info['pay_sort'],
                    $info['domain_name'],
                    $info['watch_is_fee_count'],
                    $info['game_float'],
                    $info['game_bottom_nav_show'],
                    $info['openBlack'],
                    $info['player_cfg']
                );
            }
            $info['pwa_game'] = 1;//游戏底部导航展示与否  1 展示  | 0 否
            //触发首页
            //$this->channel && BizAppVisit::behavior(BizAppVisit::ID_VISIT_HOME);
            $info['mv_nag_tab'] = [
                ['name' => '正在看', 'sort' => 'see'],
                ['name' => '最热', 'sort' => 'hot'],
                ['name' => '推荐', 'sort' => 'recommend'],
                ['name' => '最新', 'sort' => 'new'],
                ['name' => '畅销', 'sort' => 'sale'],
                ['name' => '随机', 'sort' => 'rand'],
            ];

            $info['mv_vip_tab'] = [
                ['name' => '正在看', 'sort' => 'see'],
                ['name' => '最热', 'sort' => 'hot'],
                ['name' => '推荐', 'sort' => 'recommend'],
                ['name' => '最新', 'sort' => 'new'],
                ['name' => '随机', 'sort' => 'rand'],
            ];

            $info['mv_find_tab'] = [
                ['name' => '正在看', 'sort' => 'see'],
                ['name' => '最热', 'sort' => 'hot'],
                ['name' => '推荐', 'sort' => 'recommend'],
                ['name' => '最新', 'sort' => 'new'],
                ['name' => '畅销', 'sort' => 'sale'],
                ['name' => '随机', 'sort' => 'rand'],
            ];

            $info['mv_original_tab'] = [
                ['name' => '正在看', 'sort' => 'see'],
                ['name' => '最热', 'sort' => 'hottest'],
                ['name' => '推荐', 'sort' => 'recommend'],
                ['name' => '最新', 'sort' => 'newest'],
                ['name' => '畅销', 'sort' => 'sale'],
                ['name' => '随机', 'sort' => 'rand'],
            ];

            $info['post_tab'] = [
                ['name' => '推荐', 'sort' => 'recommend'],
                ['name' => '最新', 'sort' => 'new'],
                ['name' => '精华', 'sort' => 'choice'],
                ['name' => '视频', 'sort' => 'video'],
            ];

            $info['ai_tab'] = [
                ['name' => '热度', 'sort' => FaceMaterialModel::SEARCH_SORT_BY_WEEKLY_USAGE],
                ['name' => '最新上架', 'sort' => FaceMaterialModel::SEARCH_SORT_BY_NEWEST],
                ['name' => '使用最多', 'sort' => FaceMaterialModel::SEARCH_SORT_BY_USE_COUNT],
                ['name' => '点赞', 'sort' => FaceMaterialModel::SEARCH_SORT_BY_LIKE_COUNT],
                ['name' => '收藏', 'sort' => FaceMaterialModel::SEARCH_SORT_BY_FAVORITE_COUNT],
                ['name' => '随机', 'sort' => FaceMaterialModel::SEARCH_SORT_RANDOM],
            ];

            $info['seed_tab'] = [
                ['name' => '最新', 'sort' => 'new'],
                ['name' => '推荐', 'sort' => 'recommend'],//推荐 (3个月内点赞/收藏)
                ['name' => '最热', 'sort' => 'hot'],//最热 (本月浏览量)
                ['name' => '正在看', 'sort' => 'see'],
            ];

            $info['cartoon_tab'] = [
                ['name' => '最新', 'sort' => 'new'],
                ['name' => '推荐', 'sort' => 'recommend'],
                ['name' => '最热', 'sort' => 'hot'],
                ['name' => '畅销', 'sort' => 'sale'],
            ];

            $info['ai_tab'] = [
                ['name' => '热度', 'sort' => FaceMaterialModel::SEARCH_SORT_BY_WEEKLY_USAGE],
                ['name' => '最新上架', 'sort' => FaceMaterialModel::SEARCH_SORT_BY_NEWEST],
                ['name' => '使用最多', 'sort' => FaceMaterialModel::SEARCH_SORT_BY_USE_COUNT],
                ['name' => '点赞', 'sort' => FaceMaterialModel::SEARCH_SORT_BY_LIKE_COUNT],
                ['name' => '收藏', 'sort' => FaceMaterialModel::SEARCH_SORT_BY_FAVORITE_COUNT],
                ['name' => '随机', 'sort' => FaceMaterialModel::SEARCH_SORT_RANDOM],
            ];

            $info['dy_tab'] = [
                [
                    'current' => false,//当前tab 默认展示
                    'id'            => 1,
                    'name'          => '关注',
                    'type'          => '1',
                    'api_list'      => 'api/recommend/recommend_follow',
                    'params_list'   => ['type' => 0],
                ],
                [
                    'current' => true,//当前tab 默认展示
                    'id'            => 2,
                    'name'          => '推荐',
                    'type'          => '2',
                    'api_list'      => 'api/recommend/index',
                    'params_list'   => ['id' => 0],
                ],
                [
                    'current' => false,//当前tab 默认展示
                    'id'            => 3,
                    'name'          => '发现',
                    'type'          => '3',
                    'api_list'      => 'api/recommend/discover',
                    'params_list'   => ['sort' => 'see'],
                ]
            ];

            $info['rank_by_conf'] = [
                [
                    'id' => 1,
                    'name' => '点赞榜',
                    'rank_by' => MvTotalModel::FIELD_LIKE
                ],
                [
                    'id' => 2,
                    'name' => '畅销榜',
                    'rank_by' => MvTotalModel::FIELD_SALE
                ],
                [
                    'id' => 3,
                    'name' => '播放榜',
                    'rank_by' => MvTotalModel::FIELD_VIEW
                ],
            ];

            $info['rank_time_conf'] = [
                [
                    'id' => 1,
                    'name' => '日榜',
                    'rank_time' => 'day'
                ],
                [
                    'id' => 2,
                    'name' => '周榜',
                    'rank_time' => 'week'
                ],
                [
                    'id' => 3,
                    'name' => '月榜',
                    'rank_time' => 'month'
                ],
                [
                    'id' => 4,
                    'name' => '总榜',
                    'rank_time' => 'all'
                ],
            ];
            //上报app_id
            $info['click_app_id'] = config('click.report.app_id');
            $info['click_transit_path'] = setting('click.report.transit_path', config('click.report.url'));
            $info['bury_point'] = $this->get_bury_point();
            $info['click_app_id'] = config('click.report.app_id');
            // 升级失败 提示 按钮提示 跳转地址
            $info['upgrade_fail'] = [
                'title' => '文件校验失败，请去官网下载正版~',
                'label' => '去官网升级',
                'url'   => getShareURL()
            ];
            
            return $this->showJson($info);
        }catch (Throwable $exception){
            error_log($exception->getMessage(), 3, APP_PATH . '/storage/logs/time.log');
        }
    }

    /**
     * 广告组合处理
     */
    private function _homeAdsComplex(&$info){
        $ads = AdService::getADsByPosition(AdsModel::POSITION_SCREEN);
        // 开屏广告
        if (version_compare($_POST['version'], '1.1.0', '>')){
            $ads_screen = [];
            foreach ($ads as $val){
                $ads_screen[] = [
                    'index_ads_type' => $val['type'] ?? 0,
                    'index_ads_url' => $val['url'] ?? '',
                    'index_ads_thumb' => $val['img_url'] ?? '',
                    'advertise_code' => $val['advertise_code'],
                    'advertise_location_code' => $val['advertise_location_code'],
                    'ad_type'       => $val['ad_type'] ?: '',
                    'ad_slot_name'  => $val['ad_slot_name'] ?: '',
                ];
            }
            $info['ads_screen'] = $ads_screen;

            //弹窗APP
            $app_show = (int)setting('home_app_list_show', 0);
            $info['apps'] = [];
            if ($app_show == 1){
                $info['apps'] = AdService::getNoticeAppList(request()->getMember());
            }
        }else{
            $ad = [];
            if ($ads) {
                $rand = array_rand($ads);
                $ad = $ads[$rand];
            }
            $info['index_ads_type'] = $ad['type'] ?? 0;
            $info['index_ads_url'] = $ad['url'] ?? '';
            $info['index_ads_thumb'] = $ad['img_url'] ?? '';
        }

        // 活动弹窗
        $adActive = AdService::getADsByPosition(AdsModel::POSITION_ACTIVE_POP);
        $info['pop_ads_v2'] = $adActive;
        if ($adActive) {
            $adActive = $adActive[0];
            $info['activity_type'] = $adActive['type'];
            $info['activity_thumb'] = $adActive['img_url'];
            if (!$adActive['url']) {
                $info['activity_url'] = '';
            } elseif (in_array($adActive['type'], [2, 4])) {//不是外部链接
                $info['activity_url'] = getDataByExplode('#', $adActive['url']);//81592#81589
            } elseif ($adActive['type'] == 1) {
                $info['activity_url'] = $adActive['url'];
            } else {
                $info['activity_url'] = $adActive['url'];
            }
        }
    }

    public function verifyUrlAction()
    {
        $member = request()->getMember();
        $url = (new VerifyService())->verifyUrl($member->aff);
        $data = [
            'verifyUrl' =>$url.'?t='.time()
        ];
        return $this->showJson($data);
    }

    // 点击上报
    public function error_reportAction()
    {
        try {
            $id = $this->post['id'] ?? 0;
            $text = $this->post['text'];
            $scr_img = $this->post['scr_img'] ?? '';
            //更新截屏地址
            if ($id){
                if ($scr_img){
                    $domain = DomainErrorLogModel::find($id);
                    if ($domain){
                        $domain->scr_img = $scr_img;
                        $domain->save();
                    }
                }
                return $this->successMsg('成功');
            }
            $text = htmlspecialchars_decode($text);
            $text1 = json_decode($text,true);
            if (json_last_error() != JSON_ERROR_NONE) {
                $text1 = $text;
            }
            $data = [
                'server' => $_SERVER,
                'report' => $text1,
            ];
            $domain = DomainErrorLogModel::create(
                [
                    'ip' => USER_IP,
                    'position' => $this->position['area'],
                    'city' => $this->position['city'],
                    'text' => json_encode($data),
                    'aff' => request()->getMember()->aff,
                    'scr_img' => $scr_img,
                    'created_at' => \Carbon\Carbon::now()
                ]
            );
            return $this->showJson([
                'id' => $domain->id,
                'status' => 1,
                'url' => ''
            ]);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * R2上传配置
     * @return bool
     */
    public function r2upload_infoAction()
    {
        $member = request()->getMember();
        if ($member->isBan()){
            return $this->errorJson('涉嫌违规，没有权限操作，请联系壮壮~');
        }
        $data = ObjectR2Service::r2UploadInfo();
        if (!$data) {
            return $this->errorJson('上传配置异常，关闭重试～');
        }
        $data['uploadName'] = $data['UploadName'];
        unset($data['UploadName']);
        return $this->showJson($data);
    }

    private function get_bury_point()
    {
        return [
            // 落地页展示
            'is_report_landing_page_view'  => 0,
            // 落地页点击
            'is_report_landing_page_click' => 0,
            // 用户注册
            'is_report_user_register'      => 0,
            // 用户登录
            'is_report_user_login'         => 0,
            // 用户在线
            'is_report_realtime_online'    => 0,
            // 订单创建
            'is_report_order_created'      => 0,
            // 订单支付成功
            'is_report_order_paid '        => 0,
            // 金币消耗
            'is_report_coin_consume'       => 0,
            // 导航路径行为
            'is_report_navigation'         => 1,
            // 应用页面展示
            'is_report_app_page_view'      => 1,
            // 应用页面点击
            'is_report_page_click'         => 1,
            // APP广告行为
            'is_report_advertising'        => 1,
            // 页面存活
            'is_report_page_lifecycle'     => 1,
            // 视频事件
            'is_report_video_event'        => 1,
            // 视频点赞
            'is_report_video_like'         => 0,
            // 视频评论
            'is_report_video_comment'      => 0,
            // 视频收藏
            'is_report_video_collect'      => 0,
            // 视频购买
            'is_report_video_purchase'     => 0,
            // 关键词搜索
            'is_report_keyword_search'     => 0,
            // 关键词搜索点击
            'is_report_keyword_click'      => 1,
            // 广告展示
            'is_report_ad_impression'      => 1,
            // 广告点击
            'is_report_ad_click'           => 1,
            // 是否加密上报
            'is_encryption'                => 1,
            // 下发KEY/IV/SIGN 加密使用方法aes-128-cbc 签名算法通用
            'encryption_key'               => cfg_get('dx.ads_report.encryption_key'),
            'encryption_iv'                => cfg_get('dx.ads_report.encryption_iv'),
            'sign_key'                     => cfg_get('dx.ads_report.sign_key'),
            // 这是CF-RAY-XF的请求头
            'authentication_key'           => cfg_get('dx.ads_report.authentication_key'),
            'authentication_time'          => cfg_get('dx.ads_report.authentication_time'),
            'click_app_id'                 => config('click.report.app_id'),
            'click_transit_path'           => replace_share('https://{share.ggsb}/api/eventTracking/batchReport.json'),
        ];
    }

    public function hijackAction()
    {
        try {
            $validator = Validator::make($this->post, [
                'json' => 'required',
                'type'  => 'required'
            ]);
            test_assert(!$validator->fail($msg), $msg);
            $json = html_entity_decode($this->post['json']);
            $type = $this->post['type'];
            jobs([HijackLogModel::class, 'create_record'], [$type, $json]);

            return $this->successMsg('上报成功');
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

}