<?php

// 视频模块

use Dom\Comment;
use helper\QueryHelper;
use helper\Validator;
use service\AdService;
use service\MvService;
use service\TabService;
use service\TopCreatorService;

class MvController extends BaseController
{
    use \repositories\MvRepository,
        \repositories\UsersRepository,
        \repositories\CommentsRepository;


    /**
     * 推荐视频
     * @return bool|void
     * @author xiongba
     * @date 2020-06-16 11:48:33
     */
    public function listOfFeatureAction()
    {
      
        $is_aw = $_POST['is_aw'] ?? 0;
        $sort = $_POST['sort'] ?? 'new';
        $tab_id = $_POST['tab_id'] ?? '-1';
        try {
            $ads = [];
            $creator = [];
            if ($this->page == 1) {
                $ads = AdService::getADsByPosition(AdsModel::POSITION_TAB_FEATURE);
                $creator = cached('mxzpr')->expired(1000)->serializerJSON()->fetch(function () {
                    return MemberModel::orderByDesc('fabulous_count')
                        ->where('videos_count', '>', 5)
                        ->orderByDesc('regdate')
                        //->where('regdate','>=',strtotime('-30 days'))
                        ->limit(10)
                        ->get(['uid', 'aff', 'nickname', 'thumb', 'auth_status', 'vip_level', 'expired_at'])
                        ->toArray();
                });
            }
            $service = new \service\MvService();

            if($sort == 'see'){
                $results = MvModel::listSee($tab_id, $this->page, 15);
            }elseif ($sort == 'hottest'){
                $results = MvModel::listRank($tab_id, $this->page, 15);
            }else{
                $results = $service->getListByScore(request()->getMember(), (int)$this->page, $is_aw,$sort);
            }


            return $this->showJson(array_merge([
                'creator' => $creator,
                'ads'     => $ads,
            ], $results));
        } catch (\Throwable $e) {
            errLog("listOfFeatureAction:{$e->getMessage()}");
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 获取导航标签视频
     * @return bool|void
     * @author xiongba
     * @date 2020-06-16 11:48:33
     */
    public function listOfTabAction()
    {
        $tabId = $_POST['tabId'] ?? 0;
        $is_aw = $_POST['is_aw'] ?? 0;
        $sort = $_POST['sort'] ?? 'new';
        try {
            $ads = [];
            if ($this->page == 1) {
                $ads = AdService::getADsByPosition(AdsModel::POSITION_PLAY);
                if (!$ads) {
                    $ads = AdService::getADsByPosition(AdsModel::POSITION_TAB_FEATURE);
                }
            }
            $service = new MvService();
            if($sort == 'see'){
                $data = MvModel::listSee($tabId, $this->page, 15,1);
            }elseif ($sort == 'hottest'){
                $data = MvModel::listRank($tabId, $this->page, 15,1);
            }else{
                $data =  $service->getlistdata($tabId,request()->getMember(),$is_aw,$sort);
            }

            return $this->showJson(['list' => $data, 'ads' => $ads]);
        } catch (\Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function list_of_aw_tab_listAction()
    {
        $tabId = $_POST['tabId'] ?? 0;
        $is_aw = $_POST['is_aw'] ?? 0;
        $sort= $_POST['sort'] ?? 'new';
        $is_aw = 1;
        try {
            $ads = [];
            if ($this->page == 1) {
                $ads = AdService::getADsByPosition(AdsModel::POSITION_AW);
//                if (!$ads) {
//                    $ads = AdService::getADsByPosition(AdsModel::POSITION_PLAY);
//                }
            }
            $member = request()->getMember();
            MvModel::setWatchUser($member);
            MemberModel::setWatchUser($member);
            $data = (new MvService())->getTabList($tabId, $member, $is_aw,$sort);
            if (!$data) {
                $tagStr = TabModel::getMatchString($tabId);
                $items = MvModel::queryWithUser()
                    ->where('is_aw', $is_aw)
                    ->with('user_topic')
                    ->whereRaw("match(tags) against(? in boolean mode)", [$tagStr])
                    ->when($sort,function ($query)use($sort){
                        if($sort == 'pay'){
                            $query->orderByDesc('count_pay');
                        }elseif ($sort == 'view'){
                            $query->orderByDesc('rating');
                        }else{
                            $query->orderByDesc('refresh_at');
                        }
                    })
                    ->forPage($this->page, $this->limit)
                    ->get();
                $data = (new \service\MvService())->v2format($items, request()->getMember());
            }
            $middleData = [
                [
                    'cover_full' => url_cover('/upload/ads/20230801/2023080120424880745.png'),
                    'type'       => 'tab',
                    'api'        => 'api/mv/list_of_aw_tab_list',
                    'name'       => '最近更新',
                    'params'     => ['tabId' => $tabId, 'is_aw' => 1, 'sort' => 'new']
                ],
                [
                    'cover_full' => url_cover('/upload/ads/20230801/2023080120465883897.png'),
                    'type'       => 'tab',
                    'api'        => 'api/mv/list_of_aw_tab_list',
                    'name'       => '热门购买',
                    'params'     => ['tabId' => $tabId, 'is_aw' => 1, 'sort' => 'pay']
                ],
                [
                    'cover_full' => url_cover('/upload/ads/20230801/2023080120473387600.png'),
                    'type'       => 'tab',
                    'api'        => 'api/mv/list_of_aw_tab_list',
                    'name'       => '最多观看',
                    'params'     => ['tabId' => $tabId, 'is_aw' => 1, 'sort' => 'view']
                ],
            ];
            return $this->showJson(['list' => $data, 'middle_data' => $middleData, 'ads' => $ads]);
        } catch (\Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }


    /**
     * 视频标签
     * @return bool|void
     * @author xiongba
     */
    public function listOfTagAction()
    {
        $tag = request()->getPost('tag');
        $type = request()->getPost('type', 'newest');
        $service = new \service\TagMvService();
        $data = $service->videoForVideo($tag, null, $type);
        return $this->showJson($data);
    }

    /**
     * 视频标签(新)
     */
    public function listOfTagNewAction()
    {
        try {
            $tag = $this->post['tag'] ?? "全部";
            $tab_id = $this->post['tab_id'] ?? 0;
            $sort = $this->post['sort'] ?? 'newest';
            $type = $this->post['type'] ?? 0;
            if ($tag == '全部' && $tab_id == 0){
                throw new Exception('数据异常');
            }
            $service = new \service\TagMvService();
            list($page, $limit) = QueryHelper::pageLimit();
            $data = $service->tagVideoList($tag, $tab_id, $type, $sort, $page, $limit);
            //远程广告
            if (version_compare($_POST['version'], AdsModel::ADS_VERSION, '>')) {
                $list_ads = AdService::getADsByPosition(AdsModel::POSITION_MV_LIST_MIX);
                $list_ads = AdsModel::formatMixAds($list_ads, $page);
                return $this->listJson($data, ['list_ads' => $list_ads]);
            }
            return $this->showJson($data);
        }catch (Throwable $exception){
            return $this->errorJson($exception->getMessage());
        }
    }

    /**
     * 最新视频
     * @return bool|void
     * @author xiongba
     */
    public function listOfLatestAction()
    {
        $is_aw = request()->getPost('is_aw', 0);
        $i = 0;
        $query = MvModel::queryWithUser();
        /*if (!showVideoStyle(request()->getMember()) && $this->page <= 3) {
            $query->where('coins', '=', 0);
            $i = 1;
        }*/
        list($limit, $offset, $page) = QueryHelper::restLimitOffset();

        $rediskey = "lastes:{$page}:{$i}";
        if ($is_aw) {
            $rediskey = $rediskey . ':' . $is_aw;
        }
        $query->where('is_aw', $is_aw);
        $list = cached($rediskey)->serializerPHP()->fetch(function () use ($query, $page, $limit) {
            $list = $query->with('user_topic')
                ->forPage($page, $limit)->orderByDesc('refresh_at')->get();
            return $list;
        });
        $data = (new \service\MvService())->v2format($list, request()->getMember());
        $ads = AdService::getADsByPosition(AdsModel::POSITION_LIST);
        if (!$ads) {
            $ads = AdService::getADsByPosition(AdsModel::POSITION_TAB_FEATURE);
        }
        return $this->showJson(['list' => $data, 'ads' => $ads]);
    }

    /**
     * 最热视频
     * @return bool|void
     * @author xiongba
     */
    public function listOfHottestAction()
    {
        return $this->showJson(['list' => [], 'ads' => []]);
        $is_aw = request()->getPost('is_aw', 0);
        $data = (new MvService())->getHotList(request()->getMember(), $is_aw);
        if (!$data) {
            $list = MvModel::queryWithUser()
                ->where('is_aw', $is_aw)
                ->with('user_topic')
                ->where('created_at', '>=', strtotime('-30 days'))
                ->forPage($this->page, $this->limit)->orderByDesc('like')->get();
            $data = (new \service\MvService())->v2format($list, request()->getMember());
        }
        $banner = [];
        if ($this->page == 1) {
            $banner = AdService::getADsByPosition(AdsModel::POSITION_JINGXUAN);
            if (!$banner) {
                $banner = AdService::getADsByPosition(AdsModel::POSITION_TAB_FEATURE);
            }
        }
        return $this->showJson(['list' => $data, 'ads' => $banner]);
    }

    /**
     * 我关注的人
     * @return bool|void
     */
    public function listOfFollowAction()
    {
        $member = request()->getMember();
        MvModel::setWatchUser($member);
        MemberModel::setModel($member);
        $uid = $this->member['uid'];
        $list = cached('tb_fl_v:' . $uid)
            ->hash($this->page)
            ->fetchPhp(function () use ($uid){
                $likeVid = UserAttentionModel::query()
                    ->join('mv', 'mv.uid', '=', 'member_attention.touid')
                    ->where('member_attention.uid', $uid)
                    ->where('mv.type', MvModel::TYPE_LONG)
                    ->orderByDesc('mv.id')
                    ->forPage($this->page, $this->limit)
                    ->pluck('mv.id')
                    ->toArray();

                $items = MvModel::queryBase()
                    ->with('user_topic')
                    ->with('user:uid,nickname,thumb,vip_level,sexType,expired_at,uuid,aff')
                    ->whereIn('id', $likeVid)
                    ->where('is_aw', MvModel::AW_NO)
                    ->get();
                return array_keep_idx($items, $likeVid);
            }, 600);
        //$this->limit = 1;
//        $uidArt = UserAttentionModel::getList(request()->getMember())->pluck('touid');
//        $list = null;
//        if ($uidArt) {
//            $list = $cached->serializerJSON()
//                ->expired(600)
//                ->setSaveEmpty(true)
//                ->fetch(function () use ($uidArt, $uid) {
//                    /* foreach ($uidArt as $_u) {
//                         redis()->sAdd(UserAttentionModel::REDIS_USER_FOLLOWED_LIST . $uid, $_u);
//                     }*/
//                    $items = $query = MvModel::queryBase()
//                        ->with('user_topic')
//                        ->whereIn('uid', $uidArt)
//                        ->where('is_aw', MvModel::AW_NO)
//                        ->with('user:uid,nickname,thumb,vip_level,sexType,expired_at,uuid,aff')
//                        ->forPage($this->page, $this->limit)
//                        ->orderByDesc('id')
//                        ->get();
//                    return (new \service\MvService())->v2format($items, request()->getMember());
//                });
//        }
        $vlist = [];
        if (empty($list)) {
            $cached = cached('foryou:follow:' . $this->member['uid'])->hash($this->page);
            $vlist = $cached->serializerJSON()
                ->expired(600)
                ->fetch(function () {
                    //$subQuery = UserAttentionModel::where('uid', $this->member['uid'])->select('touid');
                    $uidAry = collect(explode(',', setting('follow:foryou', '99,100,101,102,103')));
                    if ($uidAry->count() > 5) {
                        $uidAry = $uidAry->random(5);
                    }
                    $items = MvModel::queryWithUser()
                        ->with('user_topic')
                        ->select(['mv.*'])
                        //->leftJoin("member_attention as ma", 'ma.touid', 'mv.uid')
                        ->whereIn('uid', $uidAry)
                        ->where('is_aw', MvModel::AW_NO)
                        //->whereRaw(sprintf("uid in (%s)", $subQuery->toSql()), $this->member['uid'])
                        ->forPage($this->page, $this->limit)
                        ->orderByDesc('id')
                        ->get();
                    return (new \service\MvService())->v2format($items, request()->getMember());
                });
        }
        $result = [
            'list' => (new \service\MvService())->v2format($list, $member),
        ];
        //errLog("follw:".var_export([$list,$vlist],1));
        if (!empty($vlist)) {
            $result['vlist'] = $vlist;
        }
        return $this->showJson($result);
    }

    /**
     * @return bool
     * @author xiongba
     */
    public function detailAction()
    {
        $id = $this->post['id'] ?? 0;
        try {
            if (empty($id)) {
                throw new \Yaf\Exception('参数错误', 422);
            }
            $data = (new \service\MvService())->firstById($id, request()->getMember());
            $data['my_ticket_number'] = MvTicketModel::myInitMvTicketNumber(request()->getMember());
            return $this->showJson($data);
        } catch (Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }


    /**
     * 新的视频详情 带有推荐和评论
     * @return bool
     * @date 2024.6.17
     * @Version 4.7.3
     */
    public function mv_detailAction()
    {
        $id = $this->post['id'] ?? 0;
        $tab_id = $this->post['tab_id'] ?? 0;
        try {
            if (empty($id)) {
                throw new \Yaf\Exception('参数错误', 422);
            }
            $member = request()->getMember();
            $service  = new MvService();
            $detail = $service->firstById($id, $member);

            if($tab_id && ($tab_id >= -1)){
                $service->add2Rank($detail['id'],$tab_id);
                $service->add2SeeRank($detail['id'],$tab_id);
            }
            $detail['my_ticket_number'] = MvTicketModel::myInitMvTicketNumber($member);
            $data['detail'] = $detail;
            $data['recommend'] = null ;//MvService::getRecommendByMvTags($detail['tags_list'],$detail['is_aw'],$detail['id']);
            $data['ads'] =  \service\AdService::getADsByPosition(AdsModel::POS_ORIGINAL_BANNER);
            if($detail['collect_id']){
                $topic_data = \service\TopicService::getTopicInfo($detail['collect_id'],$member);
                if($topic_data){
                    $info['id'] = $topic_data['id'];
                    $info['title'] = $topic_data['title'];
                    $data['topic_info']  =   $info;
                }
            }
            if($data){
                jobs([MvModel::class, 'add2SeeRank'], [$detail['id'], $detail['construct_id']]);
            }
            return $this->showJson($data);
        } catch (Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 新的视频详情 带有推荐和评论-最新迭代
     * @return bool
     * @date 2024.6.17
     * @Version 4.7.3
     */
    public function detail480Action()
    {
        try {
            $id = $this->post['id'] ?? 0;
            test_assert($id, '参数错误', 422);
            $member = request()->getMember();
            $service  = new MvService();
            $detail = $service->firstById($id, $member);

            $detail['my_ticket_number'] = MvTicketModel::myInitMvTicketNumber($member);
            $data['detail'] = $detail;
            $data['recommend'] = null ;//MvService::getRecommendByMvTags($detail['tags_list'],$detail['is_aw'],$detail['id']);
            $data['ads'] =  \service\AdService::getADsByPosition(AdsModel::POS_ORIGINAL_BANNER);
            if($detail['collect_id']){
                $topic_data = \service\TopicService::getTopicInfo($detail['collect_id'],$member);
                if($topic_data){
                    $info['id'] = $topic_data['id'];
                    $info['title'] = $topic_data['title'];
                    $data['topic_info']  =   $info;
                }
            }
            //各种排行版
            if($detail && $detail['is_18'] == MvModel::IS_18_YES){
                if ($detail['type'] == MvModel::TYPE_SHORT){
                    //短视频在看和推荐
                    jobs([MvModel::class, 'add2ShortMvSeeRank'], [$detail['id']]);
                }else{
                    if (isset($detail['construct_id'])){
                        jobs([MvModel::class, 'add2SeeRank'], [$detail['id'], $detail['construct_id']]);
                    }
                    //全部
                    if (isset($detail['is_aw']) && $detail['is_aw'] == MvModel::AW_NO){
                        //所有长视频和推荐
                        jobs([MvModel::class, 'add2LongMvSeeRank'], [$detail['id']]);
                    }
                }
            }
            return $this->showJson($data);
        } catch (Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 视频推荐
     * @return bool|null
     * @throws Exception
     */
    public function detail_recommendAction(){
        try {
            $data = $this->post;
            $validator = Validator::make($data, [
                'id' => 'required|numeric'
            ]);
            if ($validator->fail($msg)){
                $this->errorJson($msg);
            }
            $id = $data['id'];
            $data['recommend'] = MvService::getRecommendByMvTags($id);
            return $this->showJson($data);
        }catch (Throwable $e){
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 改变置顶的状态
     * @return bool|void
     * @author xiongba
     * @date 2020-10-15 14:28:19
     */
    public function toggleTopAction()
    {
        $id = $this->post['id'] ?? 0;
        try {
            if (empty($id)) {
                throw new \Exception('参数错误', 422);
            }
            /** @var MvModel $model */
            $where = ['id' => $id, 'uid' => $this->member['uid']];
            $model = MvModel::where($where)->first();
            if (empty($model)) {
                throw new \Exception('视频不存在', 422);
            }
            $set_top = $model->is_top ? 0 : 1;
            if ($model->is_top == MvModel::IS_TOP_NO) {
                //如果视频没有置顶，标示想置顶，置顶就需要检查一下
                $count = MvModel::where(['uid' => $this->member['uid'], 'is_top' => MvModel::IS_TOP_YES])->count();
                if ($count >= 3) {
                    throw new \Exception('最多置顶3个视频，可取消后再操作', 422);
                }
            }
            $itOk = $model->update(['is_top' => $set_top]);
            /* $itOk = MvModel::toggleColumn($where , 'is_top' , array_keys(MvModel::IS_TOP));*/
            if (empty($itOk)) {
                throw new \Exception('操作失败', 422);
            }
            $model->msg = '操作成功';
            foreach (range(0, 10) as $page) {
                cached(MvModel::REDIS_USER_VIDEOS_ITEM . $model->uid)->suffix("_$page")->clearCached();
            }
            $model->is_top = $set_top;
            return $this->showJson($model->toArray());
        } catch (Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }


    /**
     * 视频首页列表
     */
    public function indexAction()
    {
        $date = date('Y-m-d');
        'test' == APP_ENVIRON && $date = '2020-10-16';//测试环境
        $tuiVidArray = [];
        $mvList = DailyVideoModel::getVideoByDate($date, 3, $tuiVidArray);
        $query = MvModel::queryWithUser()
            ->with('user_topic')
            ->selectRaw('id,topic_id,uid,coins,vip_coins,title,duration,rating,`like`,tags,cover_thumb,m3u8,full_m3u8,is_free,status,is_aw,filter_vip');
        try {
            $ids = MvTotalModel::getVideoId('sale_num', 4);
            $list = (clone $query)->whereIn('id', $ids)->orderByDesc('id')->limit(100)->get();
            $list = (new \service\MvService())->v2format($list, request()->getMember());
            $list = array_sort_by_idx($list, $ids, 'id');
            $hotSale = [
                'type'    => 'dou_mai',
                'name'    => '热门购买',
                'subName' => '',
                'icon'    => url_live('/new/xiao/20201014/2020101415232031734.png'),
                'desc'    => '',
                'item'    => $list
            ];
        } catch (\Throwable $e) {
            $hotSale = [];
        }

        try {
            $ids = MvTotalModel::getVideoId('like_num', 4);
            $list = (clone $query)->whereIn('id', $ids)->orderByDesc('id')->limit(100)->get();
            $list = (new \service\MvService())->v2format($list, request()->getMember());
            $list = array_sort_by_idx($list, $ids, 'id');
            $hotLike = [
                'type'    => 'dou_xi_huan',
                'name'    => '热门点赞',
                'subName' => '',
                'icon'    => url_live('/new/xiao/20201014/2020101415240919479.png'),
                'desc'    => '',
                'item'    => $list
            ];
        } catch (\Throwable $e) {
            $hotLike = [];
        }

        $tuiData = (new \service\MvService())->v2format($mvList, request()->getMember());
        //$tuiData = array_sort_by_idx($tuiData, $tuiVidArray, 'id');
        shuffle($tuiData);

        $body = [
            [
                'type'    => 'mei_ri',
                'name'    => '今日热点',
                'subName' => substr($date, 5) . '日已更新',
                'icon'    => url_live('/new/xiao/20200923/2020092320045894756.png'),
                'desc'    => '',
                'item'    => $tuiData
            ],
        ];

        if (!empty($hotSale)) {
            $body[] = $hotSale;
        }
        if (!empty($hotLike)) {
            $body[] = $hotLike;
        }

        $tagStrId = setting('index:tags', '24:28152-28153-28154');
        $ary = explode(',', $tagStrId);
        $tagIds = [];
        $dataIds = [];
        foreach ($ary as $item) {
            $_ary = explode(':', $item);
            $tagIds[] = $tagId = $_ary[0];
            $dataIds[$tagId] = explode('-', $_ary[1]) ?? [];
        }
        $items = TagsModel::whereIn('id', $tagIds)
            ->get()
            ->keyBy('id')
            ->map(function (\TagsModel $item) use ($dataIds, $query) {
                $ids = $dataIds[$item->id] ?? [];
                if (empty($ids)) {
                    $ids = cached('index:tags-vid:' . $item->id)
                        ->expired(7200)
                        ->serializerJSON()
                        ->fetch(function () use ($item) {
                            return MvTagModel::where('tag', $item->name)->orderByDesc('id')->limit(100)->pluck('mv_id');
                        });
                }
                $items = (clone $query)->whereIn('id', collect($ids)->shuffle()->slice(0, 3))->limit(3)->get();
                $item->list = (new \service\MvService())->v2format($items);
                return $item;
            });
        $items = array_keep_column($items->toArray(), $tagIds);
        $items = array_values($items);
        $body[] = [
            'type'    => 'tags-mv',
            'name'    => '发现精彩',
            'subName' => '',
            'icon'    => url_live('/new/xiao/20201014/2020101415240919479.png'),
            'desc'    => '',
            'item'    => array_filter((array)$items)
        ];


        $banner = AdService::getADsByPosition(AdsModel::POSITION_INDEX_HOME);


        $icon = [
            [
                'name' => '入站必刷',
                'icon' => url_live('/new/xiao/20200923/2020092320000422957.png'),
                //'icon' => url_live('/new/ads/20230105/2023010521231990220.png'),//new year 玉兔饮春
                'type' => 'must-welcome',
            ],
            [
                'name' => '金币视频',
                'icon' => url_live('/new/xiao/20201016/2020101618490740250.png'),
                //'icon' => url_live('/new/ads/20230105/2023010521245081854.png'),//new year 玉兔饮春
                'type' => 'vip',
            ],
            [
                'name' => '每周热点',
                'icon' => url_live('/new/xiao/20201015/2020101511413281237.png'),
                //'icon' => url_live('/new/ads/20230105/2023010521252226197.png'),//new year 玉兔饮春
                'type' => 'must-week',
            ],
            [
                'name' => '创作教程',
                'icon' => url_live('/new/xiao/20201015/2020101511421117195.png'),
                //'icon' => url_live('/new/ads/20230105/2023010521262681334.png'),//new year 玉兔饮春
                'type' => 'up-study',
            ]
        ];
        $_ver = $this->post['version']??'5.0.0';
        if(version_compare($_ver, '4.7.0', '>=')){
            $icon = [
                [
                    'name' => '金币视频',
                    'icon' => url_live('/new/xiao/20201016/2020101618490740250.png'),
                    'type' => 'vip',
                ],
                [
                    'name' => '排行榜',
                    'icon' => url_live('/upload_01/ads/20240625/2024062511155583859.png'),
                    'type' => 'rank',
                ],

                [
                    'name' => '合集',
                    'icon' => url_live('/upload_01/ads/20240625/2024062511214557305.png'),
                    'type' => 'collect',
                ],
                [
                    'name' => '创作教程',
                    'icon' => url_live('/new/xiao/20201015/2020101511421117195.png'),
                    'type' => 'up-study',
                ]
            ];
        }
        $hotTopic = cached('index:hot_topic')
            ->serializerJSON()
            ->expired(3600)
            ->setSaveEmpty(true)
            ->fetch(function () {
                return UserTopicModel::queryBase()
                    ->orderByDesc('like_count')
                    ->limit(4)
                    ->get()
                    ->toArray();
            });

        $result = [
            'banner'    => $banner,
            'icon'      => $icon,
            'body'      => $body,
            'hot_topic' => [
                'type'    => 'hot_topic',
                'name'    => '热门合集',
                'subName' => '',
                'icon'    => url_live('/new/xiao/20200923/2020092320080797006.png'),
                'desc'    => '',
                'item'    => $hotTopic
            ],
            'ranking'   => cached("rk:index")->serializerJSON()->expired(3600)->fetch(function () {
                return (new TopCreatorService)->getAllRank('day', 3);
            })
        ];
        if(version_compare($_ver, '4.7.0', '>=')){
            unset($result['hot_topic']);
        }
        return $this->showJson($result);

    }

    /**
     * 获取视频完整播放链接
     * @return bool
     */
    public function longAction()
    {
        $id = $this->post['id'] ?? '';
        if (empty($id)) {
            return $this->errorJson('参数错误');
        }
        $member = request()->getMember();
        MvModel::setWatchUser($member);
        $mv = MvModel::find($id);
        if (!$mv) {
            return $this->errorJson('视频不存在');
        }
        if ($mv->coins > 0 && $mv->uid != $member->uid) {
            if (!$mv->is_pay) {
                return $this->errorJson('视频您还没有购买');
            }
        }
        if (!empty($mv['full_m3u8'])) {
            $playURL = getPlayUrl($mv['full_m3u8'], false);
        } else {
            $playURL = getPlayUrl($mv['m3u8'], false);
        }
        $mv->addHidden(['m3u8', 'cover_thumb', 'full_m3u8', 'v_ext']);
        $mv = $mv->toArray();
        $mv['play_url'] = $playURL;
        $this->showJson($mv);
    }

    /**
     * 点赞
     * @return bool
     */
    public function likingAction()
    {
        try {
            $validator = Validator::make($this->post, [
                'id' => 'required|numeric',
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);
            $id = $this->post['id'];
            if ($this->member['role_id'] == MemberModel::USER_ROLE_LEVEL_BANED) {
                return $this->errorJson('您已经被禁言');
            }
            if (!frequencyLimit(10, 3, request()->getMember())) {
                return $this->errorJson('短时间内赞操作太頻繁了,稍后再试试');
            }
            $member = request()->getMember();
            $service = new \service\MvLikeService();
            $msg = $service->toggleLikeMv($member, $id);

            return $this->showJson(['success' => true, 'msg' => $msg]);
        } catch (Throwable $e) {
            return $this->errorJson('错误的请求');
        }
    }

    /**
     * 提交观看记录
     * @return bool
     */
    public function watchingAction()
    {
        $this->initMember();//入口不处理验证用户 这里单独处理
        $logAry = $this->post['log'] ?? [];
        $watchIdx = $this->post['id_log'] ?? '';
//        $timestamp = (int)($this->post['timestamp'] ?? time());
        $timestamp = time();
        $uid = request()->getMember()->uid;
        /*if($this->member['uid'] == 9575974){
            errLog("loging:".var_export([$logAry,$watchIdx,$timestamp,$uid],true));
        }*/
        if (empty($timestamp)) {
            $timestamp = time();
        }
        if (empty($watchIdx)) {
            return $this->errorJson('参数错误');
        }
        if (is_string($logAry)) {
            $logAry = json_decode($logAry, true);
            if (json_last_error() != JSON_ERROR_NONE) {
                $logAry = [];
            }
        }

        $data = $this->handleCreateWatch(request()->getMember(), $logAry, $watchIdx, $timestamp);
        $this->showJson($data);
    }

    public function pwaWatchingAction()
    {
        $member = request()->getMember();
        $watch_id = $this->post['mv_id'] ?? 0;
        if (!$watch_id) {
            return $this->errorJson("非法参数");
        }
        if ($member->is_vip) {
            $data = [
                'watched_count' => 0,
                'can_watch'     => 1024,// 可用观看次数
            ];
            return $this->showJson($data);
        }
        $key = MvModel::generateWatchKey($member);
        redis()->sAddTtl($key, $watch_id, 86600);
        $watchCount = $this->getUserTodayWatchCount($this->member['uid']);
        $canWatch = (int)setting("site.can_watch_count", 6);
        $data = [
            'watched_count' => $watchCount,//已经看了多少次了
            'can_watch'     => ($canWatch >= $watchCount) ? ($canWatch - $watchCount) : 0,
        ];
        return $this->showJson($data);

    }


    /**
     * 上传视频
     * @author xiongba
     * @date 2020-11-12 09:41:40
     */
    public function uploadAction()
    {
        if (empty($this->post['title']) or empty($url = $this->post['url'])) {
            return $this->errorJson('请填写完整');
        }

        $tags = $this->post['tags'] ?? '';
        $tags = collect(explode(',', $tags))->filter()->values()->toArray();
        if (empty($tags)) {
            return $this->errorJson('标签不能为空');
        }
        $itOk = $this->post['img_url'] ?? '';
        if (empty($itOk)) {
            return $this->errorJson('请上传封面图');
        }
        $itOk = $this->post['url'] ?? '';
        if (empty($itOk)) {
            return $this->errorJson('请上传视频');
        }

        //check
        $res = MvModel::checkMemberToReleaseGoldMV($this->member['uid']);
        $is_fee = $res['can_release_fee'] ?? 0;
        $is_can = $res['can_release'] ?? 0;
        $not_can_msg = $res['msg_tips'] ?? '该用户不允许上传视频';
        if (!$is_can) {
            return $this->errorJson($not_can_msg);
        }
        $coins = isset($this->post['coins']) ? (int)$this->post['coins'] : 0;
        if (!$is_fee && $coins) {
            return $this->errorJson('你的付费视频额度已超比例，请先上传免费视频');
        }
        $member = request()->getMember();
        if ($member->isBan()) {
            return $this->errorJson('你涉嫌违规，没有权限操作');
        }

        $return = $this->uploadMv();
        if ($return['success'] == true) {
            MvUploadIpInfoModel::addData(request()->getMember());//ip 限制 统计
            return $this->showJson($return);
        } else {
            return $this->errorJson($return['msg']);
        }
    }

    // 标签列表
    public function tagsAction()
    {
        $data = $this->getTags();
        $this->showJson($data);
    }

    /**
     *用户发布砖石视频 新增 配置 接口
     */
    public function preUploadAction()
    {
        $member = $this->member;
        //$this->errLog("\r\n preUploadAction:".var_export($member,true));
        $res = MvModel::checkMemberToReleaseGoldMV($member['uid']);
        $is_fee = $res['can_release_fee'] ?? 0;
        /*if (143 == $member['uid']) {//内部测试
            $is_fee = 1;
        }*/

        $tips = setting('upload.tips', '禁止上传未成年、真实强奸、吸毒、枪支、偷拍、侵害他人隐私等违规内容');
        $return = [
            'tags'         => cached('pre:upload:tag')->expired(7200)->serializerJSON()
                ->fetch(function () {
                    return TagsModel::where('user_up', TagsModel::YES)->orderBy('sort_num')->pluck('name')->toArray();
                }),
            'is_fee'       => $is_fee,
            //'price_info' => MvModel::getGoldMVPriceConf(),
            'price_max'    => abs(intval(setting('mv:coins:max', 100))),
            'rule_text'    => $tips,
            'price_text'   => '#txt#，后续可设置为付费。每日总付费视频数量不可超过免费视频数量。',
            'price_strong' => '每日前两部只可上传免费视频',
            'rule'         => [
                'rule'   => '可以上传',
                'status' => 1,
                'msg'    => '上传更多视频,收益更多~',
            ],
            'is_maker'     => (int)$member['auth_status'],
            'new_rule'     => $res
        ];
        $this->showJson($return);
    }

    /**
     * 修复视频时长
     * @return bool|void
     */
    public function fix_durationAction()
    {
//        $id = $this->post['id'] ?? 0;
//        $duration = $this->post['duration'] ?? 0;
//        $url = $this->post['url'] ?? '';
//        if (empty($duration) || empty($id) || empty($url)) {
//            return $this->showJson('ok');
//        }
//        MvModel::where('id', $id)->update(['duration' => $duration]);
        return $this->showJson('ok');
    }

    /**
     * 举报类型
     * @return bool|void
     */
    public function report_typeAction()
    {
        return $this->showJson(explode(',', setting('mv:report-type', '男女,收費不合理,標題黨')));
    }

    /**
     * 举报视频
     * @return bool|void
     */
    public function report_pushAction()
    {
        $mv_id = $this->post['mv_id'] ?? 0;
        $content = $this->post['content'] ?? '';
        $uuid = request()->getMember()->uuid;
        if (empty($mv_id) || empty($content) || empty($uuid)) {
            return $this->showJson('ok');
        }

        $lock = 'lock-r:' . $uuid . '-' . $mv_id;
        if (redis()->setnx($lock, 1)) {
            redis()->expire($lock, 100);
            $mv_uid = MvModel::where('id', $mv_id)->value('uid');
            MvReportModel::createBy($mv_id, $mv_uid, $content, $uuid);
        }
        return $this->showJson('ok');
    }

    /**
     * 答题选项
     */
    public function uploadAnswerAction()
    {
        $data = [
            [
                'title' => '1、下面哪些内容是可以在搜同社区中上传的？',
                'type'  => 0,
                'item'  => [
                    [
                        'name'  => '· A 带有广告水印的视频内容',
                        'check' => 0,
                    ],
                    [
                        'name'  => '· B 同志露屌露屁眼等大尺度内容',
                        'check' => 1,
                    ],
                    [
                        'name'  => '· C 直男AV或短视频内容',
                        'check' => 0,
                    ],
                    [
                        'name'  => '· D 男性幼童大尺度内容',
                        'check' => 0,
                    ],
                    [
                        'name'  => '· E 吸毒嗑药嗨操型内容',
                        'check' => 0,
                    ]
                ]
            ],
            [
                'title' => '2、搜同社区目前上传大小为100M，如果上传的视频超过大小限制，我应该怎么处理？（多选）',
                'type'  => 1,
                'item'  => [
                    [
                        'name'  => '· A 骂在线客服',
                        'check' => 0,
                    ],
                    [
                        'name'  => '· B 不上传',
                        'check' => 0,
                    ],
                    [
                        'name'  => '· C 保证视频清晰度的情况下，压缩视频上传',
                        'check' => 1,
                    ],
                    [
                        'name'  => '· D 裁剪视频，分段上传',
                        'check' => 1,
                    ]
                ]
            ],
            [
                'title' => '3、什么样的视频在搜同中最受欢迎？（多选）',
                'type'  => 1,
                'item'  => [
                    [
                        'name'  => '· A 从头到尾打飞机的视频',
                        'check' => 0,
                    ],
                    [
                        'name'  => '· B 时长较短，1分钟以下的视频',
                        'check' => 0,
                    ],
                    [
                        'name'  => '· C 封面精致，标题吸引，且视频大于5分钟的视频',
                        'check' => 1,
                    ],
                    [
                        'name'  => '· D 剧情丰富，内容不单薄的视频',
                        'check' => 1,
                    ]
                ]
            ],
            [
                'title' => '4、下面哪种方式可以增加自己的视频收入（多选）',
                'type'  => 1,
                'item'  => [
                    [
                        'name'  => '· A 全部上传付费视频',
                        'check' => 0,
                    ],
                    [
                        'name'  => '· B 通过免费视频吸引粉丝，部分付费视频变现',
                        'check' => 1,
                    ],
                    [
                        'name'  => '· C 全部上传免费视频',
                        'check' => 0,
                    ],
                    [
                        'name'  => '· D 付费视频封面精致，标题吸引',
                        'check' => 1,
                    ]
                ]
            ],
            [
                'title' => '5、以下哪项不是搜同社区官方认证制片人的特权？',
                'type'  => 0,
                'item'  => [
                    [
                        'name'  => '· A 最高50%的视频分成',
                        'check' => 0,
                    ],
                    [
                        'name'  => '· B 上传视频更多，官方高速审核通道',
                        'check' => 0,
                    ],
                    [
                        'name'  => '· C 全部上传付费视频',
                        'check' => 1,
                    ],
                    [
                        'name'  => '· D 特殊身份标识，官方流量扶持',
                        'check' => 0,
                    ]
                ]
            ],

        ];
        $tips = setting('upload.tips', '禁止上传未成年、真实强奸、吸毒、枪支、偷拍、侵害他人隐私等违反国际法的内容');
        return $this->showJson(['answer' => $data, 'rule_text' => $tips]);
    }

    //排行榜
    public function list_rank_mvAction(){
        try {
            $rankBy = $this->post['rank_by'] ?? MvTotalModel::FIELD_LIKE;//榜单类型 默认like点赞 sell销量 play热播
            $time = $this->post['rank_time'] ?? 'day';//榜单日期 默认day日榜 week周榜 month月榜 all总榜
            list($page, $limit) = QueryHelper::pageLimit();
            if ($page == 11){
                return $this->listJson([]);
            }
            $service = new \service\RankingService();
            if ($time == 'all'){
                $data = $service->listTotalRank(MvModel::AW_NO, $rankBy, $page, $limit);
            }else{
                $data = $service->listRank(MvModel::AW_NO, $rankBy, $time, $page, $limit);
            }
            return $this->listJson($data);
        } catch (\Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

}