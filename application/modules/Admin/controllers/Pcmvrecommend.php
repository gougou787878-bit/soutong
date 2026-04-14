<?php

/**
 * Class PcmvrecommendController
 * @author xiongba
 * @date 2024-01-10 18:29:39
 */
class PcmvrecommendController extends BackendBaseController
{
    /**
     * 列表数据过滤
     * @return Closure
     * @author xiongba
     * @date 2019-12-02 17:08:03
     */
    protected function listAjaxIteration()
    {
        return function (PcMvRecommendModel $item) {
            $item->load('mv');
            $item->load('tab');
            //mv
            $item->full_href = $item->mv->m3u8?getAdminPlayM3u8($item->mv->m3u8,true):'';
            $item->title = htmlspecialchars($item->mv->title);
            $item->tagsname = htmlspecialchars($item->mv->tags);
            $item->cover_thumb_url = $item->mv->cover_thumb_url;
            $item->free_str = 'VIP';
            if ($item->mv->coins > 0) {
                $item->free_str = '金币';
            }
            //tab
            $item->tab_name = $item->tab->tab_name;

            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     * @author xiongba
     * @date 2024-01-10 18:29:39
     */
    public function indexAction()
    {
        $arr = PcTabModel::where('status', PcTabModel::STATUS_YES)->get()->pluck('tab_name', 'tab_id')->toArray();
        $this->assign('pcTab', $arr);
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2024-01-10 18:29:39
     */
    protected function getModelClass(): string
    {
       return PcMvRecommendModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2024-01-10 18:29:39
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