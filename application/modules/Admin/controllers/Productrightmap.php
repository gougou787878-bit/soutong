<?php

/**
 * Class ProductrightmapController
 *
 * @date 2022-03-29 20:58:54
 */
class ProductrightmapController extends BackendBaseController
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
            $item->load(['product','right']);
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     *
     * @date 2022-03-29 20:58:54
     */
    public function indexAction()
    {
        $this->assign('vipData',ProductModel::getAdminVIPDataList());
        $this->assign('rightData',ProductRightModel::getDataList());
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     *
     * @date 2022-03-29 20:58:54
     */
    protected function getModelClass(): string
    {
       return ProductRightMapModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     *
     * @date 2022-03-29 20:58:54
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