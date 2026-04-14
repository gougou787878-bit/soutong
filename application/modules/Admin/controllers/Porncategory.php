<?php

/**
 * Class PorncategoryController
 *
 * @date 2024-04-01 15:51:22
 */
class PorncategoryController extends BackendBaseController
{
    /**
     * 列表数据过滤
     * @return Closure
     *
     * @date 2019-12-02 17:08:03
     */
    protected function listAjaxIteration()
    {
        return function ($item) {
            $item->type_str = PornCategoryModel::TYPE_TIPS[$item->type];
            $item->show_style_str = PornCategoryModel::SHOW_STYLE_TIPS[$item->show_style];
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     *
     * @date 2024-04-01 15:51:22
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     *
     * @date 2024-04-01 15:51:22
     */
    protected function getModelClass(): string
    {
       return PornCategoryModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     *
     * @date 2024-04-01 15:51:22
     */
    protected function getPkName(): string
    {
        return 'id';
    }

    /**
     * 定义数据操作日志
     * @return string
     *
     * @date 2019-11-04 17:19:41
     */
    protected function getLogDesc(): string {
        // TODO: Implement getLogDesc() method.
        return '';
    }
}