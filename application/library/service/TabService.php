<?php


namespace service;

class TabService extends \AbstractBaseService
{

    const TAB_LIST_KEY = 'tab:list';
    const TAB_AW_LIST_KEY = 'tab:aw:list';
    const TAB_CAT_KEY = 'tab:cate';
    const TAB_SEARCH_KEY = 'tab:search';

    static function getCateList()
    {
        return cached(self::TAB_CAT_KEY)->serializerJSON()
            ->expired(4000)
            ->fetch(function () {
                return \TabModel::queryBase(['is_category' => 1])
                    ->select(['tab_id', 'tab_name', 'tags_str'])
                    ->orderByDesc('sort_num')
                    ->get()
                    ->toArray();
            });
    }

    /**
     * 获取tab栏目标签
     * @return \Illuminate\Support\Collection
     */
    public function getTabList()
    {
        $list = cached(self::TAB_LIST_KEY)
            ->serializerJSON()
            ->expired(7200)
            ->fetch(function () {
                $collect = \TabModel::queryBase(['is_tab' => 1])
                    ->orderBy('sort_num')
                    ->get(['tab_id', 'tab_name', 'sort_num', 'status']);
                return collect2raw($collect);
            });
        return \TabModel::makeCollect($list);
    }

    /**
     * 获取tab栏目标签
     * @return \Illuminate\Support\Collection
     */
    public function getAwTabList()
    {
        $list = cached(self::TAB_AW_LIST_KEY)
            ->fetchPhp(function () {
                $collect = \TabModel::queryBase(['is_aw' => 1])
                    ->orderBy('sort_num')
                    ->get(['tab_id', 'tab_name', 'sort_num', 'status']);
                return collect2raw($collect);
            },7200);
        return \TabModel::makeCollect($list);
    }

    public static function getSearchList()
    {
        $list = cached(self::TAB_SEARCH_KEY)
            ->serializerJSON()
            ->expired(7200)
            ->fetch(function () {
                $collect = \TabModel::queryBase(['is_search' => 1])
                    ->orderBy('sort_num')->get()->map(function ($item) {
                        if (is_null($item)) {
                            return null;
                        }
                        return [
                            'title' => $item->tab_name,
                            'data'  => $item->tags_ary
                        ];

                    })->filter()->values()->toArray();

                return $collect;
            });
        return $list;
    }

    static function clearTabList()
    {
        redis()->del(self::TAB_LIST_KEY);
        redis()->del(self::TAB_CAT_KEY);
        redis()->del(self::TAB_SEARCH_KEY);
    }


}