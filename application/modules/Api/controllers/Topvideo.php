<?php


use helper\QueryHelper;

class TopvideoController extends BaseController
{


    /**
     * 本周热议榜
     * @author xiongba
     * @date 2020-09-24 15:11:35
     */
    public function commentTop1Action()
    {
        $mvId = cached('week:comment-top-1')
//            ->clearCached()
            ->expired(7200)
            ->serializerJSON()
            ->fetch(function () {
                return CommentModel::where('created_at', '>', date('Y-m-d H:i:s',strtotime('-1 week')))
                    ->groupBy('mv_id')
                    ->selectRaw("count(*) as cc,mv_id")
                    ->orderByDesc('cc')
                    ->limit(2)
                    ->get()
                    ->pluck('mv_id');
            });
        $list = MvModel::queryBase()
//            ->with('user_topic')
            ->with('user:uid,nickname,thumb,vip_level,sexType,expired_at,uuid')
            ->whereIn('id', $mvId)
            ->limit(2)
            ->get();
        $list = (new \service\MvService())->v2format($list);
        foreach ($list as &$item) {
            $item['comment_list'] = CommentModel::with('user:uid,nickname,thumb,vip_level,sexType,expired_at,uuid')
                ->where('mv_id', $item['id'])
                ->orderByDesc('like_num')
                ->orderByDesc('id')
                ->limit(5)
                ->get();
        }
        unset($item);

        return $this->showJson((new \service\MvService())->v2format($list, request()->getMember()));
    }


    /**
     * 热播 点赞
     * @author xiongba
     * @date 2020-09-24 15:09:04
     */
    public function likeAction()
    {
        $searchService = new \service\SearchService;
        if(version_compare($this->post['version'], '4.7.0', '>=')){
            list($limit, $offset, $page) = QueryHelper::restLimitOffset();
            $list = $searchService->getRankListByType('like',$offset,$limit);
        }else{
            $list = $searchService->getHotLike(30);
        }

        return $this->showJson($list);
    }

    /**
     * 热销 购买
     * @return bool
     * @author xiongba
     * @date 2020-09-24 15:10:10
     */
    public function saleAction()
    {
        $searchService = new \service\SearchService;
        if(version_compare($this->post['version'], '4.7.0', '>=')){
            list($limit, $offset, $page) = QueryHelper::restLimitOffset();
            $list = $searchService->getRankListByType('sale',$offset,$limit);
        }else{
            $list = $searchService->getHotSale(30);
        }

        return $this->showJson($list);
    }


    /**
     * 热播 浏览
     * @return bool
     * @author xiongba
     * @date 2020-09-24 15:10:10
     */
    public function playAction()
    {
        $searchService = new \service\SearchService;
        if(version_compare($this->post['version'], '4.7.0', '>=')){
            list($limit, $offset, $page) = QueryHelper::restLimitOffset();
            $list = $searchService->getRankListByType('view',$offset,$limit);
        }else{
            $list = $searchService->getHotPlay(30);
        }
        return $this->showJson($list);
    }

    /**
     * 创作者视频 热播 排行
     * @return bool
     * @author xiongba
     * @date 2020-09-24 15:10:10
     */
    public function creator_playAction()
    {
        $searchService = new \service\SearchService;
        if(version_compare($this->post['version'], '4.7.0', '>=')){
            list($limit, $offset, $page) = QueryHelper::restLimitOffset();
            $list = $searchService->getRankListByType('creator',$offset,$limit);
        }else{
            $list = $searchService->getCreatorHotPlay(30);
        }
        return $this->showJson($list);
    }


}