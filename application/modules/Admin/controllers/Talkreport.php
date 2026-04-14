<?php

/**
 * Class TalkreportController
 * @author xiongba
 * @date 2021-08-04 15:26:54
 */
class TalkreportController extends BackendBaseController
{
    /**
     * 列表数据过滤
     * @return Closure
     * @author xiongba
     * @date 2019-12-02 17:08:03
     */
    protected function listAjaxIteration()
    {
        return function (TalkReportModel $item) {
            $item->value = htmlentities($item->value);
            return $item;
        };
    }


    public function closeAction()
    {
        $id = $_POST['id'] ?? 0;
        if (empty($id)) {
            return $this->ajaxError('参数错误');
        }
        $model = TalkReportModel::find($id);
        if (empty($model)) {
            return $this->ajaxError('投诉数据不存在');
        }

        $model->status = TalkReportModel::STATUS_YES;
        $model->save();
        return $this->ajaxSuccessMsg('处理完成');
    }


    protected function listAjaxWhere()
    {
        if (isset($_GET['where'])) {
            return [];
        }
        return [
            ['status', '=', TalkReportModel::STATUS_NO]
        ];
    }


    /**
     * 试图渲染
     * @return string
     * @author xiongba
     * @date 2021-08-04 15:26:54
     */
    public function indexAction()
    {
        $this->display();
    }

    protected function getModelQuery()
    {
        return TalkReportModel::with('frommember', 'tomember');
    }

    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2021-08-04 15:26:54
     */
    protected function getModelClass(): string
    {
        return TalkReportModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2021-08-04 15:26:54
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
    protected function getLogDesc(): string
    {
        // TODO: Implement getLogDesc() method.
        return '';
    }
}