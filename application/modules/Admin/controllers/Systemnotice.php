<?php

/**
 * Class SystemnoticeController
 * @author xiongba
 * @date 2021-07-19 22:12:50
 */
class SystemnoticeController extends BackendBaseController
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
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     * @author xiongba
     * @date 2021-07-19 22:12:50
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2021-07-19 22:12:50
     */
    protected function getModelClass(): string
    {
       return SystemNoticeModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2021-07-19 22:12:50
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

    public function clearCachedAction()
    {
        $uuid = $_POST['uuid'] ?? 'xyz';
        $flag = $_POST['flag'] ?? '';
        $query = SystemNoticeModel::where('uuid',$uuid);
        $b = '';
        if($flag == 'status'){
            $b = $query->update(['status'=>SystemNoticeModel::STAT_OK]);
        }else if($flag == 'clear'){
            $b = $query->delete();
        }
        return $this->ajaxSuccessMsg("ok#{$b} effected");
    }
}