<?php

use service\CacheKeyService;


class CachekeysController extends BackendBaseController
{
    public function listAjaxAction()
    {
        $groups = CacheKeyService::all_group();
        $result = [
            'count' => $groups->count(),
            'data'  => $groups,
            "msg"   => '',
            "desc"  => '',
            'code'  => 0
        ];
        return $this->ajaxReturn($result);
    }

    /**
     * 试图渲染
     * @return string
     *
     * @date 2023-06-15 23:23:43
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     *
     * @date 2023-06-15 23:23:43
     */
    protected function getModelClass(): string
    {
        return CacheKeysModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     *
     * @date 2023-06-15 23:23:43
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

    public function refreshAction()
    {
        try {
            if ($_POST['group']) {
                CacheKeyService::clear_group($_POST['group']);
            }
            return $this->ajaxSuccessMsg('成功清理缓存');
        } catch (Throwable $e) {
            return $this->ajaxError($e->getMessage());
        }
    }
}