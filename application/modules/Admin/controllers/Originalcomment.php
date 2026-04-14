<?php

/**
 * 原创评论
 * Class OriginalcommentController
 */
class OriginalcommentController extends BackendBaseController
{
    /**
     * 列表数据过滤
     * @return Closure
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
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     */
    protected function getModelClass(): string
    {
       return OriginalCommentModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     */
    protected function getPkName(): string
    {
        return 'id';
    }

    /**
     * 定义数据操作日志
     * @return string
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
        /** @var OriginalCommentModel $model */
        $model = OriginalCommentModel::where('id', $post['id'])->first();
        if (is_null($model)) {
            return $this->ajaxError('数据不存在');
        }
        try {
            $model->update(['status' => OriginalCommentModel::STATUS_PASS]);
            //维护帖子评论数
            $post = OriginalModel::where('id',$model->original_id);
            if ($post){
                $post->increment('com_count');
                //清理缓存
//                GirlCommentModel::clearCacheWhenCreatePostComment($model->original_id);
            }
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
        $flag = OriginalCommentModel::where('id', $post['id'])->update(['status' => OriginalCommentModel::STATUS_UNPASS]);
        return $this->ajaxSuccessMsg("操作成功#{$flag}");

    }

    public function doBlackAction(){
        if (!$this->getRequest()->isPost()) {
            return $this->ajaxError('请求错误');
        }
        $post = $this->postArray();
        $flag = $post['flag']??'';
        /** @var OriginalCommentModel $model */
        $model  = OriginalCommentModel::find($post['id']);
        if(!is_null($model)){
            if($flag == 'ban'){
                //禁言和下掉VIP
                MemberModel::ban($model->aff);
                OriginalCommentModel::where(['uid'=>$model->aff])->delete();
            }
            if($flag == 'black'){
                MemberModel::where(['uid'=>$model->aff])->update(['role_id'=>MemberModel::USER_ROLE_BLACK]);
                OriginalCommentModel::where(['uid'=>$model->aff])->delete();
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
        $comments = OriginalCommentModel::whereIn('id', $ary)
            ->where('status', OriginalCommentModel::STATUS_WAIT)
            ->get();

        try {
            transaction(function () use ($comments) {
                /** @var OriginalCommentModel $comment */
                foreach ($comments as $comment) {
                    $ret = OriginalCommentModel::where('id', $comment->id)
                        ->update(['status' => OriginalCommentModel::STATUS_PASS]);
                    if ($ret <= 0)
                        throw new Exception('系统异常');

                    $postId = $comment->original_id;
                    $post = OriginalModel::find($postId);
                    if ($post){
                        $post->increment('com_count');
                        //清理缓存
//                        OriginalCommentModel::clearCacheWhenCreatePostComment($postId);
                    }
                }
            });
            return $this->ajaxSuccessMsg('操作成功');
        } catch (Exception $e) {
            return $this->ajaxError($e->getMessage());
        }
    }
}