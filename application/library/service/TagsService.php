<?php


namespace service;


class TagsService
{

    public static function getList(int $page, int $size)
    {
//        $ids = cached('tb:tags:page')
//            ->clearCached()
//            ->hash($page)
//            ->expired(3600)
//            ->serializerJSON()
//            ->fetch(function () use ($page, $size) {
//                return \TagsModel::queryBase()->orderBy('sort_num')->forPage($page, $size)->pluck('id');
//            });
        $data = \TagsModel::queryBase()
            ->where('status', \TagsModel::YES)
            //->whereIn('id', $ids)
            ->orderBy('sort_num')->orderByDesc('id')->forPage($page, $size)
            ->get();
//        $data = array_sort_by_idx($data , $ids , 'id');
//        $data = collect($data);
        if ($data->isEmpty()) {
            return [];
        }
        return $data;
    }
}