<?php

use helper\QueryHelper;
use service\RecommendService;
use service\TabNewService;

/**
 * 首页相关接口
 *
 * Class RecommendController
 */
class RecommendController extends BaseController
{
    //首页推荐视频接口
    public function indexAction()
    {
        try {
            $member = request()->getMember();
            $service = new RecommendService();
            $result = [];
            //视频
            $result['mv_list'] = $service->getIndexList($member);
            
            return $this->showJson($result);
        }catch (Throwable $e){
            return $this->errorJson($e->getMessage());
        }
    }

    //关注
    public function recommend_followAction(){
        try {
            $member =request()->getMember();
            $service = new TabNewService();
            list($page, $limit) = QueryHelper::pageLimit();
            $result = $service->recommendFollowMvList($member, $page, $limit);
            return $this->showJson($result);
        }catch (Throwable $e){
            $this->errorJson($e->getMessage());
        }
    }

    //发现
    public function discoverAction(){
        try {
            $sort = $this->post['sort'] ?? 'see';
            $service = new RecommendService();
            list($page,$limit) = QueryHelper::pageLimit();
            $result = $service->discover($sort, $page, $limit);
            $this->listJson($result);
        }catch (Throwable $e){
            $this->errorJson($e->getMessage());
        }
    }

}