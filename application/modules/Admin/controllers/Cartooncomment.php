<?php

/**
 * 动漫评论
 * Class CartooncommentController
 */
class CartooncommentController extends BackendBaseController
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
       return CartoonCommentModel::class;
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
        /** @var CartoonCommentModel $model */
        $model = CartoonCommentModel::where('id', $post['id'])->first();
        if (is_null($model)) {
            return $this->ajaxError('数据不存在');
        }
        try {
            $model->update(['status' => CartoonCommentModel::STATUS_PASS]);
            //维护帖子评论数
            $post = CartoonModel::where('id',$model->cartoon_id);
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
        $flag = CartoonCommentModel::where('id', $post['id'])->update(['status' => CartoonCommentModel::STATUS_UNPASS]);
        return $this->ajaxSuccessMsg("操作成功#{$flag}");

    }

    public function doBlackAction(){
        if (!$this->getRequest()->isPost()) {
            return $this->ajaxError('请求错误');
        }
        $post = $this->postArray();
        $flag = $post['flag']??'';
        /** @var CartoonCommentModel $model */
        $model  = CartoonCommentModel::find($post['id']);
        if(!is_null($model)){
            if($flag == 'ban'){
                //禁言和下掉VIP
                MemberModel::ban($model->aff);
                CartoonCommentModel::where(['uid'=>$model->aff])->delete();
            }
            if($flag == 'black'){
                MemberModel::where(['uid'=>$model->aff])->update(['role_id'=>MemberModel::USER_ROLE_BLACK]);
                CartoonCommentModel::where(['uid'=>$model->aff])->delete();
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
        $comments = CartoonCommentModel::whereIn('id', $ary)
            ->where('status', CartoonCommentModel::STATUS_WAIT)
            ->get();

        try {
            transaction(function () use ($comments) {
                /** @var CartoonCommentModel $comment */
                foreach ($comments as $comment) {
                    $ret = CartoonCommentModel::where('id', $comment->id)
                        ->update(['status' => CartoonCommentModel::STATUS_PASS]);
                    if ($ret <= 0)
                        throw new Exception('系统异常');

                    $postId = $comment->original_id;
                    $post = CartoonModel::find($postId);
                    if ($post){
                        $post->increment('com_count');
                        //清理缓存
//                        CartoonCommentModel::clearCacheWhenCreatePostComment($postId);
                    }
                }
            });
            return $this->ajaxSuccessMsg('操作成功');
        } catch (Exception $e) {
            return $this->ajaxError($e->getMessage());
        }
    }
}