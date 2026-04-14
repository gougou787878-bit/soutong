<?php

/**
 * Class WordNoticeController
 *
 * @date 2022-03-21 10:42:35
 */
class WordNoticeController extends BackendBaseController
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
            $item->position_str =$item->position > 0 ? WordNoticeModel::POSITION[$item->position]:'未选择';
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     *
     * @date 2022-03-21 10:42:35
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     *
     * @date 2022-03-21 10:42:35
     */
    protected function getModelClass(): string
    {
       return WordNoticeModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     *
     * @date 2022-03-21 10:42:35
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

    public function saveAfterCallback($model)
    {
        if ($model){
            WordNoticeModel::clearCache($model->position);
        }
    }
}