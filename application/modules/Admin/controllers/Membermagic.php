<?php

/**
 * Class MembermagicController
 *
 * @date 2025-08-09 18:16:31
 */
class MembermagicController extends BackendBaseController
{
    /**
     * 列表数据过滤
     * @return Closure
     *
     * @date 2019-12-02 17:08:03
     */
    protected function listAjaxIteration()
    {
        return function (MemberMagicModel $item) {
            $item->status_str = MemberMagicModel::STATUS_TIPS[$item->status];
            $item->delete_str = MemberMagicModel::DELETE_TIPS[$item->is_delete];
            $item->thumb_wh_str = $item->thumb_w . '-' . $item->thumb_h;
            $item->cover_wh_str = $item->cover_width . '-' . $item->cover_height;
            $item->pay_type_str = MemberMagicModel::PAY_TYPE_TIPS[$item->pay_type];
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     *
     * @date 2025-08-09 18:16:31
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     *
     * @date 2025-08-09 18:16:31
     */
    protected function getModelClass(): string
    {
       return MemberMagicModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     *
     * @date 2025-08-09 18:16:31
     */
    protected function getPkName(): string
    {
        return 'id';
    }

    /**
     * 定义数据操作日志
     * @return string
     *
     * @date 2019-11-04 17:19:41
     */
    protected function getLogDesc(): string {
        // TODO: Implement getLogDesc() method.
        return '';
    }

    public function totalAction(){
        $where = array_merge(
            $this->getSearchPrefixLikeParam(),
            $this->getSearchLikeParam(),
            $this->getSearchWhereParam(),
            $this->getSearchBetweenParam()
        );
        $unlock = MemberMagicModel::selectRaw("count(id) as totalCount, SUM(coins) as totalCoins")->where($where)->first();
        $data = [
            'totalCount'     => $unlock->totalCount,
            'totalCoins'   => $unlock->totalCoins,
        ];
        return $this->ajaxSuccess($data);
    }

    public function retryAction()
    {
        try {
            $ids = $_POST['ids'] ?? 0;
            $ids = array_unique(array_filter(explode(",", $ids)));
            test_assert(count($ids), '请选择操作的记录');
            MemberMagicModel::whereIn('id', $ids)
                ->where('status', MemberMagicModel::STATUS_FAIL)
                ->increment('re_ct',1);
            MemberMagicModel::whereIn('id', $ids)
                ->where('status', '!=', MemberMagicModel::STATUS_SUCCESS)
                ->update(['status' => MemberMagicModel::STATUS_WAIT]);
            return $this->ajaxSuccessMsg('设置成功');
        } catch (Throwable $e) {
            return $this->ajaxError($e->getMessage());
        }
    }
}