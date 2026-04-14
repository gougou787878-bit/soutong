<?php

/**
 * Class PosttopicController
 * @author xiongba
 * @date 2023-06-09 20:11:13
 */
class PosttopicController extends BackendBaseController
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
     * @date 2023-06-09 20:11:13
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2023-06-09 20:11:13
     */
    protected function getModelClass(): string
    {
       return PostTopicModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2023-06-09 20:11:13
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
     * 每次編輯更新片源時間
     * @param null $setPost
     * @return mixed
     */
    protected function postArray($setPost = null)
    {
        $post = parent::postArray();
        $post['created_at'] = date('Y-m-d H:i:s');
        return $post;
    }
}