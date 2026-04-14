<?php

use service\TopicService;

/**
 * Class TopicController
 */
class TopicController extends BaseController
{


    /**
     * 专题列表 合集列表
     * @return bool|void
     */
    public function listAction()
    {
        $page = $this->post['page'] ?? 1;
        $limit = $this->post['limit'] ?? 30;
        $sort = $this->post['sort'] ?? 'new';
        $member = request()->getMember();
        $data = TopicService::getTopics($member,$sort,$page, $limit);
        return $this->showJson($data);
    }

    /**
     * 专题合集视频列表 根据专题获取视频  分页查询
     * @param int $limit 20
     * @param int $page 1
     *
     * @return bool|void
     */
    public function mvlistAction()
    {
        $topic_id = $this->post['topic_id'] ?? 0;
        if (!$topic_id) {
            return $this->showJson([]);
        }
        $member = request()->getMember();
        $rowInfo = TopicService::getTopicInfo($topic_id,$member,1);
        if(!$rowInfo){
            return $this->showJson([]);
        }

        $data = TopicService::getMVList($member,$topic_id);

        return $this->showJson(['info'=>$rowInfo,'list'=>$data]);
    }
    /**
     * 点赞视频
     * @return bool|void
     */
    public function toggle_likeAction()
    {
        $topic_id = intval($this->post['topic_id'] ?? 0);
        if (empty($topic_id)) {
            return $this->errorJson('数据错误');
        }

        $service = new TopicService();
        try {
            $member = request()->getMember();
            $status = $service->toggle_like($member, $topic_id);
            return $this->showJson(['status' => $status, 'msg' => '操作成功']);
        } catch (\Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 购买合集
     * @return bool|null
     */
    public function buyAction(){
        $topic_id = intval($_POST['topic_id'] ?? 0);
        if (empty($topic_id)) {
            return $this->errorJson('参数错误');
        }
        try {
            //频率控制
            \helper\Util::PanicFrequency(sprintf("topic-%d-%d",$this->member['uid'],$topic_id),1,10,'操作太频繁,5秒后重试');
            $data =  TopicService::buyCollect($topic_id);
            return $this->showJson($data);
        } catch (\Throwable $e) {
            return $this->errorJson($e->getMessage(), $e->getCode());
        }
    }

    /**
     * 我的购买
     * @return bool
     */
    public function my_buyAction()
    {
        $member = request()->getMember();
        $uid = $member->uid;
        list($page, $limit, $last_id) = \helper\QueryHelper::pageLimit();
        $data = TopicPayModel::getUserBuyData($uid, $page, $limit,$member);
        return $this->showJson(['list' => $data]);
    }

    /**
     * 点赞的合集
     * @return bool|void
     */
    public function my_likeAction()
    {

        $uid = intval($this->post['uid'] ?? 0);
        $member = request()->getMember();
        if (empty($uid)) {
            $uid = $member->uid;
        }

        $service = new TopicService();
        try {

            $model = $service->listOfLike($uid, $member);
            return $this->showJson($model);
        } catch (\Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }


}