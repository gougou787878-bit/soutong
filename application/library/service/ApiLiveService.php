<?php

namespace service;

use Constant;
use DB;
use LiveFavoritesModel;
use LiveLikeModel;
use LivePayModel;
use LiveThemeModel;
use LiveModel;
use AdsModel;
use LiveRelatedModel;
use MemberCoinrecordModel;
use MemberModel;
use PrivilegeModel;
use UserFavoritesLogModel;
use Throwable;
use LiveCommentModel;
use UsersProductPrivilegeModel;
use WordNoticeModel;

class ApiLiveService
{
    public static function list_nav()
    {
        return LiveThemeModel::list_nav()
            ->map(function ($item) {
                $item->type = 1;
                return $item;
            })->prepend([
                'id'      => 0,
                'name'    => '热门推荐',
                'type' => 0
            ]);
    }

    public static function rec_live($page, $limit)
    {
        $themes = LiveThemeModel::list_rec($page, $limit);
        $banners = $page == 1 ? AdService::getADsByPosition(AdsModel::POSITION_LIVE_BANNER) : [];
        return ['banners' => $banners, 'themes' => $themes];
    }

    public function list_live( $id, $page, $limit)
    {
        $banners = $page == 1 ? AdService::getADsByPosition(AdsModel::POSITION_LIVE_BANNER) : [];
        $lives = LiveModel::list_live($id, $page, $limit);
        $lives = $this->v2format($lives);

        return ['banners' => $banners, 'lives' => $lives];
    }

    public function recommend($member, $id, $page, $limit)
    {
        if ($page == 2){
            return [];
        }
        $ids = LiveRelatedModel::list_theme_ids($id);
        $list = LiveModel::list_recommend($id, $ids, $page, $limit);
        return $this->v2format($list);
    }

    public function detail(MemberModel $member, $id)
    {
        LiveModel::setWatchUser($member);
        $live = LiveModel::detail($id);
        test_assert($live, '该直播已被删除');
        $this->formatItem($live, $member);
        $banners = AdService::getADsByPosition(AdsModel::POSITION_LIVE_DETAIL);
        jobs([LiveModel::class, 'incrViewCt'], [$live->id]);
        return [
            'live'    => $live,
            'banners' => $banners,
        ];
    }

    public function favorite(MemberModel $member, $id){
        /** @var LiveModel $live */
        $live = LiveModel::detail($id);
        test_assert($live, '直播不存在');

        /** @var LiveFavoritesModel $record */
        $record = LiveFavoritesModel::where('aff', $member->aff)->where('live_id', $id)->first();
        $flag = transaction(function () use ($member, $record, $live, $id) {
            $key = sprintf(LiveFavoritesModel::MEMBER_FAVORITE_LIVE_SET, $member->aff);
            if ($record) {
                $itOk1 = $record->delete();
                test_assert($itOk1, '操作失败');
                jobs([LiveModel::class, 'decrByFavorite'], [$id]);
                //redis
                redis()->sRem($key, $id);
                return 0;
            } else {
                $record = LiveFavoritesModel::create([
                    'aff' => $member->aff,
                    'live_id' => $live->id,
                    'created_at' => \Carbon\Carbon::now(),
                    'updated_at' => \Carbon\Carbon::now(),
                ]);
                test_assert($record, '操作失败');
                jobs([LiveModel::class, 'incrByFavorite'], [$id]);
                //redis
                redis()->sAdd($key, $id);
                return 1;
            }
        });
        return [
            'msg' => $flag ? '收藏成功' : '取消收藏成功',
            'is_favorite' => $flag,
        ];
    }

    //收藏列表
    public function listFavorite(MemberModel $member, $page, $limit)
    {
        $list = LiveFavoritesModel::liveList($member->aff, $page, $limit);
        if ($list) {
            $list = $this->v2format($list);
        }
        return $list;
    }

    public function formatItem($data, $watchByMember = null)
    {
        /** @var LiveModel $data */
        $data->addHidden([
            'favorite_oct',
            'real_view_count',
            'real_like_count',
            'real_favorite_count',
            'pay_ct',
            'pay_coins',
            'reward_ct',
            'reward_coins',
            'sort',
            'created_at',
            'updated_at',
        ]);
        if (!is_null($watchByMember)) {
            $data->watchByUser($watchByMember);
        }
        //直播地址
        $data->use_hls = null;
        if ($data->is_pay && $data->hls){
            $data->hls = LiveModel::process_hls($data->hls);
            if (count($data->hls) > 1){
                $data->use_hls = $data->hls[count($data->hls) - 2];
            }else{
                $data->use_hls = $data->hls[0];
            }
        }else{
            $data->hls = [];
        }

        return $data;
    }

    public function v2format($items, $watchByMember = null)
    {
        if (empty($items)) {
            return [];
        }
        $lists = [];
        foreach ($items as $datum) {
            $lists[] = $this->formatItem($datum, $watchByMember);
        }

        return $lists;
    }

    /**
     * @throws Throwable
     */
    public function buy(MemberModel $member, $id)
    {
        /** @var LiveModel $live */
        $live = LiveModel::detail($id);
        test_assert($live, '直播不存在');
        if ($live->type != LiveModel::TYPE_COINS || $live->coins <= 0){
            throw new \Exception('此直播不支持购买');
        }
        $livePay = LivePayModel::hasBuy($member->aff, $id);
        test_assert(!$livePay, '请勿重复购买');
        //$rs = redis()->sIsMember(sprintf(LiveModel::LIVE_PAY_SET, $id), $member->aff);
        //test_assert(!$rs, '本场直播已付费，请勿重复购买');

        //$zhe = UsersProductPrivilegeModel::getUserDiscount(PrivilegeModel::RESOURCE_TYPE_LIVE_COINS) / 100;
        $zhe = 1;
        $total = ceil($live->coins * $zhe);
        if ($member->coins < $total) {
            throw new \Exception('金币余额不足', 1008);
        }
        return transaction(function () use ($member, $live, $total, $zhe) {
            $tips = sprintf("[购买直播]%s", $live->username);
            if($zhe !=1 ){
                $tips = sprintf("[折扣%s购买直播]%s", $zhe, $live->username);
            }
            //扣款
            $itOk = MemberModel::where([
                ['uid', '=', $member->uid],
                ['coins', '>=', $total],
            ])->update([
                'coins'       => DB::raw("coins-{$total}"),
                'consumption' => DB::raw("consumption+{$total}")
            ]);
            if (!$itOk) {
                throw new \Exception('扣款失败,请确认您的余额是否足够', 1008);
            }

            $reachcoin = $total;
            //记录金币日志
            $rs3 = \UsersCoinrecordModel::createForExpend('buyLive', $member->uid, 0,
                $total,
                $live->id,
                0,
                0,
                0,
                null,
                $tips);

            # 日志记录
            $data = [
                'aff'        => $member->aff,
                'live_id'   => $live->id,
                'coins'      => $total,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $isOK = LivePayModel::create($data);
            test_assert($isOK, '购买记录写入失败');

            //记录购买到集合
            //redis()->sAdd(sprintf(LiveModel::LIVE_PAY_SET, $live->id), $member->aff);
            //购买记录维护
            jobs([LiveModel::class, 'incrPayCount'], [$live->id, $total]);
            //清流用户缓存
            \MemberModel::clearFor($member); 

            //返回播放地址
            $hls = LiveModel::process_hls($live->hls);
            if (count($hls) > 1){
                $use_hls = $hls[count($hls) - 2];
            }else{
                $use_hls = $hls[0];
            }

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
                'product_id'            => (string)$live->id,
                'product_name'          => "直播:" . $live->username,
                'coin_consume_amount'   => (int)$live->coins,
                'coin_balance_before'   => (int)($member->coins),
                'coin_balance_after'    => (int)$member->coins - $total,
                'consume_reason_key'    => 'live_unlock',
                'consume_reason_name'   => '直播解锁',
                'order_id'              => (string)$rs3->id,
                'create_time'           => to_timestamp($rs3->addtime),
            ]);

            return [
                'hls'       => $hls,
                'use_hls'   => $use_hls,
            ];
        });
    }

    public function list_buy(MemberModel $member, $page, $limit){
        //购买列表
        $list = LivePayModel::listBuy($member->aff, $page, $limit);
        return $this->v2format($list);
    }

    public function search($member, $word, $page, $limit)
    {
        $lives = LiveModel::search($word, $page, $limit);
        return $this->v2format($lives);
    }

    public function listCommentsByLiveId(MemberModel $member, $live_id, $page, $limit)
    {
        $novel = LiveModel::detail($live_id);
        test_assert($novel,"直播不存在");
        return LiveCommentModel::listCommentsByLiveId($member, $live_id, $page, $limit);
    }

    public function listCommentsByCommentId(MemberModel $member, $commentId, $page, $limit)
    {
        $comment = LiveCommentModel::find($commentId);
        test_assert($comment,'此评论不存在');
        $post = LiveModel::detail($comment->live_id);
        test_assert($post,'此直播不存在');
        return LiveCommentModel::listCommentsByCommentId($comment->id, $comment->live_id, $page, $limit);
    }

    public function createComComment(MemberModel $member, $commentId, $content, $cityName)
    {
        $parentComment = LiveCommentModel::getCommentById($commentId);
        test_assert($parentComment,'此评论不存在');

        $data = [
            'live_id'       => $parentComment->live_id,
            'pid'           => $parentComment->id,
            'aff'           => $member->aff,
            'comment'       => $content,
            'status'        => LiveCommentModel::STATUS_WAIT,
            'refuse_reason' => '',
            'ipstr'         => USER_IP,
            'is_top'        => LiveCommentModel::TOP_NO,
            'cityname'      => $cityName,
            'created_at'    => \Carbon\Carbon::now(),
            'updated_at'    => \Carbon\Carbon::now(),
        ];
        $comment = LiveCommentModel::create($data);
        test_assert($comment,'系统异常,异常码:1001');

        return true;
    }

    public function createPostComment(MemberModel $member, $id, $content, $cityname)
    {
        $live = LiveModel::detail($id);
        test_assert($live,'此直播不存在');

        $data = [
            'live_id'       => $live->id,
            'pid'           => 0,
            'aff'           => $member->aff,
            'comment'       => $content,
            'status'        => LiveCommentModel::STATUS_WAIT,
            'refuse_reason' => '',
            'ipstr'         => USER_IP,
            'is_top'        => LiveCommentModel::TOP_NO,
            'cityname'      => $cityname,
            'created_at'    => \Carbon\Carbon::now(),
            'updated_at'    => \Carbon\Carbon::now(),
        ];
        $comment = LiveCommentModel::create($data);
        test_assert($comment,'系统异常,异常码:1001');

        return true;
    }

    /**
     * @throws Throwable
     */
    public function reward(MemberModel $member, $id, $coins)
    {
        /** @var LiveModel $live */
        $live = LiveModel::detail($id);
        test_assert($live, '该直播已被删除');
        test_assert($member->coins >= $coins, '金币余额不足', 1008);
        transaction(function () use ($member, $live, $coins) {
            $tips = sprintf("[直播打赏]%s", $live->username);

            //扣款
            $itOk = MemberModel::where([
                ['uid', '=', $member->uid],
                ['coins', '>=', $coins],
            ])->update([
                'coins'       => DB::raw("coins-{$coins}"),
                'consumption' => DB::raw("consumption+{$coins}")
            ]);
            if (!$itOk) {
                throw new \Exception('扣款失败,请确认您的余额是否足够', 1008);
            }

            //记录金币日志
            $rs3 = \UsersCoinrecordModel::createForExpend('rewardLive', $member->uid, 0,
                $coins,
                $live->id,
                0,
                0,
                0,
                null,
                $tips);
            //购买记录维护
            jobs([LiveModel::class, 'incrRewardCount'], [$live->id, $coins]);
            //清流用户缓存
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
                'product_id'            => (string)$live->id,
                'product_name'          => "直播打赏:" . $live->username,
                'coin_consume_amount'   => (int)$coins,
                'coin_balance_before'   => (int)($member->coins),
                'coin_balance_after'    => (int)$member->coins - $coins,
                'consume_reason_key'    => 'live_tip',
                'consume_reason_name'   => '直播打赏',
                'order_id'              => (string)$rs3->id,
                'create_time'           => to_timestamp($rs3->addtime),
            ]);

        });
    }

    public function like(MemberModel $member, $id){
        /** @var LiveModel $live */
        $live = LiveModel::detail($id);
        test_assert($live, '直播不存在');
        /** @var LiveLikeModel $record */
        $record = LiveLikeModel::where('aff', $member->aff)->where('live_id', $id)->first();
        $flag = transaction(function () use ($member, $record, $live, $id) {
            $key = sprintf(LiveLikeModel::MEMBER_LIVE_LIKE_SET, $member->uid);
            if ($record) {
                $itOk1 = $record->delete();
                test_assert($itOk1, '操作失败');
                jobs([LiveModel::class, 'decrByLike'], [$id]);
                //redis
                redis()->sRem($key, $id);
                return 0;
            } else {
                $record = LiveLikeModel::create([
                    'aff' => $member->aff,
                    'live_id' => $live->id,
                    'created_at' => \Carbon\Carbon::now(),
                    'updated_at' => \Carbon\Carbon::now(),
                ]);
                test_assert($record, '操作失败');
                jobs([LiveModel::class, 'incrByLike'], [$id]);
                //redis
                redis()->sAdd($key, $id);
                return 1;
            }
        });
        return [
            'msg' => $flag ? '点赞成功' : '取消点赞成功',
            'is_like' => $flag,
        ];
    }
}