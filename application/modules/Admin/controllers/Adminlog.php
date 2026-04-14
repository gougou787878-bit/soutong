<?php

/**
 * Class AdminlogController
 * @author xiongba
 * @date 2020-01-17 18:57:38
 */
class AdminlogController extends BackendBaseController
{
    /**
     * 列表数据过滤
     * @return Closure
     * @author xiongba
     * @date 2019-12-02 17:08:03
     */
    protected function listAjaxIteration()
    {
        return function ( AdminLogModel $item) {
            if (in_array($this->getUser()->username , ['superadmin' , 'ksxiongba'])){
                $item->context = '';
            }
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     * @author xiongba
     * @date 2020-01-17 18:57:38
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2020-01-17 18:57:38
     */
    protected function getModelClass(): string
    {
       return AdminLogModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2020-01-17 18:57:38
     */
    protected function getPkName(): string
    {
        return 'id';
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2019-11-04 17:19:41
     */
    protected function getLogDesc(): string
    {
       return '';
    }
}