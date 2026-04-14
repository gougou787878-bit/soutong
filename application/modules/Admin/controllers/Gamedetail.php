<?php

/**
 * Class GamedetailController
 * @author xiongba
 * @date 2021-05-24 15:47:22
 */
class GamedetailController extends BackendBaseController
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
            $_d = @json_decode($item->description,1);
            $item->description_str = var_export($_d,1);
            $item->description_note = isset($_d[1])?var_export($_d[1],1):'-';
            $item->status_text = $item->status?'成功':'-';
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     * @author xiongba
     * @date 2021-05-24 15:47:22
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2021-05-24 15:47:22
     */
    protected function getModelClass(): string
    {
       return GameDetailModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2021-05-24 15:47:22
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
}