<?php

/**
 * Class ExchangecodeController
 * @author xiongba
 * @date 2020-03-04 20:19:12
 */
class ExchangecodeController extends BackendBaseController
{
    /**
     * 列表数据过滤
     * @return Closure
     * @author xiongba
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
     * @author xiongba
     * @date 2020-03-04 20:19:12
     */
    public function indexAction()
    {
        $this->display();
    }


    public function delAction()
    {
    }

    public function saveAction()
    {
    }

    public function delAllAction()
    {
    }

    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2020-03-04 20:19:12
     */
    protected function getModelClass(): string
    {
       return ExchangeCodeModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2020-03-04 20:19:12
     */
    protected function getPkName(): string
    {
        return 'id';
    }

    /**
     * 定义数据操作日志
     * @return string
     * @author xiongba
     * @date 2019-11-04 17:19:41
     */
    protected function getLogDesc(): string {
        // TODO: Implement getLogDesc() method.
        return '';
    }
}