<?php

/**
 * Class MembermakerController
 * @author xiongba
 * @date 2021-01-09 16:25:49
 */
class MemberauthController extends BackendBaseController
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
            $item->status_str =  MemberAuthModel::AUTH_STATUS_OPT[$item->status];
            $item->type_str =  MemberAuthModel::AUTH_type_OPT[$item->type];
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     * @author xiongba
     * @date 2021-01-09 16:25:49
     */
    public function indexAction()
    {
        $this->display();
    }

    /**
     * 试图渲染
     * @return string
     * @author xiongba
     * @date 2019-12-30 15:59:05
     */
    public function checkAction()
    {
        $this->display();
    }

    public function allAction()
    {
        $this->display();
    }

    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2021-01-09 16:25:49
     */
    protected function getModelClass(): string
    {
        return MemberAuthModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2021-01-09 16:25:49
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
    protected function getLogDesc(): string
    {
        // TODO: Implement getLogDesc() method.
        return '';
    }

    /**
     * 取消认证
     * @return bool
     * @author xiongba
     * @date 2019-12-30 18:59:28
     */
    public function doCancelAction()
    {
        if (!request()->isPost()) {
            return $this->ajaxError('请求失败');
        }

        $id = request()->getPost('id');
        $refuse = request()->getPost('refuse');

        if (empty($id) || empty($refuse)) {
            return $this->ajaxError('参数错误');
        }

        /** @var MemberAuthModel $memberAuth */
        $memberAuth= MemberAuthModel::where('id', $id)->first();
        $member = $memberAuth->member;
        try {
            DB::beginTransaction();
            $memberAuth->status = MemberAuthModel::AUTH_STAT_NO;
            $memberAuth->refuse_reason = $refuse;
            $memberAuth->nickname = $member->nickname;
            $memberAuth->save();
            if($memberAuth->type == 1){
                $updateData = ['girl_auth'=>MemberModel::AUTH_STATUS_NO];
                $msg = '约炮认证';
            }else{
                $updateData = ['post_auth'=>MemberModel::AUTH_STATUS_NO];
                $msg = '原创认证';
            }
            MemberModel::where(['uuid' => $memberAuth->uuid])->update($updateData);
            DB::commit();
            MemberModel::clearFor($member);
            //清除用户缓存
            AdminLogModel::addUpdate($this->getUser()->username,
                sprintf("取消了{%s}的{%s}，拒绝原因{%s}", $member['nickname'],$msg, $refuse));
            MessageModel::createSystemMessage($member->uuid, MessageModel::SYSTEM_MSG_AUTH_CANCEL, ['msg'=>$msg,'reason' => $refuse]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->ajaxError($e->getMessage());
        }

        return $this->ajaxSuccess('操作成功');
    }

    /**
     * 拒绝通过
     * @return bool
     * @author xiongba
     * @date 2019-12-30 18:59:28
     */
    public function doRefuseAction()
    {
        if (!request()->isPost()) {
            return $this->ajaxError('请求失败');
        }

        $id = request()->getPost('id');
        $refuse = request()->getPost('refuse');

        if (empty($id) || empty($refuse)) {
            return $this->ajaxError('参数错误');
        }
        /** @var MemberAuthModel $memberAuth */
        $memberAuth = MemberAuthModel::where('id', $id)->first();
        if ($memberAuth->status != MemberAuthModel::AUTH_STAT_ING) {
            return $this->ajaxError("当前状态不允许修改");
        }
        try {
            DB::beginTransaction();
            $memberAuth->status = MemberAuthModel::AUTH_STAT_NO;
            $memberAuth->refuse_reason = $refuse;
            $memberAuth->nickname = $memberAuth->member->nickname;
            $memberAuth->save();
            if($memberAuth->type == 1){
                $msg = '约炮认证';
            }else{
                $msg = '原创认证';
            }
            DB::commit();
            //清除用户缓存
            AdminLogModel::addUpdate($this->getUser()->username,
                sprintf("拒绝了{%s}的{%s}，拒绝原因{%s}", $memberAuth->member->nickname,$msg, $refuse));
            MessageModel::createSystemMessage($memberAuth->uuid, MessageModel::SYSTEM_MSG_TPL_AUTH_APPLY_FAIL, ['msg'=>$msg,'reason' => $refuse]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->ajaxError($e->getMessage());
        }


        return $this->ajaxSuccess('操作成功');
    }


    /**
     * 通过审核
     * @return bool
     * @author xiongba
     * @date 2019-12-30 18:59:20
     */
    public function doPassAction()
    {
        if (!request()->isPost()) {
            return $this->ajaxError('请求失败');
        }

        $id = request()->getPost('id');

        if (empty($id)) {
            return $this->ajaxError('参数错误');
        }
        /** @var MemberAuthModel $memberAuth */
        $memberAuth = MemberAuthModel::where('id', $id)->first();
        if ($memberAuth->status != MemberAuthModel::AUTH_STAT_ING) {
            return $this->ajaxError("当前状态不允许修改");
        }
        try {
            DB::beginTransaction();
            $memberAuth->status = MemberAuthModel::AUTH_STAT_YES;
            $memberAuth->save();

            if($memberAuth->type == 1){
                $updateData = ['girl_auth'=>MemberModel::AUTH_STATUS_YES];
                $msg = '约炮认证';
            }else{
                $updateData = ['post_auth'=>MemberModel::AUTH_STATUS_YES];
                $msg = '原创认证';
            }
            MemberModel::where(['uuid' => $memberAuth->uuid])->update($updateData);
            DB::commit();
            MemberModel::clearFor($memberAuth->member);
        } catch (\Throwable $e) {
            return $this->ajaxError($e->getMessage());
        }
        AdminLogModel::addUpdate($this->getUser()->username,
            sprintf("审核通过了{%s}的{%s}", $memberAuth->member->nickname,$msg));
        return $this->ajaxSuccess('操作成功');
    }
}