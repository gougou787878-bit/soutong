<?php

/**
 * Class UsertopicController
 * @author xiongba
 * @date 2021-02-23 15:57:33
 */
class TopicController extends BackendBaseController
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
            $item->status_str = TopicModel::STAT[$item->status];
            $item->refresh_at_str = date('Y-m-d H:i:s',$item->refresh_at);
            $item->total_coins = TopicRelationModel::getSumCoins($item->id);
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     * @author xiongba
     * @date 2021-02-23 15:57:33
     */
    public function indexAction()
    {
        $this->display();
    }


    public function delAction()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->ajaxError('请求错误');
        }
        $post = $this->postArray();
        /** @var TopicModel $model */
        $model = TopicModel::find($post['_pk']);

        if (empty($model)){
            return $this->ajaxError('合集不存在');
        }
        try {
            $this->del($model);
            return $this->ajaxSuccessMsg('操作成功');
        } catch (Throwable $e) {
            return $this->ajaxError($e->getMessage());
        }
    }

    public function saveAfterCallback($model)
    {
        redis()->del(\service\TopicService::TOPIC_LIST_KEY);
    }

    public function del($topic)
    {
        return transaction(function () use ($topic) {
            //更新视频的topid
            \MvModel::where('collect_id', $topic->id)->update(['collect_id' => 0]);

            TopicRelationModel::where('topic_id',$topic->id)->delete();
            //删除点赞
            $likeCount = \TopicLikeModel::where('topic_id' , $topic->id)->count();
            if ($likeCount){
                \TopicLikeModel::where('topic_id' , $topic->id)->delete();
            }
            if (!$topic->delete()) {
                throw new \Exception('操作失败，请重试3');
            }
            return true;
        });
    }

    public function refreshAction()
    {
         TopicModel::query()->get()->map(function ($item){
             $nums =  TopicRelationModel::query()->where('topic_id',$item->id)->count();
             $item->update(['video_count'=>$nums]);
         });
        return $this->ajaxSuccessMsg('更新成功');
    }


    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2021-02-23 15:57:33
     */
    protected function getModelClass(): string
    {
        return TopicModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2021-02-23 15:57:33
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
}