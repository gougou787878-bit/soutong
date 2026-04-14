<?php
/**
 *
 * @date 2020/2/27
 * @author
 * @copyright kuaishou by KS
 * @todo 热点配置相关
 *
 */

namespace service;

/**
 * Class HotService
 * @package service
 */
class HotService
{
    const HOT_DATA_KEY = 'hotdata';

    /**
     * 热点配置全数据
     *
     * @return mixed
     */
    public static function getHotData()
    {
        $data = cached(self::HOT_DATA_KEY)->expired(3600)->serializerPHP()->fetch(function () {
            $data = \HotModel::queryBase()->select(['id', 'tips'])->get();
            if (!$data) {
                return [];
            }
            return $data->toArray();
        });
        return $data;
    }
    static function getSaohuaTips($sid)
    {
        $data = self::getHotData();
        $data = array_column($data, 'tips', 'id');
        return isset($data[$sid]) ? $data[$sid] : '';
    }

    /**
     *  清除缓存
     * @return int
     */
    public static function clearHotCache()
    {
        return redis()->del(self::HOT_DATA_KEY);
    }

    /**
     * 获取热点配置的mv 随机块结构
     * @param int $mv_id
     * @return array|mixed|null
     */
    public static function getHotMVSliceData($mv_id = 0)
    {
        static $data = null;
        if ($data === null) {
            $data = self::getHotData();
        }
        if (!$data) {
            return [];
        }
        if (count($data) < 2) {
            return $data;
        }
        shuffle($data);
        $sand = rand(3, 8);
        $chunkData = array_chunk($data,$sand);
        return $chunkData[0];
    }


}