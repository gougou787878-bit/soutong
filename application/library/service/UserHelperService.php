<?php
/**
 *
 * @date 2020/3/16
 * @author
 * @copyright kuaishou by KS
 *
 */


namespace service;


use helper\QueryHelper;

class UserHelperService
{

    static function getList()
    {
//        list($limit , $offset , $page) = QueryHelper::restLimitOffset();
//        return cached(\UserhelperModel::REDIS_LIST_KEY)
//            ->suffix($page)
//            ->expired(3600)
//            ->serializerPHP()
//            ->fetch(function () use ($limit , $offset) {
//                return \UserhelperModel::query()->select(['id', 'question', 'answer'])->where('status', '=',
//                    \UserhelperModel::STATUS_ENABLE)->orderByDesc('id')->limit($limit)->offset($offset)->get();
//            });
        list($page , $limit) = QueryHelper::pageLimit();
        $cacheKey = sprintf(\UserhelperModel::REDIS_LIST_KEY . ':new:%s:%s', $page, $limit);
        return cached($cacheKey)
            ->group('helper:user:new')
            ->chinese('常见问题')
            ->fetchPhp(function () use ($page, $limit) {
                return \UserhelperModel::query()
                    ->select(['id', 'question', 'answer'])
                    ->where('status',\UserhelperModel::STATUS_ENABLE)
                    ->orderByDesc('id')
                    ->forPage($page, $limit)
                    ->get();
            });
    }

}