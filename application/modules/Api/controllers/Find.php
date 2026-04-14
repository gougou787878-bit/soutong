<?php

use helper\QueryHelper;
use helper\Validator;
use \service\FindThingService;
use \service\AdService;

/**
 * 求片 逻辑控制处理
 *
 * Class FindController
 */
class FindController extends BaseController
{
    //求片首页配置数据
    public function homeAction()
    {
        $ads = AdService::getADsByPosition(AdsModel::POS_FIND_BANNER);
        $tab = [
            [
                'current' => false,
                'id'      => 1,
                'name'    => '最新',
                'type'    => 'list',
                'api'     => 'api/find/list',
                'params'  => ['type' => "new"],
            ],
            [
                'current' => true,
                'id'      => 2,
                'name'    => '精华',
                'type'    => 'list',
                'api'     => 'api/find/list',
                'params'  => ['type' => "hot"],
            ],
            [
                'current' => false,
                'id'      => 3,
                'name'    => '榜单',
                'type'    => 'list',
                'api'     => 'api/find/rank',
                'params'  => null,
            ],
        ];

        return $this->showJson(['ads' => $ads, 'tab' => $tab,]);
    }

    /**
     * 发布求片需求
     */
    public function createAction()
    {
        try {
            $data = $this->post;
            $title = htmlspecialchars($data['title']);
            $images = $data['images'] ?? [];
            $coins = (int)($data['coins'] ?? 0);
            $vid = (int)($data['vid'] ?? 0);

            if (mb_strlen($title) < 10) {
                return $this->errorJson('详细的描述更方便找片,不少于10个字哟~');
            }

            $ary = explode(',', setting('global:filter:keyword', '幼女,幼幼'));
            foreach ($ary as $item) {
                if (strpos($title, $item) !== false) {
                    //关键字过滤
                    return $this->errorJson('您提交的内容涉嫌违规~');
                }
            }

            $member = request()->getMember();
            if ($member->isBan()) {
                return $this->errorJson('违规发布广告,已被禁言~');
            }

            $findModel = FindModel::where('uuid', $member->uuid)->orderByDesc('created_at')->first();
            /** @var \FindModel $findModel */
            if ($findModel && $findModel->created_at > TIMESTAMP - 600) {
                return $this->errorJson('两次求片的时间间隔太短');
            }

            if ($vid) {
                /** @var MvModel $mv */
                $mv = MvModel::queryBase()->where('id', $vid)->first();
                if (is_null($mv)) {
                    return $this->errorJson('该片已违规下架');
                } elseif ($mv->uid == $member->uid) {
                    return $this->errorJson('自己不能引用自己的求片~');
                }
            }

            //仅会员用户可选择免费或赏金发布求片
            if ($member->isFeeMonthVip()) {
                if ($coins){
                    if ($member->coins < $coins) {
                        return $this->errorJson('金币余额不足,请充值~');
                    }
                    $_t = FindModel::MIN_FIND_COINS;
                    if ($_t > $coins) {
                        return $this->errorJson("设置免费求片或不小于{$_t}金币~");
                    }
                }
            } else {
                //return $this->errorJson('仅充值会员用户发布求片信息~');
            }
            $service = new FindThingService();
            $service->createFind($member->refresh(), $title, $images, $coins,$vid);
            $this->successMsg('求片已经提交，进入审核队列！');
        }catch (Throwable $e){
            $this->errorJson($e->getMessage());
        }
    }

    /**
     * 求完整视频 没有 就 创建  ；有就到详细
     */
    public function checkFindAction()
    {
        try {
            $validator = Validator::make($this->post, [
                'mv_id' => 'required|numeric|min:1',//视频ID
            ]);
            if ($validator->fail($msg)) {
                return $this->errorJson($msg);
            }
            $vid = (int)($this->post['mv_id']);
            /** @var FindModel $row */
            $row = FindModel::queryAll()->where('vid', '=', $vid)->first();
            $return =[
                'find_id'        => 0,
                'status'         => 0,
                'forward_create' => 1,
                'tips'           => '发布求片~',
            ];
            if ($row) {
                $return = [
                    'find_id'        => $row->id,
                    'status'         => $row->status,
                    'forward_create' => $row->status ? 0 : 2,
                    'tips'           => $row->status ? '查看该视频的完整求片回复' : '该视频的完整求片正在审核,请耐心等待~',
                ];
            }
            return $this->showJson($return);
        } catch (\Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }


    /**
     * 举报
     * @return bool
     * @author xiongba
     * @date 2019-12-16 15:42:24
     */
    public function post_reportAction()
    {
        try {
            $validator = Validator::make($this->post, [
                'find_reply_id' => 'required|numeric|min:1',
                'content'       => 'required',
            ]);
            if ($validator->fail($msg)) {
                return $this->errorJson($msg);
            }
            $id = $this->post['find_reply_id'];
            $content = $this->post['content'];
            $reply = FindReplyModel::find($id);
            test_assert($reply,"回复不存在");
            FindReplyReportModel::createBy($reply->find_id, $reply->id, $content, request()->getMember()->uuid);
            return $this->successMsg('我们已收到您的举报,客服将在稍后核实');
        }catch (Throwable $e){
            return $this->errorJson($e->getMessage());
        }
    }
    /**
     * 举报类型
     * @return bool
     * @author xiongba
     * @date 2019-12-16 15:42:24
     */
    public function post_reportTypeAction()
    {
        $reportString = setting('find.report.type', '广告#未成年#强奸/迷奸/偷拍');
        $data = explode('#', $reportString);
        return $this->showJson([
            'list' => $data
        ]);
    }


    /**
     * 发布求片的详细  回复详情
     */
    public function detailAction()
    {
        try {
            $validator = Validator::make($this->post, [
                'find_id' => 'required|numeric|min:1',
            ]);
            if ($validator->fail($msg)) {
                return $this->errorJson($msg);
            }
            $find_id = (int)$this->post['find_id'];
            $service = new FindThingService();
            $row = $service->getFindRow($find_id);
            test_assert($row,'查无求片信息~');
            $member = request()->getMember();
            $row->is_like = FindThingService::getIsLook($member->uuid,$find_id);

            $appendDetailList = $service->getAppendCoinList($row->id);
            $total_info = $service->getAppendCoinTotal($row->id);
            $return = [];

            $return['detail'] = $row;
            $return['append_list'] = collect($appendDetailList)->slice(0 , 4)->toArray();
            $return['append_ct'] = (int)$total_info['ct'];
            $return['append_sum'] = (int)$total_info['sum'];
            $return['count_down'] = ['expire_at' => $row->created_at + FindModel::REPLY_MAX_TTL, 'now'=> TIMESTAMP];
            //远程广告
            $return['ads'] = AdService::getADsByPosition(AdsModel::POSITION_FIND_DETAIL);
//            $expireInfo = FindAppendModel::getFirstFindAppend($row->id);
//            //倒计时
//            if (is_null($expireInfo)){
//                $return['count_down'] = ['expire_at' => $row->created_at + 96 * 3600, 'now'=> TIMESTAMP];
//            }else{
//                $return['count_down'] = $expireInfo->findExpiredInfo();
//            }

            return $this->showJson($return);
        }catch (Throwable $e){
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 根据求片编号 获取回复列表数据 ；按用户 ；采纳的优先置顶
     */
    public function replyByFindAction(){
        try {
            $validator = Validator::make($this->post, [
                'find_id' => 'required|numeric|min:1',
            ]);
            if ($validator->fail($msg)) {
                return $this->errorJson($msg);
            }
            $find_id = $this->post['find_id'];
            $service = new FindThingService();
            $list = $service->getReplyByFind(request()->getMember(), $find_id);
            return $this->showJson($list);
        }catch (Throwable $e){
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 推片评论列表
     */
    public function commentByReplyAction()
    {
        try {
            $validator = Validator::make($this->post, [
                'reply_id' => 'required|numeric|min:1',
            ]);
            if ($validator->fail($msg)) {
                return $this->errorJson($msg);
            }
            $member = request()->getMember();
            $reply_id = $this->post['reply_id'];
            list($page,$limit) = QueryHelper::pageLimit();
            $service = new FindThingService();
            $list = $service->getReplyByReplyList($member,$reply_id,$page,$limit);
            return $this->showJson($list);
        }catch (Throwable $e){
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 想看发布求片 每人天限1次
     */
    public function wantLookAction()
    {
        try {
            $validator = Validator::make($this->post, [
                'find_id' => 'required|numeric|min:1',
            ]);
            if ($validator->fail($msg)) {
                return $this->errorJson($msg);
            }
            $member = request()->getMember();
            $find_id = $this->post['find_id'];
            $service = new FindThingService();
            $flag = $service->wantLookFind($member,$find_id);
            $msg = $flag ? '处理成功' : "已经发表想看了";
            return $this->successMsg($msg);
        }catch (Throwable $e){
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 个人中心 我的想看
     */
    public function myLookAction(){
        $member = request()->getMember();
        $service = new FindThingService();
        $return = $service->wantLookFindList($member);
        return $this->showJson($return);
    }

    /**
     * 追加赏金
     */
    public function appendCoinsAction()
    {
        try {
            $validator = Validator::make($this->post, [
                'coins' => 'required|numeric|min:1',
                'find_id' => 'required|numeric|min:1',
            ]);
            if ($validator->fail($msg)) {
                return $this->errorJson($msg);
            }
            $coins = abs((int)$this->post['coins']);
            $find_id = $this->post['find_id'] ?? 0;
            $member = request()->getMember();
            if ($member->isBan()){
                return $this->errorJson('您已被禁言');
            }
            $service = new FindThingService();
            $service->appendCoinFind($member, $find_id, $coins);
            return $this->successMsg('追加赏金成功');
        }catch (Throwable $e){
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 对品论打赏
     * @return bool
     */
    public function rewardAction()
    {
        try {
            $validator = \helper\Validator::make($this->post, [
                'reply_id' => 'required|numeric',
                'coins'    => 'required|numeric|min:1|max:100',
            ]);
            if ($validator->fail($msg)) {
                return $this->errorJson($msg);
            }
            $reply_id = $this->post['reply_id'];
            $coins = $this->post['coins'];
            $member = request()->getMember();
            if ($member->isBan()) {
                return $this->errorJson('账号违规,不能打赏');
            }
            $service = new FindThingService();
            $service->reward($member, $reply_id, $coins);
            return $this->successMsg("打赏成功");
        } catch (\Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 追加赏金列表详细
     */
    public function appendDetailAction()
    {
        try {
            $validator = \helper\Validator::make($this->post, [
                'find_id' => 'required|numeric',
            ]);
            if ($validator->fail($msg)) {
                return $this->errorJson($msg);
            }
            $find_id = $this->post['find_id'];
            $service = new FindThingService();
            $findModel = $service->getFindRow($find_id);
            test_assert($findModel,'查无求片记录');
            $rs = $service->getAppendCoinList($find_id);
            $return = [
                'title' => '共追加了#金币',
                'total' => $findModel->total_coins,
                'list'  => $rs
            ];
            return $this->showJson($return);
        } catch (\Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }


    /**
     * 我要推荐 回复求片
     */
    public function replyAction()
    {
        try {
            $validator = \helper\Validator::make($this->post, [
                'find_id' => 'required',
                'vid'     => 'required',
            ]);
            if ($validator->fail($msg)) {
                return $this->errorJson($msg);
            }
            $member = request()->getMember();
            $find_id = $this->post['find_id'];
            $vid = $this->post['vid'];
            $vid = explode(',', $vid);
            $service = new FindThingService();
            $service->replyFind($find_id,$vid,$member);
            return $this->successMsg('操作成功');
        } catch (\Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 对找片的回复进行点赞
     */
    public function praiseReplyAction()
    {
        try {
            $validator = \helper\Validator::make($this->post, [
                'reply_id' => 'required',
                'type'     => 'enum:unset,set',
            ]);
            if ($validator->fail($msg)) {
                return $this->errorJson($msg);
            }
            $reply_id = $this->post['reply_id'];
            $type = $this->post['type'];
            $member = request()->getMember();
            $service = new FindThingService();
            $service->praiseReply($member,$reply_id,$type);
            return $this->successMsg('点赞成功');
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 对找片的回复进行评论
     */
    public function commentReplyAction()
    {
        try {
            $validator = \helper\Validator::make($this->post, [
                'reply_id' => 'required',
                'comment'  => 'required',
            ]);
            if ($validator->fail($msg)) {
                return $this->errorJson($msg);
            }
            $member = request()->getMember();
            if ($member->is_vip) {
                //return $this->errorJson('仅允许会员用户发布评论信息~');
            }
            $reply_id = $this->post['reply_id'];
            $comment = $this->post['comment'];
            $to_uuid = $this->post['to_uuid'] ?? null;
            $service = new FindThingService();
            $service->commentReply($member,$reply_id,$comment,$to_uuid);
            return $this->successMsg('操作成功');
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 对找片的回复的评论进行点赞
     */
    public function praiseCommentReplyAction()
    {
        try {
            $validator = \helper\Validator::make($this->post, [
                'comment_id' => 'required',
                'type'     => 'enum:unset,set',
            ]);
            if ($validator->fail($msg)) {
                return $this->errorJson($msg);
            }
            $comment_id = $this->post['comment_id'];
            $type = $this->post['type'];
            $member = request()->getMember();
            $service = new FindThingService();
            $service->praiseComment($member,$comment_id,$type);
            return $this->successMsg('操作成功');
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }


    /**
     * 作品库
     * @author xiongba
     * @date 2020-07-11 13:54:33
     */
    public function mvListAction()
    {
        try {
            $validator = \helper\Validator::make($this->post, [
                'type' => 'enum:all,my',
            ]);
            if ($validator->fail($msg)) {
                return $this->errorJson($msg);
            }
            $kwy = $this->post['kwy'] ?? '';
            $type = $this->post['type'];
            $member = request()->getMember();
            $uid =  $member->uid;
            $mvIds = [];
            if (!empty($mvIds)) {
                //$query->whereIn('id', collect($mvIds)->slice(0,300)->toArray());
            }
            list($page, $limit) = \helper\QueryHelper::pageLimit();
            $key = "find:mv:work:list:%d:%d";
            $key = sprintf($key,$page,$limit);
            if ($type == 'my'){
                $key .= ":".$uid;
            }
            if (!empty($kwy)) {
                if (mb_strlen($kwy)<2) {
                    return $this->errorJson('至少两位搜索关键字');
                }
//                $kwy = strip_tags($kwy);
//                $kwys = fenKeywords($kwy);
//                $mvIds = MvWordsModel::getVidByWords($kwys);
                $key .= ":".substr(md5($kwy),-1,6);
            }
            if ($type == 'all' && !empty($kwy)){
                $result = (new \service\SearchService())->searchMv($kwy, $member);
                $data = $result['list'];
            }else{
                $data = cached($key)
                    ->fetchPhp(function () use ($type,$uid,$kwy,$page,$limit){
                        return MvModel::queryBase()
                            ->when($type == 'my',function ($q) use ($uid){
                                return $q->where('uid',$uid);
                            })
                            ->when($kwy,function ($q) use ($kwy){
                                return $q->whereRaw("match(tags) against(? in boolean mode)",[$kwy])->orWhere('title','like',$kwy);
                            })
                            ->forPage($page,$limit)
                            ->orderByDesc('id')
                            ->get();
                    });
                if ($data){
                    $data = (new \service\MvService())->formatList($data);
                }
            }

            return $this->showJson($data);
        } catch (\Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 个人中心-我的发片列表
     */
    public function myFindListAction()
    {
        try {
            $member = request()->getMember();
            $service = new FindThingService();
            $return = $service->getMyFindList($member);
            return $this->showJson($return);
        }catch (Throwable $e){
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 他人中心-求片列表
     */
    public function userFindListAction()
    {
        try {
            $validator = \helper\Validator::make($this->post, [
                'uid' => 'required|numeric',
            ]);
            if ($validator->fail($msg)) {
                return $this->errorJson($msg);
            }
            $uid = $this->post['uid'];
            $member = request()->getMember();
            list($page, $limit) = QueryHelper::pageLimit();
            $service = new FindThingService();
            $return = $service->getUserFindList($member, $uid, $page, $limit);
            return $this->showJson($return);
        }catch (Throwable $e){
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 求片列表 （含公共 最热|最新）包含广告处理  帖子新版本后 10.29
     */
    public function listAction()
    {
        try {
            $member = request()->getMember();
            $type = $this->post['type'] ?? 'hot';
            $isMatch = $this->post['is_match'] ?? 0;//0 全部 1 已经采纳 2 未采纳
            $hasCoins = $this->post['has_coins'] ?? 0;//0 全部 1 有赏金 2 没有赏金
            $dateRange = $this->post['date_range'] ?? null;
            $sort = $this->post['sort'] ?? 'like';
            if (!in_array($type,['hot','new'])){
                return $this->errorJson("类型不正确");
            }
            if (!in_array($isMatch,[0,1,2])){
                return $this->errorJson("类型不正确");
            }
            if (!in_array($hasCoins,[0,1,2])){
                return $this->errorJson("类型不正确");
            }
            list($page, $limit) = QueryHelper::pageLimit();
            $service = new FindThingService();
            $return = $service->getFindList($member,$type,$page,$limit,$sort,$hasCoins ,$isMatch,$dateRange);
            return $this->showJson(['list' => $return]);
        }catch (Throwable $e){
            return $this->errorJson($e->getMessage());
        }
    }


    /**
     * 公共  榜单
     */
    public function rankAction()
    {
        try {
            $service = new FindThingService();
            $return  = $service->getReplyRank(date('Ymd'));
            return $this->showJson($return);
        }catch (Throwable $e){
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 个人中心-我的采纳
     */
    public function myAcceptAction()
    {
        try {
            $validator = \helper\Validator::make($this->post, [
                'find_id' => 'required|numeric|min:1',
                'reply_id' => 'required|numeric|min:1',
            ]);
            if ($validator->fail($msg)) {
                return $this->errorJson($msg);
            }
            $find_id = $this->post['find_id'];
            $reply_id = $this->post['reply_id'];
            $service = new FindThingService();
            //check has find
            $findObject = $service->getFindRow($find_id);
            test_assert($findObject,'查无求片记录');
            $member = request()->getMember();
            if($findObject->uuid != $member->uuid){
                return $this->errorJson("无权操作求片记录");
            }
            /** @var FindReplyModel $replyObject */
            $replyObject = FindReplyModel::where([
                'id'=>$reply_id,
                'find_id'=>$findObject->id,
            ])->first();
            test_assert($replyObject,'无效回复记录');

            if($member->uuid == $replyObject->uuid){
                return $this->errorJson("自己不能采纳自己~");
            }
            if($findObject->is_match || $replyObject->is_accept){
                return $this->successMsg("已经采纳并自动分配赏金~");
            }
            $service->doAccept($findObject,$replyObject);
            return $this->successMsg("已经采纳并自动分配赏金~");
        }catch (Throwable $e){
            return $this->errorJson($e->getMessage());
        }

    }

    /**
     * 个人中心 我的推荐（回复的求片）
     */
    public function myReplyAction(){
        try {
            $service = new FindThingService();
            $member = request()->getMember();
            $list = $service->getReplyByUser($member);
            return $this->showJson($list);
        }catch (Throwable $e){
            return $this->errorJson($e->getMessage());
        }
    }



}