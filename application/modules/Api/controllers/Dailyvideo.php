<?php

use service\DailvideoService;
use service\MvService;


/**
 * Class DailyvideoController
 */
class DailyvideoController extends BaseController
{




    /**
     * 每日推荐视频列表 根据分页查询
     * @param int $limit 20
     * @param int $page 1
     *
     * @return bool|void
     */
    public function listAction()
    {
        $date = $this->post['date'] ?? date('Y-m-d', TIMESTAMP);
        $page = $this->post['page'] ?? 1;
        if ($page > 1) {
            $page = $page - 1;
            $date = date('Y-m-d', strtotime("-{$page} days"));
        }
        if (empty($date)) {
            $date = date('Y-m-d', TIMESTAMP);
        }
        $rowInfo = DailvideoService::getDailyVideoInfoByDate($date);
        if (!$rowInfo || $rowInfo->number < 1) {
            return $this->showJson([]);
        }
        $dailyData = $rowInfo->toArray();
        $mvData = $dailyData['mvList'];
        $data =(new MvService())->v2format($mvData);
        unset($dailyData['mvList']);
        return $this->showJson(['info' => $dailyData, 'list' => $data]);
    }

}