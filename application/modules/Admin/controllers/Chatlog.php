<?php

/**
 * Class ChatlogController
 * @author xiongba
 * @date 2021-03-11 20:57:18
 */
class ChatlogController extends BackendBaseController
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
            $item->from_avater_full = url_avatar($item->from_avater);
            $item->to_avater_full = url_avatar($item->to_avater);
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     * @author xiongba
     * @date 2021-03-11 20:57:18
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2021-03-11 20:57:18
     */
    protected function getModelClass(): string
    {
        return ChatLogModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2021-03-11 20:57:18
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

    public function banAction()
    {
        $uuid = $_POST['uuid'] ?? '';
        if (empty($uuid)) {
            return $this->ajaxError('无效用户');
        }
        $member = MemberModel::where('uuid', '=', $uuid)->first();
        if ($member) {
            $member->role_id = MemberModel::USER_ROLE_LEVEL_BANED;
            $member->save();
        }
        MemberModel::clearFor($member->toArray());
        return $this->ajaxError('操作成功');
    }
}