<?php

/**
 * Class EgglogController
 *
 * @date 2024-09-21 15:33:08
 */
class EgglogController extends BackendBaseController
{
    /**
     * 列表数据过滤
     * @return Closure
     *
     * @date 2019-12-02 17:08:03
     */
    protected function listAjaxIteration()
    {
        return function (EggLogModel $item) {
            $item->setHidden([]);
            $item->load('lottery');
            $item->giveaway_type_str = EggItemModel::GIVEAWAY_TYPE[$item->giveaway_type];
            //$item->item_icon_url = url_cover($item->item_icon);
            $item->lottery_name = $item->lottery->lottery_name;
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     *
     * @date 2024-09-21 15:33:08
     */
    public function indexAction()
    {
        $this->assign('lotteryAry' , EggModel::pluck('lottery_name','id' )->toArray());
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     *
     * @date 2024-09-21 15:33:08
     */
    protected function getModelClass(): string
    {
       return EggLogModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     *
     * @date 2024-09-21 15:33:08
     */
    protected function getPkName(): string
    {
        return 'log_id';
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