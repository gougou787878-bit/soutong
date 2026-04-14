<?php

/**
 * Class SettingController
 * @author xiongba
 * @date 2020-02-26 15:19:34
 */
class SettingController extends BackendBaseController
{

    public function init()
    {
        $this->xssDecode();
        parent::init();
    }

    /**
     * 列表数据过滤
     * @return Closure
     * @author xiongba
     * @date 2019-12-02 17:08:03
     */
    protected function listAjaxIteration()
    {
        return function ($item) {
            return JAddSlashes($item->toArray());
        };
    }

    /**
     * 试图渲染
     * @return string
     * @author xiongba
     * @date 2020-02-26 15:19:34
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2020-02-26 15:19:34
     */
    protected function getModelClass(): string
    {
        return SettingModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2020-02-26 15:19:34
     */
    protected function getPkName(): string
    {
        return 'id';
    }

    function saveAfterCallback($model)
    {
        if(!is_null($model)){
            SettingModel::pushCached();
        }
    }

    public function _deleteActionAfter()
    {
        SettingModel::pushCached();
    }


}