<?php

use tools\RedisService;

/**
 * 进入直播榜单
 * 贡献榜不能是实时数据
 */

class ContributeRoomController extends IndexController
{
    /**
     * 榜单数据
     */
    function indexAction()
    {
        // uid 为主播的uid
        $liveUUid = $_REQUEST["uuid"];

        $token = $_REQUEST["token"];
        // 通过token找出uid
        $uid = $_REQUEST["userid"];
        if (!$uid || !$liveUUid) {
            return;
        }

        // 榜单数量限制
        $DataRaw = StatisticsModel::instance()->readFace1vs1($liveUUid);

        $data['day'] = [];
        $uuidArray = array_merge($DataRaw['day'], $DataRaw['week'], $DataRaw['month']);
        $memberArray = MemberModel::whereIn('uuid', $uuidArray)->get()->toArray();
        $memberArray = array_reindex($memberArray, 'uuid');
        foreach ($DataRaw['day'] as $item) {
            $v = $memberArray[$item] ?? [];
            if (!empty($v)){
                $v['thumb'] = url_avatar($v['thumb']);
                $v['d'] = RedisService::redis()->zScore('real_statistics_day_' . $liveUUid, $item);
                $data['day'][] = $v;
            }
        }

        $data['week'] = [];
        foreach ($DataRaw['week'] as $item) {
            $v = $memberArray[$item] ?? [];
            if (!empty($v)) {
                $v['thumb'] = url_avatar($v['thumb']);
                $v['w'] = RedisService::redis()->zScore('real_statistics_week_' . $liveUUid, $item);
                $data['week'][] = $v;
            }
        }

        $data['month'] = [];
        foreach ($DataRaw['month'] as $item) {
            $v = $memberArray[$item] ?? [];
            if (!empty($v)) {
                $v['thumb'] = url_avatar($v['thumb']);
                $v['m'] = RedisService::redis()->zScore('real_statistics_month_' . $liveUUid, $item);
                $data['month'][] = $v;
            }
        }

        $data['total'] = [];
       /* foreach ($DataRaw['total'] as $item) {
            $v = \tools\RedisService::redis()->get('user:' . $item);
           // if (!$v) {
               $v =  $MemberModel->where('uuid',$item)->first()->toArray();
           // } else {
           //     $v = unserialize($v);
          //  }
            $v['thumb'] = $this->config->img->img_head_url . $v['thumb'];
            $v['a'] =  \tools\RedisService::redis()->zScore('real_statistics_total_'.$liveUUid, $item);
            $data['total'][] = $v;
        }*/

        $config = ConfigModel::instance()->getConfig();
        $this->getView()->assign('config', $config);


       // $member = $this->member;
        $member =MemberModel::where('uid',$uid)->first()->toArray();

        $this->member['thumb'] = url_avatar($member['thumb']);


        $this->getView()->assign('meInfo', $this->member);
        $this->getView()->assign('data', $data);

        $liveData = MemberModel::where('uuid',$liveUUid)->first()->toArray();

        $this->getView()->assign('liveData', $liveData);


        // 主播当天妹币数
        $actorDayKey = singleton(StatisticsModel::class)->actorDay;
        $actorWeekKey = singleton(StatisticsModel::class)->actorWeek;
        $actorMonthKey = singleton(StatisticsModel::class)->actorMonth;
        $actorTotalKey = singleton(StatisticsModel::class)->actorTotal;

        $data['actor_total']['d'] = \tools\RedisService::zScore($actorDayKey,$liveUUid)?:0;
        $data['actor_total']['w'] = \tools\RedisService::zScore($actorWeekKey,$liveUUid)?:0;
        $data['actor_total']['m'] = \tools\RedisService::zScore($actorMonthKey,$liveUUid)?:0;
        //$data['actor_total']['a'] = \tools\RedisService::zScore($actorTotalKey,$liveUUid)?:0;
        $data['actor_total']['a'] = 0;

        $this->show('ContributeRoom');

    }

}