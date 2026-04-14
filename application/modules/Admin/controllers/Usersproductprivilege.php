<?php

/**
 * Class UsersproductprivilegeController
 *
 * @date 2022-04-08 21:28:12
 */
class UsersproductprivilegeController extends BackendBaseController
{
    /**
     * 列表数据过滤
     * @return Closure
     *
     * @date 2019-12-02 17:08:03
     */
    protected function listAjaxIteration()
    {
        return function (UsersProductPrivilegeModel $item) {
            $item->resource_type_str = PrivilegeModel::RESOURCE_TYPE[$item->resource_type];
            $item->privilege_type_str = PrivilegeModel::PRIVILEGE_TYPE[$item->privilege_type];
            $item->day_remain = 0;
            if ($item->privilege_type != PrivilegeModel::PRIVILEGE_TYPE_DISCOUNT){
                $item->day_remain = $item->value;
            }
            //今日剩余次数
            if ($item->day_value > 0){
                $item->day_remain = UsersProductPrivilegeModel::hasDayValue($item->aff, $item->privilege_id, $item->value, $item->day_value);
            }
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     *
     * @date 2022-04-08 21:28:12
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     *
     * @date 2022-04-08 21:28:12
     */
    protected function getModelClass(): string
    {
       return UsersProductPrivilegeModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     *
     * @date 2022-04-08 21:28:12
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
}