<?php


namespace service;

use helper\QueryHelper;
use OriginalCommentModel;
use OriginalModel;
use OriginalVideoModel;

class OriginalService
{
    public function getList($tab, $kwy, $sort='newest')
    {
        list($page,$limit) = QueryHelper::pageLimit();
        if ($sort == 'rand'){
            return OriginalModel::randList($kwy, $page, $limit);
        }else{
            return OriginalModel::list($tab, $kwy, $sort, $page, $limit);
        }
    }


    public static function getDetail($id,$selected = 1,$is_pay=0){
        $member = request()->getMember();
        OriginalModel::setWatchUser($member);
        /** @var OriginalModel $original */
        $original = OriginalModel::queryBase()->where('id',$id)->first();
        if(!$original){
            throw new \Exception('作品已下架');
        }
        OriginalVideoModel::setWatchUser($member);
        $items  = OriginalVideoModel::queryBase()
            ->where('pid',$original->id)
            ->orderBy('sort')
            ->get();
        if(!$items->toArray()){
            throw new \Exception('资源不存在');
        }
        $created_at =  $original->created_at;
        $full_title =  $original->title;
        $video_id = 0;
        $source = '';
        $preview_video = '';
        $data['is_pay'] = 0;
        $data['coins'] = 0;
        if($items){
            $videos = [];
            /** @var OriginalVideoModel $value */
            foreach ($items as $value){
                $item = [];
                $item['sort'] =  $value->sort;
                $item['id'] =  $value->id;
                $item['name'] = '第'.$value->sort.'集';
                $item['selected'] =  false;
                if($selected  ==  $value->sort){
                    $video_id = $value->id;
                    $original->is_series > 0 &&  $full_title .= $item['name'];
                    $created_at =  $value->created_at;
                    $data['is_pay'] = $value->is_pay;
                    if($value->is_pay || $is_pay){
                        $source = getPlayUrl($value->source);
                    }else{
                        $preview_video = url_video_short($value->source);
                        if($preview_video){
                            $preview_video = $preview_video.'&seconds=10';
                        }
                    }
                    if($member->is_vip && $value->coins <=0){
                        $data['is_pay']  = 1;
                        $source = getPlayUrl($value->source);
                        $preview_video = '';
                    }

                    $item['selected'] =  true;

                    $data['is_free'] = $value->is_free;
                    $data['coins'] =  $value->coins;
                }

                $videos[] = $item;
            }
        }
        $data['id'] =  $original->id;
        $data['title'] =  $full_title;
        !empty($original->tags) &&  $data['tags'] = explode(',',$original->tags);
        $data['play_count'] =  $original->play_count;
        $data['like_count'] =  $original->like_count;
        $data['created_at'] =  date('Y-m-d H:i:s',strtotime($created_at));
        $data['is_like'] =  $original->is_like;
        $data['com_count'] =  $original->com_count;
        $data['preview_video'] =  $preview_video;
        $data['source'] =  $source;
        $data['video_id'] =  $video_id;
        $data['cover_full'] =  $original->cover_full;

        if($original->is_series){
            $data['videos'] =  $videos;
        }
        //浏览次数
        jobs([OriginalModel::class, 'incrView'], [$id]);

        return $data;

    }

    /**
     * 获取详情推荐
     * @param $tags
     * @param $id 原创ID
     * @return array
     */
    public static function getRecommendByTags($tags,$id){
        $tagStr = implode(',',$tags);
        $str = md5($tagStr.$id);
        $key = "original:detail:recommend:{$str}";
        $items = cached($key)
            ->expired(900)
            ->serializerPHP()
            ->fetch(function () use ($id, $tagStr) {
                return OriginalModel::queryBase()
                    ->select(OriginalModel::SHOW_COLUMS)
                    ->where('id','<>',$id)
                    ->whereRaw("match(tags) against(? in boolean mode)", [$tagStr])
                    ->limit(6)
                    ->orderByDesc('id')
                    ->get()->map(function (OriginalModel $item){
                        $item->setHidden(['tags']);
                        return $item;
                    });
            });
        return $items;
    }


    /**
     * 原创点赞/取消点赞
     * @throws \RedisException
     * @throws \Exception
     */
    public function likeOriginal($aff, $id)
    {
        $original = OriginalModel::find($id);
        test_assert($original,"原创不存在");
        /** @var \OriginalUserLikeModel $record */
        $record = \OriginalUserLikeModel::getIdsById( $aff, $id);
        if (!$record) {
            $data = [
                'original_id'        => $id,
                'uid'       => $aff
            ];
            \OriginalUserLikeModel::create($data);
            $original->increment('like_count');
            return [true,'点赞成功',true];
        } else {
            $record->delete();
            if ($original->like_count > 0){
                $original->decrement('like_count');
            }
        }
        return [true,'已取消点赞',false];
    }
    /**
     * 创建评论
     * @throws \Exception
     */
    public function createComment(\MemberModel $member, $id, $content, $cityname)
    {
        $aff = $member->aff;
        $original = OriginalModel::find($id);
        test_assert($original,'此帖子不存在');
        $status = OriginalCommentModel::STATUS_PASS;
        $data = [
            'original_id'       => $original->id,
            'pid'           => 0,
            'aff'           => $aff,
            'content'       => $content,
            'status'        => $status,
            'refuse_reason' => '',
            'ipstr'         => USER_IP,
            'cityname'      => $cityname
        ];
        $comment = OriginalCommentModel::create($data);
        test_assert($comment,'系统异常,异常码:1001');

        $original->increment('com_count');
        return true;
    }
    public function createComComment(\MemberModel $member, $commentId, $content, $cityname)
    {
        $aff = $member->aff;
        $parentComment = OriginalCommentModel::getCommentById($member, $commentId);
        test_assert($parentComment,'此评论不存在');

        $data = [
            'original_id'       => $parentComment->original_id,
            'pid'           => $parentComment->id,
            'aff'           => $aff,
            'content'       => $content,
            'status'        => OriginalCommentModel::STATUS_WAIT,
            'refuse_reason' => '',
            'ipstr'         => USER_IP,
            'cityname'      => $cityname,
        ];
        $comment = OriginalCommentModel::create($data);
        test_assert($comment,'系统异常,异常码:1001');
        bg_run(function () use ($member, $content, $comment){
            //检查评论
            FilterService::checkPostComment($member, $content, $comment);
        });

        return true;
    }


    /**
     * @throws \RedisException
     * @throws \Exception
     */
//    public static  function listComments($id, \MemberModel $member)
//    {
//        list($page,$limit) = QueryHelper::pageLimit();
//        return cached(sprintf(\OriginalCommentModel::ORIGINAL_COMMENT_LIST_DETAIL_KEY,$id,$page,$limit))
//            ->group(\OriginalCommentModel::ORIGINAL_COMMENT_LIST_DETAIL_GROUP_KEY)
//            ->chinese('评论列表')
//            ->fetchPhp(function () use ($member,$id,$page,$limit){
//                return \OriginalCommentModel::queryBase()
//                    ->select(['id','content','aff','created_at','like_num'])
//                    ->where(['original_id'=>$id])
//                    ->with('user:aff,uid,nickname,thumb,aff,expired_at,vip_level,uuid')
//                    ->orderByDesc('id')
//                    ->forPage($page,$limit)
//                    ->get()
//                    ->map(function (\OriginalCommentModel $item)use($member){
//                        $member && $item->watchByUser($member);
//                        return $item;
//                    });
//            });
//    }

    /**
     * @throws \RedisException
     * @throws \Exception
     */
    public static function listCommentsByPostId(\MemberModel $member,$postId)
    {
        OriginalCommentModel::setWatchUser($member);
        list($page,$limit) = QueryHelper::pageLimit();
        $post = OriginalModel::find($postId);
        test_assert($post,"此帖子不存在");
        return OriginalCommentModel::listCommentsByPostId($member,$postId, $post->aff, $page, $limit);
    }


    /**
     * 评论点赞/取消点赞
     * @throws \Exception
     */
    function likeComment($aff,$id, $commentId)
    {

        $record = \OriginalCommentUserLikeModel::getIdsById( $aff,$commentId);
        if (!$record) {
            $data = [
                'aff'        => $aff,
                'original_id'    => $id,
                'related_id' => $commentId
            ];
            \OriginalCommentUserLikeModel::create($data);
            OriginalCommentModel::where(['id'=>$commentId])->increment('like_num');
            return [true,'评论点赞成功',true];
        } else {
            $record->delete();
            OriginalCommentModel::where(['id'=>$commentId])
                ->where('like_num','>',0)->decrement('like_num');

        }
        return [true,'已取消评论点赞',false];
    }

    /**
     * 购买原创
     * @param $comics_id
     * @return bool
     * @throws \Throwable
     */
    static function buyOriginal($video_id)
    {

        $member = request()->getMember();
        /** @var OriginalVideoModel $originalVideo */
        $originalVideo =  OriginalVideoModel::queryBase()->where('id',$video_id)->first();
        /** @var OriginalModel $original */
        $original = $originalVideo->original;

        if (is_null($originalVideo)) {
            throw new \Exception("视频不存在");
        }
        if ($originalVideo->coins <= 0) {
            throw new \Exception('当前定价暂未设置');
        }
        $total = $originalVideo->coins;
        if ($member->coins <= 0) {
            throw new \Exception('余额不足，不能进行支付');
        }
        if ($total > $member->coins) {
            throw new \Exception('余额不足，不能进行支付');
        }

        $has_pay = \OriginalPayModel::hasBuy($member->uid, $video_id);
        if ($has_pay) {
            $data =  self::getDetail($original->id,$originalVideo->sort,$has_pay);
            return $data;
        }
        try {
            \DB::beginTransaction();
            $where[] = ['uid', '=', $member->uid];
            $where[] = ['coins', '>=', $total];
            $is_ok = \MemberModel::where($where)->decrement('coins', $total);
            //金币用户减
            if (!$is_ok) {
                throw new \Exception('余额不足，不能进行支付');
            }
            \OriginalPayModel::create([
                'uid'        => $member->uid,
                'coins'      => $total,
                'video_id'      => $video_id,
                'original_id'      => $original->id,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $tips = "[购买原创]{$original->title}";
            if($original->is_series > 0){
                $str = '第'.$originalVideo->sort.'集';
                $tips .=$str;
            }
            //记录日志
            $rs3 = \UsersCoinrecordModel::createForExpend('buyOriginal', $member->uid, $member->uid,
                $total,
                $originalVideo->id,
                0,
                0,
                0,
                null,
                $tips);
            $originalVideo->increment('pay_count');
            $original->increment('pay_count');
            $data =  self::getDetail($original->id,$originalVideo->sort,1);
            \DB::commit();
        } catch (\Throwable $exception) {
            \DB::rollBack();
            throw  $exception;
        }
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
            'product_id'            => (string)$original->id,
            'product_name'          => "购买原创:" . $original->title,
            'coin_consume_amount'   => (int)$total,
            'coin_balance_before'   => (int)($member->coins),
            'coin_balance_after'    => (int)$member->coins - $total,
            'consume_reason_key'    => 'original_purchase',
            'consume_reason_name'   => '原创购买',
            'order_id'              => (string)$rs3->id,
            'create_time'           => to_timestamp($rs3->addtime),
        ]);

        return  $data;

    }


}