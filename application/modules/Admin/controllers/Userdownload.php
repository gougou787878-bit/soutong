<?php

/**
 * Class UserdownloadController
 * @author xiongba
 * @date 2024-03-06 11:58:33
 */
class UserdownloadController extends BackendBaseController
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
     * @date 2024-03-06 11:58:33
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2024-03-06 11:58:33
     */
    protected function getModelClass(): string
    {
       return UserDownloadModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2024-03-06 11:58:33
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

    public function addDownloadNumAction(){
        try {
            $aff = $_POST['aff'] ?? 0;
            $val = $_POST['val'] ?? 0;
            test_assert($aff, 'aff必填');
            test_assert($val, '次数必填');
            $member = MemberModel::firstAff($aff);
            test_assert($member, 'aff对应的用户不存在');
            test_assert($member->is_vip, '不是VIP用户，不能添加下载次数');
            UserDownloadModel::addDownloadNum($aff, $val);

            return $this->ajaxSuccessMsg("操作成功");
        }catch (Throwable $e){
            return $this->ajaxError($e->getMessage());
        }
    }
}