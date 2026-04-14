<?php

/**
 * 关系入库
 * Class IndexController
 */
class InBaseController extends \Yaf\Controller_Abstract
{
    private $TABLE_PREFIX = 'ks_';


    /**
     * 喜欢视频关系入库
     */
    public function relationUserLikeVideoAction()
    {
        $redisKey = singleton(StatisticsModel::class)->actorContributeRankKey;
    }

    /**
     * 喜欢主播关系入库
     */
    public function consumeListAction()
    {
        $redisKey = singleton(StatisticsModel::class)->userContributeRankKey;
    }

}
