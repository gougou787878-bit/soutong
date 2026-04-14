<?php

/**
 * Class AinavController
 *
 * @date 2025-08-12 22:22:02
 */
class AinavController extends BackendBaseController
{
    /**
     * 列表数据过滤
     * @return Closure
     *
     * @date 2019-12-02 17:08:03
     */
    protected function listAjaxIteration()
    {
        return function (AiNavModel $item) {
            $item->type_str = AiNavModel::TYPE_TIPS[$item->type];
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     *
     * @date 2025-08-12 22:22:02
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     *
     * @date 2025-08-12 22:22:02
     */
    protected function getModelClass(): string
    {
       return AiNavModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     *
     * @date 2025-08-12 22:22:02
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