<?php

use helper\QueryHelper;
use helper\Util;
use service\AdService;
use service\LanPronService;
use service\SearchService;
use service\TabService;

/**
 *
 *gay 资源网站 lanPron 咨询
 *
 * 所有业务逻辑处理  沿用pwa服务资源
 *
 * Class LanpronController
 */
class LanpronController extends BaseController
{
    const TITLE = <<<HTML
蓝PORN|蓝porn|搜同| 蓝PORN- 全球最大华语同志色情视频平台
HTML;
    const KEYWORD = <<<HTML
蓝PORN,蓝PornAPP,BluePorn,搜同社区,搜同APP,淡蓝,搜同,搜同社区,搜同在线,搜同APP下载,男同APP下载,同志APP,同志视频免费,同志短视频,同志色情,直男出柜,blued,gay片,羽锡,Hentai鸟,刘以轩,男男性爱
HTML;
    const DESCRIPTION = <<<HTML
蓝porn，在线G片平台，华语圈最强同志视频站。蓝porn提供全球最新网黄流出视频，日韩欧美最强GV，全网素人G片，国产同志色情视频杂线免费极速观看！无需翻墙的男同社區APP，千萬哥哥們的交心軟件！
HTML;


    /**
     * 导航
     */
    public function tabAction()
    {
        $ads_pop = AdService::getADsByPosition(AdsModel::POSITION_LANPRON_POP);
        $ads = AdService::getADsByPosition(AdsModel::POSITION_LANPRON_HOME);
        $this->showJson([
            'title'       => self::TITLE,
            'keyword'     => self::KEYWORD,
            'description' => self::DESCRIPTION,
            'ads'         => $ads,
            'ads_pop'     => $ads_pop,
            'data'        => LanPronService::$tabData,
            'friends'     => LanPronService::getLanPornData(),
            'hot_search'  => (new SearchService())->getHotKeyword(),
            'bottom'      => [
                [
                    'name' => '商务合作',
                    'type' => '/shangwu',
                    'list' => '/api/lanpron/contact'
                ],
                [
                    'name' => '永久地址',
                    'type' => '/address',
                    'list' => '/api/lanpron/address'
                ],
                [
                    'name' => '下载工具',
                    'type' => '/downtool',
                    'list' => ShangwuModel::getDownTool()
                ],
                /* [
                     'name' => '友情链接',
                     'type' => '/friends',
                     'list' => LanPronService::getLanPornData()
                 ],*/
            ]
        ]);
    }

    /**
     * 首页
     */
    public function homeAction()
    {
        $data = LanPronService::getHomePageData();
        $this->showJson($data);
    }

    /**
     * 最新
     */
    public function newAction()
    {
        $data['total'] = 100;
        $data['ads'] = AdService::getADsByPosition(\AdsModel::POSITION_LANPRON_END);
        list($page, $limit, $last_ix) = QueryHelper::pageLimit(12);
        $data['list'] = LanPronService::getNewMvData($page, $limit);
        if ($data['list']) {
            $data['list'] = LanPronService::formatList($data['list']);
        }
        return $this->showJson($data);

    }

    /**
     * 热门 - 每周热点更新
     */
    public function hotAction()
    {
        $data['total'] = 100;
        $data['ads'] = AdService::getADsByPosition(\AdsModel::POSITION_LANPRON_MID);
        list($page, $limit, $last_ix) = QueryHelper::pageLimit(12);
        $data['list'] = LanPronService::getWeekMvData($page, $limit);
        if ($data['list']) {
            $data['list'] = LanPronService::formatList($data['list']);
        }
        return $this->showJson($data);
    }

    /**
     * 他们最近在看  - 每日视频推荐
     */
    public function viewedAction()
    {
        $data['total'] = 100;
        $data['ads'] = AdService::getADsByPosition(\AdsModel::POSITION_LANPRON_MID);
        list($page, $limit, $last_ix) = QueryHelper::pageLimit(12);
        $data['list'] = LanPronService::getDailyMvData($page, $limit);
        if ($data['list']) {
            $data['list'] = LanPronService::formatList($data['list']);
        }
        return $this->showJson($data);
    }
    public function viewdAction()
    {
        return $this->viewedAction();
    }

    /**
     * 全部分类
     */
    public function categoryAction()
    {
        $data = TabService::getCateList();
        return $this->showJson($data);
    }

    /**
     * 分类标签视频
     */
    public function tag_mvAction()
    {
        $tag = $_POST['tag'] ?? '';
        $data['total'] = 30;
        $data['ads'] = AdService::getADsByPosition(\AdsModel::POSITION_LANPRON_MID);
        list($page, $limit, $last_ix) = QueryHelper::pageLimit(12);
        $data['list'] = LanPronService::getTagMvData($tag, $page, $limit);
        if ($data['list']) {
            $data['list'] = LanPronService::formatList($data['list']);
        }
        return $this->showJson($data);
    }

    /**
     * 搜索视频
     */
    public function searchAction()
    {
        $kwy = $_POST['kwy'] ?? '';
        $kwy = strip_tags($kwy);
        if (mb_strlen($kwy) < 2) {
            return $this->errorJson('至少两位搜索关键字');
        }
        if (preg_match('/[\xf0-\xf7].{3}/', $kwy)) { //过滤Emoji表情
            return $this->errorJson('不支持[Emoji]表情');
        }
        if (!Util::frequency('search' . USER_IP, 30, 300)) {
            return $this->errorJson('设备搜索太频繁~,休息一会儿重试');
        }
        list($page, $limit, $last_ix) = QueryHelper::pageLimit(12);
        $data = LanPronService::searchData($kwy, $page, $limit);
        $data['ads'] = AdService::getADsByPosition(\AdsModel::POSITION_LANPRON_MID);
        return $this->showJson($data);

    }

    /**
     * 视频详细
     */
    public function detailAction()
    {
        try {
            $code = $_POST['code'] ?? '';
            $id = LanPronService::getCode2ID($code);
            $data = LanPronService::getRowDetail($id);
            $return = [];
            $return['ads'] = AdService::getADsByPosition(\AdsModel::POSITION_LANPRON_DETAIL_1);
            //$return['ads_two'] = AdService::getADsByPosition(\AdsModel::POSITION_LANPRON_DETAIL_2);
            $return['ads_play'] = AdService::getADsByPosition(\AdsModel::POSITION_LANPRON_DETAIL_3);
            $return['row'] = $data;
            $return['share'] = [
                'tg' => 'https://t.me/bluelifeG',
                'tw' => 'https://twitter.com/xiaolanfuli1',
            ];

            $return['down'] = [
                'url'  => getShareURL(),
                'tips' => '搜同社区app',
            ];
           /* if(rand(10,100)%2){
                $return['down'] = [
                    'url'  => 'https://nmq.news',
                    'tips' => '男蜜圈',
                ];
            }*/
            return $this->showJson($return);
        } catch (\Yaf\Exception $e) {
            return $this->errorJson($e->getMessage());
        }

    }

    /**
     * 视频详细-推荐视频
     */
    public function recommendAction()
    {
        $code = $_POST['code'] ?? '';
        $id = LanPronService::getCode2ID($code);
        $list = LanPronService::getRecommend($id);
        if ($list) {
            shuffle($list);
        }
        return $this->showJson(['list' => $list]);
    }

    public function contactAction()
    {
        $return = [];
        $return['title'] = '关于蓝PORN';
        $content = <<<CON
'全球最好G片平台，华语圈最强同志APP！<br/>
上蓝pron，观看全球最新网黄流出视频，日韩欧美最强GV，全网素人G片！<br/>
无需翻墙的男同社區APP，千萬哥哥們的交心軟件！'
CON;
        $return['content'] = setting('porn.about', $content);
        $return['image'] = [
            url_ads('/new/ads/20220621/2022062122145699299.jpeg'),
            url_ads('/new/ads/20220621/2022062122154639444.jpeg'),
            url_ads('/new/ads/20220621/2022062122162837875.jpeg'),
        ];
        $return['data'] = ShangwuModel::getShangwu();
        return $this->showJson($return);
    }

    public function addressAction()
    {
        $return = [];
        $return['data'] = [
            'https://bluemv.tips',
            'https://b133.mrvnik.com',
            'https://0211.juriek.com',
            'https://04a.qhzqbv.com',
            'https://62ec.hwnhto.com',
        ];
        return $this->showJson($return);
    }

}