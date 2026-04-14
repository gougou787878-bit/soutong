<?php

use service\AppFeedSystemService;

/**
 * Class FeedbackController
 * @author xiongba
 * @date 2020-03-30 17:40:52
 */
class FeedbackController extends BackendBaseController
{
    /**
     * 列表数据过滤
     * @return Closure
     * @author xiongba
     * @date 2019-12-02 17:08:03
     */
    protected function listAjaxIteration()
    {
        return function (FeedbackModel $item) {
            if (!empty($item->thumb)) {
                $item->thumb = url_avatar(htmlentities($item->thumb));
            }

            $item->addtime_str = date('m月d-H:i', $item->addtime);
            $item->vip_level = 0;
            $member = $item->withMember;
            if ($member) {
                $item->user_nickname = trim($member->nickname);
                $item->user_phone = $member->phone ?? '';
                $item->user_uuid = $member->uuid;
                $item->vip_level = $member->vip_level;
            } else {
                $item->user_nickname = '';
                $item->user_phone = '';
                $item->user_uuid = '';
            }
            $item->reply = '';

            if ($item->withReply) {
                $item->reply = $this->mb_chunk_split($item->withReply->content, 30, '<br>');
                $item->replyat_str = date('m月d-H:i', $item->withReply->created_at);
            }
            return $item;
        };
    }

    public function batch_replyAction()
    {
        $ids = explode(',', $_POST['ids']);
        $content = trim($_POST['body'] );
        foreach ($ids as $fid) {
            $model = FeedbackModel::where('id', $fid)->first();
            if (empty($model)){
                continue;
            }
            FeedbackReplyModel::insert([
                'content'    => $content,
                'fid'        => $fid,
                // 'mid'        => $_SESSION['ADMIN_ID'] ?? 0,
                'created_at' => time()
            ]);
            $uid = $model->uid;
            $isOk = FeedbackModel::where('uid', $uid)
                ->where('status', FeedbackModel::STATUS_ING)
                ->update(['status' => FeedbackModel::STATUS_DONE]);
            if ($isOk) {
                async_task_cgi(function () use ($fid, $content, $model) {
                    (new AppFeedSystemService())->addFeed([
                        'uuid'      => $model->withMember->uuid,
                        'app_type'  => $model->withMember->oauth_type,
                        'aff'       => $model->withMember->aff,
                        'product'   => $model->platform,
                        'type'      => 1,
                        'nickname'  => $model->withMember->nickname,
                        'content'   => $content,
                        'version'   => $model->withMember->app_version,
                        'ip'        => USER_IP,
                        'vip_level' => MemberModel::USER_VIP_TYPE[$model->withMember->vip_level] ?? '普通人',
                        'status'    => 1,
                    ]);
                });

                //恢复会员
                $this->recoverVip($uid);
            }
        }
        $this->showJson('批量回复成功');
    }


    public function mb_chunk_split($string, $length = 76, $separator = "\r\n")
    {
        if (mb_strlen($string) <= $length) {
            return $string;
        }
        $len = mb_strlen($string);
        $size = ceil($len / $length);
        $ary = [];
        for ($i = 0; $i < $size; $i++) {
            $ary[] = mb_substr($string, 0, $length);
            $string = mb_substr($string, $length);
        }
        return join($separator, $ary);
    }

    protected function listTotalDescp(array $where, array $data)
    {
        $count = FeedbackModel::where($where)->count();
        return "总计：" . $count;
    }

    public function recoverVip($uid){
        //暂时停了
        //return;
        //查看用户是否被下掉会员
        /** @var MemberSnapshotModel $snapShot */
        $snapShot = MemberSnapshotModel::onWriteConnection()->where('uid', $uid)
            ->where('created_at','>','2024-11-05 00:00:00')
            ->first();
        //未恢复的
        if ($snapShot && $snapShot->status == 0){
            $data = @json_decode($snapShot->data, true);
            if ($data && is_array($data)) {
                $user = $data['user'];
                $free_member = $data['free_member'];
                $member = MemberModel::find($snapShot->uid);
                if ($member) {
                    if ($user['vip_level'] > $member->vip_level){
                        //恢复用户
                        $member->expired_at = max($user['expired_at'],$member->expired_at);
                        $member->vip_level = $user['vip_level'];
                        $member->save();
                        //恢复通卡设置
                        if ($free_member){
                            /** @var FreeMemberModel $freemember */
                            $freemember = FreeMemberModel::where('uid',$snapShot->uid)->first();
                            if ($freemember && $free_member['expired_at'] > TIMESTAMP){
                                $freemember->expired_at = max($freemember->expired_at,$free_member['expired_at']);
                                $freemember->type = max($freemember->type,$free_member['type']);
                                $freemember->save();
                            }else{
                                if ($free_member['expired_at'] > TIMESTAMP){
                                    FreeMemberModel::create([
                                        'uid' => $uid,
                                        'type' => $free_member['type'],
                                        'created_at' => time(),
                                        'expired_at' => $free_member['expired_at']
                                    ]);
                                }
                            }
                            //刷新通卡缓存
                            redis()->del(sprintf(FreeMemberModel::REDIS_FREE_DAY_TYPE, $uid, FreeMemberModel::FREE_DAY_MV));
                            redis()->del(sprintf(FreeMemberModel::REDIS_FREE_DAY_TYPE, $uid, FreeMemberModel::FREE_DAY_MV_ADD_COMMUNITY));
                        }
                        $snapShot->update(['status' => 1]);
                    }
                    MemberModel::clearFor($member->getAttributes());
                }
            }
        }
    }

    public function setstatusAction()
    {
        $id = $_POST['id'];
        if (empty($id)) {
            return $this->ajaxError('参数错误');
        }

        $model = FeedbackModel::where('id', $id)->first();
        if(!is_null($model)){
            (new AppFeedSystemService())->addFeed([
                'uuid'      => $model->withMember->uuid,
                'app_type'  => $model->withMember->oauth_type,
                'aff'       => $model->withMember->aff,
                'product'   => $model->platform,
                'type'      => 1,
                'nickname'  => $model->withMember->nickname,
                'content'   => '爸爸您好，已处理~',
                'version'   => $model->withMember->app_version,
                'ip'        => USER_IP,
                'vip_level' => MemberModel::USER_VIP_TYPE[$model->withMember->vip_level] ?? '普通人',
                'status'    => 1,
            ]);
        }
        $uid = $model->uid;
        FeedbackModel::where('uid', $uid)
            ->where('status', FeedbackModel::STATUS_ING)
            ->update(['status' => FeedbackModel::STATUS_DONE]);

        return $this->ajaxSuccessMsg('操作成功');
    }

    /**
     * 试图渲染
     * @return string
     * @author xiongba
     * @date 2020-03-30 17:40:52
     */
    public function indexAction()
    {
        $this->assign('coins_uint', setting('feedback_coins_uint', 2));
        $this->display();
    }

    public function listAjaxWhere()
    {
        return [
            //['status', '=', FeedbackModel::STATUS_ING]
        ];
    }


    protected function listAjaxOrder()
    {
        $orderBy['platform'] = 'desc';
        $orderBy['id'] = 'desc';
        return $orderBy;
    }
    public function getModelQuery()
    {
        $query = FeedbackModel::with('withMember')
            ->with('withReply')
            ->from('feedback')
            ->joinSub(
                FeedbackModel::groupBy('uid', 'status')->selectRaw('max(id) as _id'),
                'a',
                function (\Illuminate\Database\Query\JoinClause $joinClause) {
                    return $joinClause->on('feedback.id', 'a._id');
                }
            );
        return $query;
    }

    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2020-03-30 17:40:52
     */
    protected function getModelClass(): string
    {
        return FeedbackModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2020-03-30 17:40:52
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

    public function recoveryuserAction()
    {
        $uid = $this->post['uid'] ?? '0';
        /** @var MemberSnapshotModel $snapShot */
        $snapShot = MemberSnapshotModel::where('uid', $uid)->where('created_at','>','2024-10-01')->first();
        if (is_null($snapShot)) {
            return $this->ajaxError('没有快照记录，不能恢复vip');
        } elseif ($snapShot->status) {
            return $this->ajaxError('已经同步快照恢复vip');
        }
        $data = @json_decode($snapShot->data, true);
        if ($data && is_array($data)) {
            $user = $data['user'];
            $free_member = $data['free_member'];
            $member = MemberModel::find($snapShot->uid);
            if ($member) {
                if ($user['vip_level'] > $member->vip_level){
                    //恢复用户
                    $member->expired_at = max($user['expired_at'],$member->expired_at);
                    $member->vip_level = $user['vip_level'];
                    $member->save();
                    //恢复通卡设置
                    if ($free_member){
                        /** @var FreeMemberModel $freemember */
                        $freemember = FreeMemberModel::where('uid',$snapShot->uid)->first();
                        if ($freemember && $free_member['expired_at'] > TIMESTAMP){
                            $freemember->expired_at = max($freemember->expired_at,$free_member['expired_at']);
                            $freemember->type = max($freemember->type,$free_member['type']);
                            $freemember->save();
                        }else{
                            if ($free_member['expired_at'] > TIMESTAMP){
                                FreeMemberModel::create([
                                    'uid' => $uid,
                                    'type' => $free_member['type'],
                                    'created_at' => time(),
                                    'expired_at' => $free_member['expired_at']
                                ]);
                            }
                        }
                        //刷新通卡缓存
                        redis()->del(sprintf(FreeMemberModel::REDIS_FREE_DAY_TYPE, $uid, FreeMemberModel::FREE_DAY_MV));
                        redis()->del(sprintf(FreeMemberModel::REDIS_FREE_DAY_TYPE, $uid, FreeMemberModel::FREE_DAY_MV_ADD_COMMUNITY));
                    }
                    $snapShot->update(['status' => 1]);
                }
                MemberModel::clearFor($member->getAttributes());
                $this->fastAutoReplay($member->uid,'您好，会员已经恢复，重启app登录即可！');
                return $this->ajaxError('已经同步快照恢复vip');
            }
        }

        return $this->ajaxError('操作失败～，手动查看');
    }

    protected function fastAutoReplay($uid, $content)
    {
        FeedbackModel::where('uid', $uid)->update(['status' => FeedbackModel::STATUS_DONE]);
        /** @var FeedbackModel $model */
        $model = FeedbackModel::where('uid', $uid)->orderByDesc('id')->first();
        if (is_null($model)) {
            return;
        }
        FeedbackReplyModel::insert([
            'content'    => $content,
            'fid'        => $model->id,
            // 'mid'        => $_SESSION['ADMIN_ID'] ?? 0,
            'created_at' => time()
        ]);
        $fid = $model->id;
        async_task_cgi(function () use ($fid, $content, $model) {
            (new AppFeedSystemService())->addFeed([
                'uuid'      => $model->withMember->uuid,
                'app_type'  => $model->withMember->oauth_type,
                'aff'       => $model->withMember->aff,
                'product'   => $model->platform,
                'type'      => 1,
                'nickname'  => $model->withMember->nickname,
                'content'   => $content,
                'version'   => $model->withMember->app_version,
                'ip'        => USER_IP,
                'vip_level' => '普通人',
                'status'    => 1,
            ]);
        });
    }
}