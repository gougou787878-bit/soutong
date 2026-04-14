<?php

/**
 * Class KouruleController
 *
 * @date 2021-11-12 18:30:05
 */
class KouruleController extends BackendBaseController
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

            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     *
     * @date 2021-11-12 18:30:05
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     *
     * @date 2021-11-12 18:30:05
     */
    protected function getModelClass(): string
    {
        return KouRuleModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     *
     * @date 2021-11-12 18:30:05
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
    protected function getLogDesc(): string
    {
        // TODO: Implement getLogDesc() method.
        return '';
    }

    protected function postArray($setPost = null)
    {
        $post = parent::postArray();
        $post['type'] = 'cps';
        if ($post['agent_id']) {

        }
        $post['created_at'] = date('Y-m-d H:i:s');
        $post['admin_name'] = $this->getUser()->username;
        return $post;
    }

    public function setAgent_id($value, $data, $id)
    {
        $value = trim($value);
        /** @var MemberModel $agent */
        $w = [];
        if(strlen($value) == 32){
            $w['uuid'] = $value;
        }else{
            $w['uid'] = $value;
        }
        $agent = MemberModel::where($w)->first();
        if (is_null($agent)) {
            throw new Exception('查无用户', 5200);
        }
        return $agent->uid;
    }

    public function setPoint($value, $data, $id)
    {
        $value = intval($value);
        if(!$value || $value>100 ){
            throw new Exception('扣量点子（0,100]', 5200);
        }
        return $value;
    }

    protected function saveAfterCallback($model)
    {
        KouRuleModel::clearCache();
    }

    public function refreshAction()
    {
        KouRuleModel::clearCache();
        return $this->ajaxSuccessMsg('扣量规则已经全部涮新~');
    }
}