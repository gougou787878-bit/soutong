<?php

/**
 * Class MembermakerController
 * @author xiongba
 * @date 2021-01-09 16:25:49
 */
class MembermakerController extends BackendBaseController
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
        return MemberMakerModel::class;
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
     * 取消传作者身份认证
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

        /** @var MemberMakerModel $memberCreator */
        $memberCreator = MemberMakerModel::where('id', $id)->first();
        $member = $memberCreator->member;
        try {
            DB::beginTransaction();
            $memberCreator->status = MemberMakerModel::CREATOR_STAT_NO;
            $memberCreator->refuse_reason = $refuse;
            $memberCreator->nickname = $member->nickname;
            $memberCreator->save();
            MemberModel::where(['uuid' => $memberCreator->uuid])->update(['auth_status' => MemberModel::AUTH_STATUS_NO]);
            DB::commit();
            MemberModel::clearFor($member);
            //清除用户缓存
            AdminLogModel::addUpdate($this->getUser()->username,
                sprintf("取消了{%s}的创作者身份，拒绝原因{%s}", $member['nickname'], $refuse));
            MessageModel::createSystemMessage($member->uuid, MessageModel::SYSTEM_MSG_TPL_CREATOR_CANCEL, ['reason' => $refuse]);
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
        /** @var MemberMakerModel $memberCreator */
        $memberCreator = MemberMakerModel::where('id', $id)->first();
        if ($memberCreator->status != MemberMakerModel::CREATOR_STAT_ING) {
            return $this->ajaxError("当前状态不允许修改");
        }
        try {
            DB::beginTransaction();
            $memberCreator->status = MemberMakerModel::CREATOR_STAT_NO;
            $memberCreator->refuse_reason = $refuse;
            $memberCreator->nickname = $memberCreator->member->nickname;
            $memberCreator->save();
            DB::commit();
            //清除用户缓存
            AdminLogModel::addUpdate($this->getUser()->username,
                sprintf("拒绝了{%s}的创作者申请，拒绝原因{%s}", $memberCreator->member->nickname, $refuse));
            MessageModel::createSystemMessage($memberCreator->uuid, MessageModel::SYSTEM_MSG_TPL_CREATOR_APPLY_FAIL, ['reason' => $refuse]);
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
        $memberCreator = MemberMakerModel::where('id', $id)->first();
        if ($memberCreator->status != MemberMakerModel::CREATOR_STAT_ING) {
            return $this->ajaxError("当前状态不允许修改");
        }
        try {
            $memberCreator->status = MemberMakerModel::CREATOR_STAT_YES;
            $memberCreator->save();
            MemberModel::where(['uuid' => $memberCreator->uuid])->update(['auth_status' => MemberModel::AUTH_STATUS_YES]);
            DB::commit();
            MemberModel::clearFor($memberCreator->member);
        } catch (\Throwable $e) {
            return $this->ajaxError($e->getMessage());
        }
        AdminLogModel::addUpdate($this->getUser()->username,
            sprintf("审核通过了{%s}的创作者申请", $memberCreator->member->nickname));
        return $this->ajaxSuccess('操作成功');
    }
}