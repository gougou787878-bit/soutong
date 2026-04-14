<?php

use helper\QueryHelper;
use service\AdService;
use service\ManhuaService;
use service\TagsService;

/**
 * Class ManhuaController
 */
class ManhuaController extends BaseController
{

    /**
     * 漫画主页入口
     * @return bool|void
     */
    public function indexAction()
    {
        $return = [];
        $return['ads'] = AdService::getADsByPosition(AdsModel::POSITION_MANHUA);
        $return['icon'] = [
            [
                'name' => '连载',
                'icon' => url_ads('/upload_01/ads/20250218/2025021815182164137.png'),
                //'icon' => url_ads('/new/ads/20230105/2023010521325164227.png'),//new year
                'type' => 'doing',
                'key'  => 'type',
            ],
            [
                'name' => '完结',
                'icon' => url_ads('/upload_01/ads/20250218/2025021815184615119.png'),
                //'icon' => url_ads('/new/ads/20230105/2023010521341667888.png'),//new year
                'type' => 'finish',
                'key'  => 'type',
            ],
            [
                'name' => '短篇',
                'icon' => url_ads('/upload_01/ads/20250218/2025021815183911094.png'),
                //'icon' => url_ads('/new/ads/20230105/2023010521350151830.png'),//new year
                'type' => 'short',
                'key'  => 'type',
            ],
            [
                'name' => 'VIP',
                // 'name' => '',
                'icon' => url_ads('/upload_01/ads/20250218/2025021815185359396.png'),
                // 'icon' => url_ads('/new/ads/20230105/2023010521303755909.png'),//new year
                'type' => 'vip',
                'key'  => 'type',
            ],
            [
                'name' => '金币',
                // 'name' => '',
                'icon' => url_ads('/upload_01/ads/20250218/2025021815190227486.png'),
                // 'icon' => url_ads('/new/ads/20230105/2023010521303755909.png'),//new year
                'type' => 'pay',
                'key'  => 'type',
            ],
//            [
//                'name' => '分类',
//                'icon' => url_ads('/new/xiao/20220610/2022061020131786157.png'),
//                //'icon' => url_ads('/new/ads/20230105/2023010521355422864.png'),//new year
//                'type' => '',
//                'key'  => 'type',
//            ]
        ];
        $return['data'] = ManhuaService::getHomeData();
        return $this->showJson($return);
    }

    /**
     * 搜索
     * @return bool
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
        $kwy = emoji_reject($kwy);
        try {
            $data = ManhuaService::searchManhua(['word' => $kwy]);
            return $this->showJson(['list' => $data]);
        } catch (Throwable $e) {
            return $this->errLog($e->getMessage());
            return $this->errorJson('频繁搜索，关键字解析有误~');
        }
    }

    /**
     * 过滤
     * @return bool
     */
    public function filterAction()
    {

        $return = [
            'category' => [],
            'list'     => [],
        ];
        list($limit, $offset, $page) = QueryHelper::restLimitOffset();
        if ($page == 0) {
            $tabData = collect(ManhuaService::getSearchList())->map(function ($item) {
                if (empty($item)) {
                    return [];
                }
                return ['label' => $item['tab_name'], 'value' => $item['tab_id']];
            })->values()->toArray();
            array_unshift($tabData, ['label' => '主题', 'value' => '']);

            $return['category'] = [
                [
                    'name'  => 'order',
                    'items' => [
                        ['label' => '最新', 'value' => ''],
                        ['label' => '热播', 'value' => 'rating'],
                        ['label' => '最赞', 'value' => 'favorites'],
                    ],
                ],
                [
                    'name'  => 'type',
                    'items' => [
                        ['label' => '分类', 'value' => ''],
                        ['label' => '连载', 'value' => 'doing'],
                        ['label' => '完结', 'value' => 'finish'],
                        ['label' => '短篇', 'value' => 'short'],
                        ['label' => 'vip', 'value' => 'vip'],
                        ['label' => '付费', 'value' => 'pay'],
                        //['label' => '长篇',   'value' => 'long'],
                        //['label' => '单行本', 'value' => 'single'],
                    ],
                ],
                [

                    'name'  => 'tab',
                    'items' => $tabData
                ],
            ];
        }
        $return['list'] = ManhuaService::searchManhua($this->post, true);
        return $this->showJson($return);
    }

    /**
     * 根据分类-标签  进入标签所属漫画列表
     * @return bool
     */
    public function cat_listAction()
    {
        $tabId = $this->post['tab'] ?? '';//默认最新
        $order = $this->post['order'] ?? '';//排序 //最新 最热
        if ($order != 'new' && $order != 'hot') {
            $order = 'new';
        }
       // DB::enableQueryLog();
        $query = MhModel::queryBase();
        if ($tabId) {
            $tagStr = MhTabModel::getMatchString($tabId);
            $query->whereRaw("match(tags) against(? in boolean mode)", [$tagStr]);
        }
        if($order == 'hot'){
            $query->orderByDesc('favorites');
        }else{
            $query->orderByDesc('refresh_at');
        }
        $list = cached("mh:cat:{$tabId}:{$order}:{$this->page}")
            ->fetchJson(function () use ($query) {
                $list = $query->forPage($this->page, $this->limit)->get();
                return $list?$list->toArray():[];
            });
        //$list = $query->forPage($this->page, $this->limit)->get();
        //errLog("cat_listAction".var_export([$_POST,DB::getQueryLog()],true));
        return $this->showJson(['list' => $list]);
    }

    /**
     * 漫画章节目录 详细页面
     * @return bool
     */
    public function detailAction()
    {
        $id = $this->post['id'] ?? 0;
        try {
            $member = request()->getMember();
            $data = ManhuaService::getDetailData($member, $id);
            $this->showJson($data);
        } catch (Exception $exception) {
            errLog($exception->getMessage());
            $this->errorJson('查无漫画信息');
        }
    }

    /**
     * 漫画阅读
     * @return bool
     */
    public function readAction()
    {
        try {
            $comics_id = $this->post['id'] ?? 0;
            $series_id = $this->post['s_id'] ?? 0;
            $data = ManhuaService::readManhua(request()->getMember(), $comics_id, $series_id);
            return $this->showJson(['data' => $data]);
        } catch (Exception $exception) {
            errLog($exception->getMessage());
            return $this->errorJson($exception->getMessage());
            $this->errorJson('查无漫画信息');
        }

    }

    /**
     * 漫画 推荐列表
     * @return bool
     */
    public function recommendAction()
    {
        $man_hua_id = (int)($this->post['id'] ?? 0);
        $data = ManhuaService::guessByManHuaLike($man_hua_id);
        return $this->showJson(['list' => $data]);
    }

    /**
     * 漫画 购买
     * @return bool
     */
    public function buyAction()
    {
        $comics_id = $this->post['id'] ?? 0;
        //return $this->showJson('支付成功~');
        try {
            $data = ManhuaService::getOrderData($comics_id);
            $data = [
                'status' => 1,
                'msg'    => '支付成功~'
            ];
            return $this->showJson($data);
        } catch (Throwable $exception) {
            return $this->errorJson($exception->getMessage());
        }
    }

    /**
     * 点赞收藏
     * @return bool
     */
    public function likingAction()
    {
        $id = $this->post['id'] ?? 0;
        try {
            $data = ManhuaService::getFavorites($id);
            return $this->showJson($data);
        } catch (Exception $exception) {
            return $this->errorJson($exception->getMessage());
        }
    }

    /**
     * 我的喜欢
     * @return bool
     */
    public function my_likingAction()
    {
        $member = request()->getMember();
        $uid = $member->uid;
        list($page, $limit, $last_id) = \helper\QueryHelper::pageLimit();
        $data = MhFavoritesModel::getUserData($uid, $page, $limit);
        return $this->showJson(['list' => $data]);
    }

    /**
     * 我的购买
     * @return bool
     */
    public function my_buyAction()
    {
        $member = request()->getMember();
        $uid = $member->uid;
        list($page, $limit, $last_id) = \helper\QueryHelper::pageLimit();
        $data = MhPayModel::getUserBuyData($uid, $page, $limit);
        return $this->showJson(['list' => $data]);
    }

}