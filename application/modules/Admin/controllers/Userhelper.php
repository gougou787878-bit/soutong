<?php

/**
 * Class UserhelperController
 * @author xiongba
 * @date 2020-03-16 17:57:30
 */
class UserhelperController extends BackendBaseController
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
     * @date 2020-03-16 17:57:30
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2020-03-16 17:57:30
     */
    protected function getModelClass(): string
    {
       return UserhelperModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2020-03-16 17:57:30
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
     * 过滤 post数据，
     * @param null $setPost
     * @return mixed
     */
    protected function postArray($setPost = null) {
        if ($setPost !== null) {
            $this->_setPost = $setPost;
            $post = $this->_setPost;
        }
        if (empty($post)) {
            $post = request()->getPost();
        }
        $post['created_at'] = date('Y-m-d',TIMESTAMP);
        return $post;
    }

    public function saveAfterCallback($model)
    {
        UserhelperModel::clearCache();
    }


    public function _deleteActionAfter()
    {
        UserhelperModel::clearCache();
    }


}