<?php

/**
 * Class MvbackuserController
 * @author xiongba
 * @date 2021-01-02 18:13:02
 */
class MvbackuserController extends BackendBaseController
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
     * @date 2021-01-02 18:13:02
     */
    public function indexAction()
    {
        /*$d = MvBackUserModel::getBackUserList();
        print_r($d);die;*/
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2021-01-02 18:13:02
     */
    protected function getModelClass(): string
    {
       return MvBackUserModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2021-01-02 18:13:02
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
    protected function postArray($setPost = null)
    {
        $post = parent::postArray();
        if(MvBackUserModel::where(['uid'=>intval($post['uid'])])->exists()){
            throw new Exception("黑名单用户已经存在",422);
        }
        $post['created_at'] = date('Y-m-d H:i:s',TIMESTAMP);
        return $post;
    }

    /**
     * 删除数据
     */
    public function delAction() {
        if (!$this->getRequest()->isPost()) {
            return $this->ajaxError('请求错误');
        }
        $post = $_POST;
        $where = ['id' => $post['_pk']];
        $model = MvBackUserModel::where($where)->first();
        if (!$model){
            return $this->ajaxError('黑名单不存在');
        }

        if ($model->delete()) {
            MvBackUserModel::clearCache();
            return $this->ajaxSuccessMsg('操作成功');
        } else {
            return $this->ajaxError('操作错误');
        }
    }

    /**
     * @param $model
     */
    protected function saveAfterCallback($model)
    {
        if (is_null($model)) {
            return;
        }
        MvBackUserModel::clearCache();
    }
}