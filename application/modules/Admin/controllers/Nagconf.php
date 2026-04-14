<?php

/**
 * Class NagconfController
 *
 * @date 2023-08-10 17:47:00
 */
class NagconfController extends BackendBaseController
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
            /** @var NagConfModel $item */
            $item->load('navigation');
            $item->nag_title = '';
            if (isset($item->navigation->title)){
                $am_str = $item->navigation->is_aw ? "暗网" : "明网";
                $item->nag_title = sprintf("%s(%s)",$item->navigation->title,$am_str);
            }
            $item->nag_mid_str = NavigationModel::MID_STYLE_CONF_TIPS[$item->mid_style];
            $item->type_str = NagConfModel::CONF_TIPS[$item->type];
            $item->icon_url = url_cover($item->icon);
            $item->status_str = NavigationModel::STATUS_TIPS[$item->status];
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     *
     * @date 2023-08-10 17:47:00
     */
    public function indexAction()
    {
        $nagArr = [];
        NavigationModel::where('status',NavigationModel::STATUS_YES)
            ->selectRaw('id,title,is_aw')
            ->get()->map(function ($item) use (&$nagArr){
                $am_str = $item->is_aw ? "暗网" : "明网";
                $nagArr[$item->id] = sprintf("%s(%s)", $item->title, $am_str);
            });
        $this->assign('nagArr', $nagArr);
        $this->display();
    }

    /**
     * 获取对应的model名称
     * @return string
     *
     * @date 2023-08-10 17:47:00
     */
    protected function getModelClass(): string
    {
        return NagConfModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     *
     * @date 2023-08-10 17:47:00
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
}