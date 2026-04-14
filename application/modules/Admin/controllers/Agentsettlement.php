<?php


/**
 * Class AgentsettlementController
 * @author xiongba
 * @date 2020-03-04 13:02:27
 */
class AgentsettlementController extends BackendBaseController
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
            /** @var \AgentSettlementModel $item */
            $item->total_amount = $item->total_amount / HT_JE_BEI;
            $item->real_amount = $item->real_amount / HT_JE_BEI;
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     * @author xiongba
     * @date 2020-03-04 13:02:27
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2020-03-04 13:02:27
     */
    protected function getModelClass(): string
    {
       return AgentSettlementModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2020-03-04 13:02:27
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
        return '渠道汇款';
    }

    /**
     * 保存数据
     * @return bool
     * @author xiongba
     * @date 2019-11-04 16:08:32
     */
    public function saveAction() {
        if (!$this->getRequest()->isPost()) {
            return $this->ajaxError('请求错误');
        }
        $date =date('Y-m-d H:i:s',TIMESTAMP);
        $post = $this->postArray();
        $post['add_time'] = $date;
        if(isset($post['start']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/',trim($post['start']))){
            return $this->ajaxError('核算开始时间格式不对');
        } if(isset($post['end']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/',trim($post['end']))){
            return $this->ajaxError('核算截止时间格式不对');
        }
        if(isset($post['agent_id'])){
            $hasAgent = AgentsUserModel::where('id',(int)$post['agent_id'])->first();
            if(!$hasAgent){
                return $this->ajaxError('无效渠道代理');
            }
            if ($hasAgent->channel != $post['channel']) {
                return $this->ajaxError('渠道编号与渠道标识不匹配');
            }
        }

        $pk =  $post['_pk'] ?? '';
        unset($post['_pk']);
        $model = null;
        if($pk){
           $model = AgentSettlementModel::where('id',$pk)->update($post);
        }else{
            $model = AgentSettlementModel::create($post);
        }
        if($model){
            return $this->ajaxSuccessMsg('操作成功');
        }
        return $this->ajaxError('操作错误');
    }

    /**
     * 删除数据
     * 后台全局公共方法
     * @return mixed
     * @author xiongba
     * @date 2019-11-08 11:19:24
     */
    public function delAction() {
        if (!$this->getRequest()->isPost()) {
            return $this->ajaxError('请求错误');
        }
        $post = $this->postArray();
        $className = $this->getModelClass();
        $pkName = $this->getPkName();
        $where = [$pkName => $post['_pk']];
        if ($className::where($where)->update(['is_delete'=>1,'is_show'=>0])) {
            return $this->ajaxSuccessMsg('操作成功');
        } else {
            return $this->ajaxError('操作错误');
        }
    }
}