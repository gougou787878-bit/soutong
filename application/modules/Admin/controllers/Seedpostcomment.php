<?php

/**
 * Class SeedpostcommentController
 *
 * @date 2024-02-28 16:41:48
 */
class SeedpostcommentController extends BackendBaseController
{
    /**
     * 列表数据过滤
     * @return Closure
     *
     * @date 2019-12-02 17:08:03
     */
    protected function listAjaxIteration()
    {
        return function (SeedPostCommentModel $item) {
            $item->load('user');
            $item->user_thumb = $item->user->avatar_url;
            $item->user_nickname = $item->user->nickname;
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     *
     * @date 2024-02-28 16:41:48
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     *
     * @date 2024-02-28 16:41:48
     */
    protected function getModelClass(): string
    {
       return SeedPostCommentModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     *
     * @date 2024-02-28 16:41:48
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
     * 通过审核
     */
    public function doPassAction()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->ajaxError('请求错误');
        }
        $post = $this->postArray();
        /** @var SeedPostCommentModel $model */
        $model = SeedPostCommentModel::where('id', $post['id'])->first();
        if (is_null($model)) {
            return $this->ajaxError('数据不存在');
        }
        try {
            $model->update(['status' => SeedPostCommentModel::STATUS_PASS]);
            //帖子评论数量
            SeedPostModel::where('id',$model->post_id)->increment('comment_ct');
            SeedPostCommentModel::clearCache($model);
            return $this->ajaxSuccessMsg('操作成功');
        } catch (\Throwable $e) {
            errLog("CommentsModel:" . $e->getMessage());
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
        $flag = SeedPostCommentModel::where('id', $post['id'])->update(['status' => PostCommentModel::STATUS_UNPASS]);
        return $this->ajaxSuccessMsg("操作成功#{$flag}");

    }

    public function doBlackAction(){
        if (!$this->getRequest()->isPost()) {
            return $this->ajaxError('请求错误');
        }
        $post = $this->postArray();
        $flag = $post['flag']??'';
        /** @var SeedPostCommentModel $model */
        $model  = SeedPostCommentModel::find($post['id']);
        if(!is_null($model)){
            if($flag == 'ban'){
                MemberModel::where(['aff'=>$model->aff])->update(['role_id'=>MemberModel::USER_ROLE_LEVEL_BANED]);
                SeedPostCommentModel::where(['aff'=>$model->aff,'status'=>SeedPostCommentModel::STATUS_WAIT])->delete();
            }
            if($flag == 'black'){
                MemberModel::where(['aff'=>$model->aff])->update(['role_id'=>MemberModel::USER_ROLE_BLACK]);
                SeedPostCommentModel::where(['aff'=>$model->aff,'status'=>SeedPostCommentModel::STATUS_WAIT])->delete();
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
        $comments = SeedPostCommentModel::whereIn('id', $ary)
            ->where('status', SeedPostCommentModel::STATUS_WAIT)
            ->get();

        try {
            transaction(function () use ($comments) {
                foreach ($comments as $comment) {
                    $ret = SeedPostCommentModel::where('id', $comment->id)
                        ->update(['status' => SeedPostCommentModel::STATUS_PASS]);
                    if ($ret <= 0)
                        throw new Exception('系统异常');

                    $postId = $comment->post_id;
                    $post = PostModel::find($postId);
                    if ($post){
                        $post->increment('comment_ct');
                    }

                    //清理缓存
                    SeedPostModel::clear_seed_detail($postId);
                    SeedPostCommentModel::clearCache($comment);
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
            $comments = SeedPostCommentModel::query()->whereIn('id',$commentIds)->where('status',SeedPostCommentModel::STATUS_WAIT)->get();
            if (!$comments){
                return $this->ajaxError('没有待审核的评论');
            }

            foreach ($comments as $comment) {
                $data = [
                    'status'       => SeedPostCommentModel::STATUS_UNPASS,
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