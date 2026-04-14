<?php

/**
 * Class NavigationController
 * @date 2024-10-15 15:31:07
 */
class NavigationController extends BackendBaseController
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
            /** @var NavigationModel $item */
            //$item->status_str = NavigationModel::STATUS_TIPS[$item->status];
            $item->mid_style_str = NavigationModel::MID_STYLE_TIPS[$item->mid_style];
            $item->bot_style_str = NavigationModel::BOT_STYLE_TIPS[$item->bot_style];
            //$item->is_mw_str = NavigationModel::STATUS_TIPS[$item->is_mw];
            //$item->is_aw_str = NavigationModel::STATUS_TIPS[$item->is_aw];
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     * @author xiongba
     * @date 2024-10-15 15:31:07
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2024-10-15 15:31:07
     */
    protected function getModelClass(): string
    {
       return NavigationModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2024-10-15 15:31:07
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