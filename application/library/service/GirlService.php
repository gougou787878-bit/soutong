<?php


namespace service;

use Carbon\Carbon;
use Elasticsearch\Endpoints\License\Post;
use helper\QueryHelper;
use Overtrue\Pinyin\Pinyin;

use RuntimeException;

class GirlService
{


    /**
     * 地区筛选 前端数据
     * @param int $level
     * @return mixed
     */
    static function getAreaDataList(int $level = 2)
    {
        return cached(\AreaCnModel::AREA_KEY)->expired(86400)->serializerJSON()->fetch(function () use ($level) {
            return \AreaCnModel::queryBase()->where('level', $level)->get(['id', 'areaname'])->toArray();;
        });
    }

    /**
     * @param int $level
     * @return mixed
     */
    static function getHotAreaDataList(int $level = 2)
    {
        return cached(\AreaCnModel::AREA_KEY_HOT)->expired(86400)->serializerJSON()->fetch(function () use ($level) {
            return \AreaCnModel::queryHot()->where('level', $level)->get(['id', 'areaname'])->toArray();;
        });
    }

    /**
     *  话题详情信息
     * @param $id
     * @param $watchMember
     * @return mixed
     * @throws \Exception
     */
    public function getTopicDetail($id,$watchMember = null)
    {
        $topic = \GirlTopicModel::getTopicById($id, $watchMember);
        if (is_null($topic)) {
            throw new \Exception('无此话题');
        }
        bg_run(function ($id){
            \GirlTopicModel::where('id', $id)->increment('view_num');
        });
        return $topic;
    }
    /**
     * @throws \Exception
     */
    public function toggleFollowTopic($aff, $topicId)
    {
        $record = \GirlTopicUserLikeModel::getRecordByParam($aff, $topicId);
        if ($record) {
            $record->delete();
            \GirlTopicModel::where('id',$topicId)->decrement('follow_num');
            return [true,'取消关注成功',false];//is_follow
        } else {
            $data = [
                'aff'        => $aff,
                'related_id' => $topicId,
            ];
            $d['created_at'] =date('Y-m-d H:i:s');
            \GirlTopicUserLikeModel::updateOrCreate($data,$d);
            \GirlTopicModel::where('id',$topicId)->increment('follow_num');
        }
        \GirlTopicUserLikeModel::clearFollowCache($aff);
        return [true,'关注成功',true];
    }



    public function listTopics( $page, $limit,\MemberModel $memberModel=null)
    {
        return \PostTopicModel::listTopics($page, $limit,$memberModel);
    }

    // 发布帖子

    /**
     * @throws \RedisException
     */
    public function createPost(\MemberModel $member, $topicId, $categoryId, $content, $title, $medias, $cityname, $ipstr, $price,$contact)
    {
        $isPass = false;
        # 免审核用户每天发布帖子次数限制
        if ($isPass) {
            //test_assert($curNum <= $num, '您今日的免审核次数已经完！');
        }

        $status = $isPass ? \GirlModel::STATUS_PASS : \GirlModel::STATUS_WAIT;
        test_assert(mb_strlen($content) < 50000, '您发的内容太多了,无法保存');
        transaction(function () use ($member,$topicId,$categoryId,$content,$ipstr,$cityname,$title,$status,$price,$medias,$contact){
            $data = [
                'topic_id'   => $topicId,
                'category'   => $categoryId,
                'content'    => $content,
                'aff'        => $member->aff,
                'ipstr'      => $ipstr,
                'cityname'   => $cityname,
                'refresh_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
                'title'      => $title,
                'status'     => $status,
                'price'      => $price,
                'contact'    => $contact
            ];
            /** @var \GirlModel $new */
            $new = \GirlModel::create($data);
            test_assert($new, '系统异常,异常码:1001');
            $isFinished = \GirlModel::FINISH_OK;
            foreach ($medias as $val) {
                $media_url = strip_tags($val['media_url']??'');
                if(empty($media_url)){
                    continue;
                }
                $extension = pathinfo($media_url, PATHINFO_EXTENSION);
                if(!in_array($extension, ['mp4' , 'gif' , 'png' , 'jpeg' , 'jpg' , 'swf' , 'icon' , 'm3u8' ])){
                    continue ;
                }
                $media = [
                    'aff'          => $new->aff,
                    'relate_type'  => \GirlMediaModel::TYPE_RELATE_POST,
                    'pid'          => $new->id,
                    'media_url'    => $media_url,
                    'thumb_width'  => intval($val['thumb_width'] ?? 0),
                    'thumb_height' => intval($val['thumb_height'] ?? 0),
                    'created_at'   => date('Y-m-d H:i:s'),
                    'updated_at'   => date('Y-m-d H:i:s'),
                ];
                if ($extension == 'mp4') {
                    if (isset($val['cover']) && !empty($val['cover'])) {
                        $media['cover'] = $val['cover'];
                    }
                    $media['type'] = \GirlMediaModel::TYPE_VIDEO;
                    $media['status'] = \GirlMediaModel::STATUS_NO;
                    $isFinished = \GirlModel::FINISH_NO;
                } else {
                    $media['cover'] = $val['media_url'];
                    if (isset($val['cover']) && !empty($val['cover'])) {
                        $media['cover'] = $val['cover'];
                    }
                    $media['type'] = \GirlMediaModel::TYPE_IMG;
                    $media['status'] = \GirlMediaModel::STATUS_OK;
                }
                $media = \GirlMediaModel::create($media);
                test_assert($media,"系统异常");
                if ($media->type == \GirlMediaModel::TYPE_VIDEO) {
                    $new->increment('video_num');
                } else {
                    $new->increment('photo_num');
                }
            }
            $new->update([
                'is_finished' => $isFinished
            ]);
        });
        return true;
    }


    protected function sync2Es(\PostModel $post)
    {
        $ES = new EsLib();
        try {
            if (
                $post->status == \PostModel::STATUS_PASS
                && $post->is_deleted == \PostModel::DELETED_NO
                && $post->is_finished == \PostModel::FINISH_OK
            ) {
                // 如果都是有效的则存储到ES
                $post->makeHidden('_id');
                $post->yuancheng_id = $post->_id;
                $ES->insert(\PostModel::ES_index, $post->toArray(), $post->id);
            } else {
                $ES->delete(\PostModel::ES_index,$post->toArray(), $post->id);
            }
        } catch (\Throwable $e) {
            $msg = '[远程同步视频数据-同步至ES失败]' . PHP_EOL;
            $msg .= '时间:' . date('Y-m-d H:i:s', TIMESTAMP) . PHP_EOL;
            $msg .= '数据:' . print_r($post->toArray(), true) . PHP_EOL;
            $msg .= 'ES报错:' . $e->getMessage() . PHP_EOL;
            trigger_log($msg);
        }
    }

    function listFollowPosts($aff, $type,$member = null)
    {
        list($limit, $offset, $page) = QueryHelper::restLimitOffset();
        //$aff = 42442;
        if ($type == \GirlModel::TYPE_FOLLOW_TOPIC){
            $ids = \GirlTopicUserLikeModel::where('aff', $aff)->pluck('related_id')->toArray();
        }else{
            $uidAttention = \UserAttentionModel::getList($member);
            $ids = $uidAttention?collect($uidAttention)->pluck('touid')->toArray():[1];
        }
        if (!$ids) {
            return [];
        }
        $postData = \GirlModel::queryBase()->with('topic:id,name')
            ->with('medias:pid,media_url,cover,thumb_width,thumb_height,type')
            ->when($type == \GirlModel::TYPE_FOLLOW_TOPIC, function ($q) use ($ids) {
                return $q->whereIn('topic_id', $ids);
            })
            ->when($type == \GirlModel::TYPE_FOLLOW_USER, function ($q) use ($ids) {
                return $q->whereIn('aff', $ids);
            })
            ->orderByDesc('set_top')
            ->orderByDesc('refresh_at')
            ->orderByDesc('sort')
            ->orderByDesc('id')
            ->limit($limit)->offset($offset)->get();
        return $this->formatPost($postData, $member);
    }


    public function listFavoritPost(\MemberModel $member){
        list($page, $limit) = QueryHelper::pageLimit();
        $postData = \GirlUserLikeModel::listLikePosts($member->aff,$page,$limit);
        if (!$postData){
            return [];
        }
        return $this->formatPost($postData,$member);
    }

    //购买的帖子列表
    public function listBuyGirl(\MemberModel $member){
        list($page, $limit) = QueryHelper::pageLimit();
        $postData = \GirlPayLogModel::listBuyGirls($member->aff,$page,$limit);
        if (!$postData){
            return [];
        }
        return $this->formatPost($postData,$member);
    }

    public function listTopicPost(\MemberModel $member, $cate, $topicId = 0){
        list($page,$limit) = QueryHelper::pageLimit();
        $postData = \GirlModel::listTopicPosts($cate, $topicId, $page, $limit);
        return $this->formatPost($postData,$member);
    }

    public function listMemberPost(\MemberModel $member, $aff, $kwy = ''){
        list($page,$limit) = QueryHelper::pageLimit();
        $postData = \GirlModel::listMemberPosts($aff, $kwy, $page, $limit);
        return $this->formatPost($postData,$member);
    }
    function formatPost($postData,\MemberModel $member =null){
        if(!$postData){
            return $postData;
        }
        foreach ($postData as $key=>&$post){
            /** @var \GirlModel $post */

            if(!is_null($member)){
                $post->watchByUser($member);
                if (!is_null($post->user)){
                    $post->user->watchByUser($member);
                }
            }
            if ($medias = $post->medias) {
                $medias = collect($medias)->map(function ($media) use ($post) {
                        /** @var \GirlMediaModel $media */
                        if ($media->type == \GirlMediaModel::TYPE_IMG) {
                            $media->media_url_full = url_cover($media->media_url);
                        } elseif ($media->type == \GirlMediaModel::TYPE_VIDEO) {
                            $extension = pathinfo($media->media_url, PATHINFO_EXTENSION);
                            if ($extension == 'm3u8') {
                                $media->media_url_full = getPlayUrl($media->media_url, $post->is_pay ? false : true);
                            } else {
                                return null;//非法视频或 没有切完 等下放出去
                            }
                        }
                        return $media;
                    })->filter()->values()->toArray();
            }
            unset($post->medias);//取消关系
            $post->medias = $medias;
        }
        return $postData;
    }

    // 获取帖子详情

    /**
     * @throws \Exception
     */
    public function getDetail($id,$member = null)
    {
        $girl = \GirlModel::getPostById($id);
        if(is_null($girl)){
            return null;
        }
        bg_run(function () use ($id){
            \GirlModel::where(['id'=>$id])->increment('view_num');
        });
        $postData = $this->formatPost([$girl],$member);
        return $postData[0];
    }

    // 创建查询记录
    protected function createSearchRecord($word, $aff)
    {
        \SearchIndexModel::addOrUpdate([
            'word' => $word,
            'aff'  => $aff,
            'type' => \SearchWordModel::TYPE_POST
        ]);
        \SearchTopModel::incrementNum(\SearchWordModel::TYPE_POST, $word);
    }

    protected function listPostIds($word, $page, $limit): ?array
    {
        try {
            $cacheKey = sprintf("es:post:search:%s:%s:%s", $word, $page, $limit);
            $ids = cached($cacheKey)
                ->fetchJson(function () use ($word) {
                    $query = "select id from @{post} where title like '%$word%'";
                    $results = \LibEs::querySql($query, 1000);
                    return collect($results['rows'])->flatten()->toArray();
                });
            return collect($ids)->forPage($page, $limit)->values()->toArray();
        } catch (\Throwable $e) {
            $msg = '[获取ES中的帖子失败]' . PHP_EOL;
            $msg .= '时间:' . date('Y-m-d H:i:s', TIMESTAMP) . PHP_EOL;
            $msg .= '搜索关键字:' . $word . PHP_EOL;
            $msg .= 'ES报错:' . $e->getMessage() . PHP_EOL;
            trigger_log($msg);
            return null;
        }
    }


    // 帖子点赞/取消点赞

    /**
     * 帖子点赞/取消点赞
     * @throws \RedisException
     * @throws \Exception
     */
    public function likeGirl($aff, $postId)
    {
        $post = \GirlModel::find($postId);
        test_assert($post,"帖子不存在");
        /** @var \GirlUserLikeModel $record */
        $record = \GirlUserLikeModel::getIdsById( $aff, $postId);
        /** @var \MemberModel $postMember */
        $postMember = \MemberModel::where('aff',$post->aff)->first();
        test_assert($postMember,'发帖用户不存在');
        if (!$record) {
            $data = [
                'aff'        => $aff,
                'type'       => \GirlUserLikeModel::TYPE_POST,
                'related_id' => $postId,
                'created_at' => \Carbon\Carbon::now()
            ];
            \GirlUserLikeModel::create($data);
            $post->increment('like_num');
            //获赞排行榜
//            \MemberRankModel::addMemberRank($postMember->uuid,\MemberRankModel::FIELD_PRAIZE);
          return [true,'点赞成功',true];
        } else {
            if ($record->created_at > date('Y-m-d')){
                \MemberRankModel::reduceMemberRank($postMember->uuid,\MemberRankModel::FIELD_PRAIZE);
            }
            $record->delete();
            if ($post->like_num > 0){
                $post->decrement('like_num');
            }
        }
        \GirlUserLikeModel::clearCacheByAff(\GirlUserLikeModel::TYPE_POST, $aff);
        return [true,'已取消点赞',false];
    }


    /**
     * 评论点赞/取消点赞
     * @throws \Exception
     */
     function likeComment($aff,$post_id, $commentId)
    {
        $record = \GirlCommentUserLikeModel::getIdsById( $aff,$commentId);
        $comment = \GirlCommentModel::where('id',$commentId)->first();
        if(!$comment){
            return [false,'评论不存在',false];
        }
        if (!$record) {
            $data = [
                'aff'        => $aff,
                'girl_id'    => $post_id,
                'related_id' => $commentId,
                'created_at' => date('Y-m-d H:i:s'),
            ];
            \GirlCommentUserLikeModel::create($data);
            $comment->increment('like_num');
            return [true,'评论点赞成功',true];
        } else {
            $record->delete();
            $comment->where('like_num','>',0)->decrement('like_num');

        }
        return [true,'已取消评论点赞',false];
    }

//    /**
//     * 帖子收藏/取消收藏
//     * @throws \RedisException
//     * @throws \Exception
//     */
//    public function togglefavoritePost($aff, $postId)
//    {
//
//        $record = \PostUserCollectModel::where('aff', $aff)
//            ->where('type', \PostUserCollectModel::TYPE_POST)
//            ->where('related_id', $postId)
//            ->first();
//        if (!$record) {
//            $data = [
//                'type'       => \PostUserCollectModel::TYPE_POST,
//                'aff'        => $aff,
//                'related_id' => $postId,
//                'created_at'  => Carbon::now()->toDateTimeString(),
//                'updated_at'  => Carbon::now()->toDateTimeString(),
//            ];
//            \PostUserCollectModel::create($data);
//            \PostModel::where(['id'=>$postId])->increment('favorite_num');
//            return [true,'收藏成功',true];
//        }
//            $record->delete();
//
//        return [true,'取消收藏成功',false];
//    }


    /**
     * 解锁
     * @throws \RedisException
     * @throws \Exception
     */
    public function unlock_contact(\MemberModel $member, $postId)
    {
        /** @var \GirlModel $girl */
        $girl = \GirlModel::queryBase()->find($postId);
        if (is_null($girl)){
            throw new \Exception('此帖子不存在');
        }
        /** @var \MemberModel $member */
        $member = \MemberModel::firstAff($member->aff);

        $amount = \GirlModel::UNLOCK_FEE;

        if ((int)$girl->aff === (int)$member->aff)
        {
            throw new \Exception('无需解锁自己的帖子');
        }

        //查看是否购买
        $exists = \GirlPayLogModel::onWriteConnection()->where('aff',$member->aff)->where('girl_id',$postId)->exists();
        if ($exists){
            $res = ['status'=>true,'contact'=>$girl->contact];;
            return  $res;
//            throw new \Exception('已经购买了,请勿重复购买');
        }

        if ($member->coins < $amount)
        {
            throw new \Exception('金币余额不足');
        }

        $data = [
            'aff'          => $member->aff,
            'aff_nickname' => $member->nickname,
            'thumb' => $member->thumb,
            'girl_id'      => $girl->id,
            'girl_title'   => $girl->title,
            'girl_aff'     => $girl->aff,
            'amount'       => $amount,
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ];
        transaction(function () use ($member, $girl, $amount, $data) {
            $total = $amount;
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
            $tips = "[解锁约炮联系人]{$girl->title}#金币： $total";
            $rs3 = \UsersCoinrecordModel::createForExpend('buyGirl', $member->uid, $member->uid,
                $total,
                $girl->id,
                0,
                0,
                0,
                null,
                $tips);
            # 日志记录
            $itOK = \GirlPayLogModel::insert($data);
            test_assert($itOK, '解锁日志记录失败');
            # 解锁记录维护
             $girl->increment('reward_num');
            # 解锁金额维护
            $girl->increment('reward_amount', $amount);

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
                'product_id'            => (string)$girl->id,
                'product_name'          => "解锁约炮联系人:" . $girl->title,
                'coin_consume_amount'   => (int)$total,
                'coin_balance_before'   => (int)($member->coins),
                'coin_balance_after'    => (int)$member->coins - $total,
                'consume_reason_key'    => 'sourct_buy_girl',
                'consume_reason_name'   => '约炮解锁',
                'order_id'              => (string)$rs3->id,
                'create_time'           => to_timestamp($rs3->addtime),
            ]);
        });
        $res = ['status'=>true,'contact'=>$girl->contact];;
        return  $res;
    }

    /**
     * @throws \RedisException
     * @throws \Exception
     */
//    public function listCommentsByPostId($postId, \MemberModel $member)
//    {
//        list($page,$limit) = QueryHelper::pageLimit();
//        return cached(sprintf(\PostCommentModel::POST_COMMENT_LIST_DETAIL_KEY,$postId,$page,$limit))
//            ->group(\PostCommentModel::POST_COMMENT_LIST_DETAIL_GROUP_KEY)
//            ->chinese('评论列表')
//            ->fetchPhp(function () use ($member,$postId,$page,$limit){
//                return \PostCommentModel::queryBase()->where(['post_id'=>$postId])
//                    ->with('user:aff,uid,nickname,thumb,aff,expired_at,vip_level,uuid,sexType')
//                    ->orderByDesc('is_top')
//                    ->orderByDesc('id')
//                    ->forPage($page,$limit)
//                    ->get()
//                    ->map(function (\PostCommentModel $item)use($member){
//                        $member && $item->watchByUser($member);
//                        return $item;
//                    });
//            });
//    }

    /**
     * @throws \RedisException
     * @throws \Exception
     */
    public function listCommentsByPost(\MemberModel $member,$postId)
    {
        list($page,$limit) = QueryHelper::pageLimit();
        $post = \GirlModel::find($postId);
        test_assert($post,"此帖子不存在");
        return \GirlCommentModel::listCommentsByPostId($member,$postId, $post->aff, $page, $limit);
    }

    public function listCommentsByCommentId(\MemberModel $member,$commentId, $page, $limit)
    {
        $comment = \PostCommentModel::find($commentId);
        test_assert($comment,'此评论不存在');
        $post = \PostModel::find($comment->post_id);
        test_assert($post,'此帖子不存在');
        return \PostCommentModel::listCommentsByCommentId($member,$comment->id, $comment->post_id, $post->aff, $page, $limit);
    }


    /**
     * @param $post
     * @param $member
     * @param $content
     * @param $cityname
     * @return \PostCommentModel
     * @throws \RedisException
     * @throws \Exception
     */
//    public function createPostComment(\PostModel $post,\MemberModel $member, $content, $cityname='火星')
//    {
//        $status = \PostCommentModel::STATUS_WAIT;
//        //年卡及以上会员直接通过
////        if (in_array($member->vip_level,[\MemberModel::VIP_LEVEL_YEAR,\MemberModel::VIP_LEVEL_LONG]) && $member->is_vip){
////            $status = \PostCommentModel::STATUS_PASS;
////        }
//        $data = [
//            'post_id'       => $post->id,
//            'pid'           => 0,
//            'aff'           => $member->aff,
//            'comment'       => $content,
//            'status'        => $status,
//            'refuse_reason' => '',
//            'is_finished'   => 1,
//            'ipstr'         => USER_IP,
//            'is_top'        => \PostCommentModel::TOP_NO,
//            'cityname'      => $cityname,
//            'created_at'    => date('Y-m-d H:i:s'),
//        ];
//        /** @var \PostCommentModel $comment */
//        $comment = \PostCommentModel::create($data);
//        if (is_null($comment)) {
//            throw new \Exception('系统异常,异常码:1001');
//        }
//        //维护评论数量
//        if ($status == \PostCommentModel::STATUS_PASS){
//            $post->increment('comment_num');
//        }
//        bg_run(function () use ($member, $content, $comment){
//            //检查评论
//            FilterService::checkPostComment($member, $content, $comment);
//        });
//
//        return $comment;
//    }

    /**
     * @throws \Exception
     */
    public function createPostComment(\MemberModel $member, $id, $content, $cityname)
    {
        $aff = $member->aff;
        $post = \GirlModel::find($id);
        test_assert($post,'此帖子不存在');
        $status = \GirlCommentModel::STATUS_WAIT;
        $data = [
            'girl_id'       => $post->id,
            'pid'           => 0,
            'aff'           => $aff,
            'comment'       => $content,
            'status'        => $status,
            'refuse_reason' => '',
            'ipstr'         => USER_IP,
            'is_top'        => \GirlCommentModel::TOP_NO,
            'is_finished'   => \GirlCommentModel::FINISH_OK,
            'cityname'      => $cityname,
            'created_at'    => \Carbon\Carbon::now()
        ];
        $comment = \GirlCommentModel::create($data);
        test_assert($comment,'系统异常,异常码:1001');

        bg_run(function () use ($member, $content, $comment){
            //检查评论
            FilterService::checkPostComment($member, $content, $comment);
        });

        return true;
    }

    public function createComComment(\MemberModel $member, $commentId, $content, $cityname)
    {
        $aff = $member->aff;
        $parentComment = \GirlCommentModel::getCommentById($member, $commentId);
        test_assert($parentComment,'此评论不存在');

        $data = [
            'girl_id'       => $parentComment->girl_id,
            'pid'           => $parentComment->id,
            'aff'           => $aff,
            'comment'       => $content,
            'status'        => \GirlCommentModel::STATUS_WAIT,
            'refuse_reason' => '',
            'ipstr'         => USER_IP,
            'is_top'        => \GirlCommentModel::TOP_NO,
            'is_finished'   => \GirlCommentModel::FINISH_OK,
            'cityname'      => $cityname,
            'created_at'    => \Carbon\Carbon::now(),
        ];
        $comment = \GirlCommentModel::create($data);
        test_assert($comment,'系统异常,异常码:1001');
        bg_run(function () use ($member, $content, $comment){
            //检查评论
            FilterService::checkPostComment($member, $content, $comment);
        });

        return true;
    }

    // 帖子收益统计
    public function postIncome($aff)
    {
        // 今日总计
        $day = date('Y-m-d', time());
        $dayStart = $day . ' 00:00:00';
        $dayEnd = $day . ' 23:59:59';
        $dayNum = \PostRewardLogModel::where('post_aff', $aff)
            ->where('created_at', '>=', $dayStart)
            ->where('created_at', '<=', $dayEnd)
            ->sum('amount');

        // 总计
        $totalNum = \PostRewardLogModel::where('post_aff', $aff)
            ->sum('amount');

        return ['day_num' => $dayNum, 'total_num' => $totalNum];
    }

//    public function postIncomeList($page, $limit){
//        return \PostRewardLogModel::memberPostIncome($page, $limit);
//    }
    /**
     *  帖子列表 最新/ 精华
     * @throws \RedisException
     */
    public function listPosts($cate, $where = [], $member = null)
    {
        list($page,$limit) = QueryHelper::pageLimit();
        $query = \GirlModel::queryBase();
        if($cate =='verify' || $cate == 'refuse'){
            $query = \GirlModel::query();
        }
        $query->with('topic:id,name')
            ->with('medias:pid,media_url,cover,thumb_width,thumb_height,type,status');
        if($where){
            $query->where($where);
        }
        if ($cate == 'choice') {
            $query->where('is_best',\GirlModel::BEST_OK);
            $query->orderByDesc('set_top')->orderByDesc('sort')->orderByDesc('id');
        } else {
            //new
            $query->orderByDesc('set_top')->orderByDesc('sort')->orderByDesc('refresh_at')->orderByDesc('id');
        }
        //print_r($where);die;
        //\DB::enableQueryLog();
        $postData = $query->forPage($page,$limit)->get();
        //print_r(\DB::getQueryLog());
        //return $postData;
        return $this->formatPost($postData,$member);

    }
    /**
     * @throws \RedisException
     */
    public function listRank(\MemberModel $member , $rankBy, $rankTime,$num){
        $list = cached(sprintf('community:rank:bak:%s:%s:%s',$rankBy,$rankTime,$num))
            ->group('community:rank:list')
            ->chinese('社区排行榜')
            ->fetchPhp(function () use ($rankBy,$rankTime,$num){
                $users = \MemberRankModel::getRankByRedis($rankBy,$rankTime,$num);
                if (!$users){
                    return [];
                }
                $uuidArr = array_keys($users);
                return \MemberModel::selectRaw('aff,uid,nickname,thumb,aff,expired_at,vip_level,uuid,sexType')
                    ->whereIn('uuid',$uuidArr)
                    ->get()
                    ->map(function ($item) use ($users){
                        $item->val = max($users[$item->uuid], 0);
                        return $item;
                    });
            },600);
        collect($list)->each(function ($item) use ($member){
            $item->watchByUser($member);
        });
        if (!is_array($list)){
            $list = $list->toArray();
        }
        array_multisort(array_column($list, 'val'), SORT_DESC, $list);
        return $list;
    }

    public function getRecommendMember(\MemberModel $member){
        $aff = setting('community.rec.aff','');
        $recMember = null;
        if ($aff){
            $recMember = cached('community:rec:member:'.$aff)
                ->fetchPhp(function () use ($aff){
                    return \MemberModel::selectRaw('aff,uid,person_signnatrue,nickname,thumb,aff,expired_at,vip_level,uuid,sexType')
                        ->where('aff',$aff)
                        ->first();
                });
            if($recMember){
                $recMember->watchByUser($member);
            }
        }
        return $recMember;
    }

    public function getRecommendPost(\MemberModel $member){
        $postIds = setting('community.rec.post','');
        $postIds = explode(',',$postIds);
        $list = [];
        if ($postIds){
            $list = cached('community:rec:post:'.implode(':',$postIds))
                ->fetchPhp(function () use ($postIds){
                    return \PostModel::queryBase()
                        ->whereIn('id',$postIds)
                        ->get();
                });
            if($list){
                collect($list)->each(function (\PostModel $item) use ($member){
                    $item->watchByUser($member);
                });
            }
        }
        return $list;
    }

    public function incomeList(\MemberModel $member,$type){
        list($page,$limit) = QueryHelper::pageLimit();
        $list = \PostModel::incomeList($member->aff,$type,$page,$limit);
        return $this->formatPost($list);
    }

    public function unlockList($id){
        list($page,$limit) = QueryHelper::pageLimit();
        $post = \PostModel::queryBase()->where('id',$id)->first();
        test_assert($post,'帖子不存在');
        return \PostRewardLogModel::unlockList($id,$page,$limit);
    }

    /**
     * 搜索
     * @throws \RedisException
     */
    public function listSearchPost($word, $aff, $page, $limit)
    {
        $where = [];
        $word &&  $where[] = ['title','like',"%{$word}%"];
        return  $this->listPosts('new',$where);
    }
}