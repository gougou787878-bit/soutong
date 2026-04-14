<?php

/**
 * 原创
 * Class OriginalController
 */
class OriginalController extends BackendBaseController
{
    /**
     * 列表数据过滤
     * @return Closure
     */
    protected function listAjaxIteration()
    {
        return function ($item) {
            $item->is_series_str = OriginalModel::SERIES_TIPS[$item->is_series];
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
       return OriginalModel::class;
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

}