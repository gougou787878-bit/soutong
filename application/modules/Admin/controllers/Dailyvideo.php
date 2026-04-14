<?php

use service\DailvideoService;

/**
 * Class DailyvideoController
 * @author xiongba
 * @date 2020-09-22 19:41:05
 */
class DailyvideoController extends BackendBaseController
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
     * @date 2020-09-22 19:41:05
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2020-09-22 19:41:05
     */
    protected function getModelClass(): string
    {
        return DailyVideoModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2020-09-22 19:41:05
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

    /**
     * @param $model
     */
    protected function saveAfterCallback($model)
    {
        if (is_null($model)) {
            return;
        }
        DailvideoService::clearMvList($model->day);//清楚缓存
    }
}