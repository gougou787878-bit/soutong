<?php

/**
 * Class PictureController
 * 
 * @date 2022-06-28 20:53:41
 */
class PictureController extends BackendBaseController
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
     * @date 2022-06-28 20:53:41
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     * 
     * @date 2022-06-28 20:53:41
     */
    protected function getModelClass(): string
    {
       return PictureModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * 
     * @date 2022-06-28 20:53:41
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

    public function refreshAction(){
        $id = $_POST['id'] ?? '';
        $flag = 0;
        if ($id) {
            $flag = PictureModel::where(['id' => $id])->update(['refresh_at' => date("Y-m-d H:i:s")]);
        }
        return $this->ajaxSuccessMsg("处理状态：『{$flag}』");
    }
}