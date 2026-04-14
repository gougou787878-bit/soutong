<?php

/**
 * Class PrivilegeController
 *
 * @date 2022-04-08 21:27:32
 */
class PrivilegeController extends BackendBaseController
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
     * @date 2022-04-08 21:27:32
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     *
     * @date 2022-04-08 21:27:32
     */
    protected function getModelClass(): string
    {
       return PrivilegeModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     *
     * @date 2022-04-08 21:27:32
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
            ProductPrivilegeModel::where('privilege_id', $model->id)->delete();
        }
    }
}