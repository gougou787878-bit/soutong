<?php

/**
 * Class VersionController
 * @author xiongba
 * @date 2021-04-28 11:44:51
 */
class VersionController extends BackendBaseController
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
     * @date 2021-04-28 11:44:51
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2021-04-28 11:44:51
     */
    protected function getModelClass(): string
    {
        return VersionModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2021-04-28 11:44:51
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

    protected function saveAfterCallback($model)
    {
        if (is_null($model)) {
            return;
        }
        VersionModel::clearVersionCache(VersionModel::TYPE_ANDROID);//清楚缓存
        VersionModel::clearVersionCache(VersionModel::TYPE_IOS);//清楚缓存
        if (empty($model->via)){
            $apk = $model->apk;
            $is_update = 0;
            if ($model->custom == VersionModel::CUSTOM_NO){
                $old_host = parse_url($model->apk, PHP_URL_HOST);
                $new_host = parse_url(TB_APP_DOWN_URL, PHP_URL_HOST);
                $apk = str_replace($old_host, $new_host, $model->apk);
            }else{
                $is_update = 1;
            }
            jobs([VersionModel::class, 'defend_apk'], [$apk, $is_update]);
        }
    }

    public function refreshAction()
    {
        VersionModel::clearVersionCache(VersionModel::TYPE_ANDROID);//清楚缓存
        VersionModel::clearVersionCache(VersionModel::TYPE_IOS);//清楚缓存
        return $this->ajaxSuccess('成功');
    }

}