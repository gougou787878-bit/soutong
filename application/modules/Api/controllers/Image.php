<?php

use helper\QueryHelper;
use service\AdService;
use service\PictureService;

/**
 * Class ImageController
 */
class ImageController extends BaseController
{

    /**
     * Image主页入口
     * @return bool|void
     */
    public function indexAction()
    {
        $return = [];
        $return['ads'] = AdService::getADsByPosition(AdsModel::POSITION_PIC);
        $return['icon'] = [
            [
                'name' => '超高清',
                'icon' => url_ads('/upload_01/ads/20250218/2025021815105996165.png'),
                'type' => 'high',
                'key'  => 'type',
            ],
            [
                'name' => 'VIP',
                'icon' => url_ads('/upload_01/ads/20250218/2025021815111835633.png'),
                'type' => 'vip',
                'key'  => 'type',
            ],
            [
                'name' => '分类',
                'icon' => url_ads('/upload_01/ads/20250218/2025021815113328954.png'),
                'type' => '',
                'key'  => 'type',
            ],
            [
                'name' => '金币',
                'icon' => url_ads('/upload_01/ads/20250218/2025021815114733593.png'),
                'type' => 'pay',
                'key'  => 'type',
            ],

        ];
        $return['data'] = PictureService::getHomeData();
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
            $data = PictureService::searchManhua(['word' => $kwy]);
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
            $tabData = collect(PictureService::getSearchList())->map(function ($item) {
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
                        ['label' => '超高清', 'value' => 'high'],
                        ['label' => 'VIP', 'value' => 'vip'],
                        ['label' => '付费', 'value' => 'pay'],
                    ],
                ],
                [

                    'name'  => 'tab',
                    'items' => $tabData
                ],
            ];
        }
        $return['list'] = PictureService::searchManhua($this->post, true);
        return $this->showJson($return);
    }

    /**
     * 根据分类-标签  进入标签所属图集列表
     * @return bool
     */
    public function cat_listAction()
    {
        $tabId = $this->post['tab'] ?? '';//默认最新
        $order = $this->post['order'] ?? '';//排序 //最新 最热
        if ($order != 'new' && $order != 'hot') {
            $order = 'new';
        }
        APP_ENVIRON == 'test' && $this->limit = 2;
        // DB::enableQueryLog();
        $query = PictureModel::queryBase();
        if ($tabId) {
            $tagStr = PictureTabModel::getMatchString($tabId);
            $query->whereRaw("match(tags) against(? in boolean mode)", [$tagStr]);
        }
        if ($order == 'hot') {
            $query->orderByDesc('favorites');
        } else {
            $query->orderByDesc('refresh_at');
        }
        $list = cached("pic:cat:{$tabId}:{$order}:{$this->page}")
            ->fetchJson(function () use ($query) {
                $list = $query->forPage($this->page, $this->limit)->get();
                return $list ? $list->toArray() : [];
            });
        //$list = $query->forPage($this->page, $this->limit)->get();
        //errLog("cat_listAction".var_export([$_POST,DB::getQueryLog()],true));
        return $this->showJson(['list' => $list]);
    }

    /**
     * 图集章节目录 详细页面 图集阅读
     * @return bool
     */
    public function detailAction()
    {
        $id = $this->post['id'] ?? 0;
        try {
            $member = request()->getMember();
            $data = PictureService::getDetailData($member, $id);
            $this->showJson($data);
        } catch (Exception $exception) {
            errLog($exception->getMessage());
            $this->errorJson('查无图集信息');
        }
    }


    /**
     * 图集 购买
     * @return bool
     */
    public function buyAction()
    {
        $comics_id = $this->post['id'] ?? 0;
        /*return $this->showJson([
            'status' => 1,
            'msg'    => '支付成功~'
        ]);*/
        try {
            $data = PictureService::getOrderData($comics_id);
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
            $data = PictureService::getFavorites($id);
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
        $data = PictureFavoritesModel::getUserData($uid, $page, $limit);
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
        $data = PicturePayModel::getUserBuyData($uid, $page, $limit);
        return $this->showJson(['list' => $data]);
    }

}