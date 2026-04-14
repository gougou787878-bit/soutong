<?php


namespace traits;


use AdminLogModel;
use exception\ErrorPageException;

trait RBACVerifyTrait
{


    final protected function verifyRabc($controller, $action, $admin_id)
    {
        $model = \AdminMenuModel::where(['controller' => $controller, 'action' => $action])->first();
        if (!empty($model)) {
            if (!in_array($model->id, $this->getRule($admin_id))) {
                throw new ErrorPageException('不允许访问', 403);
            }
        }
    }


    /**
     * @param $admin_id
     * @return array
     * @throws ErrorPageException
     * @author xiongba
     * @date 2019-11-12 15:11:47
     */
    protected function getRule($admin_id)
    {
        $permit = \AdminPermitModel::where(['admin_id' => $admin_id])->first();
        if (empty($permit)) {
            AdminLogModel::addOther($this->getUser()->username, "拒绝访问");
            throw new ErrorPageException('不允许访问', 403);
        }
        $role = \AdminRoleModel::where(['id' => $permit->role_id])->first();
        if (empty($role)) {
            AdminLogModel::addOther($this->getUser()->username, "拒绝访问");
            throw new ErrorPageException('不允许访问', 403);
        }
        return explode(',', $role->rule);
    }


}