<?php



/**
 * Class MvreportController
 * @author xiongba
 */
class MvreportController extends BackendBaseController
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
            /** @var MvReportModel $item */
            $item->mv_img_thumb = url_cover($item->mv->cover_thumb);
            $item->mv_title = $item->mv->title;
            $item->mv_status = $item->mv->status;
            $item->mv_is_delete = $item->mv->is_delete;
            $item->mv_pre_href = getAdminPlayM3u8($item->mv->m3u8);
            $item->mv_username = $item->mv->user->nickname;
            $item->mv_uid = $item->mv->uid;
            $item->mv_coins = $item->mv->coins;
            unset($item->mv);
            return $item;
        };
    }


    protected function listAjaxWhere()
    {
        return [];
        return [
            ['status','=',MvReportModel::STATUS_INIT]
        ];
    }

    /**
     * 试图渲染
     * @return string
     * @author xiongba
     * @date 2019-12-27 15:45:40
     */
    public function indexAction()
    {
        $this->display();
    }

    /**
     *批量处理忽略视频
     */
    public function refuseAction(){
        $id = $_POST['_pk'] ?? null;
        $model = MvReportModel::find($id);
        if (is_null($model)) {
            return $this->ajaxError('无效举报记录');
        }
        //$mv = MvModel::find($model->mv_id);
        $w = [
            ['mv_id','=',$model->mv_id],
            ['status','=',MvReportModel::STATUS_INIT],
        ];
        MvReportModel::where($w)->update(['status' => MvReportModel::STATUS_FAIL]);
        return $this->ajaxSuccessMsg('已成功对所有次视频的举报忽略处理~');
    }
    /**
     *批量处理忽略视频 撤销视频处理
     */
    public function recoveryAction(){
        $id = $_POST['_pk'] ?? null;
        $model = MvReportModel::find($id);
        if (is_null($model)) {
            return $this->ajaxError('无效举报记录');
        }
        if ($model->mv_id) {
            MvModel::where('id', $model->mv_id)->update(['status' => MvModel::STAT_CALLBACK_DONE]);
        }
        $w = [
            ['mv_id','=',$model->mv_id],
            ['status','=',MvReportModel::STATUS_INIT],
        ];
        MvReportModel::where($w)->update(['status' => MvReportModel::STATUS_SUCCESS]);
        return $this->ajaxSuccessMsg('已成功对所有次视频的举报忽略处理~');
    }

    /**
     * 批量处理举报视频
     * @return bool
     */
    public function delMvAction()
    {
        $id = $_POST['_pk'] ?? null;
        $model = MvReportModel::find($id);
        if (is_null($model)) {
            return $this->ajaxError('无效举报记录');
        }
        $mv = MvModel::find($model->mv_id);
        $mv->status = MvModel::STAT_REMOVE;
        $flag = $mv->save();
        if (true) {
            $w = [
                ['mv_id', '=', $model->mv_id],
                ['status', '=', MvReportModel::STATUS_INIT],
            ];
            MvReportModel::where($w)->update(['status' => MvReportModel::STATUS_SUCCESS]);
            return $this->ajaxSuccessMsg('已成功删除此视频并且对此视频的所有举报标记处理成功');
        }
        $limit1 = max(intval(setting('mv:report:income:limit', 0)) , 0);
        $limit2 = max(intval(setting('mv:report:cancel:limit', 0)) , 0);
        if ($flag) {
            //视频拥有者
            $tpl = null;
            $params = [];
            if ($limit1 && $limit2) {
                $tpl = MessageModel::SYSTEM_MSG_TPL_MV_REPORT_3;
                $params = [
                    $mv->title,
                    $model->content,
                    $limit1,
                    $limit2
                ];
            } elseif ($limit1) {
                $tpl = MessageModel::SYSTEM_MSG_TPL_MV_REPORT_1;
                $params = [
                    $mv->title,
                    $model->content,
                    $limit1
                ];
            } elseif ($limit2) {
                $tpl = MessageModel::SYSTEM_MSG_TPL_MV_REPORT_2;
                $params = [
                    $mv->title,
                    $model->content,
                    $limit2
                ];
            }
            if ($tpl) {
                MessageModel::createSystemMessage($mv->user->uuid , $tpl , $params);
            }

            $desc = vsprintf('您的 %s 视频被多次举报 %s，经核实现已被删除', ['title' => $mv->title,'content'=>$model->content]);
            MessageModel::createMessage($mv->user->uuid , '系统通知' , $desc);
            //举报者
            $desc_reporter =vsprintf('您举报的视频 %s %s,经核实现已被删除', ['title' => $mv->title,'content'=>$model->content]);
            MessageModel::createMessage($model->uuid, '', '', $desc_reporter);

        }
        //MvReportModel::where('id',$id)->update(['status'=>MvReportModel::STATUS_SUCCESS]);
        $w = [
            ['mv_id','=',$model->mv_id],
            ['status','=',MvReportModel::STATUS_INIT],
        ];
        MvReportModel::where($w)->update(['status' => MvReportModel::STATUS_SUCCESS]);
        $w = [
            ['mv_id', '=', $model->mv_id],
            ['status', '=', MvReportModel::STATUS_SUCCESS],
        ];
        $score = MvReportModel::where($w)->count();
        if ($limit1 && $score >= $limit1) {
            //扣收益
            $payCount = MvPayModel::where('mv_id', $mv->id)->count();
            $payCoin = $payCount * max(1, $mv->coins) * 10; //处罚的蓝票
            $desc = sprintf("你的视频《%s》被投诉[%s]受理成功，处罚当前视频的10倍收益%d蓝票", $mv->title, $model->content, $payCoin);
            MessageModel::createMessage($mv->user->uuid , '系统通知' , $desc);
        }
        if ($limit2 && $score >= $limit2) {
            //取消创作者
            $desc = sprintf("你的视频《%s》被投诉[%s]受理成功，并且该视频投诉总次数大于：%d，[%s]开始取消您的创作者身份",
                $mv->title,
                $model->content,
                $score,
                date('Y-m-d H:i:s')
            );
            MessageModel::createMessage($mv->user->uuid , '系统通知' , $desc);
        }
        return $this->ajaxSuccessMsg('已成功删除此视频并且对此视频的所有举报标记处理成功');
    }


    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2019-12-27 15:45:40
     */
    protected function getModelClass(): string
    {
        return MvReportModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
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


    public function saveAfterCallback($model)
    {
        if (empty($model)) {
            return;
        }
    }


    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2019-11-04 17:19:41
     */
    protected function getLogDesc(): string
    {
        return '举报处理';
    }

}