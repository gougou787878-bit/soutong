<?php

/**
 * Class FindreplyController
 * @author xiongba
 * @date 2020-07-10 16:05:18
 */
class FindreplyController extends BackendBaseController
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
            /** @var FindReplyModel $item */
            $item->mvIds = $item->getMvAry()->map(function ($item){
                return $item->id;
            });
            $item->load('member');
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     * @author xiongba
     * @date 2020-07-10 16:05:18
     */
    public function indexAction()
    {
        $this->display();
    }



    public function acceptAction()
    {   
        try {
            $request = $this->getRequest();
            
            // 获取 POST 数据
            $id = $request->getPost('id', 0);
            $isAccept = FindReplyModel::IS_ACCEPT_YES;
    
            // 先检查 `replyObject` 是否存在
            $replyObject = FindReplyModel::find($id);
            if (empty($replyObject)) {
                return $this->ajaxError('回复资源不存在');
            }
    
            // 检查 `findObject`
            $findObject = FindModel::find($replyObject->find_id);
            if (empty($findObject)) {
                return $this->ajaxError('求片资源不存在');
            }

            if ($findObject->is_match == FindModel::MACTH_YES) {
                return $this->ajaxError('赏金已经分配，无法采纳');
            }
    
    
            DB::beginTransaction();
            
            $today = (int) date('Ymd');
            
            $rankQuery = \FindMemberRankModel::where('uuid', $replyObject->uuid)->where('day', $today);
            $exists = $rankQuery->exists();
    
            if ($exists) {
                if ($isAccept == FindReplyModel::IS_ACCEPT_YES) {
                    $rankQuery->increment('receive');
                }
            } else {
                \FindMemberRankModel::insert([
                    'uuid' => $replyObject->uuid,
                    'day' => $today,
                    'receive' => 1
                ]);
            }
    
            // 更新 `is_accept`
            $replyObject->is_accept = $isAccept;
            if (!$replyObject->save()) {
                DB::rollBack();
                return $this->ajaxError('采纳操作失败');
            }

            // 更新 `is_match`
            $findObject->is_match = FindModel::MACTH_YES;
            if (!$findObject->save()) {
                DB::rollBack();
                return $this->ajaxError('采纳操作失败');
            }
    
            // 处理奖励
            $coins = $findObject->coins;
            $uuid = $replyObject->uuid;
    
            if ($coins > 0) {
                $toMember = \MemberModel::where('uuid', $uuid)->first();
                if (!$toMember) {
                    DB::rollBack();
                    return $this->ajaxError("用户信息不存在，无法发放奖励");
                }
                $toMember->increment("score", $coins);
                $toMember->increment("score_total", $coins);
    
                $tips = "[回复求片被采纳]# 获取收益： $coins";
                \UsersCoinrecordModel::addIncome('findReply', $toMember->uid, $toMember->uid, $coins, 0, 0, $tips);
    
                // 更新 `is_back`
                $findObject->is_back = FindModel::BACK_YES;
                $findObject->save();
            }
    
            DB::commit();
            return $this->ajaxSuccessMsg("操作成功");
    
        } catch (Exception $e) {
            DB::rollBack();
            return $this->ajaxError($e->getMessage());
        }
    }
    
    

    public function statusAction()
    {
        $id = $_POST['_pk'] ?? 0;
        $model = FindReplyModel::find($id);
        if (empty($model)) {
            return $this->ajaxError('资源不存在');
        }
     
        $status = $_POST['status'] ?? FindReplyModel::STATUS_INIT;
        if ($model->status != FindReplyModel::STATUS_INIT) {
            return $this->ajaxError('状态不可更改');
        }

        try {
            DB::beginTransaction();
            if ($status == FindReplyModel::STATUS_REJECT) {
                $itOk = FindModel::find($model->find_id)->decrement('reply');
                if (empty($itOk)) {
                    throw new \Exception('操作失败');
                }
            }
            $model->status = $status;
            $itOk = $model->save();
            if (empty($itOk)) {
                throw new \Exception('操作失败');
            }
            DB::commit();
            return $this->ajaxSuccessMsg('操作成功 ');
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->ajaxError($e->getMessage());
        }

    }

    protected function getModelObject()
    {
        return FindReplyModel::with('member');
    }


    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2020-07-10 16:05:18
     */
    protected function getModelClass(): string
    {
        return FindReplyModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2020-07-10 16:05:18
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
        return '';
    }
}