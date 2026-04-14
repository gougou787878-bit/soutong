<?php
namespace service;

use FindModel;
use FindReplyModel;
use MemberModel;
use helper\QueryHelper;


/**
 * Class FindThingService
 * @package service
 * @example  求片相关 服务逻辑处理
 */
class FindThingService
{

    /**
     * 根据 id 找求片信息
     */
    public function getFindRow($find_id){
        /** @var FindModel $row */
        $row =  FindModel::getRow($find_id);
        if($row){
            $row->created_at_format = date('Y-m-d H:i',$row->created_at);
            $row->images = $row->getImagesAttribute();
            $row->member && $row->member->thumb = url_avatar($row->member->thumb);
            $row->mv_info = null;
            if($row->vid){
                $row->mv_info = $row->mvInfo();
            }
        }
        return $row;
    }


    /**
     * 创建发布求片信息
     * @throws \Exception
     */
    public function createFind(\MemberModel $member, $title, $images, $coins = 0,$vid = 0)
    {
        transaction(function () use ($member, $title, $images, $coins,$vid){
            if ($coins > 0){
                $itOk = \MemberModel::where([
                    ['uid', '=', $member->uid],
                    ['coins', '>=', $coins],
                ])->update([
                    'coins'       => \DB::raw("coins-{$coins}"),
                    'consumption' => \DB::raw("consumption+{$coins}")
                ]);
                if (empty($itOk)) {
                    throw new \Exception('扣款失败,请确认您的金币是否足够', 1008);
                }

                $tips = "[求片赏金]{$title}#消费金币： $coins";
                $rs3 = \UsersCoinrecordModel::createForExpend('findMv', $member->uid, 0,
                    $coins,
                    0,
                    0,
                    0,
                    0,
                    null,
                    $tips);
            }
            //日志记录
            $itOK = \FindModel::addData($member->uuid, $title, $images, $coins, $vid, FindModel::STAT_TO_CHECK);
            test_assert($itOK, '求片记录失败');
            \MemberModel::clearFor($member);

            //金币消耗上报
            (new EventTrackerService(
                $member->oauth_type,
                $member->invited_by,
                $member->uid,
                $member->oauth_id,
                $_POST['device_brand'] ?? '',
                $_POST['device_model'] ?? ''
            ))->addTask([
                'event'                 => EventTrackerService::EVENT_COIN_CONSUME,
                'product_id'            => '0',
                'product_name'          => "求片赏金:" . $title,
                'coin_consume_amount'   => (int)$coins,
                'coin_balance_before'   => (int)($member->coins),
                'coin_balance_after'    => (int)$member->coins - $coins,
                'consume_reason_key'    => 'find_related',
                'consume_reason_name'   => '求片相关',
                'order_id'              => (string)$rs3->id,
                'create_time'           => to_timestamp($rs3->addtime),
            ]);

        });
        return true;
    }


    /**
     * 想看 求片
     */
    public function wantLookFind(\MemberModel $memeber, $findID)
    {
        $key = "look:{$findID}";
        if (!redis()->sIsMember($key, $memeber->uuid)) {
            \FindLookModel::updateOrCreate(['uuid'=>$memeber->uuid,'find_id'=>$findID],['create_at'=>TIMESTAMP]);
            \FindModel::where('id', $findID)->increment('like');
            redis()->sAdd($key, $memeber->uuid);
            return true;
        }
        return false;
    }

    static function getIsLook($uuid, $findID)
    {
        $key = "look:{$findID}";
        static $todayMember = null;
        if ($todayMember !== null) {
            if (isset($todayMember[$key]) && in_array($uuid, $todayMember[$key])) {
                return true;
            }
        }
        $flag = redis()->sIsMember($key, $uuid);
        if ($flag) {
            $todayMember[$key][] = $uuid;
        }
        if(\FindLookModel::where(['uuid'=>$uuid,'find_id'=>$findID])->exists()){
            $todayMember[$key][] = $uuid;
            redis()->sAdd($key,$uuid);
            return true;
        }
        return $flag;
    }

    public function findIsEnd(FindModel $find){
        //查看是否已经有人追加了赏金
        $expireInfo = \FindAppendModel::getFirstFindAppend($find->id);
        //倒计时
        if (is_null($expireInfo)){
            $return['count_down'] = ['expire_at' => $find->created_at + 96 * 3600, 'now'=> TIMESTAMP];
            if ($find->created_at + 96 * 3600 > TIMESTAMP){
                return true;
            }
            return false;
        }else{
            if ($find->created_at + 48 * 3600 > TIMESTAMP){
                return true;
            }
            return false;
        }
    }

    /**
     * 追加赏金
     * @throws \Exception
     */
    public function appendCoinFind(\MemberModel $member, $find_id, $coins)
    {
        if ($member->coins < $coins){
            throw new \Exception('金币余额不足,请先充值');
        }
        /** @var FindModel $findModel */
        $findModel = $this->getFindRow($find_id);
        test_assert($findModel,'查无求片记录');
        //是否匹配 分配赏金
        if ($findModel->is_match == FindModel::MACTH_YES) {
            throw new \Exception('截止求片活动已结束');
        }
        //是否超过48小时
//        if ($findModel->reply == 0 && ($findModel->created_at + FindModel::REPLY_MAX_TTL) < TIMESTAMP) {
//            //无人接单退回
//            throw new \Exception('截止求片活动已自动关闭~');
//        }
        //是否超过最大求片时间
        if ($findModel->created_at + FindModel::REPLY_MAX_TTL < TIMESTAMP) {
            throw new \Exception('截止求片活动已自动关闭~');
        }

        transaction(function () use ($member,$findModel,$coins){
            //扣款
            $itOk = \MemberModel::where([
                ['uid', '=', $member->uid],
                ['coins', '>=', $coins],
            ])->update([
                'coins'       => \DB::raw("coins-{$coins}"),
                'consumption' => \DB::raw("consumption+{$coins}")
            ]);
            test_assert($itOk,'扣款失败,请确认您的金币是否足够');
            $tips = "[求片追加赏金]#消费金币： $coins";
            $rs3 = \UsersCoinrecordModel::createForExpend('findMv', $member->uid, 0,
                $coins,
                0,
                0,
                0,
                0,
                null,
                $tips);
            $itOk = \FindModel::where('id', $findModel->id)->increment('total_coins', $coins);
            test_assert($itOk,'追加赏金失败');
            $findAppendModel = \FindAppendModel::addData($findModel->id, $findModel->uuid, $member->uuid, $coins);
            test_assert($findAppendModel,'追加赏金失败');
            //金币消耗上报
            (new EventTrackerService(
                $member->oauth_type,
                $member->invited_by,
                $member->uid,
                $member->oauth_id,
                $_POST['device_brand'] ?? '',
                $_POST['device_model'] ?? ''
            ))->addTask([
                'event'                 => EventTrackerService::EVENT_COIN_CONSUME,
                'product_id'            => '0',
                'product_name'          => "求片追加赏金:" . $findModel->id,
                'coin_consume_amount'   => (int)$coins,
                'coin_balance_before'   => (int)($member->coins),
                'coin_balance_after'    => (int)$member->coins - $coins,
                'consume_reason_key'    => 'find_related',
                'consume_reason_name'   => '求片相关',
                'order_id'              => (string)$rs3->id,
                'create_time'           => to_timestamp($rs3->addtime),
            ]);
        });
        //清空追加赏金列表信息
        //redis()->del("json:".self::_getAppendCoinKey($findModel->id));
        cached('')->clearGroup('find:append:coins:list:'.$find_id);
         //消息通知
        \MemberModel::clearFor($member);



        return true;
    }

    private static function _getAppendCoinKey($find_id)
    {
        return "append:list:{$find_id}";
    }

    /**
     * @param $find_id
     * @return array
     */
    public function getAppendCoinList($find_id)
    {
        list($page , $limit) = QueryHelper::pageLimit();
        $key = sprintf('find:append:coins:list:%d:%d:%d',$find_id,$page,$limit);
        return cached($key)
            ->group('find:append:coins:list')
            ->chinese('求片赏金追加列表')
            ->fetchPhp(function () use ($find_id , $page ,$limit){
                return \FindAppendModel::getAppendList($find_id, $page, $limit);
            },600);
    }

    public function getAppendCoinTotal($find_id){
        $key = sprintf('find:append:coins:total:%d',$find_id);
        return cached($key)
            ->group('find:append:coins:total')
            ->chinese('求片赏金总和')
            ->fetchJson(function () use ($find_id){
                return \FindAppendModel::getTotalInfo($find_id);
            },600);
    }

    public function getReplyByFind(\MemberModel $getMember, int $find_id)
    {
        list($page, $limit) = QueryHelper::pageLimit();

        $total = FindReplyModel::getCountByFindId($find_id);
        //list
        $list = FindReplyModel::getListByFindId($find_id,$page,$limit);
        collect($list)->each(function ($item) use ($getMember){
            /** @var FindReplyModel $item */
            $mv_list = \FindReplyMvModel::getMvList($item->id);
            $item->mvs = (new MvService())->formatList($mv_list,$getMember);
            $item->comment_list = \FindReplyCommentModel::getReplyByReply($item->id,1,3);
        });

        return [
            'total' => $total,
            'list'  => $list
        ];
    }

    public function getReplyByReplyList($member,$reply_id,$page,$limit){
        return \FindReplyCommentModel::getReplyByReply($reply_id,$page,$limit);
    }

    /**
     * 获取我的回复的数据
     */
    public static function getReplyByUser(\MemberModel $getMember)
    {
        $uuid = $getMember->uuid;
        list($page, $limit) = QueryHelper::pageLimit();
        $key = sprintf(FindReplyModel::FIND_REPLY_SELF_LIST,$uuid,$page,$limit);
        return cached($key)
            ->group(FindReplyModel::FIND_REPLY_SELF_LIST_GROUP)
            ->fetchPhp(function () use ($uuid,$page,$limit){
                return FindReplyModel::with(["myfind"=>function($query){
                        $query->with('member:uuid,nickname,thumb,followed_count,auth_status');
                    }])
                    ->where('uuid',$uuid)
                    ->orderByDesc('id')
                    ->forPage($page,$limit)
                    ->get()->map(function ($item) use ( $uuid) {
                        /** @var FindModel $item */
                        $find = $item->myfind;
                        $find->images = $find->getImagesAttribute();
                        $find->created_at_format = date('Y-m-d H:i', $find->created_at);
                        if ($find->member) {
                            $find->member->thumb = url_avatar($find->member->thumb);
                        }
                        $find->mv_info = null;
                        if ($find->vid) {
                            $find->mv_info = $find->mvInfo();
                        }
                        $find->is_like = self::getIsLook($uuid, $find->id);
                        return $find;
                    });
            },600);
    }

    /**
     * 回复求片
     */
    public function replyFind($findId, array $vid, \MemberModel $member)
    {
        $model = FindModel::find($findId);
        test_assert($model,'资源不存在');
        if ($model->uuid == $member->uuid) {
            throw new \Exception('不能对自己进行回复');
        }
        transaction(function () use ($model,$vid,$member){
            $replay = FindReplyModel::create([
                'find_id'    => $model->id,
                'uuid'       => $member->uuid,
                'status'     => FindReplyModel::STATUS_INIT,
                'created_at' => TIMESTAMP,
                'praize'     => 0,
                'comment'    => 0,
                'is_accept'  => FindReplyModel::IS_ACCEPT_NO,
            ]);
            test_assert($replay,'操作失败,请重试');
            $itOk = $model->increment('reply');
            test_assert($itOk,'操作失败,请重试');
            $insertValue = [];
            $vid = \MvModel::whereIn('id', $vid)->pluck('id')->toArray();
            foreach ($vid as $item) {
                $insertValue[] = [
                    'reply_id'  => $replay->id,
                    'uuid'      => $member->uuid,
                    'mv_id'     => $item,
                    'create_at' => TIMESTAMP,
                ];
            }
            if (count($insertValue) > 10) {
                throw new \Exception('每次回复不能大于10条视频');
            }
            $itOk = \FindReplyMvModel::insert($insertValue);
            test_assert($itOk,'操作失败,请重试');
        });
        \MemberRankModel::addMemberRank($member->uuid,\MemberRankModel::FIELD_RECEIVE);//每日接单排行榜统计
        return true;
    }

    /**
     * 对回复进行点赞
     */
    public function praiseReply(MemberModel $getMember,$reply_id, $type)
    {
        $model = FindReplyModel::find($reply_id);
        test_assert($model,'资源不存在');
        transaction(function () use ($getMember,$model,$reply_id,$type){
            if ($type == 'unset') {
                $itOk = \FindReplyLikesModel::where(['uuid' => $getMember->uuid, 'reply_id' => $reply_id])->delete();
                if (empty($itOk)) {
                    throw new \Exception('操作失败,请重试');
                }
                if ($model->praize >= 1){
                    $model->decrement('praize');
                }
            } else {
                $itOk = \FindReplyLikesModel::create([
                    'uuid'     => $getMember->uuid,
                    'reply_id' => $reply_id,
                ]);
                if (empty($itOk)) {
                    throw new \Exception('操作失败,请重试');
                }
                $model->increment('praize');
            }
        });
    }

    /**
     * 对回复进行评论
     */
    public function commentReply(MemberModel $getMember,$reply_id, $comment, $toUuid)
    {
        $model = FindReplyModel::find($reply_id);
        test_assert($model,'资源不存在');
        $itOk = \FindReplyCommentModel::create([
            'find_id'    => $model->find_id,
            'reply_id'   => $model->id,
            'uuid'       => $getMember->uuid,
            'to_uuid'    => $toUuid ?? '', //回复谁的
            'comment'    => $comment,
            'is_checked' => intval(setting('find:reply:comment:checked', 1)),
            'like_num'   => 0,
            'reply_num'  => 0,
            'created_at' => TIMESTAMP,
        ]);
        if (empty($itOk)) {
            throw new \Exception('操作失败,请重试');
        }
        $itOk = $model->increment('comment');
        if (empty($itOk)) {
            throw new \Exception('操作失败,请重试');
        }
        return true;
    }


    /**
     * 对回复进行点赞
     */
    public function praiseComment(MemberModel $getMember,$comment_id, $type)
    {
        $model = \FindReplyCommentModel::find($comment_id);
        test_assert($model,'资源不存在');
        transaction(function () use ($getMember,$comment_id,$model,$type){
            if ($type == 'unset') {
                $itOk = \FindReplyLikeCommentModel::where([
                    'uuid'       => $getMember->uuid,
                    'comment_id' => $comment_id,
                ])->delete();
                test_assert($itOk,'操作失败,请重试');
                if ($model->like_num > 0){
                    $model->decrement('like_num');
                }
            } else {
                $itOk = \FindReplyLikeCommentModel::create([
                    'uuid'       => $getMember->uuid,
                    'comment_id' => $comment_id,
                ]);
                test_assert($itOk,'操作失败,请重试');
                $model->increment('like_num');
            }
        });

        return true;
    }

    /**
     * 获取 最新 最热列表 数据
     */
    public function getFindList(MemberModel $member,$type,$page,$limit,$sort,$hasCoins ,$isMatch,$dateRange)
    {
        $uuid = $member->uuid;
        if ($hasCoins == 0) {
            //不筛选赏金
            $where = [];
        } elseif ($hasCoins == 1) {
            //有赏金
            $where[] = ['coins', '>', 0];
        } else {
            //没赏金
            $where[] = ['coins', '=', 0];
        }
        if ($isMatch != 0) {
            if ($isMatch == 1) {
                $where[] = ['is_match', '=', FindModel::MACTH_YES]; //采纳
            } else {
                $where[] = ['is_match', '=', FindModel::MACTH_DEFAULT];//未采纳
            }
        }
        if (!empty($dateRange)) {
            //时间范围, 格式 2020-07-15,2020-07-27
            $ary = explode(',', $dateRange);
            if (isset($ary[0])) {
                $where[] = ['created_at', '>=', strtotime($ary[0])];
            }
        }
        if ($type == 'new'){
            $order = 'created_at';
        }else{
            $order = $sort;
            'coins' == $sort &&  $order = 'total_coins';//总赏金排序
        }
        $query =  FindModel::queryBase()
            ->where($where)
            ->orderByDesc($order)
            ->orderByDesc('is_top')
            ->forPage($page,$limit);
        $hash = substr(md5(json_encode($where)),-1,6);
        $key = sprintf(FindModel::REDIS_FIND_LIST,$page,$limit,$type,$hash,$order);
        $data = cached($key)
            ->group(FindModel::REDIS_FIND_LIST_GROUP)
            ->chinese('求片列表')
            ->fetchPhp(function () use ($page, $limit, $order, $query) {
                return $query->with('member:uuid,uid,nickname,thumb,followed_count,auth_status')
                    ->get()->map(function ($item) {
                        if (empty($item->member)) {
                            return null;
                        }
                        return $item;
                    })->filter();
            },700);
        $data = $data->map(function (FindModel $item) {
            $item->images = $item->getImagesAttribute();
            $item->created_at_format = date('Y-m-d H:i', $item->created_at);
            if ($item->member) {
                $item->member->thumb = url_avatar($item->member->thumb);
            }else{
                return null;
            }
            $item->mv_info = null;
            if ($item->vid) {
                $item->mv_info = $item->mvInfo();
            }
            $item->is_like = 0;
            return $item;
        });

        return $data->map(function ($item) use ($uuid) {
            if (is_object($item) && empty($item->member)){
                return null;
            }
            $item->is_like = self::getIsLook($uuid, $item->id);
            return $item;
        })->filter()->values();

    }

    /**
     * 按天获取 推荐排行
     */
    public function getReplyRank($day,$top = 10){
        $key = "find:reply:rank:{$day}:{$top}";
        $data = cached($key)
            ->group('find:reply:rank')
            ->chinese('求片排行榜')
            ->fetchPhp(function () use ($day,$top){
            return \FindMemberRankModel::getRankByDay($day,$top);
        },1000);
        if($data){
            return $data;
        }
        //没有就获取总榜数据
        return cached('find:reply:rank:all')
            ->chinese('总求片排行榜')
            ->fetchPhp(function () use ($top){
            return \FindMemberRankModel::getAllReplyRank($top);
        });
    }

    /**
     * 个人中心- 我的求片列表（包含待审核）
     */
    public function getMyFindList(MemberModel $member){
        list($page, $limit) = QueryHelper::pageLimit();
        return FindModel::queryAll()
                ->with('member:uuid,nickname,thumb,followed_count,auth_status')
                ->where('uuid',$member->uuid)
                ->orderBy('status')
                ->forPage($page,$limit)
                ->get()
                ->map(function($item){
                    if(is_null($item)){
                        return null;
                    }
                    $item->images = $item->getImagesAttribute();
                    $item->created_at_format = date('Y-m-d H:i',$item->created_at);
                    $item->member && $item->member->thumb = url_avatar($item->member->thumb);
                    $item->mv_info = null;
                    if($item->vid){
                        $item->mv_info = $item->mvInfo();
                    }
                    $item->is_like = self::getIsLook($item->member->uuid, $item->id);
                    return $item;
        })->filter();
    }

    public function getUserFindList(MemberModel $member, $uid, $page, $limit){
        $uuid = MemberModel::getUuidByUid($uid);
        test_assert($uuid, '用户不存在');
        $key = sprintf(FindModel::USER_FIND_LIST, $uid, $page, $limit);
        $data = cached($key)
            ->group(FindModel::USER_FIND_LIST_GROUP)
            ->chinese('他人求片列表')
            ->fetchPhp(function () use ($uuid, $page, $limit) {
                return FindModel::queryBase()
                    ->with('member:uuid,uid,nickname,thumb,followed_count,auth_status')
                    ->where('uuid', $uuid)
                    ->orderByDesc('id')
                    ->forPage($page,$limit)
                    ->get()->map(function ($item) {
                        if (empty($item->member)) {
                            return null;
                        }
                        return $item;
                    })->filter()->values();
            });
        $my_uuid = $member->uuid;
        return $data->map(function (FindModel $item) use ($my_uuid) {
            $item->images = $item->getImagesAttribute();
            $item->created_at_format = date('Y-m-d H:i', $item->created_at);
            $item->member->thumb = url_avatar($item->member->thumb);
            $item->mv_info = null;
            if ($item->vid) {
                $item->mv_info = $item->mvInfo();
            }
            $item->is_like = self::getIsLook($my_uuid, $item->id);
            return $item;
        });
    }

    public function doAccept($findObj, FindReplyModel $replyObj){
        /** @var FindModel $row */
        $row = FindModel::where('id',$findObj->id)->first();
        $coins = $row->canGetCoins();//应得到
        transaction(function () use ($findObj,$replyObj,$row,$coins){
            FindModel::where('id',$findObj->id)->update(['is_match'=>FindModel::MACTH_YES]);
            FindReplyModel::where('id',$replyObj->id)->update(['is_accept'=>FindReplyModel::IS_ACCEPT_YES,'coins'=>$coins]);
            if($coins){
                /** @var MemberModel $toMember */
                $toMember = MemberModel::where('uuid',$replyObj->uuid)->first();
                $toMember->increment("score", $coins);
                $toMember->increment("score_total", $coins);
                $tips = "[回复求片被采纳]# 获取收益： $coins";
                //\UsersCoinrecordModel::addIncome('buymv', 0, $toMember->uid, $coins, $findObj->id, 0, $tips);
                \UsersCoinrecordModel::createForExpend('findReply', 0, $toMember->uid,
                    $coins,
                    $findObj->id,
                    0,
                    0,
                    0,
                    null,
                    $tips);
            }

            return true;
        });
    }

    /**
     * @desc  打赏
     */
    public function reward(\MemberModel $member, $reply_id, $coins)
    {
        /** @var FindModel $row */
        $reply = FindReplyModel::find($reply_id);
        test_assert($reply,'资源不存在');
        $toMember = $reply->member;
        test_assert($toMember,'打赏用户不存在');
        //不能打赏给自己
        if ($member->uuid == $toMember->uuid){
            throw new \Exception('不能给自己打赏');
        }
        $need_coin = $coins;//需要金币
        $reach_coin = $need_coin;//到账金币
        //提成0.1
//        if ($need_coin >= 10) {
//            $reach_coin = $need_coin - floor($need_coin * 0.1);
//        } else {
//            $reach_coin = $need_coin;
//        }
        transaction(function () use ($member, $toMember,$need_coin,$reach_coin,$reply){
            $total = $need_coin;
            $itOk = \MemberModel::where([
                ['uid', '=', $member->uid],
                ['coins', '>=', $total],
            ])->update([
                'coins'       => \DB::raw("coins-{$total}"),
                'consumption' => \DB::raw("consumption+{$total}")
            ]);
            if (empty($itOk)) {
                throw new \Exception('扣款失败,请确认您的金币是否足够', 1008);
            }
            $tips = "[打赏求片]#消耗金币： $total";
            $rs3 = \UsersCoinrecordModel::createForExpend('buymv', $member->uid, $toMember->uid,
                $total,
                $reply->id,
                0,
                0,
                0,
                null,
                $tips);
            $toMember->increment("score", $reach_coin);
            $toMember->increment("score_total", $reach_coin);

            $reply->coins += $need_coin;
            $itOk = $reply->save();
            if (empty($itOk)){
                throw new \Exception('打赏失败,情重试');
            }

            \MemberModel::clearFor($member);
            \MemberModel::clearFor($toMember);

            //金币消耗上报
            (new EventTrackerService(
                $member->oauth_type,
                $member->invited_by,
                $member->uid,
                $member->oauth_id,
                $_POST['device_brand'] ?? '',
                $_POST['device_model'] ?? ''
            ))->addTask([
                'event'                 => EventTrackerService::EVENT_COIN_CONSUME,
                'product_id'            => (string)$reply->id,
                'product_name'          => "求片评论打赏:" . $reply->comment,
                'coin_consume_amount'   => (int)$total,
                'coin_balance_before'   => (int)($member->coins),
                'coin_balance_after'    => (int)$member->coins - $total,
                'consume_reason_key'    => 'find_related',
                'consume_reason_name'   => '求片相关',
                'order_id'              => (string)$rs3->id,
                'create_time'           => to_timestamp($rs3->addtime),
            ]);

        });
        return true;
    }


    /**
     * 获取 我的想看
     */
    public function wantLookFindList(\MemberModel $member)
    {
        list($page, $limit) = QueryHelper::pageLimit();
        $uuid =  $member->uuid;
        $where['uuid'] = $uuid;
        return  \FindLookModel::where($where)
            ->with(["myfind"=>function($query){
                $query->with('member:uuid,nickname,thumb,followed_count,auth_status');
            }])
            ->orderByDesc('id')
            ->forPage($page,$limit)
            ->get()->map(function ($item) use ( $uuid) {
                /** @var FindModel $item */
                $find = $item->myfind;
                $find->images = $find->getImagesAttribute();
                $find->created_at_format = date('Y-m-d H:i', $find->created_at);
                if ($find->member) {
                    $find->member->thumb = url_avatar($find->member->thumb);
                }
                $find->mv_info = null;
                if ($find->vid) {
                    $find->mv_info = $find->mvInfo();
                }
                $find->is_like = self::getIsLook($uuid, $find->id);
                return $find;
            });
    }

}