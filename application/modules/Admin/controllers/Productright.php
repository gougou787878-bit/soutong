<?php

/**
 * Class ProductrightController
 *
 * @date 2022-03-29 20:58:48
 */
class ProductrightController extends BackendBaseController
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
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     *
     * @date 2022-03-29 20:58:48
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     *
     * @date 2022-03-29 20:58:48
     */
    protected function getModelClass(): string
    {
       return ProductRightModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     *
     * @date 2022-03-29 20:58:48
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

    public function deleteAfterCallback($model, $isDelete)
    {
        if ($isDelete){
            ProductRightMapModel::where('product_right_id', $model->id)->delete();
        }
    }
}