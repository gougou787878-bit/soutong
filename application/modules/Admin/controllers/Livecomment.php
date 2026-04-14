<?php

/**
 * Class LiveCommentController
 *
 * @date 2024-01-05 18:36:14
 */
class LiveCommentController extends BackendBaseController
{
    /**
     * 列表数据过滤
     * @return Closure
     *
     * @date 2019-12-02 17:08:03
     */
    protected function listAjaxIteration()
    {
        return function (LiveCommentModel $item) {
            $item->status_str = LiveCommentModel::STATUS_TIPS[$item->status];
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
     * @date 2024-01-05 18:36:14
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     *
     * @date 2024-01-05 18:36:14
     */
    protected function getModelClass(): string
    {
        return LiveCommentModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     *
     * @date 2024-01-05 18:36:14
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
        /** @var LiveCommentModel $model */
        $model = LiveCommentModel::where('id', $post['id'])->first();
        if (is_null($model)) {
            return $this->ajaxError('数据不存在');
        }
        try {
            $model->update(['status' => LiveCommentModel::STATUS_PASS]);
            //评论数量
            LiveModel::where('id',$model->live_id)->increment('comment_ct');
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
        try {
            if (!$this->getRequest()->isPost()) {
                return $this->ajaxError('请求错误');
            }
            $post = $this->postArray();
            $flag = LiveCommentModel::where('id', $post['id'])->update(['status' => LiveCommentModel::STATUS_REJECT]);
            return $this->ajaxSuccessMsg("操作成功#{$flag}");
        }catch (Throwable $e){
            return $this->ajaxError($e->getMessage());
        }
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
        $comments = LiveCommentModel::whereIn('id', $ary)
            ->where('status', LiveCommentModel::STATUS_WAIT)
            ->get();

        try {
            transaction(function () use ($comments) {
                foreach ($comments as $comment) {
                    $ret = LiveCommentModel::where('id', $comment->id)
                        ->update(['status' => LiveCommentModel::STATUS_PASS]);
                    if ($ret <= 0)
                        throw new Exception('系统异常');

                    $live_id = $comment->live_id;
                    $live = LiveModel::find($live_id);
                    if ($live){
                        $live->increment('comment_ct');
                    }
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
            $comments = LiveCommentModel::query()->whereIn('id',$commentIds)->where('status',LiveCommentModel::STATUS_WAIT)->get();
            if (!$comments){
                return $this->ajaxError('没有待审核的评论');
            }

            foreach ($comments as $comment) {
                $data = [
                    'status'       => LiveCommentModel::STATUS_REJECT,
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