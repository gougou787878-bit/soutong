<?php

/**
 * Class MvpayController
 * @author xiongba
 * @date 2020-05-11 19:54:44
 */
class MvpayController extends BackendBaseController
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
            /** @var MvPayModel $item */
            $item->coins = $item->coins / HT_JE_BEI;
            if ($item->mv){
                $item->mv->m3u8 = getAdminPlayM3u8($item->mv->m3u8);
            }
            return $item;
        };
    }


    public function refundAction()
    {
        $id = $_POST['id'];
        $id = explode(',', $id);
        $data = MvPayModel::where('is_refund', MvPayModel::IS_REFUND_NO)
            ->with('creator')
            ->with('user')
            ->whereIn('id', $id)
            ->get();

        $okId = [];

        foreach ($data as $item){
            try{
                DB::beginTransaction();
                $total = $item->coins;
                if ($item->creator){
                    $itOk = $item->creator->incrMustGE_raw(['score' => -$total]);
                    if (empty($itOk)) {
                        throw new \Exception("扣款失败,请确认{$item->mv_uid}的金币是否足够", 1008);
                    }
                    //记录日志
                    $itOk = \UserVoterecordModel::addExpend($item->mv_uid, 'mv_refund', $total);
                    if (empty($itOk)) {
                        throw new \Exception('记录日志失败');
                    }
                }

                if ($item->user){
                    $itOk = $item->user->increment('coins' , $total);
                    if (empty($itOk)) {
                        throw new \Exception('添加用户金币失败', 1008);
                    }
                    //记录日志
                    $itOk = \UsersCoinrecordModel::addIncome('mv_refund', $item->uid, $item->uid, $total, 0, 0, '客服退款：' . $total);
                    if (empty($itOk)) {
                        throw new \Exception('操作失败，请重试');
                    }
                }
                $item->is_refund = MvPayModel::IS_REFUND_YES;
                $item->save();
                DB::commit();
                $okId[] = $item->id;
            }catch (\Throwable $e){
                DB::rollBack();
                if (!empty($okId)){
                    AdminLogModel::addLog($this->getUser()->username  ,'mv_refund' , '视频退款,'.join(',' , $okId));
                }
               return $this->ajaxError($e->getMessage());
            }
        }
        if (!empty($okId)){
            AdminLogModel::addLog($this->getUser()->username  ,'mv_refund' , '视频退款,'.join(',' , $okId));
        }
        return $this->ajaxSuccessMsg('操作成功');
    }

    /**
     * 试图渲染
     * @return string
     * @author xiongba
     * @date 2020-05-11 19:54:44
     */
    public function indexAction()
    {
        $this->display();
    }

    protected function getModelQuery()
    {
        return MvPayModel::with(['mv', 'user']);
    }


    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2020-05-11 19:54:44
     */
    protected function getModelClass(): string
    {
       return MvPayModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2020-05-11 19:54:44
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

    public function totalAction()
    {
        $query = MvPayModel::query();
        $where = array_merge(
            $this->getSearchLikeParam(),
            $this->getSearchWhereParam(),
            $this->getSearchBetweenParam($query)
        );

        /** @var MvPayModel[] $all */
        $total_number = $query->where($where)->count('id');
        $total_gold = $query->where($where)->sum('coins');

        $data = [
            'total_number' => $total_number,
            'total_gold'   => number_format($total_gold, 2, '.', ''),
            'total_money'  => number_format($total_gold / 7.5, 2, '.', '')
        ];
        return $this->ajaxSuccess($data);
    }
}