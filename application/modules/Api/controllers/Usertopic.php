<?php

use service\UserTopicService;

/**
 * Class TopicController
 */
class UsertopicController extends BaseController
{

    /**
     * 合集列表
     * @return bool|void
     */
    public function listOfTopicAction()
    {
        $uid = intval($this->post['uid'] ?? 0);
        if (empty($uid)) {
            $uid = request()->getMember()->uid;
        }
        $data = (new UserTopicService())->listOfTopic($uid);
        return $this->showJson(['list' => $data]);
    }

    /**
     * 视频列表
     * @return bool|void
     */
    public function listmvOfTopicAction()
    {
        $topic_id = intval($this->post['topic_id'] ?? 0);
        $kwy = $this->post['kwy'] ?? '';
        if (!$topic_id) {
            return $this->errorJson('参数错误');
        }
        $rowInfo = UserTopicService::getTopicInfo($topic_id , request()->getMember());
        if (!$rowInfo) {
            return $this->errorJson('合集不存在');
        }
        $data = UserTopicService::getMVList($topic_id, $kwy);
        return $this->showJson(['info' => $rowInfo, 'list' => $data]);
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

        $service = new UserTopicService();
        try {
            $member = request()->getMember();
            $status = $service->toggle_like($member, $topic_id);
            return $this->showJson(['status' => $status, 'msg' => '操作成功']);
        } catch (\Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }


    /**
     * 点赞的合集
     * @return bool|void
     */
    public function likeOfTopicAction()
    {

        $uid = intval($this->post['uid'] ?? 0);
        $member = request()->getMember();
        if (empty($topic_id)) {
            $uid = $member->uid;
        }

        $service = new UserTopicService();
        try {

            $model = $service->listOfLike($uid, $member);
            return $this->showJson($model);
        } catch (\Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 置顶合集
     * @return bool|void
     */
    public function toggle_topAction()
    {
        $topic_id = intval($this->post['topic_id'] ?? 0);
        if (empty($topic_id)) {
            return $this->errorJson('数据错误');
        }

        $service = new UserTopicService();
        try {
            $member = request()->getMember();
            $model = $service->toggle_top($member, $topic_id);
            return $this->showJson($model);
        } catch (\Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }



    /**
     * 创建合集
     */
    public function createAction()
    {
        $title = htmlspecialchars($this->post['title'] ?? '');//标题
        $desc = htmlspecialchars($this->post['desc'] ?? '');//简介
        $image = htmlspecialchars($this->post['image'] ?? ''); //封面
        $idStr = htmlspecialchars($this->post['mv_id'] ?? ''); //视频id
        $determine = htmlspecialchars($this->post['determine'] ?? 0); //是否强制创建，会抠金币


        if (empty($title) || empty($desc) || empty($image) || empty($idStr)) {
            return $this->errorJson('数据不能为空');
        }

        $service = new UserTopicService();
        try {
            $member = request()->getMember();
            $model = $service->create_topic($member, $title, $desc, $image, $idStr, boolval($determine));
            return $this->showJson($model);
        } catch (\Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 修改合集的视频
     * @return bool|void
     */
    public function updateAction()
    {
        $topic_id = intval($this->post['topic_id'] ?? 0);
        $idStr = htmlspecialchars($this->post['mv_id'] ?? ''); //视频id

        if (empty($topic_id) || empty($idStr)) {
            return $this->errorJson('数据不能为空');
        }
        $service = new UserTopicService();
        try {
            $member = request()->getMember();
            $model = $service->update_topic($member, $topic_id, $idStr);
            return $this->showJson($model);
        } catch (\Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }
    /**
     * 删除合集
     * @return bool|void
     */
    public function deleteAction()
    {
        $topic_id = intval($this->post['topic_id'] ?? 0);

        if (empty($topic_id)) {
            return $this->errorJson('数据不能为空');
        }
        $service = new UserTopicService();
        try {
            $member = request()->getMember();
            $service->delete_topic($member, $topic_id);
            return $this->showJson('删除成功');
        } catch (\Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function listmvAction()
    {
        $kwy = $this->post['kwy'] ?? '';
        $service = new UserTopicService();
        try {
            $member = request()->getMember();
            $list = $service->listmv($member, $kwy);
            return $this->showJson(['list' => $list]);
        } catch (\Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 热门合集
     * @return bool|void
     */
    public function popularAction(){
        $service = new UserTopicService();
        try {
            $member = request()->getMember();
            $list = $service->popular($member , 30);
            return $this->showJson(['list' => $list]);
        } catch (\Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

}