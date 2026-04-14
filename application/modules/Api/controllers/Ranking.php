<?php


use tools\RedisService;

class RankingController extends BaseController
{

    /**
     * 获赞榜单
     * @author xiongba
     * @date 2020-06-01 13:52:58
     */
    public function praisedAction()
    {
        $type = $this->post['type'] ?? 'day';
        if (!in_array($type, ['day', 'month', 'week'])) {
            return $this->errorJson('类型不支持');
        }
        try {
            $service = new service\RankingService;
            $uids = $service->getPraisedByDay($type, 0, 10);
            $data = $service->getMemberInfo($uids, $this->member, ['fabulous_count']);
            return $this->showJson(collect($data)->sortByDesc('fabulous_count')->values()->toArray());
        } catch (\Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 邀请榜单
     * @author xiongba
     * @date 2020-06-01 13:53:11
     */
    public function inviteAction()
    {
        $type = $this->post['type'] ?? 'day';
        if (!in_array($type, ['day', 'month', 'week'])) {
            return $this->errorJson('类型不支持');
        }
        try {
            $service = new service\RankingService;
            $uids = $service->getInviteByDay($type, 0, 10);
            $data = $service->getMemberInfo($uids, $this->member, ['invited_num']);
            return $this->showJson(collect($data)->sortByDesc('invited_num')->values()->toArray());
        } catch (\Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }





}