<?php

/**
 * Class ProductprivilegeController
 *
 * @date 2022-04-08 21:27:50
 */
class ProductprivilegeController extends BackendBaseController
{
    /**
     * 列表数据过滤
     * @return Closure
     *
     * @date 2019-12-02 17:08:03
     */
    protected function listAjaxIteration()
    {
        return function ($item) {
            $item->load(['product','privilege']);
            if (empty($item->product)){
                $item->p_pname = "<span style='color: red'>产品已删除</span>";
            }else{
                $item->p_pname = $item->product->pname;
            }
            if (empty($item->privilege)){
                $item->privilege_str = "<span style='color: red'>权限已删除</span>";
            }else{
                $item->privilege_str = $item->privilege->resource_type_str . '|' . $item->privilege->privilege_type_str . '|' . $item->privilege->value . '|' . $item->privilege->day_value;
            }
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     *
     * @date 2022-04-08 21:27:50
     */
    public function indexAction()
    {
        $this->assign('vipData',ProductModel::getAdminVIPDataList());
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     *
     * @date 2022-04-08 21:27:50
     */
    protected function getModelClass(): string
    {
       return ProductPrivilegeModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     *
     * @date 2022-04-08 21:27:50
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