<?php

/**
 * Class PorncommentController
 *
 * @date 2024-04-01 15:50:12
 */
class PorncommentController extends BackendBaseController
{
    /**
     * 列表数据过滤
     * @return Closure
     *
     * @date 2019-12-02 17:08:03
     */
    protected function listAjaxIteration()
    {
        return function (PornCommentModel $item) {
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
     * @date 2024-04-01 15:50:12
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     *
     * @date 2024-04-01 15:50:12
     */
    protected function getModelClass(): string
    {
       return PornCommentModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     *
     * @date 2024-04-01 15:50:12
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
        try {
            test_assert($this->getRequest()->isPost(), '请求错误');
            $post = $this->postArray();
            /** @var PornCommentModel $model */
            $model = PornCommentModel::where('id', $post['id'])->first();
            test_assert($model, '数据不存在');
            $model->update(['status' => PostCommentModel::STATUS_PASS]);
            //黄游评论数量
            PornGameModel::where('id',$model->porn_id)->increment('comment_count');
            //PornGameModel::clearCache($model);
            return $this->ajaxSuccessMsg('操作成功');
        }catch (\Throwable $e){
            return $this->ajaxError($e->getMessage());
        }
    }

    /**
     * 拒绝审核
     */
    public function doRejectAction()
    {
        try {
            test_assert($this->getRequest()->isPost(), '请求错误');
            $post = $this->postArray();
            $flag = PornCommentModel::where('id', $post['id'])->update(['status' => PornCommentModel::STATUS_UNPASS]);
            return $this->ajaxSuccessMsg("操作成功#{$flag}");
        }catch (Throwable $e){
            return $this->ajaxError($e->getMessage());
        }
    }

    public function doBlackAction(){
        try {
            test_assert($this->getRequest()->isPost(), '请求错误');
            $post = $this->postArray();
            $flag = $post['flag']??'';
            /** @var PornCommentModel $model */
            $model  = PornCommentModel::find($post['id']);
            if(!is_null($model)){
                if($flag == 'ban'){
                    MemberModel::where(['aff'=>$model->aff])->update(['role_id'=>MemberModel::USER_ROLE_LEVEL_BANED]);
                    PornCommentModel::where(['aff'=>$model->aff,'status'=>PostCommentModel::STATUS_WAIT])->delete();
                }
                if($flag == 'black'){
                    MemberModel::where(['aff'=>$model->aff])->update(['role_id'=>MemberModel::USER_ROLE_BLACK]);
                    PornCommentModel::where(['aff'=>$model->aff,'status'=>PostCommentModel::STATUS_WAIT])->delete();
                }
                MemberModel::clearFor($model->load('user')->user);
            }
            return $this->ajaxSuccessMsg("操作成功#{$flag}");
        }catch (Throwable $e){
            return $this->ajaxError($e->getMessage());
        }
    }

    public function passAllAction()
    {
        try {
            test_assert($this->getRequest()->isPost(), '请求错误');
            $post = $this->postArray();
            $ary = explode(',', $post['ids'] ?? '');
            $ary = array_filter($ary);
            $ary = array_unique($ary);
            $comments = PornCommentModel::whereIn('id', $ary)
                ->where('status', PornCommentModel::STATUS_WAIT)
                ->get();

            transaction(function () use ($comments) {
                foreach ($comments as $comment) {
                    $ret = PornCommentModel::where('id', $comment->id)
                        ->update(['status' => PornCommentModel::STATUS_PASS]);
                    if ($ret <= 0)
                        throw new Exception('系统异常');

                    $postId = $comment->post_id;
                    $post = PornGameModel::find($postId);
                    if ($post){
                        $post->increment('comment_count');
                    }

                    //清理缓存
                    //PostModel::clearDetailCache($postId);
                    //PornCommentModel::clearCache($comment);
                }
            });
            return $this->ajaxSuccessMsg('操作成功');
        } catch (Exception $e) {
            return $this->ajaxError($e->getMessage());
        }
    }

    public function batchRefuseAction(){
        try {
            test_assert($this->getRequest()->isPost(), '请求错误');
            $content = $_POST['refused'] ?? '';
            $ids = $_POST['comment_ids'] ?? '';
            $commentIds = explode(',', $ids);

            test_assert($content, '拒绝理由不能为空');
            test_assert($commentIds, '评论ID不能为空');

            //过滤已经审核通过的
            $comments = PornCommentModel::query()->whereIn('id',$commentIds)->where('status',PornCommentModel::STATUS_WAIT)->get();
            test_assert($comments, '没有待审核的评论');

            foreach ($comments as $comment) {
                $data = [
                    'status'       => PornCommentModel::STATUS_UNPASS,
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