<?php

class EgguserController extends BackendBaseController
{

    /**
     * 列表数据过滤
     * @return Closure
     */
    protected function listAjaxIteration()
    {
        return function (EggUserModel $item) {
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
     * 获取本控制器和哪个model绑定
     * @return string
     */
    protected function getModelClass(): string
    {
        return EggUserModel::class;
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
    protected function getLogDesc(): string
    {
        return '';
    }

    public function addLotteryTimesAction(){
        try {
            $aff = $_POST['aff'] ?? 0;
            $val = $_POST['val'] ?? 0;
            test_assert($aff, 'aff必填');
            test_assert($val, '次数必填');
            $member = MemberModel::firstAff($aff);
            test_assert($member, 'aff对应的用户不存在');
            EggUserModel::addUserLottery($member, $val);

            return $this->ajaxSuccessMsg("操作成功");
        }catch (Throwable $e){
            return $this->ajaxError($e->getMessage());
        }
    }
}