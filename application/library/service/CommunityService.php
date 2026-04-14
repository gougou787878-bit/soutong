<?php


namespace service;

use Carbon\Carbon;
use Elasticsearch\Endpoints\License\Post;
use helper\QueryHelper;
use MemberModel;
use Overtrue\Pinyin\Pinyin;

use PostCommentModel;
use PostModel;
use PostTopicModel;
use RuntimeException;

class CommunityService
{
    /**
     *  话题详情信息
     * @param $id
     * @param $watchMember
     * @return mixed
     * @throws \Exception
     */
    public function getTopicDetail($id,$watchMember = null)
    {
        PostTopicModel::setWatchUser($watchMember);
        $topic = PostTopicModel::getTopicById($id, $watchMember);
        if (is_null($topic)) {
            throw new \Exception('无此话题');
        }
        bg_run(function ($id){
            PostTopicModel::where('id', $id)->increment('view_num');
        });
        return $topic;
    }

    public function getHotTopicList(){
       return PostTopicModel::listHotTopics();
    }
    /**
     * @throws \Exception
     */
    public function toggleFollowTopic($aff, $topicId)
    {
        $record = \PostTopicUserLikeModel::getRecordByParam($aff, $topicId);
        if ($record) {
            $record->delete();
            PostTopicModel::where('id',$topicId)->decrement('follow_num');
            return [true,'取消关注成功',false];//is_follow
        } else {
            $data = [
                'aff'        => $aff,
                'related_id' => $topicId,
            ];
            $d['created_at'] =date('Y-m-d H:i:s');
            \PostTopicUserLikeModel::updateOrCreate($data,$d);
            PostTopicModel::where('id',$topicId)->increment('follow_num');
        }
        \PostTopicUserLikeModel::clearFollowCache($aff);
        return [true,'关注成功',true];
    }



    public function listTopics($page, $limit, MemberModel $memberModel=null)
    {
        return PostTopicModel::listTopics($page, $limit,$memberModel);
    }

    // 发布帖子

    /**
     * @throws \RedisException
     */
    public function createPost(MemberModel $member, $topicId, $categoryId, $content, $title, $medias, $cityname, $ipstr, $price)
    {
        $isPass = false;
        # 免审核用户每天发布帖子次数限制
        if ($isPass) {
            //test_assert($curNum <= $num, '您今日的免审核次数已经完！');
        }

        $status = $isPass ? PostModel::STATUS_PASS : PostModel::STATUS_WAIT;
        test_assert(mb_strlen($content) < 50000, '您发的内容太多了,无法保存');
        transaction(function () use ($member,$topicId,$categoryId,$content,$ipstr,$cityname,$title,$status,$price,$medias){
            $type = PostModel::TYPE_PAY_FREE;
            if ($price > 0){
                $type = PostModel::TYPE_PAY_COINS;
            }
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
                'type'       => $type,
            ];
            /** @var PostModel $new */
            $new = PostModel::create($data);
            test_assert($new, '系统异常,异常码:1001');
            $isFinished = PostModel::FINISH_OK;
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
                    'relate_type'  => \PostMediaModel::TYPE_RELATE_POST,
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
                    $media['type'] = \PostMediaModel::TYPE_VIDEO;
                    $media['status'] = \PostMediaModel::STATUS_NO;
                    $isFinished = PostModel::FINISH_NO;
                } else {
                    $media['cover'] = $val['media_url'];
                    if (isset($val['cover']) && !empty($val['cover'])) {
                        $media['cover'] = $val['cover'];
                    }
                    $media['type'] = \PostMediaModel::TYPE_IMG;
                    $media['status'] = \PostMediaModel::STATUS_OK;
                }
                $media = \PostMediaModel::create($media);
                test_assert($media,"系统异常");
                if ($media->type == \PostMediaModel::TYPE_VIDEO) {
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


    protected function sync2Es(PostModel $post)
    {
        $ES = new EsLib();
        try {
            if (
                $post->status == PostModel::STATUS_PASS
                && $post->is_deleted == PostModel::DELETED_NO
                && $post->is_finished == PostModel::FINISH_OK
            ) {
                // 如果都是有效的则存储到ES
                $post->makeHidden('_id');
                $post->yuancheng_id = $post->_id;
                $ES->insert(PostModel::ES_index, $post->toArray(), $post->id);
            } else {
                $ES->delete(PostModel::ES_index,$post->toArray(), $post->id);
            }
        } catch (\Throwable $e) {
            $msg = '[远程同步视频数据-同步至ES失败]' . PHP_EOL;
            $msg .= '时间:' . date('Y-m-d H:i:s', TIMESTAMP) . PHP_EOL;
            $msg .= '数据:' . print_r($post->toArray(), true) . PHP_EOL;
            $msg .= 'ES报错:' . $e->getMessage() . PHP_EOL;
            trigger_log($msg);
        }
    }

    function listFollowPosts(MemberModel $member, $type)
    {
        MemberModel::setWatchUser($member);
        PostModel::setWatchUser($member);
        $aff = $member->aff;
        list($limit, $offset, $page) = QueryHelper::restLimitOffset();
        //$aff = 42442;
        if ($type == PostModel::TYPE_FOLLOW_TOPIC){
            $ids = \PostTopicUserLikeModel::where('aff', $aff)->pluck('related_id')->toArray();
        }else{
            $uidAttention = \UserAttentionModel::getList($member);
            $ids = $uidAttention?collect($uidAttention)->pluck('touid')->toArray():[1];
        }
        if (!$ids) {
            return [];
        }
        $postData = PostModel::queryBase()->with('topic:id,name')
            ->with('medias:pid,media_url,cover,thumb_width,thumb_height,type')
            ->when($type == PostModel::TYPE_FOLLOW_TOPIC, function ($q) use ($ids) {
                return $q->whereIn('topic_id', $ids);
            })
            ->when($type == PostModel::TYPE_FOLLOW_USER, function ($q) use ($ids) {
                return $q->whereIn('aff', $ids);
            })
            ->orderByDesc('set_top')
            ->orderByDesc('refresh_at')
            ->orderByDesc('sort')
            ->orderByDesc('id')
            ->limit($limit)->offset($offset)->get();
        return $this->formatPost($postData, $member);
    }

    function listFollowUserPosts($member = null){
        list($limit,$offset,$page) = QueryHelper::restLimitOffset();
        $uidAttention = \UserAttentionModel::getList($member);
        $uidData = $uidAttention?collect($uidAttention)->pluck('to_uid')->toArray():[1];
        $service = new CommunityService();
        $where = [];
        $uidData &&  $where[] = ['aff','in',$uidData];
        $postData = $this->listPosts('new',$where,$member);
        return $this->formatPost($postData,$member);
    }

    public function listFavoritPost(MemberModel $member){
        PostModel::setWatchUser($member);
        list($page, $limit) = QueryHelper::pageLimit();
        $postData = \PostUserLikeModel::listLikePosts($member->aff,$page,$limit);
        /*if($_POST['oauth_id'] == 'ed10e48f0f0f221ff9c4ce113fbeda2c'){
            errLog("ppp:".$member->aff);
            errLog("ppp:".var_export($postData,true));
        }*/
        if (!$postData){
            return [];
        }
        return $this->formatPost($postData,$member);
    }

    //购买的帖子列表
    public function listBuyPost(MemberModel $member){
        PostModel::setWatchUser($member);
        MemberModel::setWatchUser($member);
        list($page, $limit) = QueryHelper::pageLimit();
        $postData = \PostRewardLogModel::listBuyPosts($member->aff,$page,$limit);
        if (!$postData){
            return [];
        }
        return $this->formatPost($postData,$member);
    }

    public function listTopicPost(MemberModel $member, $cate, $topicId = 0){
        PostModel::setWatchUser($member);
        MemberModel::setWatchUser($member);
        list($page,$limit) = QueryHelper::pageLimit();
        $postData = PostModel::listTopicPosts($cate, $topicId, $page, $limit);
        return $this->formatPost($postData,$member);
    }

    public function listMemberPost(MemberModel $member, $aff, $kwy = ''){
        PostModel::setWatchUser($member);
        MemberModel::setWatchUser($member);
        list($page,$limit) = QueryHelper::pageLimit();
        $postData = PostModel::listMemberPosts($aff, $kwy, $page, $limit);
        return $this->formatPost($postData,$member);
    }

    /**
     *  帖子列表 最新/ 精华
     * @throws \RedisException
     */
    public function listPosts($cate, $where = [], $member = null)
    {
        list($page,$limit) = QueryHelper::pageLimit();
        $query = PostModel::queryBase();
        if($cate =='verify' || $cate == 'refuse'){
            $query = PostModel::query();
        }
        $query->with('topic:id,name')
            ->with('medias:pid,media_url,cover,thumb_width,thumb_height,type,status');
        if($where){
            $query->where($where);
        }
        if ($cate == 'choice') {
            $query->where('is_best', PostModel::BEST_OK);
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

    function formatPost($postData, MemberModel $member =null){
        if(!$postData){
            return $postData;
        }
        foreach ($postData as $key=>&$post){
            /** @var PostModel $post */
            $medias = [];
            //$medias = $post->medias;
            //print_r($medias->toArray());die;
            if ($medias = $post->medias) {
                $medias = collect($medias)->map(function ($media) use ($post) {
                    /** @var \PostMediaModel $media */
                    if ($media->type == \PostMediaModel::TYPE_IMG) {
                        $media->media_url_full = url_cover($media->media_url);
                    } elseif ($media->type == \PostMediaModel::TYPE_VIDEO) {
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
    public function getPostDetail($postId,$member = null)
    {
        PostModel::setWatchUser($member);
        MemberModel::setWatchUser($member);
        $post = PostModel::getPostById($postId);
        if(is_null($post)){
            return null;
        }
        bg_run(function () use ($postId){
            PostModel::where(['id'=>$postId])->increment('view_num');
        });
        $postData = $this->formatPost([$post],$member);
        return $postData[0];
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

    // 搜索

    /**
     * 搜索
     * @throws \RedisException
     */
    public function listSearchPost($word, $aff, $page, $limit)
    {
        $where = [];
        $word &&  $where[] = ['title','like',"%{$word}%"];
        return $this->listPosts('new',$where);
    }


    // 帖子点赞/取消点赞

    /**
     * 帖子点赞/取消点赞
     * @throws \RedisException
     * @throws \Exception
     */
    public function likePost($aff, $postId)
    {
        $post = PostModel::find($postId);
        test_assert($post,"帖子不存在");
        /** @var \PostUserLikeModel $record */
        $record = \PostUserLikeModel::getIdsById( $aff, $postId);
        /** @var MemberModel $postMember */
        $postMember = MemberModel::where('aff',$post->aff)->first();
        test_assert($postMember,'发帖用户不存在');
        if (!$record) {
            $data = [
                'aff'        => $aff,
                'type'       => \PostUserLikeModel::TYPE_POST,
                'related_id' => $postId,
                'created_at' => \Carbon\Carbon::now()
            ];
            \PostUserLikeModel::create($data);
            $post->increment('like_num');
            //获赞排行榜
            \MemberRankModel::addMemberRank($postMember->uuid,\MemberRankModel::FIELD_PRAIZE);
          return [true,'帖子点赞成功',true];
        } else {
            if ($record->created_at > date('Y-m-d')){
                \MemberRankModel::reduceMemberRank($postMember->uuid,\MemberRankModel::FIELD_PRAIZE);
            }
            $record->delete();
            if ($post->like_num > 0){
                $post->decrement('like_num');
            }
        }
        \PostUserLikeModel::clearCacheByAff(\PostUserLikeModel::TYPE_POST, $aff);
        return [true,'已取消点赞',false];
    }


    /**
     * 评论点赞/取消点赞
     * @throws \Exception
     */
     function likeComment($aff,$post_id, $commentId)
    {

        $record = \PostCommentUserLikeModel::getIdsById( $aff,$commentId);
        if (!$record) {
            $data = [
                'aff'        => $aff,
                'post_id'    => $post_id,
                'related_id' => $commentId,
                'created_at' => date('Y-m-d H:i:s'),
            ];
            \PostCommentUserLikeModel::create($data);
            PostCommentModel::where(['post_id'=>$commentId,'pid'=>$post_id])->increment('like_num');
            return [true,'评论点赞成功',true];
        } else {
            $record->delete();
            PostCommentModel::where(['post_id'=>$commentId,'pid'=>$post_id])->where('like_num','>',0)->decrement('like_num');

        }
        return [true,'已取消评论点赞',false];
    }

    /**
     * 帖子收藏/取消收藏
     * @throws \RedisException
     * @throws \Exception
     */
    public function togglefavoritePost($aff, $postId)
    {

        $record = \PostUserCollectModel::where('aff', $aff)
            ->where('type', \PostUserCollectModel::TYPE_POST)
            ->where('related_id', $postId)
            ->first();
        if (!$record) {
            $data = [
                'type'       => \PostUserCollectModel::TYPE_POST,
                'aff'        => $aff,
                'related_id' => $postId,
                'created_at'  => Carbon::now()->toDateTimeString(),
                'updated_at'  => Carbon::now()->toDateTimeString(),
            ];
            \PostUserCollectModel::create($data);
            PostModel::where(['id'=>$postId])->increment('favorite_num');
            return [true,'收藏成功',true];
        }
            $record->delete();

        return [true,'取消收藏成功',false];
    }


    /**
     *  // 解锁
     * @throws \RedisException
     * @throws \Exception
     */
    public function reward(MemberModel $member, $postId)
    {
        /** @var PostModel $post */
        $post = PostModel::queryBase()->find($postId);
        if (is_null($post)){
            throw new \Exception('此帖子不存在');
        }
        /** @var MemberModel $member */
        $member = MemberModel::firstAff($member->aff);
        /** @var MemberModel $peer */
        $peer = MemberModel::firstAff($post->aff);

        $amount = $post->price;

        if ((int)$post->aff === (int)$member->aff)
        {
            throw new \Exception('无需解锁自己的帖子');
        }

        //查看是否购买
        $exists = \PostRewardLogModel::onWriteConnection()->where('aff',$member->aff)->where('post_id',$postId)->exists();
        if ($exists){
            throw new \Exception('已经购买了,请勿重复购买');
        }

        if ($member->coins < $amount)
        {
            throw new \Exception('金币余额不足');
        }

        $data = [
            'aff'          => $member->aff,
            'aff_nickname' => $member->nickname,
            'thumb' => $member->thumb,
            'post_id'      => $post->id,
            'post_title'   => $post->title,
            'post_aff'     => $post->aff,
            'amount'       => $amount,
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ];
        return transaction(function () use ($member, $peer, $post, $amount, $data) {
            $total = $amount;
            $itOk = MemberModel::where([
                ['uid', '=', $member->uid],
                ['coins', '>=', $total],
            ])->update([
                'coins'       => \DB::raw("coins-{$total}"),
                'consumption' => \DB::raw("consumption+{$total}")
            ]);
            if (empty($itOk)) {
                throw new \Exception('扣款失败,请确认您的金币是否足够', 1008);
            }
            $tips = "[购买社区帖子]{$post->title}#金币： $total";
            $rs3 = \UsersCoinrecordModel::createForExpend('buyPost', $member->uid, $post->aff,
                $total,
                $post->id,
                0,
                0,
                0,
                null,
                $tips);
            //社区收益
            $peer->increment("post_coins", $amount);
            //社区总收益
            $peer->increment("total_post_coins", $amount);
            //进入排行榜
            \MemberRankModel::addMemberRank($peer->uuid,\MemberRankModel::FIELD_PROFIT,$amount);

            # 日志记录
            $itOK = \PostRewardLogModel::insert($data);
            test_assert($itOK, '解锁日志记录失败');
            # 解锁记录维护
             $post->increment('reward_num');
            # 解锁金额维护
             $post->increment('reward_amount', $amount);
            redis()->sAdd(sprintf(MemberModel::POSTS_PAID, $member->aff), $post->id);
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
                'product_id'            => (string)$post->id,
                'product_name'          => "社区帖子:" . $post->title,
                'coin_consume_amount'   => (int)$post->price,
                'coin_balance_before'   => (int)($member->coins),
                'coin_balance_after'    => (int)$member->coins - $total,
                'consume_reason_key'    => 'post_unlock',
                'consume_reason_name'   => '社区解锁',
                'order_id'              => (string)$rs3->id,
                'create_time'           => to_timestamp($rs3->addtime),
            ]);

            return true;
        });
    }

    /**
     * @throws \RedisException
     * @throws \Exception
     */
    public function listCommentsByPostId($postId, MemberModel $member)
    {
        PostCommentModel::setWatchUser($member);
        list($page,$limit) = QueryHelper::pageLimit();
        return cached(sprintf(PostCommentModel::POST_COMMENT_LIST_DETAIL_KEY,$postId,$page,$limit))
            ->group(PostCommentModel::POST_COMMENT_LIST_DETAIL_GROUP_KEY)
            ->chinese('评论列表')
            ->fetchPhp(function () use ($member,$postId,$page,$limit){
                return PostCommentModel::queryBase()->where(['post_id'=>$postId])
                    ->with('user:aff,uid,nickname,thumb,aff,expired_at,vip_level,uuid,sexType')
                    ->orderByDesc('is_top')
                    ->orderByDesc('id')
                    ->forPage($page,$limit)
                    ->get();
            });
    }

    /**
     * @throws \RedisException
     * @throws \Exception
     */
    public function listCommentsByPostIdNew(MemberModel $member, $postId)
    {
        PostCommentModel::setWatchUser($member);
        list($page,$limit) = QueryHelper::pageLimit();
        $post = PostModel::find($postId);
        test_assert($post,"此帖子不存在");
        return PostCommentModel::listCommentsByPostId($member,$postId, $post->aff, $page, $limit);
    }

    public function listCommentsByCommentId(MemberModel $member, $commentId, $page, $limit)
    {
        $comment = PostCommentModel::find($commentId);
        test_assert($comment,'此评论不存在');
        $post = PostModel::find($comment->post_id);
        test_assert($post,'此帖子不存在');
        return PostCommentModel::listCommentsByCommentId($member,$comment->id, $comment->post_id, $post->aff, $page, $limit);
    }


    /**
     * @param $post
     * @param $member
     * @param $content
     * @param $cityname
     * @return PostCommentModel
     * @throws \RedisException
     * @throws \Exception
     */
    public function createPostComment(PostModel $post, MemberModel $member, $content, $cityname='火星')
    {
        $status = PostCommentModel::STATUS_WAIT;
        //年卡及以上会员直接通过
//        if (in_array($member->vip_level,[\MemberModel::VIP_LEVEL_YEAR,\MemberModel::VIP_LEVEL_LONG]) && $member->is_vip){
//            $status = \PostCommentModel::STATUS_PASS;
//        }
        $data = [
            'post_id'       => $post->id,
            'pid'           => 0,
            'aff'           => $member->aff,
            'comment'       => $content,
            'status'        => $status,
            'refuse_reason' => '',
            'is_finished'   => 1,
            'ipstr'         => USER_IP,
            'is_top'        => PostCommentModel::TOP_NO,
            'cityname'      => $cityname,
            'created_at'    => date('Y-m-d H:i:s'),
        ];
        /** @var PostCommentModel $comment */
        $comment = PostCommentModel::create($data);
        if (is_null($comment)) {
            throw new \Exception('系统异常,异常码:1001');
        }
        //维护评论数量
        if ($status == PostCommentModel::STATUS_PASS){
            $post->increment('comment_num');
        }
        bg_run(function () use ($member, $content, $comment){
            //检查评论
            FilterService::checkPostComment($member, $content, $comment);
        });

        return $comment;
    }

    /**
     * @throws \Exception
     */
    public function createPostCommentNew(MemberModel $member, $id, $content, $cityname)
    {
        $aff = $member->aff;
        $post = PostModel::find($id);
        test_assert($post,'此帖子不存在');
        $status = PostCommentModel::STATUS_WAIT;
        //年卡及以上会员直接通过
//        if (in_array($member->vip_level,[\MemberModel::VIP_LEVEL_YEAR,\MemberModel::VIP_LEVEL_LONG]) && $member->is_vip){
//            $status = \PostCommentModel::STATUS_PASS;
//        }
        $data = [
            'post_id'       => $post->id,
            'pid'           => 0,
            'aff'           => $aff,
            'comment'       => $content,
            'status'        => $status,
            'refuse_reason' => '',
            'ipstr'         => USER_IP,
            'is_top'        => PostCommentModel::TOP_NO,
            'is_finished'   => PostCommentModel::FINISH_OK,
            'cityname'      => $cityname,
            'created_at'    => \Carbon\Carbon::now()
        ];
        $comment = PostCommentModel::create($data);
        test_assert($comment,'系统异常,异常码:1001');

        bg_run(function () use ($member, $content, $comment){
            //检查评论
            FilterService::checkPostComment($member, $content, $comment);
        });

        return true;
    }

    public function createComComment(MemberModel $member, $commentId, $content, $cityname)
    {
        $aff = $member->aff;
        $parentComment = PostCommentModel::getCommentById($member, $commentId);
        test_assert($parentComment,'此评论不存在');

        $data = [
            'post_id'       => $parentComment->post_id,
            'pid'           => $parentComment->id,
            'aff'           => $aff,
            'comment'       => $content,
            'status'        => PostCommentModel::STATUS_WAIT,
            'refuse_reason' => '',
            'ipstr'         => USER_IP,
            'is_top'        => PostCommentModel::TOP_NO,
            'is_finished'   => PostCommentModel::FINISH_OK,
            'cityname'      => $cityname,
            'created_at'    => \Carbon\Carbon::now(),
        ];
        $comment = PostCommentModel::create($data);
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

    public function postIncomeList($page, $limit){
        return \PostRewardLogModel::memberPostIncome($page, $limit);
    }

    /**
     * @throws \RedisException
     */
    public function listRank(MemberModel $member , $rankBy, $rankTime, $num){
        MemberModel::setWatchUser($member);
        $list = cached(sprintf('community:rank:bak:%s:%s:%s',$rankBy,$rankTime,$num))
            ->group('community:rank:list')
            ->chinese('社区排行榜')
            ->fetchPhp(function () use ($rankBy,$rankTime,$num){
                $users = \MemberRankModel::getRankByRedis($rankBy,$rankTime,$num);
                if (!$users){
                    return [];
                }
                $uuidArr = array_keys($users);
                return MemberModel::selectRaw('aff,uid,nickname,thumb,aff,expired_at,vip_level,uuid,sexType')
                    ->whereIn('uuid',$uuidArr)
                    ->get()
                    ->map(function ($item) use ($users){
                        $item->val = max($users[$item->uuid], 0);
                        return $item;
                    });
            },600);
        if (!is_array($list)){
            $list = $list->toArray();
        }
        array_multisort(array_column($list, 'val'), SORT_DESC, $list);
        return $list;
    }

    public function getRecommendMember(MemberModel $member){
        $aff = setting('community.rec.aff','');
        $recMember = null;
        if ($aff){
            MemberModel::setWatchUser($member);
            $recMember = cached('community:rec:member:'.$aff)
                ->fetchPhp(function () use ($aff){
                    return MemberModel::selectRaw('aff,uid,person_signnatrue,nickname,thumb,aff,expired_at,vip_level,uuid,sexType')
                        ->where('aff',$aff)
                        ->first();
                });
        }
        return $recMember;
    }

    public function getRecommendPost(MemberModel $member){
        $postIds = setting('community.rec.post','');
        $postIds = explode(',',$postIds);
        $list = [];
        if ($postIds){
            $list = cached('community:rec:post:'.implode(':',$postIds))
                ->fetchPhp(function () use ($postIds){
                    return PostModel::queryBase()
                        ->whereIn('id',$postIds)
                        ->get();
                });
            if($list){
                collect($list)->each(function (PostModel $item) use ($member){
                    $item->watchByUser($member);
                });
            }
        }
        return $list;
    }

    public function incomeList(MemberModel $member, $type){
        list($page,$limit) = QueryHelper::pageLimit();
        $list = PostModel::incomeList($member->aff,$type,$page,$limit);
        return $this->formatPost($list);
    }

    public function unlockList($id){
        list($page,$limit) = QueryHelper::pageLimit();
        $post = PostModel::queryBase()->where('id',$id)->first();
        test_assert($post,'帖子不存在');
        return \PostRewardLogModel::unlockList($id,$page,$limit);
    }
}