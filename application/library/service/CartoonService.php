<?php


namespace service;

use AdsModel;
use CartoonCategoryModel;
use CartoonChaptersModel;
use CartoonCommentLikesModel;
use CartoonCommentModel;
use CartoonLikeModel;
use CartoonModel;
use CartoonPayModel;
use DB;
use MemberModel;
use UsersCoinrecordModel;

class CartoonService
{
    public function construct(MemberModel $member, $page, $limit){
        $ads = [];
        if ($page == 1) {
            //广告banner
            $ads = AdService::getADsByPosition(AdsModel::POSITION_CARTOON_BANNER);
        }
        //分类
        $list = CartoonCategoryModel::getListByCat($member, $page);
        return [
            'ads' => $ads,
            'list' => $list
        ];
    }


    public function getList(MemberModel $member, $category_id, $sort, $page, $limit)
    {
        return CartoonModel::list($category_id, $sort, $page, $limit);
    }

    public function search(MemberModel $member, $kwy, $page, $limit)
    {
        return CartoonModel::search($kwy, $page, $limit);
    }

    public function tagList(MemberModel $member, $tag, $sort, $page, $limit)
    {
        return CartoonModel::tagList($tag, $sort, $page, $limit);
    }

    public function getDetail(MemberModel  $member, $id,$selected = 1,$is_pay=0){
        CartoonModel::setWatchUser($member);
        /** @var CartoonModel $cartoon */
        $cartoon = CartoonModel::queryBase()->where('id', $id)->first();
        test_assert($cartoon, '作品已下架');

        CartoonChaptersModel::setWatchUser($member);
        $items  = CartoonChaptersModel::queryBase()
            ->where('pid', $cartoon->id)
            ->orderBy('sort')
            ->get();
        test_assert($items->toArray(), '资源不存在');

        $created_at =  $cartoon->created_at;
        $full_title =  $cartoon->title;
        $video_id = 0;
        $source = '';
        $preview_video = '';
        $data['is_pay'] = 0;
        $data['coins'] = 0;
        if($items){
            $videos = [];
            /** @var CartoonChaptersModel $value */
            foreach ($items as $value){
                $item = [];
                $item['sort'] =  $value->sort;
                $item['id'] =  $value->id;
                $item['name'] = '第'.$value->sort.'集';
                $item['selected'] =  false;
                if($selected  ==  $value->sort){
                    $video_id = $value->id;
                    $cartoon->is_series > 0 &&  $full_title .= $item['name'];
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
        $data['id'] =  $cartoon->id;
        $data['title'] =  $full_title;
        !empty($cartoon->tags) &&  $data['tags'] = explode(',',$cartoon->tags);
        $data['play_count'] =  $cartoon->play_count;
        $data['like_count'] =  $cartoon->like_count;
        $data['created_at'] =  date('Y-m-d H:i:s',strtotime($created_at));
        $data['is_like'] =  $cartoon->is_favorite;
        $data['com_count'] =  $cartoon->com_count;
        $data['preview_video'] =  $preview_video;
        $data['source'] =  $source;
        $data['video_id'] =  $video_id;
        $data['cover_full'] =  $cartoon->cover_full;

        if($cartoon->is_series){
            $data['videos'] =  $videos;
        }
        //浏览次数
        jobs([CartoonModel::class, 'incrView'], [$id]);

        return $data;
    }

    /**
     * 获取详情推荐
     * @param $tags
     * @param $id 原创ID
     * @return array
     */
    public function getRecommendByTags($tags,$id){
        // 安全检查，防止 $tags 不是数组
        if (!is_array($tags) || empty($tags)) {
            return collect(); // 返回空集合，避免缓存空内容或报错
        }
        $tagStr = implode(',',$tags);
        $str = md5($tagStr.$id);
        $key = "original:detail:recommend:{$str}";
        $items = cached($key)
            ->expired(900)
            ->serializerPHP()
            ->fetch(function () use ($id, $tagStr) {
                return CartoonModel::queryBase()
                    ->select(CartoonModel::SHOW_COLUMS)
                    ->where('id','<>',$id)
                    ->whereRaw("match(tags) against(? in boolean mode)", [$tagStr])
                    ->limit(6)
                    ->orderByDesc('id')
                    ->get()->map(function (CartoonModel $item){
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
    public function like(MemberModel $member, $id)
    {
        $cartoon = CartoonModel::find($id);
        test_assert($cartoon,"动漫不存在");
        /** @var CartoonLikeModel $cartoon */
        $record = CartoonLikeModel::getIdsById($member->aff, $id);
        if (!$record) {
            $data = [
                'cartoon_id'        => $id,
                'aff'       => $member->aff
            ];
            CartoonLikeModel::create($data);
            $cartoon->increment('like_count');
            return [true,'点赞成功',true];
        } else {
            $record->delete();
            if ($cartoon->like_count > 0){
                $cartoon->decrement('like_count');
            }
        }
        return [true,'已取消点赞',false];
    }

    public function list_like(MemberModel $member, $page, $limit){
        return CartoonLikeModel::listLike($member->aff, $page, $limit);
    }

    /**
     * 创建评论
     * @throws \Exception
     */
    public function createComment(MemberModel $member, $id, $content, $cityname)
    {
        $aff = $member->aff;
        $cartoon = CartoonModel::find($id);
        test_assert($cartoon,'此动漫不存在');
        $status = CartoonCommentModel::STATUS_WAIT;
        $data = [
            'cartoon_id'    => $cartoon->id,
            'pid'           => 0,
            'aff'           => $aff,
            'comment'       => $content,
            'status'        => $status,
            'refuse_reason' => '',
            'ipstr'         => USER_IP,
            'cityname'      => $cityname
        ];
        $comment = CartoonCommentModel::create($data);
        test_assert($comment,'系统异常,异常码:1001');

        $cartoon->increment('com_count');
        return true;
    }
    public function createComComment(MemberModel $member, $commentId, $content, $cityname)
    {
        CartoonCommentModel::setWatchUser($member);
        $aff = $member->aff;
        $parentComment = CartoonCommentModel::getCommentById($member, $commentId);
        test_assert($parentComment,'此评论不存在');

        $data = [
            'cartoon_id'    => $parentComment->cartoon_id,
            'pid'           => $parentComment->id,
            'aff'           => $aff,
            'comment'       => $content,
            'status'        => CartoonCommentModel::STATUS_WAIT,
            'refuse_reason' => '',
            'ipstr'         => USER_IP,
            'cityname'      => $cityname,
        ];
        $comment = CartoonCommentModel::create($data);
        test_assert($comment,'系统异常,异常码:1001');
//        bg_run(function () use ($member, $content, $comment){
//            //检查评论
//            FilterService::checkPostComment($member, $content, $comment);
//        });

        return true;
    }

    /**
     * @throws \RedisException
     * @throws \Exception
     */
    public function listCommentsByPostId(MemberModel $member, $postId, $page, $limit)
    {
        CartoonCommentModel::setWatchUser($member);
        $post = CartoonModel::find($postId);
        test_assert($post,"动漫不存在");
        return CartoonCommentModel::listCommentsByPostId($member, $postId, $page, $limit);
    }


    /**
     * 评论点赞/取消点赞
     * @throws \Exception
     */
    function likeComment(MemberModel $member, $commentId)
    {

        $record = CartoonCommentLikesModel::getIdsById($member->uid, $commentId);
        if (!$record) {
            $data = [
                'uid'           => $member->uid,
                'comment_id'    => $commentId,
            ];
            CartoonCommentLikesModel::create($data);
            CartoonCommentModel::where(['id'=> $commentId])->increment('like_num');
            return [true,'评论点赞成功',true];
        } else {
            $record->delete();
            CartoonCommentModel::where(['id'=>$commentId])
                ->where('like_num','>',0)->decrement('like_num');

        }
        return [true,'已取消评论点赞',false];
    }

    //购买动漫
    public function buy(MemberModel $member, $video_id)
    {
        /** @var CartoonChaptersModel $cartoonChapter */
        $cartoonChapter =  CartoonChaptersModel::queryBase()->where('id', $video_id)->first();
        test_assert($cartoonChapter, '动漫不存在');
        /** @var CartoonModel $cartoon */
        $cartoon = CartoonModel::queryBase()->where('id', $cartoonChapter->pid)->first();
        test_assert($cartoon, '动漫不存在');

        $total = $cartoonChapter->coins;
        if ($total <= 0) {
            test_assert(false, '当前定价暂未设置');
        }

        if ($member->coins <= 0 || $total > $member->coins) {
            test_assert(false, '余额不足，不能进行支付');
        }

        $has_pay = CartoonPayModel::hasBuy($member->uid, $video_id);
        if ($has_pay) {
            return $this->getDetail($member, $cartoon->id, $cartoonChapter->sort, $has_pay);
        }

        DB::beginTransaction();
        $where[] = ['uid', '=', $member->uid];
        $where[] = ['coins', '>=', $total];
        $is_ok = MemberModel::where($where)->decrement('coins', $total);
        //金币用户减
        if (!$is_ok) {
            throw new \Exception('余额不足，不能进行支付');
        }
        CartoonPayModel::create([
            'uid'        => $member->uid,
            'coins'      => $total,
            'video_id'      => $video_id,
            'cartoon_id'      => $cartoon->id,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        $tips = "[购买原创]{$cartoon->title}";
        if($cartoon->is_series > 0){
            $str = '第'.$cartoon->sort.'集';
            $tips .=$str;
        }
        //记录日志
        $rs3 = UsersCoinrecordModel::createForExpend('buyCartoon', $member->uid, $member->uid,
            $total,
            $cartoon->id,
            0,
            0,
            0,
            null,
            $tips);
        $cartoonChapter->increment('pay_count');
        $cartoon->increment('pay_count');
        $data =  $this->getDetail($member, $cartoon->id, $cartoonChapter->sort,1);
        DB::commit();

        MemberModel::clearFor($member);

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
            'product_id'            => (string)$cartoon->id,
            'product_name'          => "动漫:" . $cartoon->title,
            'coin_consume_amount'   => (int)$total,
            'coin_balance_before'   => (int)($member->coins),
            'coin_balance_after'    => (int)$member->coins - $total,
            'consume_reason_key'    => 'cartoon_unlock',
            'consume_reason_name'   => '动漫解锁',
            'order_id'              => (string)$rs3->id,
            'create_time'           => to_timestamp($rs3->addtime),
        ]);

        return  $data;
    }

    public function list_buy(MemberModel $member, $page, $limit){
        return CartoonPayModel::getUserBuyData($member->uid, $page, $limit);
    }
}