<?php

/**
 * Class Findreplyreport
 */
class FindreplyreportController extends BackendBaseController
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
            /** @var FindReplyReportModel $item */
            $item->mvIds  = FindReplyMvModel::where('reply_id', $item->find_reply_id)->pluck('mv_id')->join(',');
            $item->created_str = date('Y-m-d H:i:s' , $item->created_at);
            return $item->toArray();
        };
    }


    /**
     * 试图渲染
     * @return string
     * @date 2019-12-27 15:45:40
     */
    public function indexAction()
    {
        $this->display();
    }

    /**
     *批量处理忽略视频
     */
    public function refuseAction()
    {
        $id = $_POST['_pk'] ?? null;
        $model = FindReplyReportModel::find($id);
        if (is_null($model)) {
            return $this->ajaxError('无效举报记录');
        }
        //$mv = FindReplyModel::find($model->mv_id);
        $w = [
            ['find_reply_id', '=', $model->find_reply_id],
            ['status', '=', FindReplyReportModel::STATUS_INIT],
        ];
        FindReplyReportModel::where($w)->update(['status' => FindReplyReportModel::STATUS_FAIL]);
        return $this->ajaxSuccessMsg('已成功对所有次视频的举报忽略处理~');
    }

    /**
     * 批量处理举报视频
     * @return bool
     */
    public function saveAction()
    {
        $id = $_POST['_pk'] ?? null;
        $model = FindReplyReportModel::find($id);
        if (is_null($model)) {
            return $this->ajaxError('无效举报记录');
        }
        FindReplyModel::where('id', $model->find_reply_id)->delete();
        $model->decrement('comment');
        //FindReplyReportModel::where('id',$id)->update(['status'=>FindReplyReportModel::STATUS_SUCCESS]);
        $w = [
            ['find_reply_id', '=', $model->find_reply_id],
            ['status', '=', FindReplyReportModel::STATUS_INIT],
        ];
        FindReplyReportModel::where($w)->update(['status' => FindReplyReportModel::STATUS_SUCCESS]);
        return $this->ajaxSuccessMsg('已成功删除此视频并且对此视频的所有举报标记处理成功');
    }


    /**
     * 获取对应的model名称
     * @return string
     * @date 2019-12-27 15:45:40
     */
    protected function getModelClass(): string
    {
        return FindReplyReportModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @date 2019-12-27 15:45:40
     */
    protected function getPkName(): string
    {
        return 'id';
    }


    protected function waitAction()
    {
        $this->display();
    }


    /**
     * 定义数据操作的表主键名称
     * @return string
     * @date 2019-11-04 17:19:41
     */
    protected function getLogDesc(): string
    {
        return '举报处理';
    }

}