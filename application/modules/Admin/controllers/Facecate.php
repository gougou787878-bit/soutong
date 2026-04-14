<?php

/**
 * Class FacecateController
 *
 * @date 2024-04-08 15:52:34
 */
class FacecateController extends BackendBaseController
{
    /**
     * 列表数据过滤
     * @return Closure
     *
     * @date 2019-12-02 17:08:03
     */
    protected function listAjaxIteration()
    {
        return function (FaceCateModel $item) {
            $item->type_str = FaceCateModel::TYPE_TIPS[$item->type];
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     *
     * @date 2024-04-08 15:52:34
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     *
     * @date 2024-04-08 15:52:34
     */
    protected function getModelClass(): string
    {
       return FaceCateModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     *
     * @date 2024-04-08 15:52:34
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