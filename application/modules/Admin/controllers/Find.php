<?php

/**
 * Class FindController
 * @author xiongba
 * @date 2020-07-09 23:38:36
 */
class FindController extends BackendBaseController
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
            $item->load('member');
            $item->images = $item->getImagesAttribute();
            if ($item->vid) {
                $item->mv_title = $item->mv->title;
                $item->mv_pre_href = getAdminPlayM3u8($item->mv->m3u8);
            } else {
                $item->mv_title = '';
                $item->mv_pre_href = '';
            }
            return $item;
        };
    }


    public function refreshAction(){
        $id = $_POST['id'] ?? 0;
        FindModel::clearCache($id);
    }


    public function saveAction()
    {
        if ($_POST['gateway'] == 'status') {
            return $this->forward('status');
        } else {

            if (!$this->getRequest()->isPost()) {
                return $this->ajaxError('请求错误');
            }

            $postData = $this->postArray();

            // 处理上传的图片
            $imgList = [];
            if (!empty($postData['url_1'])) $imgList[] = $postData['url_1'];
            if (!empty($postData['url_2'])) $imgList[] = $postData['url_2'];
            if (!empty($postData['url_3'])) $imgList[] = $postData['url_3'];

            $id = $postData['_pk'] ?? 0;
            $data = [
                'uuid'        => $postData['uuid'],
                'title'       => $postData['title'],
                'img'         => json_encode($imgList, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'coins'       => (int) $postData['coins'],
                'total_coins' => (int) $postData['coins'],
                'status'      => $postData['status'] ?? FindModel::STAT_TO_CHECK,
                'vid'         => $postData['vid'] ?? 0,
                'is_top'      => $postData['is_top'] ?? 0,
                'is_match'    => $postData['is_match'] ?? FindModel::MACTH_DEFAULT,
                'is_finish'   => $postData['is_finish'] ?? FindModel::IS_FINISH_NO,
                'created_at'  => time(),
            ];

            if ($id > 0) {
                // 修改数据
                $find = FindModel::find($id);
                if (!$find) {
                    return $this->ajaxError('记录不存在');
                }
                $find->update($data);
                return $this->ajaxSuccessMsg('修改成功');
            } else {
                // 新增数据
                $newFind = FindModel::create($data);
                return $this->ajaxSuccessMsg('新增成功');
            }
         
        }
    }


    public function replyAction()
    {
        $find_id = data_get($_GET,'id');
        $detail = FindModel::with('withReply')->find($find_id);
        $this->assign('detail' , $detail);
        $this->display();
    }


    public function statusAction()
    {
        $id = $_POST['_pk'] ?? 0;
        $model = FindModel::find($id);
        if (empty($model)) {
            return $this->ajaxError('资源不存在');
        }
        $status = $_POST['status'] ?? FindModel::STAT_TO_CHECK;
        /*if ($model->status != FindModel::STAT_TO_CHECK) {
            return $this->ajaxError('状态不可更改');
        }*/

        try {
            DB::beginTransaction();
            if ($status == FindModel::STAT_REFUSE) {
                if ($model->status == FindModel::STAT_TO_CHECK && $model->is_back != FindModel::BACK_YES) {
                    $coins = $model->coins;
                    $uuid = $model->uuid;
                    $model->is_back = FindModel::BACK_YES;
                    if ($coins) {
                        $itOk = \MemberModel::where('uuid',$uuid)->update([
                            'coins'       => \DB::raw("coins + {$coins}"),
                            'consumption' => \DB::raw("consumption - {$coins}")
                        ]);
                        if (empty($itOk)) {
                            throw new \Exception('退回金币失败，请重试');
                        }
                        /** @var MemberModel $member */
                        $member = MemberModel::where('uuid',$uuid)->first();
                        $tips = "[求片赏金退回]{$model->title}#获得金币： $coins";
                        \UsersCoinrecordModel::addIncome('findMv', $member->uid, $member->uid, $coins, 0, 0, $tips);
                    }
                }
            }
            $model->status = $status;
            $model->created_at = TIMESTAMP;
            $itOk = $model->save();
            if (empty($itOk)) {
                throw new \Exception('操作失败');
            }
            FindModel::clearCache($model->id);
            DB::commit();
            return $this->ajaxSuccessMsg('操作成功 ');
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->ajaxError($e->getMessage());
        }

    }

    public function listAjaxWhere()
    {
        $isTimeout = data_get($_GET, 'timeout', 0);
        if ($isTimeout) {
            //赏金超过了50金币
            return [
                ['created_at', '<', TIMESTAMP - 86400 * 2],
                ['is_match', '=', FindModel::MACTH_DEFAULT],
//                ['coins', '>=', 50],
                ['is_finish', '=', FindModel::IS_FINISH_YES],
            ];
        }
        return [];
    }


    public function matchAction()
    {
        $id = data_get($_POST, 'id');
        $flag = data_get($_POST, 'flag');
        if (empty($id) || empty($flag)) {
            return $this->ajaxError('参数错误');
        }
        $model = FindModel::find($id);
        if (empty($model)) {
            return $this->ajaxError('资源不存在');
        }
        try {
            if ($flag == 'no') {
                //不采纳
                $coins = $model->coins;
                $uuid = $model->uuid;
                //alter table tbr_find add is_finish tinyint default 0 not null comment '是否完成';
                if ($coins) {
                    $itOk = \MemberModel::where('uuid',$uuid)->update([
                        'coins'       => \DB::raw("coins + {$coins}"),
                        'consumption' => \DB::raw("consumption - {$coins}")
                    ]);
                    if (empty($itOk)) {
                        throw new \Exception('退回金币失败，请重试');
                    }
                    /** @var MemberModel $member */
                    $member = MemberModel::where('uuid',$uuid)->first();
                    $tips = "[求片赏金退回]{$model->title}#获得金币： $coins";
                    \UsersCoinrecordModel::addIncome('findMv', $member->uid, $member->uid, $coins, 0, 0, $tips);

                    $model->is_back = FindModel::BACK_YES;
                }
            } else {
                //恶意不采纳
            }

            $model->is_finish = FindModel::IS_FINISH_YES;
            $itOk = $model->save();
            if (empty($itOk)) {
                throw new \Exception('返回金额失败');
            }

            return $this->ajaxSuccessMsg("操作成功");
        } catch (\Throwable $e) {
            return $this->ajaxError($e->getMessage());
        }


    }

    function _saveActionAfter()
    {
        //FindModel::clearCache(0 , true);
    }


    /**
     * 赏金高于50金币的求片需求。若推荐者视频不满足自己的需求，
     * 可选择不采纳，48小时候求片帖子失效，由平台审核后确认是否恶意不采纳，
     * 审核通过返回赏金给发布者，审核失败（恶意不采纳）则不返回赏金
     */
    public function timeoutAction()
    {
        $this->display();
    }


    public function clear_allAction()
    {
        $uuid = $_POST['uuid'] ?? '';
        if (empty($uuid)) {
            return $this->ajaxError('参数错误哦');
        }
        FindModel::where('uuid', $uuid)->each(function (FindModel $item){
            FindReplyModel::where('find_id' , $item->id)->delete();
            FindReplyCommentModel::where('find_id' , $item->id)->delete();
            $item->delete();
        });
        return $this->ajaxSuccessMsg('操作成功');
    }

    /**
     * 试图渲染
     * @return string
     * @date 2020-07-09 23:38:36
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     * @date 2020-07-09 23:38:36
     */
    protected function getModelClass(): string
    {
        return FindModel::class;
    }

    protected function getModelObject()
    {
        return FindModel::with('member');
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @date 2020-07-09 23:38:36
     */
    protected function getPkName(): string
    {
        return 'id';
    }

    /**
     * 定义数据操作日志
     * @return string
     * @date 2019-11-04 17:19:41
     */
    protected function getLogDesc(): string
    {
        return '';
    }
}