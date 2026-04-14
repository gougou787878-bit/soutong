<?php

/**
 * 单页
 * Class PageController
 */
class PageController extends IndexController {
    /**
     * 单页
     */

	public function indexAction() {
        $id = $_GET['id'] ?? 103;
        $post = PostsModel::find($id);
        if (empty($post)){
            return ;
        }
        $post->toArray();
        $this->show("page", ['news' => $post]);
    }

    /**
     * 用户等级
     */
	public function levelAction() {
	    $data = ExperLevelModel::getLevelList();
        $this->show("level",['data'=>$data]);
    }

    /**
     * 主播等级
     */
    public function levelAnchorAction() {
        $data = ExperLevelAnchorModel::getLevelList();
        $this->show("level_anchor",['data'=>$data]);
    }

    /**
     * 代理规则
     */
    public function agentRuleAction() {
      //  $data = singleton(ExperLevelAnchorModel::class)->getLevelList();
        $config = ConfigModel::instance()->getConfig();
        $this->view->assign('config', $config);
        $this->show("agent_rule");
    }

}