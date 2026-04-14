<?php
/**
 *
 * @date 2020
 * @author
 * @todo 进站笔刷 相关逻辑
 *
 */

namespace service;

use helper\QueryHelper;

/**
 * Class PitTopService
 * @package service
 */
class PitTopService
{
    const TOPIC_LIST_KEY = 'pit:list:';

    /**
     * 进站笔涮
     * @param \MemberModel $member
     * @param int $page
     * @param int $limit
     * @return array|mixed
     */
    public static function getTopicList(\MemberModel $member, $page, $limit)
    {
        $topData = cached(self::TOPIC_LIST_KEY)
            ->expired(3600)
            ->serializerJSON()
            ->fetch(function () use ($limit, $page) {
                $items = \PitTopModel::queryBase()
                    ->orderByDesc('id')
                    ->forPage($page , $limit)
                    ->get();
                return collect2raw($items);
            });

        if (!$topData) {
            return [];
        }
        $topData = \PitTopModel::itrelated($topData, 'mv');
        $items = [];
        collect($topData)->each(function (\PitTopModel $item) use (&$items) {
            if ($item->mv) {
                $mv = (new MvService())->formatItem($item->mv);
                $mv->pit_id = $item->id;
                $mv->pit_comment = $item->comment;
                $mv->pit_created_str = date('Y-m-d', $item->created_at);
                $items[] = $mv;
            }
        });
        return $items;
    }
    /**
     * 清除列表缓存
     * @param string $date
     * @return int
     */
    static function clearCache()
    {
        redis()->del(self::TOPIC_LIST_KEY . 0);
        redis()->del(self::TOPIC_LIST_KEY . 1);
        redis()->del(self::TOPIC_LIST_KEY . 2);
        return true;
    }


}