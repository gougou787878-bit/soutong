<?php

/**
 * Class DailystatController
 * @author xiongba
 * @date 2022-06-17 16:29:28
 */
class DailystatController extends BackendBaseController
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
            $item->and_per = $item->user>0 ? round($item->and_user / $item->user * 100,2) : 0;
            $item->build_per = $item->user>0 ? round($item->build_user / $item->user * 100,2) : 0;
            $item->and_charge_per = $item->charge>0 ? round($item->and_charge / $item->charge* 100,2) : 0;
            $item->vip_charge_per = $item->charge>0 ? round($item->vip_charge / $item->charge * 100,2) : 0;
            $item->pwa_charge = round($item->charge - $item->and_charge,2);
            $item->gold_charge = round($item->charge - $item->vip_charge-$item->game_charge,2);
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     * @author xiongba
     * @date 2022-06-17 16:29:28
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2022-06-17 16:29:28
     */
    protected function getModelClass(): string
    {
       return DailyStatModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2022-06-17 16:29:28
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