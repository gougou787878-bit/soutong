<?php

/**
 * Class TagsController
 * @author xiongba
 * @date 2020-09-24 22:40:12
 */
class TagsController extends BackendBaseController
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
     * @date 2020-09-24 22:40:12
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2020-09-24 22:40:12
     */
    protected function getModelClass(): string
    {
        return TagsModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2020-09-24 22:40:12
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

    public function works_updateAction()
    {
        if (!request()->isPost()) {
            return $this->ajaxError('请求失败');
        }

        //作品数更新
        jobs([TagsModel::class, 'updateWorksNum']);

        return $this->ajaxSuccess('更新操作，已经进去后台任务');
    }
}