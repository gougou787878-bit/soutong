<?php

/**
 * Class ConstructController
 *
 * @date 2020-12-08 15:41:38
 */
class ConstructController extends BackendBaseController
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
            /** @var ConstructModel $item */
            $item->load('navigation');
            $item->icon_url = url_cover($item->icon);
            $item->bg_thumb_url = url_cover($item->bg_thumb);
            $item->show_style_str = ConstructModel::SHOW_STYLE[$item->show_style];
            $item->nag_title = '';
            if (isset($item->navigation->title)){
                $am_str = $item->navigation->is_aw ? "暗网" : "明网";
                $item->nag_title = sprintf("%s(%s)",$item->navigation->title,$am_str);
            }
            $item->type_str = ConstructModel::TYPE_TIPS[$item->type];
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     *
     * @date 2020-12-08 15:41:38
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

    public function works_updateAction()
    {
        if (!request()->isPost()) {
            return $this->ajaxError('请求失败');
        }

        //作品数更新
        ConstructModel::query()->chunkById(100,function (\Illuminate\Support\Collection $items){
            collect($items)->each(function (ConstructModel $item){
                /** @var NavigationModel $nag */
                $nag = NavigationModel::find($item->nag_id);
                $is_aw = $nag->is_aw;
                $ct = MvModel::queryBase()
                    ->where('construct_id', $item->id)
                    ->where('is_aw',$is_aw)
                    ->count('id');
                $item->work_num = $ct;
                $item->save();

            });
        });

        return $this->ajaxSuccess('操作成功');
    }


    /**
     * 获取对应的model名称
     * @return string
     *
     * @date 2020-12-08 15:41:38
     */
    protected function getModelClass(): string
    {
        return ConstructModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     *
     * @date 2020-12-08 15:41:38
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