<?php

use service\AiSdkService;

/**
 * Class MemberstripController
 *
 * @date 2024-01-02 16:01:32
 */
class MemberstripController extends BackendBaseController
{
    /**
     * 列表数据过滤
     * @return Closure
     *
     * @date 2019-12-02 17:08:03
     */
    protected function listAjaxIteration()
    {
        return function (MemberStripModel $item) {
            $item->status_str = MemberStripModel::STATUS_TIPS[$item->status];
            $item->pay_type_str = MemberStripModel::PAY_TYPE_TIPS[$item->pay_type];
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     *
     * @date 2024-01-02 16:01:32
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     *
     * @date 2024-01-02 16:01:32
     */
    protected function getModelClass(): string
    {
       return MemberStripModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     *
     * @date 2024-01-02 16:01:32
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

    public function retryAction()
    {
        try {
            $post = $this->postArray();
            $ary = explode(',', $post['ids'] ?? '');
            $ary = array_filter($ary);
            MemberStripModel::whereIn('id', $ary)
                ->whereIn('status', [MemberStripModel::STATUS_FAIL, MemberStripModel::STATUS_WAIT, MemberStripModel::STATUS_DOING])
                ->update(['status' => MemberStripModel::STATUS_WAIT]);
            return $this->ajaxSuccessMsg('操作成功');
        } catch (Exception $e) {
            return $this->ajaxError($e->getMessage());
        }
    }

    public function aiFrequencyAction(){
        try {
            $post = $this->postArray();
            $aff = (int)$post['aff'];
            $type = (int)$post['type'];
            $num = (int)$post['num'];
            if (!in_array($type, [1, 2])){
                return $this->ajaxError('类型不对');
            }
            //判断用户有没有对应卡的权限
            $member = MemberModel::find($aff);
            if (!$member->is_vip){
                return $this->ajaxError('用户不是VIP了');
            }
            $privilege = UsersProductPrivilegeModel::where('aff', $aff)
                ->where('resource_type', PrivilegeModel::RESOURCE_TYPE_AI_TY)
                ->where('privilege_type', PrivilegeModel::PRIVILEGE_TYPE_UNLOCK)
                ->where('status', UsersProductPrivilegeModel::STATUS_YES)
                ->orderByDesc('id')
                ->first();
            if (empty($privilege)){
                return $this->ajaxError('用户没有免费AI脱衣权限');
            }
            //增加还是减少
            if ($type == 1){
                $privilege->value += $num;
            }else{
                if ($privilege->value < $num){
                    return $this->ajaxError('用户剩余AI次数不足');
                }
                $privilege->value = $privilege->value - $num;
            }
            if ($privilege->isDirty()){
                $privilege->save();
                UsersProductPrivilegeModel::clearCache($aff);
            }

            return $this->ajaxSuccessMsg('操作成功');
        } catch (Exception $e) {
            return $this->ajaxError($e->getMessage());
        }
    }
}