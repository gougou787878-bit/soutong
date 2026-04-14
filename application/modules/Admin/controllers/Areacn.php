<?php

/**
 * Class AreacnController
 *
 * @date 2022-02-23 16:17:23
 */
class AreacnController extends BackendBaseController
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
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     *
     * @date 2022-02-23 16:17:23
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     *
     * @date 2022-02-23 16:17:23
     */
    protected function getModelClass(): string
    {
       return AreaCnModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     *
     * @date 2022-02-23 16:17:23
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
    /**
     * 统计
     * @return mixed
     */
    public function totalAction()
    {
        $where = array_merge(
            $this->getSearchLikeParam(),
            $this->getSearchWhereParam()
        );
        $className = $this->getModelClass();
        $query = $className::query()->where($where);
        $count = $query->count('id');
        $data = [
            'count' => $count,//记录数
        ];
        return $this->ajaxSuccess($data);
    }
    function saveAfterCallback($model)
    {
        if(!is_null($model)){
            AreaCnModel::clearRedisCache();
        }

    }
}