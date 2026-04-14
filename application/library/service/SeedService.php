<?php

namespace service;

use MemberModel;
use SeedFavoritesModel;
use SeedPostBuyLogModel;
use SeedPostModel;
use DB;
use SeedPostCommentModel;
use SeedPostMediaModel;
use SeedLikeModel;
use AdsModel;
use UsersProductPrivilegeModel;

class SeedService
{
    const SORT_NAV = [
        ['title' => '最新', 'type' => 'new'],
        ['title' => '推荐', 'type' => 'recommend'],//推荐 (3个月内点赞/收藏)
        ['title' => '最热', 'type' => 'hot'],//最热 (本月浏览量)
        ['title' => '正在看', 'type' => 'see'],
    ];

    public function list_post($member, $sort, $page, $limit)
    {
        SeedPostModel::setWatchUser($member);
        $banners = $page == 1 ? AdService::getADsByPosition(AdsModel::POSITION_SEED_LIST) : [];
        if ($sort == 'see'){
            $posts = SeedPostModel::listSeeSeedPost($page, $limit);
        }else{
            $posts = SeedPostModel::list_post($sort, $page, $limit);
        }
        return [
            'posts'   => $this->formatPost($posts),
            'banners' => $banners
        ];
    }

    public function post_detail(MemberModel $member, $id)
    {
        SeedPostModel::setWatchUser($member);
        $rs = SeedPostModel::post_detail($id);
        test_assert($rs, '帖子已被删除');
        jobs([SeedPostModel::class, 'incrViewNum'], [$id, 10]);
        jobs([SeedPostModel::class, 'addSee'], [$id]);
        return $this->formatPost([$rs], $member)[0];
    }

    public function buy(MemberModel $member, int $id)
    {
        SeedPostModel::setWatchUser($member);
        $post = SeedPostModel::post_detail($id);
        $post = $this->formatPost([$post])[0];
        test_assert($post, '帖子已被删除');
        if ($post->is_pay) {
            return $post->link;
        }
        test_assert($post->type == SeedPostModel::TYPE_COIN, '非金币类型');

        $total = $post->coins;
        //$zhe = UsersProductPrivilegeModel::getUserDiscount(\PrivilegeModel::RESOURCE_TYPE_COINS_SEED)/100;
        $zhe = 1;
        $total = ceil($total * $zhe);
        $tips = "[购买种子]{$post->title}";
        if($zhe!=1){
            $tips = "[折扣{$zhe}购买种子]#{$post->title}";
        }
        if ($member->coins < $total) {
            test_assert(false, '金币余额不足', 1008);
        }
        //开始购买
        return transaction(function () use ($member, $post, $total, $tips) {
            $itOk = MemberModel::where([
                ['uid', '=', $member->uid],
                ['coins', '>=', $total],
            ])->update([
                'coins'       => DB::raw("coins-{$total}"),
                'consumption' => DB::raw("consumption+{$total}")
            ]);
            if (empty($itOk)) {
                throw new \Exception('扣款失败,请确认您的金币是否足够', 1008);
            }

            $rs3 = \UsersCoinrecordModel::createForExpend('buySeedPost', $member->uid, 0,
                $total,
                0,
                0,
                0,
                0,
                null,
                $tips);

            $incr_coins = DB::raw('payed_coins+' . $post->coins);
            $isOK = $post->increment('payed_ct', 1, ['payed_coins' => $incr_coins]);
            test_assert($isOK, '解锁次数/金币统计失败');
            SeedPostBuyLogModel::buy_seed($member->aff, $post->id, $post->coins);
            //清流用户缓存
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
                'product_name'          => "购买种子:" . $post->title,
                'coin_consume_amount'   => (int)$post->coins,
                'coin_balance_before'   => (int)($member->coins),
                'coin_balance_after'    => (int)$member->coins - $total,
                'consume_reason_key'    => 'sourct_buy_seed',
                'consume_reason_name'   => '种子购买',
                'order_id'              => (string)$rs3->id,
                'create_time'           => to_timestamp($rs3->addtime),
            ]);

            return $post->link;
        });
    }

    public function list_first_comments(MemberModel $member, $postId, $page, $limit)
    {
        $post = SeedPostModel::post_detail($postId);
        test_assert($post, '此帖子不存在');
        SeedPostCommentModel::setWatchUser($member);
        return SeedPostCommentModel::listCommentsByFirst($member, $postId, $page, $limit);
    }

    public function list_second_comments(MemberModel $member, $commentId, $page, $limit)
    {
        $comment = SeedPostCommentModel::getDetail($commentId);
        test_assert($comment, '此评论不存在');
        $post = SeedPostModel::post_detail($comment->seed_id);
        test_assert($post, '此帖子不存在');
        return SeedPostCommentModel::listCommentsBySecond($member, $comment->id, $comment->seed_id, $page, $limit);
    }

    public function create_com_comment(MemberModel $member, $commentId, $content, $medias, $cityname)
    {
        $parentComment = SeedPostCommentModel::getDetail($commentId);
        test_assert($parentComment, '此评论不存在');

        $post = SeedPostModel::post_detail($parentComment->seed_id);
        test_assert($post, '帖子不存在');

        $data = [
            'topic_id'      => $post->topic_id,
            'seed_id'       => $parentComment->seed_id,
            'pid'           => $parentComment->id,
            'aff'           => $member->aff,
            'comment'       => $content,
            'status'        => SeedPostCommentModel::STATUS_WAIT,
            'refuse_reason' => '',
            'ipstr'         => USER_IP,
            'is_top'        => SeedPostCommentModel::TOP_NO,
            'cityname'      => $cityname,
            'created_at'    => date('Y-m-d H:i:s'),
        ];
        $comment = SeedPostCommentModel::create($data);
        test_assert($comment, '系统异常,异常码:1001');

        $isFinished = SeedPostCommentModel::FINISH_OK;
        foreach ($medias as $val) {
            $arr = explode('.', $val['media_url']);
            $media_url = str_replace("\\", '', $val['media_url']);
            $cover = str_replace("\\", '', $val['cover'] ?? '');
            $media = [
                'aff'          => $member->aff,
                'relate_type'  => SeedPostMediaModel::TYPE_RELATE_COMMENT,
                'pid'          => $comment->id,
                'media_url'    => $media_url,
                'thumb_width'  => $val['thumb_width'],
                'thumb_height' => $val['thumb_height'],
                'created_at'   => date('Y-m-d H:i:s'),
            ];
            if (end($arr) == 'mp4') {
                $media['cover'] = $cover ? $cover : $media_url;
                $media['type'] = SeedPostMediaModel::TYPE_VIDEO;
                $media['status'] = SeedPostMediaModel::STATUS_NO;
                $isFinished = SeedPostModel::FINISHED_NO;
            } else {
                $media['cover'] = $cover ? $cover : $media_url;
                $media['type'] = SeedPostMediaModel::TYPE_IMG;
                $media['status'] = SeedPostMediaModel::STATUS_OK;
            }
            $media = SeedPostMediaModel::create($media);
            if ($media->type == SeedPostMediaModel::TYPE_VIDEO) {
                $comment->increment('video_num');
            } else {
                $comment->increment('photo_num');
            }
        }
        // 判断是否有问题
        $comment->update(['is_finished' => $isFinished]);
    }

    public function create_post_comment(MemberModel $member, $id, $content, $medias, $cityname)
    {
        $post = SeedPostModel::post_detail($id);
        test_assert($post, '此帖子不存在');

        $data = [
            'topic_id'      => $post->topic_id,
            'seed_id'       => $post->id,
            'pid'           => 0,
            'aff'           => $member->aff,
            'comment'       => $content,
            'status'        => SeedPostCommentModel::STATUS_WAIT,
            'refuse_reason' => '',
            'ipstr'         => USER_IP,
            'is_top'        => SeedPostCommentModel::TOP_NO,
            'cityname'      => $cityname,
            'created_at'    => date('Y-m-d H:i:s'),
        ];
        $comment = SeedPostCommentModel::create($data);
        test_assert($comment, '系统异常,异常码:1001');

        $isFinished = SeedPostCommentModel::FINISH_OK;
        foreach ($medias as $val) {
            $arr = explode('.', $val['media_url']);
            $media = [
                'aff'         => $member->aff,
                'relate_type' => SeedPostMediaModel::TYPE_RELATE_COMMENT,
                'pid'         => $comment->id,
                'media_url'   => $val['media_url'],
                'created_at'  => date('Y-m-d H:i:s'),
            ];
            if (end($arr) == 'mp4') {
                $media['type'] = SeedPostMediaModel::TYPE_VIDEO;
                $media['status'] = SeedPostMediaModel::STATUS_NO;
                $isFinished = SeedPostCommentModel::FINISH_NO;
            } else {
                $media['type'] = SeedPostMediaModel::TYPE_IMG;
                $media['status'] = SeedPostMediaModel::STATUS_OK;
            }
            $media = SeedPostMediaModel::create($media);
            if ($media->type == SeedPostMediaModel::TYPE_VIDEO) {
                $comment->increment('video_num');
            } else {
                $comment->increment('photo_num');
            }
        }

        // 判断是否有问题
        $comment->update(['is_finished' => $isFinished]);
    }

    // 帖子点赞/取消点赞
    protected function likePost(MemberModel $member, $postId)
    {
        $post = SeedPostModel::post_detail($postId);
        test_assert($post, '帖子已经被删除');
        return SeedLikeModel::toggle(SeedLikeModel::TYPE_POST, $member->aff, $postId, function ($rs) use ($post) {
            if ($rs) {
                SeedPostModel::incrementLikeNum($post->id);
                jobs([SeedPostModel::class, 'incrementRecSort'], [$post->id]);
                //test_assert($isOk, '维护帖子点赞数据异常');
            } else {
//                SeedPostModel::where('id', $post->id)
//                    ->where('like_ct', '>', 0)
//                    ->decrement('like_ct');
                SeedPostModel::decrementLikeNum($post->id);
            }
            return ['is_like'=> $rs ? 1 : 0,'msg'=> $rs ? '点赞成功':'取消点赞成功'];
        });
    }

    // 评论点赞/取消点赞
    protected function likeComment($member, $commentId)
    {
        $comment = SeedPostCommentModel::getDetail($commentId);
        test_assert($comment, '评论已经被删除');
        return SeedLikeModel::toggle(SeedLikeModel::TYPE_COMMENT, $member->aff, $commentId, function ($rs) use ($comment) {
            if ($rs) {
                $isOk = $comment->increment('like_num');
                test_assert($isOk, '维护帖子点赞数据异常');
            } else {
                SeedPostCommentModel::where('id', $comment->id)
                    ->where('like_num', '>', 0)
                    ->decrement('like_num');
            }
            return ['is_like' => $rs ? 1 : 0];
        });
    }

    // 帖子或者评论点赞
    public function like(MemberModel $member, $type, $id)
    {
        return $type == 'post' ? $this->likePost($member, $id) : $this->likeComment($member, $id);
    }

    // 帖子收藏/取消收藏
    public function favorite(MemberModel $member, $postId)
    {
        $post = SeedPostModel::post_detail($postId);
        test_assert($post, '帖子已经被删除');
        $uid = $member->uid;
        $record = SeedFavoritesModel::where('uid', $uid)
            ->where('zy_id', $postId)
            ->first();
        $key = sprintf(SeedPostModel::KEY_AFF_SEED_FAVORITE_SET, $uid);
        if (!$record) {
            $data = [
                'uid'        => $uid,
                'zy_id'      => $postId,
                'created_at' => \Carbon\Carbon::now(),
            ];
            $isOk = SeedFavoritesModel::create($data);
            test_assert($isOk,"系统异常");
            redis()->sAdd($key, $postId);
            jobs([SeedPostModel::class, 'incrementFavoriteNum'], [$postId]);
            jobs([SeedPostModel::class, 'incrementRecSort'], [$postId]);
            return ['is_favorite' => 1,'msg' => '收藏成功'];
        } else {
            $isOk = $record->delete();
            test_assert($isOk,"系统异常");
            redis()->sRem($key, $postId);
            jobs([SeedPostModel::class, 'decrementFavoriteNum'], [$postId]);
            return ['is_favorite' => 0,'msg' => '取消收藏成功'];
        }
    }

    public function listMyFavoriteSeeds(MemberModel $member, $page, $limit)
    {
        SeedPostModel::setWatchUser($member);
        $postData = SeedFavoritesModel::listMyFavoriteSeeds($member->uid, $page, $limit);
        return $this->formatPost($postData);
    }

    public function list_buy_post(MemberModel $member, $page, $limit)
    {
        SeedPostModel::setWatchUser($member);
        $list = SeedPostBuyLogModel::list_buy_post($member->aff, $page, $limit);
        return $this->formatPost($list);
    }

    public function list_like_post(MemberModel $member, $page, $limit)
    {
        SeedPostModel::setWatchUser($member);
        $list = SeedLikeModel::list_like_post($member->aff, $page, $limit);
        return $this->formatPost($list);
    }

    public function list_search_post($member, $word, $page, $limit)
    {
        SeedPostModel::setWatchUser($member);
        $list = SeedPostModel::list_search_post($word, $page, $limit);
        return $this->formatPost($list);
    }

    public function formatPost($postData){
        if(!$postData){
            return [];
        }
        foreach ($postData as &$post){
            $medias = $post->medias;
            if ($medias) {
                $medias = collect($medias)->map(function ($media) use ($post) {
                    if ($media->type == \SeedPostMediaModel::TYPE_IMG) {
                        $media->media_url_full = url_cover($media->media_url);
                    } elseif ($media->type == \SeedPostMediaModel::TYPE_VIDEO) {
                        $extension = pathinfo($media->media_url, PATHINFO_EXTENSION);
                        if ($extension == 'm3u8') {
                            $media->media_url_full = getPlayUrl($media->media_url, true);
                            if ($post->is_pay){
                                $media->media_url_full = getPlayUrl($media->media_url);
                            }
                        } else {
                            return null;//非法视频或 没有切完 等下放出去
                        }
                    }

                    return $media;
                })->filter()->values()->toArray();
            }
            //没有权限时 不返回下载地址和密钥
            if (!$post->is_pay) {
                $post->link = '';
                $post->secret = '';
                $post->extract_code = '';
            }
            unset($post->medias);//取消关系
            $post->medias = $medias;
            //隐藏不需要的字段
            $post->addHidden(['like_ct', 'fake_like_ct', 'view_ct', 'fake_view_ct', 'comment_ct']);
        }
        return $postData;
    }
}