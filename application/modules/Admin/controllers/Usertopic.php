<?php

/**
 * Class UsertopicController
 * @author xiongba
 * @date 2021-02-23 15:57:33
 */
class UsertopicController extends BackendBaseController
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
     * @date 2021-02-23 15:57:33
     */
    public function indexAction()
    {
        $this->display();
    }


    public function saveAction()
    {
    }

    public function delAllAction()
    {
    }

    public function delAction()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->ajaxError('请求错误');
        }
        $post = $this->postArray();
        $service =new \service\UserTopicService();
        $model = UserTopicModel::find($post['_pk']);

        if (empty($model)){
            return $this->ajaxError('合集不存在');
        }

        $member = $model->user;

        try {
            $service->delete_topic($member, $post['_pk']);
            return $this->ajaxSuccessMsg('操作成功');
        } catch (Throwable $e) {
            return $this->ajaxError($e->getMessage());
        }
    }

    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2021-02-23 15:57:33
     */
    protected function getModelClass(): string
    {
       return UserTopicModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2021-02-23 15:57:33
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