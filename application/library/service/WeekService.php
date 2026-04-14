<?php
/**
 * @author
 * @todo 每周必看 相关处理
 *
 */

namespace service;

use helper\QueryHelper;

/**
 * Class WeekService
 * @package service
 */
class WeekService
{
    const TOPIC_LIST_KEY = 'wk:list:';
    const TOPIC_MV_KEY = 'wk:mv:';

    static function getMaxWeekID()
    {
        return cached('wk:max')->expired(400)->fetch(function () {
            $data = \WeekModel::queryBase()->select(['id'])->orderByDesc('id')->first();
            return $data ? $data->id : 0;
        });
    }


    /**
     * 获取可用列表
     * @param $page
     * @param $size
     * @return mixed
     */
    public static function getTopicList($limit,$offset,$page)
    {
        $data = cached(self::TOPIC_LIST_KEY.$page)
            ->expired(3600)
            ->serializerJSON()
            ->fetch(function () use ($limit, $offset) {
                return \WeekModel::queryBase()->orderByDesc('sort_number')->orderByDesc('id')->limit($limit)->offset($offset)->get()->toArray();
            });
        if (empty($data)) {
            return [];
        }
        return $data;
    }



    /**
     * 清除列表缓存
     * @param int $topic_id
     * @return int
     */
    static function clearTopicList()
    {
        redis()->del(self::TOPIC_LIST_KEY.'0');
        redis()->del(self::TOPIC_LIST_KEY.'1');
        redis()->del(self::TOPIC_LIST_KEY.'2');
        return true;
    }

    /**
     * 清除合集视频缓存
     * @param int $topic_id
     * @return int
     */
    static function clearTopicMV($topic_id = 0)
    {
        $topic_id && redis()->del(self::TOPIC_MV_KEY . $topic_id);
        return true;
    }

    /**
     * 后台使用下来 选择
     * @return array|mixed|null
     */
    public static function getSearchTopicList()
    {
        $data = \WeekModel::query()->select(['id','title'])->orderByDesc('created_at')->get()->toArray();

        return array_column($data, 'title', 'id');
    }


    /**
     * 根据编号 获取视频列表
     * @param $topic_id
     * @param \MemberModel $member
     * @return array|\Illuminate\Support\Collection
     */
    public static function getMVList($topic_id, \MemberModel $member)
    {
        $topData = cached(self::TOPIC_MV_KEY.$topic_id)
            ->expired(3600)
            ->serializerJSON()
            ->fetch(function ()use($topic_id) {
                return collect2raw(\WeekRelationModel::query()->where('week_id', $topic_id)->orderByDesc('id')->get());
            });
        if (!$topData) {
            return [];
        }

        return \WeekRelationModel::itRelated($topData , 'mv')->map(function($item){
            if ($item->mv === null){
                return false;
            }
            //$item->mv = (new MvService())->formatItem($item->mv);
            //return $item;
           $mvInfo = (new MvService())->formatItem($item->mv);
          // $mvInfo = (new MvService())->v2format($item->mv);
            $mvInfo['week_id'] = $item->week_id;
            $mvInfo['week_comment'] = $item->comment;
            $mvInfo['week_created_at_txt'] = $item->created_at_txt;
            return $mvInfo;
        })->filter()->values();
    }


}