<?php

/**
 * Class MhtagsController
 * @author xiongba
 * @date 2022-05-17 17:36:58
 */
class MhtagsController extends BackendBaseController
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
     * @date 2022-05-17 17:36:58
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2022-05-17 17:36:58
     */
    protected function getModelClass(): string
    {
       return MhTagsModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2022-05-17 17:36:58
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

    public function tagsListAction()
    {
        return $this->ajaxSuccess(MhTagsModel::orderBy('id', 'DESC')->pluck('name'));
    }
}