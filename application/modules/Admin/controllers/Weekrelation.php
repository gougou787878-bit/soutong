<?php

use service\WeekService;

/**
 * Class WeekrelationController
 * @author xiongba
 * @date 2020-11-10 18:32:58
 */
class WeekrelationController extends BackendBaseController
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
            $item->week_title = $item->week->title;
            $item->mv_img_thumb = $item->mv->cover_thumb_url;
            $item->mv_title = $item->mv->title;
            $item->mv_coins = $item->mv->coins;
            $item->mv_status = $item->mv->status;
            $item->mv_hide = $item->mv->is_hide;
            $item->mv_pre_href = getAdminPlayM3u8($item->mv->m3u8);
            $item->mv_full_href = $item->mv_pre_href;
            if ($item->mv_coins) {
                $item->mv_full_href = getAdminPlayM3u8($item->mv->full_m3u8);
            }
            $item->created_at = $item->created_at?date('Y-m-d H:i',$item->created_at):'';
            unset($item->mv, $item->topic);
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     * @author xiongba
     * @date 2020-11-10 18:32:58
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2020-11-10 18:32:58
     */
    protected function getModelClass(): string
    {
       return WeekRelationModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2020-11-10 18:32:58
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
        return '周视频推荐';
    }
    protected function postArray($setPost = null)
    {
        $post = parent::postArray();
        $post['created_at'] = TIMESTAMP;
        return $post;
    }
    /**
     *
     * @return bool
     */
    public function saveAction()
    {
        $data = $_POST;
        /*Array
        (
            [week_id] => 43
    [mv_id] => 28157
    [is_title] => 1
    [comment] => 美美哒，二狗子
    [_pk] =>
)*/
      parent::saveAction();
    }
    /**
     * @param WeekRelationModel $model
     */
    protected function saveAfterCallback($model)
    {
        if (is_null($model)) {
            return;
        }
        $data = $_POST;
        if($data['is_title']){
            $title = $model->mv->title;
            WeekModel::where('id',$model->week_id)->update(['mv_title'=>$title,'created_at'=>TIMESTAMP]);
        }
    }


    /**
     * 删除数据
     * 后台全局公共方法
     * @return mixed
     * @author xiongba
     * @date 2019-11-08 11:19:24
     */
    public function delAction()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->ajaxError('请求错误');
        }
        $post = $this->postArray();
        $className = $this->getModelClass();
        $pkName = $this->getPkName();
        $where = [$pkName => $post['_pk']];
        $model = $className::where($where)->first();
        WeekService::clearTopicList();
        WeekService::clearTopicMV($model->week_id);
        //清楚缓存
        if (empty($model) || $model->delete()) {
            \AdminLogModel::addDelete($this->getUser()->username , "删除了：".$this->getLogDesc());
            return $this->ajaxSuccessMsg('操作成功');
        } else {
            return $this->ajaxError('操作错误');
        }
    }
}