<?php

/**
 * Class MembertalktimeController
 * @author xiongba
 * @date 2021-06-26 17:29:30
 */
class MembertalktimeController extends BackendBaseController
{
    /**
     * 列表数据过滤
     * @return Closure
     * @author xiongba
     * @date 2019-12-02 17:08:03
     */
    protected function listAjaxIteration()
    {
        return function (MemberTalkTimeModel $item) {
            $item->setHidden([]);
            return $item;
        };
    }

    public function add_timeAction()
    {
        $value = intval($_POST['val'] ?? 0);
        $uid = intval($_POST['uid'] ?? 0);

        if (empty($value) || empty($uid)) {
            return $this->ajaxError('参数错误');
        }
        $talk = MemberTalkModel::find($uid);
        if (empty($talk)) {
            return $this->ajaxError('数据不存在');
        }
        $talk->expired_at = max(time(), $talk->expired_at) + $value * 3600;
        $talk->save();
        return $this->ajaxSuccessMsg('操作成功');
    }

    /**
     * 试图渲染
     * @return string
     * @author xiongba
     * @date 2021-06-26 17:29:30
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2021-06-26 17:29:30
     */
    protected function getModelClass(): string
    {
        return MemberTalkTimeModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2021-06-26 17:29:30
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
}