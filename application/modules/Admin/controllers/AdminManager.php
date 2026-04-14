<?php


use exception\ExitException;

class AdminManagerController extends BackendBaseController
{
    //use OverloadActionTrait;

    public function indexAction()
    {
        $this->display();
    }


    /**
     * 列表数据过滤
     * @return Closure
     * @author xiongba
     * @date 2019-12-02 17:08:03
     */
    protected function listAjaxIteration()
    {
        $rulePermitArray = \AdminPermitModel::get();
        $ruleArray = \AdminRoleModel::get();
        return function ($item) use ($rulePermitArray, $ruleArray) {
            /** @var ManagersModel $item */
            $item->addHidden(['password']);
            $result = $item->toArray();

            /** @var \AdminPermitModel $vv */
            foreach ($rulePermitArray as $vv) {
                if ($item->uid == $vv->admin_id) {
                    $result['_role_id'] = $vv->role_id;
                    $role = AdminRoleModel::where('id', '=', $vv->role_id)->first();
                    if ($role) {
                        $result['role_name'] = $role->name;
                    }
                }
            }
            $result['role'] = $ruleArray->toArray();
            return $result;
        };
    }


    public function _passwordActionBefore()
    {
        if ($this->getRequest()->isPost()) {

            $param = $this->getRequest()->getPost();
            if (empty($param['old_pwd']) && !empty($param['password'])) {
                $this->ajaxError('请输入旧密码', -2);
            }
            if (!empty($param['old_pwd']) && empty($param['password'])) {
                $this->ajaxError('请输入新密码', -3);
            }

            if (empty($param['old_pwd']) || empty($param['password'])) {
                $this->ajaxError('历史密码或者新密码不能为空', -3);
            }
            $model = ManagersModel::where(['id' => $this->getUser()->id])->first();
            if (empty($model)) {
                $this->ajaxError('管理员不存在', -4);
            }
            /** @var ManagersModel $model */
            if (!$model->verifyPassword($param['old_pwd'])) {
                $this->ajaxError('旧密码错误', -1);
            }

            try {
                $model->updatePassword($param['password']);
                $this->ajaxSuccessMsg('修改信息成功');
            } catch (Throwable $e) {
            }
            $this->ajaxError('操作失败');
        }
    }

    /**
     * 修改管理员密码
     * @author xiongba
     * @date 2019-11-05 16:31:53
     */
    public function passwordAction()
    {
        return $this->display();
    }

    protected function postArray($s = null)
    {
        $post = parent::postArray();
        return $post;
    }


    public function saveAction()
    {
        $post = $this->postArray();

        if (empty($post['_pk'])) {
            $oauth_id = md5(time());
            if (empty($post['password'])) {
                return $this->ajaxError('密码不能为空');
            }
            if (empty($post['flag']) || empty($post['secret'])) {
                return $this->ajaxError('验证标识不能为空，联系管理员');
            }
            $post['password'] = $this->MakePasswordHash($post['password']);
            // $this->Db->SetTable($this->manager_table);
            $post = array(
                'oauth_type' => 'adminManager',
                'oauth_id'   => $oauth_id,
                'username'   => $post['username'],
                'password'   => $post['password'],
                'role_id'    => $post['_role_id'],
                '_role_id'   => $post['_role_id'],
                'uuid'       => md5('adminManager' . $oauth_id),
                'secret'   => $post['secret'],
                'flag'   => $post['flag'],
            );
        } else {
            if (!empty($post['role_id'])){
                $post['_role_id'] = $post['role_id'];
            }
            if (isset($post['password']) && strlen($post['password']) === 0) {
                unset($post['password']);
            }else{
                $post['password'] = $this->MakePasswordHash($post['password']);
            }
        }


        $model = $this->doSave($post);

        if (empty($model)) {
            return $this->ajaxError('操作失败');
        }

        //清除连续登录3次限制
        if (isset($post['validate']) && $post['validate'] == ManagersModel::STATUS_SUCCESS) {
            $key = 'login:'.$model->username;
            redis()->del($key);
        }



        /** @var ManagersModel $model */
        if (isset($post['_role_id'])) {
            $role_id = $post['_role_id'];
            AdminPermitModel::createOrUpdate($model->uid, $role_id);
        }
        $this->ajaxSuccessMsg('操作成功');
    }


    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2019-11-04 17:20:15
     */
    protected function getModelClass(): string
    {
        return \ManagersModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2019-11-04 17:19:41
     */
    protected function getPkName(): string
    {
        return 'uid';
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2019-11-04 17:19:41
     */
    protected function getLogDesc(): string
    {
        return '管理员';
    }
    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2019-11-04 17:19:41
     */
    public function qrcodeAction()
    {
        $uid = $_POST['id'] ?? '';
        $user = \ManagersModel::query()->where("uid", $uid)->first();
        if (!$user) {
            $this->ajaxError('操作成功');
        }
        $name = $_REQUEST['name'] ?? 'FK_' . SmsLogModel::genSmsCode(6);

        $googleAuthor = new \tools\GoogleAuthenticator();
        $sec = $googleAuthor->createSecret(32);
        $user->secret = $sec;
        $url = $googleAuthor->getQRCodeGoogleUrl($name, $sec);
        if (!$user->save()) {
            $this->ajaxError('操作异常');
        }
        $this->ajaxSuccess($url);
    }

    public function google_codeAction(){
        $id = $_POST['id'] ?? null;
        if (empty($id)){
            return $this->ajaxError('参数错误');
        }

        try {
            $admin = ManagersModel::find($id);
            if (empty($admin)){
                return $this->ajaxError('参数错误');
            }
            $admin->secret = $_POST['secret'] ?? '';
            $admin->saveOrFail();

            return $this->ajaxSuccessMsg('操作ok');
        }catch (\Throwable $e){
            return  $this->ajaxError($e->getMessage());
        }


    }
}