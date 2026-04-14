<?php


class AdminRoleController extends BackendBaseController
{

    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2019-11-04 17:20:15
     */
    protected function getModelClass(): string
    {
        return \AdminRoleModel::class;
    }


    protected function postArray($setPost = null)
    {
        $post = parent::postArray();

        if (!empty($post['rule'])) {
            $post['rule'] = join(',', $post['rule']);
        }
        return $post;
    }


    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2019-11-04 17:19:41
     */
    protected function getPkName(): string
    {
        return 'id';
    }


    public function indexAction()
    {
        $this->display();
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2019-11-04 17:19:41
     */
    protected function getLogDesc(): string
    {
        return '后台角色';
    }
}