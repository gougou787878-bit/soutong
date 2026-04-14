<?php

/**
 * Class ExemptcheckController
 * @author xiongba
 * @date 2020-12-29 18:22:29
 */
class ExemptcheckController extends BackendBaseController
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
     * @date 2020-12-29 18:22:29
     */
    public function indexAction()
    {
        $this->display();
    }


    public function getModelQuery()
    {
        $query = MvModel::rightJoin('exempt_check as ec', 'mv.id', '=', 'ec.vid')
            ->select(['mv.*'])
            ->where('ec.is_check', 1);
        return ExemptCheckModel::setGrammar($query);
    }

    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2020-12-29 18:22:29
     */
    protected function getModelClass(): string
    {
        return ExemptCheckModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2020-12-29 18:22:29
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
    protected function getLogDesc(): string
    {
        // TODO: Implement getLogDesc() method.
        return '';
    }
}