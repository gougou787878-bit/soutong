<?php

// 评论模块
use service\EventTrackerService;
use service\HotService;

class CommentsController extends BaseController
{
    use \repositories\UsersRepository,
        \repositories\CommentsRepository;

    /**
     * 发布评论
     * @throws \Yaf\Exception
     */
    public function createAction()
    {
        $mvID = $this->post['mv_id'] ?? '';
        $cID = $this->post['c_id'] ?? '0';
        $sID = $this->post['s_id'] ?? '0';
        $comment = $this->post['comment'] ?? '';

        if ($mvID == '' || mb_strlen($comment) <2) {
            throw new \Yaf\Exception('评论内容至少两个字符', 422);
        }
        $key = CommentModel::REDIS_COMMENT_BAN . $this->member['uid'];
        if (redis()->get($key)) {
            return $this->errorJson('触犯禁言规则，禁止评论72小时，下回终身禁言哦');
        }
        if ($this->member['role_id'] == MemberModel::USER_ROLE_LEVEL_BANED) {
            return $this->errorJson('您已经被禁言');
        }
        $member = request()->getMember();
        if($sID){
            $tips = HotService::getSaohuaTips($sID);
            if(!$tips){
                if ($member->vip_level <= 0 || !$member->is_vip) {
                    return $this->errorJson('充值VIP才能评论哟～～，赶快进入VIP解锁更多姿势');
                }
            }else{
                $comment = $tips;
            }
        }else{
            if ($member->vip_level <= 0 || !$member->is_vip) {
                return $this->errorJson('充值VIP才能评论哟～～，赶快进入VIP解锁更多姿势');
            }
        }

        if (strlen($comment) > 255 or empty($comment)) {
            return $this->errorJson('评论内容不符合');
        }

        // TODO fitter keywords

        $last = \tools\RedisService::get(CommentModel::REDIS_COMMENT_LAST_KEY . $this->member['uuid']);
        if ($last) {
            return $this->errorJson('发送评论太频繁，请稍后重试');
        }

        $min5 = \tools\RedisService::redis()->sCard(CommentModel::REDIS_COMMENT_5MIN_KEY . $this->member['uuid']);
        if ($min5 > 3) {
            return $this->errorJson('发送评论太频繁，请稍后重试');
        }

        /** @var MvModel $mv */
        $mv = MvModel::queryWithUser()->where('id','=',$mvID)->first();
        if(is_null($mv)){
            return $this->errorJson('视频不存在或已下架处理');
        }

        $this->handleCreateComment($mv, $cID, $comment);

        //公司上报
        (new EventTrackerService(
            $this->member['oauth_type'],
            $this->member['invited_by'],
            $this->member['uid'],
            $this->member['oauth_id'],
            $this->post['device_brand'] ?? '',
            $this->post['device_model'] ?? ''
        ))->addTask([
            'event'             => EventTrackerService::EVENT_VIDEO_COMMENT,
            'video_id'          => (string)$mv->id,
            'video_title'       => $mv->title,
            'video_type_id'     => '',
            'video_type_name'   => '',
            'comment_content'   => $comment
        ]);

        $this->showJson(['success' => true, 'msg' => '评论成功']);
    }

    /**
     * 评论列表
     * @throws \Yaf\Exception
     */
    public function listAction()
    {
        $id = $this->post['id'] ?? '';
        if (!$id) {
            return $this->errorJson('参数错误',422);
        }
        $items = $this->getCommentList($id);

        $this->showJson($items);
    }

    public function likingAction()
    {
        $id = $this->post['id'] ?? '';
        if (!$id) {
            throw new \Yaf\Exception('参数错误', 422);
        }
        if ($this->member['role_id'] == MemberModel::USER_ROLE_LEVEL_BANED) {
            throw new \Yaf\Exception('您已经被禁言', 422);
        }
        $msg = '操作成功';
        $this->handleCreateCommentLiking($id, $msg);
        $this->showJson(['success' => true, 'msg' => $msg]);
    }
    public function saoTalkAction(){
        $data = HotService::getHotMVSliceData();
        return $this->showJson($data);
    }
}