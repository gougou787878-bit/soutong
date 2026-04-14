<?php

/**
 * 原创视频
 * Class OriginalvideoController
 */
class OriginalvideoController extends BackendBaseController
{
    /**
     * 列表数据过滤
     * @return Closure
     */
    protected function listAjaxIteration()
    {
        return function ($item) {
            $item->title = $item->original->title;
            $item->sort_str = '第'.$item->sort.'集';
            $item->type_str = OriginalvideoModel::TYPE_TIPS[$item->type];
            $item->is_free_str = OriginalvideoModel::FREE_TIPS[$item->is_free];
            $item->source_url =  getAdminPlayM3u8($item->source,true);
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     */
    protected function getModelClass(): string
    {
       return OriginalVideoModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     */
    protected function getPkName(): string
    {
        return 'id';
    }

    /**
     * 定义数据操作日志
     * @return string
     */
    protected function getLogDesc(): string {
        // TODO: Implement getLogDesc() method.
        return '';
    }

    protected function getSearchWhereParam()
    {
        $get = $this->getRequest()->getQuery();
        $get['where'] = $get['where'] ?? [];
        $where = [];
        foreach ($get['where'] as $key => $value) {
            if ($value === '__undefined__') {
                continue;
            }
            $value = $this->formatSearchVal($key, $value);
            $key = $this->formatKey($key);
            if (empty($key)) {
                continue;
            }

            if ($value !== '' && $key !== 'original_title') {
                $where[] = [$key, '=', $value];
            }

            if ($key == 'original_title') {
                $ids = OriginalModel::query()->where("title",'like',"%$value%")->get()->pluck('id')->toArray();
                $ids = $ids ? implode(",", $ids) : '0';
                $where[] = [\DB::raw("pid in ($ids)"),'1'];
            }
        }

        return $where;
    }

}