<?php
/**
 *
 * @date 2020/2/27
 * @author
 * @copyright kuaishou by KS
 * @todo 每日推荐业务逻辑控制
 *
 */

namespace service;

use helper\QueryHelper;

/**
 * Class DailvideoService
 * @package service
 */
class DailvideoService
{
    const D_MV_KEY = 'daily:vlist:';

    /**
     * @param $date
     * @return \DailyVideoModel
     */
    static function getDailyVideoInfoByDate($date,$limit = 10)
    {
        $data = cached(self::D_MV_KEY . $date)
            ->serializerJSON()
            ->expired(3600)
            ->fetch(function () use ($date) {
                $data = \DailyVideoModel::queryBase()->where('day', $date)->first();
                if (is_null($data)) {
                    return null;
                }
                return $data->getAttributes();
            });
        if (!$data) {
            return [];
        }
        $data = \DailyVideoModel::makeOnce($data);
        $data->mvList = $data->getVideos($limit);
        return $data;
    }

    /**
     * @param $getDailyVideoInfoByDate |  return func getDailyVideoInfoByDate()
     * @return array
     */
    static function getFormateVideoMvData($getDailyVideoInfoByDateMV)
    {
        return (new MvService())->fetchMvList($getDailyVideoInfoByDateMV);
    }

    /**
     * 清除列表缓存
     * @param string $date
     * @return int
     */
    static function clearMvList($date)
    {
        redis()->del(self::D_MV_KEY . $date);
        return true;
    }


}