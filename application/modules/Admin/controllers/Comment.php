<?php

/**
 * Class CommentController
 * @date 2023-06-09 20:10:36
 */
class CommentController extends BackendBaseController
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
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     * @author xiongba
     * @date 2023-06-09 20:10:36
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2023-06-09 20:10:36
     */
    protected function getModelClass(): string
    {
        return CommentModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2023-06-09 20:10:36
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

    /**
     * 通过审核
     */
    public function doPassAction()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->ajaxError('请求错误');
        }
        $post = $this->postArray();
        /** @var CommentModel $model */
        $model = CommentModel::where('id', $post['id'])->first();
        if (is_null($model)) {
            return $this->ajaxError('数据不存在');
        }
        try {
            $model->update(['status' => CommentModel::STATUS_SUCCESS]);
            //维护帖子评论数
            $mv = MvModel::where('id',$model->mv_id);
            if ($mv){
                $mv->increment('comment');
            }
            return $this->ajaxSuccessMsg('操作成功');
        } catch (\Throwable $e) {
            return $this->ajaxError($e->getMessage());
        }
    }

    /**
     * 拒绝审核
     */
    public function doRejectAction()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->ajaxError('请求错误');
        }
        $post = $this->postArray();
        $flag = CommentModel::where('id', $post['id'])->update(['status' => CommentModel::STATUS_FAIL]);
        return $this->ajaxSuccessMsg("操作成功#{$flag}");

    }

    public function doBlackAction(){
        if (!$this->getRequest()->isPost()) {
            return $this->ajaxError('请求错误');
        }
        $post = $this->postArray();
        $flag = $post['flag']??'';
        /** @var CommentModel $model */
        $model  = CommentModel::find($post['id']);
        if(!is_null($model)){
            if($flag == 'ban'){
                //下掉VIP权益
                MemberModel::ban($model->uid);
                CommentModel::where(['uid'=>$model->uid])->delete();
            }
            if($flag == 'black'){
                MemberModel::where(['uid'=>$model->uid])->update(['role_id'=>MemberModel::USER_ROLE_BLACK]);
                CommentModel::where(['uid'=>$model->uid])->delete();
            }
            MemberModel::clearFor($model->load('user')->user);
        }
        return $this->ajaxSuccessMsg("操作成功#{$flag}");
    }

    public function passAllAction()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->ajaxError('请求错误');
        }
        $post = $this->postArray();
        $ary = explode(',', $post['ids'] ?? '');
        $ary = array_filter($ary);
        $ary = array_unique($ary);
        $comments = CommentModel::whereIn('id', $ary)
            ->where('status', CommentModel::STATUS_WAIT)
            ->get();

        try {
            transaction(function () use ($comments) {
                foreach ($comments as $comment) {
                    $ret = CommentModel::where('id', $comment->id)
                        ->update(['status' => CommentModel::STATUS_SUCCESS]);
                    if ($ret <= 0)
                        throw new Exception('系统异常');

                    $mvId = $comment->mv_id;
                    $mv = MvModel::find($mvId);
                    if ($mv){
                        $mv->increment('comment');
                    }

//                    // 对评论人通知过审
//                    $msg = sprintf(SystemNoticeModel::AUDIT_COMMENT_PASS_MSG, $comment->comment);
//                    $model = SystemNoticeModel::addNotice($comment->aff, $msg, '审核消息');
//                    if (!$model)
//                        throw new Exception('系统异常');
//
//                    // 对上级通知评论
//                    switch ($comment->pid) {
//                        case 0:
//                            // 对帖子作者通知评论
//                            $nickname = MemberModel::firstAff($comment->aff)->nickname;
//                            $post = PostModel::where('id', $comment->post_id)->first();
//                            $autherAff = $post->aff;
//                            $postTitle = $post->title;
//                            $msg = sprintf(SystemNoticeModel::COMMENT_POST_MSG, $nickname, $postTitle, $comment->comment);
//                            $model = SystemNoticeModel::addNotice($autherAff, $msg, '评论消息');
//                            if (!$model)
//                                throw new Exception('系统异常');
//                            break;
//                        default:
//                            // 对评论人通知评论
//                            $nickname = MemberModel::firstAff($comment->aff)->nickname;
//                            $tcomment = PostCommentModel::where('id', $comment->pid)->first();
//                            $commentTitle = $tcomment->comment;
//                            $autherAff = $tcomment->aff;
//                            $msg = sprintf(SystemNoticeModel::COMMENT_COMMENT_MSG, $nickname, $commentTitle, $comment->comment);
//                            $model = SystemNoticeModel::addNotice($autherAff, $msg, '评论消息');
//                            if (!$model)
//                                throw new Exception('系统异常');
//                            break;
//                    }
                }
            });
            return $this->ajaxSuccessMsg('操作成功');
        } catch (Exception $e) {
            return $this->ajaxError($e->getMessage());
        }
    }

    public function batchRefuseAction(){
        try {
            if (!$this->getRequest()->isPost()) {
                return $this->ajaxError('请求错误');
            }
            $content = $_POST['refused'] ?? '';
            $ids = $_POST['comment_ids'] ?? '';
            $commentIds = explode(',', $ids);

            if (empty($content)) {
                return $this->ajaxError('请选择拒绝原因');
            }
            if (!$commentIds) {
                return $this->ajaxError('评论ID不能为空');
            }

            //过滤已经审核通过的
            $comments = CommentModel::query()->whereIn('id',$commentIds)->where('status',CommentModel::STATUS_WAIT)->get();
            if (!$comments){
                return $this->ajaxError('没有待审核的评论');
            }

            foreach ($comments as $comment) {
                $data = [
                    'status'       => CommentModel::STATUS_FAIL,
                    'refuse_reason'     => $content,
                    'updated_at' => \Carbon\Carbon::now()
                ];
                $comment->update($data);
            }
            return $this->ajaxSuccess('拒绝成功');
        }catch (Exception $e){
            return $this->ajaxError($e->getMessage());
        }
    }
}