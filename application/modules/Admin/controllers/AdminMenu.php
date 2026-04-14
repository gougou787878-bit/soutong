<?php


use Carbon\Carbon;
use traits\OverloadActionTrait;

class AdminMenuController extends BackendBaseController
{


    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2019-11-04 17:20:15
     */
    protected function getModelClass(): string
    {
        return \AdminMenuModel::class;
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


    protected function postArray($setPost = null)
    {
        $post = parent::postArray();
        if (isset($post['pid'])) {
            if (empty($post['pid'])) {
                $post['level'] = 1;
            } else {
                $menu = AdminMenuModel::where(['id' => $post['pid']])->first();

                if (!empty($menu)) {
                    $post['level'] = $menu->level + 1;
                    //var_dump($menu->toArray());
                } else {
                    $post['level'] = 1;
                }
            }
        }
        if (empty($post['_pk'])) {
            $post['created_at'] = Carbon::now()->toDateTimeString();
        }
        //var_dump($post);

        return $post;
    }

    /**
     * 获取菜单树形数据
     * @return bool
     * @author xiongba
     * @date 2019-11-08 16:36:47
     */
    public function treeListAction()
    {
        $data = AdminMenuModel::getTreeAll();
        return $this->ajaxSuccess($data);
    }


    /**
     * @author xiongba
     * @date 2019-12-02 17:07:45
     */
    public function listAjaxAction()
    {
        $data = [];
        $data['data'] = AdminMenuModel::getAll();
        $data['total'] = count($data['data']);
        $data['code'] = 0;
        return $this->ajaxReturn($data);
    }

    public function indexAction()
    {
        return $this->display();
    }

}