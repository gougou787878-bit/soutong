<?php

namespace service;

use PcAdsModel;
use PcPostTopicModel;
use PcTabModel;
use PcMhTabModel;

class PcService
{
    const TYPE_MV = 1;
    const TYPE_POST = 2;
    const TYPE_IMAGE = 3;
    const TYPE_TIPS = [
        self::TYPE_MV    => '视频',
        self::TYPE_POST  => '帖子',
        self::TYPE_IMAGE => '图文',
    ];

    const CATE_MV = 1;
    const CATE_COMMUNITY = 2;
    const CATE_IMAGE = 3;
    const CATE_TIPS = [
        self::CATE_MV           => '视频',
        self::CATE_COMMUNITY    => '社区',
        self::CATE_IMAGE        => '图文',
    ];

    const MV_NAVS = [
        ['name' => '推荐', 'value' => 'recommend'],
        ['name' => '最新', 'value' => 'new'],
        ['name' => '最热', 'value' => 'hot'],
    ];

    const POST_NAVS = [
        ['name' => '推荐', 'value' => 'recommend'],
        ['name' => '最新', 'value' => 'new'],
        ['name' => '最热', 'value' => 'hot'],
        ['name' => '精华', 'value' => 'choice'],
        ['name' => '视频', 'value' => 'video'],
    ];

    const MH_NAVS = [
        ['name' => '推荐', 'value' => 'recommend'],
        ['name' => '最新', 'value' => 'new'],
        ['name' => '热播', 'value' => 'rating'],
        ['name' => '最赞', 'value' => 'favorites']
    ];

    protected function listMenus(): array
    {
        return [
            [
                'name'  => '视频',
                'type'  => self::TYPE_MV,
                'cate'  => self::CATE_MV,
                'elems' => PcTabModel::listItems(),
                'api'   => '/wapi/mv/list_mvs',
                'show'  => 1
            ],
            [
                'name'  => '社区',
                'type'  => self::TYPE_POST,
                'cate'  => self::CATE_COMMUNITY,
                'elems' => PcPostTopicModel::listItems(),
                'api'   => '/wapi/post/list_posts',
                'show'  => 1
            ],
            [
                'name'  => '图文',
                'type'  => self::TYPE_IMAGE,
                'cate'  => self::CATE_IMAGE,
                'elems' => PcMhTabModel::listItems(),
                'api'   => '/wapi/manhua/list',
                'show'  => 1
            ],
        ];
    }

    public function getConfig(): array
    {
        $imgBase = USER_COUNTRY == 'CN' ? trim(TB_IMG_PWA_CN, '/') . '/' : trim(TB_IMG_PWA_US, '/') . '/';
        $day = (int)date('H') % 5 + 1;
        return [
            'img_upload_url'        => config('upload.img_upload'),
            'mp4_upload_url'        => config('upload.mp4_upload'),
            'upload_img_key'        => config('upload.img_key'),
            'upload_mp4_key'        => config('upload.mp4_key'),
            'video_upload_url'      => config('upload.site_url') . '/u.php',
            'video_encrypt_referer' => config('video.encrypt.referer'),
            'img_base'              => $imgBase,
            'menu'                  => $this->listMenus(),
            'mv_nav'                => self::MV_NAVS,
            'post_nav'              => self::POST_NAVS,
            'mh_nav'                => self::MH_NAVS,
            'placard'               => setting('pc:placard',''),//PC公告
            'pop_ads'               => PcAdService::getADsByPosition(PcAdsModel::POSITION_POP),// 弹框
            'index_banner'          => PcAdService::getADsByPosition(PcAdsModel::POSITION_HOME_BANNER),//首页列表banner
            'mv_banner'             => PcAdService::getADsByPosition(PcAdsModel::POSITION_MV_BANNER),//视频列表banner
            'community_banner'      => PcAdService::getADsByPosition(PcAdsModel::POSITION_COMMUNITY_BANNER),//社区banner
            'image_banner'          => PcAdService::getADsByPosition(PcAdsModel::POSITION_IMAGE_BANNER),//图文列表
            'detail_top_banner'     => PcAdService::getADsByPosition(PcAdsModel::POSITION_DETAIL_TOP),//详情页顶部banner
            'detail_bottom_banner'  => PcAdService::getADsByPosition(PcAdsModel::POSITION_DETAIL_BOTTOM),//详情页底部banner
            'top_ads'               => PcAdService::getADsByPosition(PcAdsModel::POSITION_DETAIL_TOP_APP),//详情页顶部APP广告
            'bottom_ads'            => PcAdService::getADsByPosition(PcAdsModel::POSITION_DETAIL_BOTTOM_APP),//详情页底部APP广告
            'detail_top_tip'        => setting('detail_top_tips', ''),
            'detail_bottom_tip'     => setting('detail_bottom_tip', ''),
            'detail_share_tip'      => setting('detail_share_tip', ''),
            'detail_share_text'     => setting('detail_share_text', ''),
            'detail_share_domain'   => setting('detail_share_domain', ''),
            'question_network_tip'  => setting('question_network_tip', ''),
            'question_browser_tip'  => setting('question_browser_tip', ''),
            'question_common_tip'   => setting('question_common_tip', ''),
            'seo_index'             => [
                'title'    => setting('seo_index_title', ''),
                'desc'     => setting('seo_index_desc', ''),
                'keywords' => setting('seo_index_keywords', ''),
            ],
            'ct_js'                 => setting('ct_js', ''), // GOOGLE统计JS,
            'tg'                    => setting('pc_tg', 'https://t.me/bluemvG'),
            'sw_tg'                 => setting('pc_sw_tg', 'https://t.me/guanfang006'),
            'download_link'         => setting('pc_app_url'),
            'web_app_url'           => sprintf("https://w%d.%s",$day,web_site('xlp')),
            'gw_url'                => replace_share("https://{share.xlp}/chan/xb3220/b49kY"),
            'office_contact'        => json_decode(setting('office_contact'), true)['data'] ?? []
        ];
    }

//    protected static function helpFeedbackList(): array
//    {
//        return (new CommonService())->helpFeedbackList();
//    }

}