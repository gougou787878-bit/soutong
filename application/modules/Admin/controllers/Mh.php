<?php

/**
 * Class MhController
 * @author xiongba
 * @date 2022-05-17 17:35:18
 */
class MhController extends BackendBaseController
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
     * @date 2022-05-17 17:35:18
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2022-05-17 17:35:18
     */
    protected function getModelClass(): string
    {
       return MhModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2022-05-17 17:35:18
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
    /**
     * 统计
     * @return mixed
     */
    public function totalAction()
    {
        $where = array_merge(
            $this->getSearchLikeParam(),
            $this->getSearchWhereParam(),
            $this->getSearchBetweenParam()
        );
        $className = $this->getModelClass();
        $query = $className::query()->where($where);
        $count = (clone  $query)->count('id');
        $count_fee = $query->where('coins','>',0)->count('id');
        $data = [
            'count'     => $count,//记录数
            'count_fee' => $count_fee,//记录数
            'rate_fee'  => round(($count_fee / $count) * 100, 2),//记录数
        ];
        return $this->ajaxSuccess($data);
    }
}