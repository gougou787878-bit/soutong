<?php

/**
 * Class KoulogController
 *
 * @date 2021-11-12 18:30:18
 */
class KoulogController extends BackendBaseController
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
            $item->agent_info = '';
            if($agent = $item->agent){
                $item->agent_info = "{$agent->nickname}[{$agent->uuid}]";
            }
            $item->rate = sprintf("%0.2f",($item->kou_number/($item->total_number-$item->base_number))*100);
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     *
     * @date 2021-11-12 18:30:18
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     *
     * @date 2021-11-12 18:30:18
     */
    protected function getModelClass(): string
    {
       return KouLogModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     *
     * @date 2021-11-12 18:30:18
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
    /**
     *agent 扣量统计 面板数据ajax
     */
    public function agentKouAjaxAction()
    {
        $result = [
            ['name' => '今日coin扣量', 'number' => KouLogModel::kouAgentNumber('cps',1)],
            ['name' => '累计coin扣量', 'number' => KouLogModel::kouAgentNumber('cps',0)],
        ];
        $this->assign('result', $result);
        return $this->display('koulog/log');
    }

}