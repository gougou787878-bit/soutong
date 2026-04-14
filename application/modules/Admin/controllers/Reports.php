<?php

use service\AppFeedSystemService;

/**
 * 主播
 * Class UserController
 */

class ReportsController extends AdminController
{
    function indexAction()
    {


        $query_link = 'd.php?mod=reports&code=index';
        $query = FeedbackModel::query()->offset($this->pageStart)->limit($this->perPageNum)->orderBy('addtime', 'desc');
        $status = $this->get['status'] ?? '0';
        $uid = $this->get['uid'] ?? '';
        $uuid = $this->get['uuid'] ?? '';
        $keyword = $this->get['keyword'] ?? '';
        $start = trim($this->get['start_time'] ?? date('Y-m-d'));
        $end = trim($this->get['end_time'] ?? date('Y-m-d'));

        $query_link .= '&status=' . $status;
        $get['status'] = $status;
        if ($status != '') {
            $query->where('status', $status);
        }
        $query_link .= '&start_time=' . $start;
        $get['start_time'] = $start;
        $start && $query->where('addtime', '>=', strtotime("{$start} 00:00:00"));

        $query_link .= '&end_time=' . $end;
        $get['end_time'] = $end;
        $end && $query->where('addtime', '<', strtotime("{$end} 23:59:59"));

        $query_link .= '&uid=' . $uid;
        $get['uid'] = $uid;
        $uid && $query->where('uid', $uid);

        $get['keyword'] = $keyword;
        $get['uuid'] = $uuid;
        if ($keyword) {
            $query_link .= '&keyword=' . $keyword;
            $query->with(['withReply', 'withMember' => function ($q) use ($get) {
                if (preg_match('/^\d{6,}/', $get['keyword'])) {
                    return $q->Where('phone', $get['keyword']);
                } else {
                    return $q->Where('nickname', 'like', $get['keyword']);
                }
            }]);
        }
        if ($uuid) {
            $query_link .= '&uuid=' . $uuid;
            $query->with(['withReply', 'withMember' => function ($q) use ($get) {
                return $q->Where('uuid', $get['uuid']);
            }]);
        }
        if (!$get['keyword'] && !$get['uuid']) {
            $query->with('withReply', 'withMember');
        }
        $query = $query->get();
        if (count($query) < $this->perPageNum) {
            $page_arr['html'] = sitePage($query_link, 1);
        } else {
            $page_arr['html'] = sitePage($query_link);
        }
        $this->getView()
            ->assign('data', $query)
            ->assign('page_arr', $page_arr)
            ->assign('formget', $get)
            ->assign('url', $this->Config->img->img_live_url)
            ->display('feedback/index_old.phtml');
    }

    function replayListAction()
    {
        $uid = $_REQUEST["uid"];
//        $list = DB::table("feedback")
//            ->leftJoin('feedback_reply', 'feedback.id', '=', 'fid')
//            ->where(function ($query) use ($uid) {
//                $query->where('feedback.uid', $uid);
//            })
//            ->select('feedback.*', 'feedback_reply.content as reply_content', 'created_at')
//            ->get()->toArray();

        $list = FeedbackModel::with('withReply')
            ->where('uid' , $uid)
            ->get()
            ->map(function (FeedbackModel $item) {
                $item->reply_content = '';
                if ($item->withReply) {
                    $item->reply_content = $item->withReply->content;
                    $item->created_at = $item->withReply->created_at;
                }
                return $item;
            })
            ->toArray();
        $this->getView()->assign('list', $list)->display('feedback/reply_lists.phtml');
    }

    function setstatusAction()
    {
        $id = intval($_GET['id']);
        if ($id) {
            $data['status'] = 1;
            $data['uptime'] = time();
            $result = singleton(FeedbackModel::class)->where("id", $id)->update($data);
            if ($result) {
                $action = "用户反馈标记处理：{$id}";
                // // setAdminLog($action);
                $this->showJson('标记成功');
            } else {
                $this->showJson('标记失败');
            }
        } else {
            $this->showJson('数据传入失败！');
        }
    }

    function delAction()
    {
        $id = intval($_GET['id']);
        if ($id) {
            $result = singleton(FeedbackModel::class)->delete($id);
            if ($result) {
                $action = "删除用户反馈：{$id}";
                // setAdminLog($action);
                $this->showJson('删除成功');
            } else {
                $this->showJson('删除失败');
            }
        } else {
            $this->showJson('数据传入失败！');
        }
    }

    function replyAction()
    {
        if ($_REQUEST['id']) {
            $where['id'] = $_REQUEST['id'];
            $data = FeedbackModel::query()->where($where)->first()->toArray();
            $this->getView()->assign('data', $data);
        }
        $this->getView()->display('feedback/reply.phtml');
    }

    /**
     * 消息推送
     */
    function reply_postAction()
    {
        // if (IS_POST) {
        $content = str_replace("\r", "", $_POST['content']);
        $content = str_replace("\n", "", $content);

        $touid = str_replace("\r", "", $_POST['touid']);
        $touid = str_replace("\n", "", $touid);
        $touid = preg_replace("/,|，/", ",", $touid);

        $fid = str_replace("\r", "", $_POST['fid']);
        $fid = str_replace("\n", "", $fid);

        $add = FeedbackReplyModel::insert([
            'content'    => $content,
            'fid'        => $fid,
            // 'mid'        => $_SESSION['ADMIN_ID'] ?? 0,
            'created_at' => time()
        ]);
        if ($add) {
            /** @var FeedbackModel $feed */
            $model = FeedbackModel::where('id',$fid)->first();
            if(!is_null($model)){
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

                //恢复会员
                $this->recoverVip($model->uid);
            }
            // setAdminLog($action);
            $this->showJson('回复成功');
        } else {
            $this->showJson('回复失败');
        }

    }

    public function recoverVip($uid){
        //查看用户是否被下掉会员
        /** @var MemberSnapshotModel $snapShot */
        $snapShot = MemberSnapshotModel::onWriteConnection()->where('uid', $uid)->where('created_at','>','2023-01-22')->first();
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

}
