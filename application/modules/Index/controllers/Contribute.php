<?php
/**
 * 贡献榜
 * 贡献榜不能是实时数据
 */

class ContributeController extends IndexController
{

    /**
     * 某人的的榜单数据
     */
    function indexAction()
    {
        // uid 为主播的uid
        $liveUid = $_REQUEST["uid"];

        $token = $_REQUEST["token"];
        // 通过token找出uid
        $uid = $_REQUEST["userid"];

        // 每用户每主播榜单数
        $redisKey = singleton(StatisticsModel::class)->liveTimeRankKey;

        $cache = getCaches($redisKey);

        $result = unserialize($cache);

        $result = empty($result) ? [] : $result;


        // 每主播榜单数
        $redisActorKey = singleton(StatisticsModel::class)->actorContributeRankKey;
        $cacheActor = getCaches($redisActorKey);
        $resultActor = unserialize($cacheActor);

        // 榜单数量限制
        $data['day'] = array_sort_2($result, 'd', 'desc', ['itemByUid']);
        $data['week'] = array_sort_2($result, 'w', 'desc', ['itemByUid']);
        $data['month'] = array_sort_2($result, 'm', 'desc', ['itemByUid']);
        $data['total'] = array_sort_2($result, 'a', 'desc', ['itemByUid']);

        $data['actor_total'] = $resultActor[$uid];

        $meInfo = $result["{$liveUid}_to_{$uid}"] ?? [];

        // 取出用户信息
        if ($meInfo) {
            $meInfo = MemberModel::find($uid);
        }

        $config = ConfigModel::instance()->getConfig();
        $this->view->assign('config', $config);
        $this->view->assign('meInfo', $meInfo);
        $this->view->assign('data', $data);

        $this->show('contribute');

    }

}