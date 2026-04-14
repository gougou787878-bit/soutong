<?php

namespace service;

use AdsModel;
use DB;
use MemberModel;
use PornCategoryModel;
use PornCommentModel;
use PornGameModel;
use PornLikeModel;
use PornPayModel;
use PrivilegeModel;
use UsersProductPrivilegeModel;
use WordNoticeModel;

class PornGameService extends \AbstractBaseService
{
    public function formatItem($data, $watchByMember = null)
    {
        /** @var PornGameModel $datum */
        $data->addHidden([
            '_id',
            'category_title',
            'real_like_count',
            'real_view_count',
            'sort',
            'status',
            'buy_num',
            'buy_coins',
        ]);
        if (!$data->is_pay){
            $data->download_url = '';
            $data->password = '';
            $data->hide_content = '';
        }
        if ($data->refresh_at){
            $data->created_at = $data->refresh_at;
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

    public function construct(MemberModel $member, $page, $limit){
        $ads = [];
        if ($page == 1) {
            //广告banner
            $ads = AdService::getADsByPosition(AdsModel::POSITION_PORN_GAME_BANNER);
        }
        //分类
        $list = PornCategoryModel::getListByCat($member, $page);
        collect($list)->map(function ($item) {
            $item['list'] = $this->v2format($item['list']);
            return $item;
        });
        return [
            'ads' => $ads,
            'list' => $list
        ];
    }

    public function list(MemberModel $member, $id, $sort, $page, $limit){
        if ($id){
            $series = PornCategoryModel::findById($id);
            test_assert($series, '分类不存在');
        }
        $list = PornGameModel::list($id, $sort, $page, $limit);
        return $this->v2format($list);
    }

    public function detail(MemberModel $member, $id){
        PornGameModel::setWatchUser($member);
        /** @var PornGameModel $game */
        $game = PornGameModel::detail($id);
        test_assert($game, '黄游不存在');
        $detail = $this->formatItem($game, $member);
        if ($detail->is_pay && $detail->content){
            $arr = explode(PHP_EOL, $detail->content);
            $arr = array_filter($arr);
            $res = [];
            foreach ($arr as $val){
                $tmp = explode('|', $val);
                $res[] = [
                    'name' => trim($tmp[0]),
                    'val' => trim($tmp[1])
                ];
            }
            $detail->hide_content = $res;
        }
        //上一篇
        $previous = PornGameModel::next($id, 1);
        //下一篇
        $next = PornGameModel::next($id, 2);
        //推荐
        $recommend_list = PornGameModel::detail_recommend($game->id, $game->category_id);
        $ads = AdService::getADsByPosition(AdsModel::POSITION_PORN_GAME_DETAIL);
        jobs([PornGameModel::class, 'incrByView'], [$id]);
        //用户免费次数
        $free_num = UsersProductPrivilegeModel::hasPrivilege(USER_PRIVILEGE, PrivilegeModel::RESOURCE_TYPE_PORN_GAME, PrivilegeModel::PRIVILEGE_TYPE_UNLOCK);
        return [
            'ads'    => $ads,
            'detail' => $detail,
            'previous' => $previous ? $this->formatItem($previous) : null,
            'next' => $next ? $this->formatItem($next) : null,
            'recommend_list' => $this->v2format($recommend_list),
            'free_num' => (int)$free_num
        ];
    }

    public function listCommentsByPornId(MemberModel $member, $porn_id, $page, $limit)
    {
        $novel = PornGameModel::detail($porn_id);
        test_assert($novel,"黄游不存在");
        return PornCommentModel::listCommentsByPornId($member, $porn_id, $page, $limit);
    }

    public function listCommentsByCommentId(MemberModel $member, $commentId, $page, $limit)
    {
        $comment = PornCommentModel::find($commentId);
        test_assert($comment,'此评论不存在');
        $post = PornGameModel::detail($comment->porn_id);
        test_assert($post,'此黄游不存在');
        return PornCommentModel::listCommentsByCommentId($comment->id, $comment->porn_id, $page, $limit);
    }

    public function createComComment(MemberModel $member, $commentId, $content, $cityName)
    {
        $parentComment = PornCommentModel::getCommentById($commentId);
        test_assert($parentComment,'此评论不存在');

        $data = [
            'porn_id'       => $parentComment->porn_id,
            'pid'           => $parentComment->id,
            'aff'           => $member->aff,
            'comment'       => $content,
            'status'        => PornCommentModel::STATUS_WAIT,
            'refuse_reason' => '',
            'ipstr'         => USER_IP,
            'is_top'        => PornCommentModel::TOP_NO,
            'cityname'      => $cityName,
            'created_at'    => \Carbon\Carbon::now(),
            'updated_at'    => \Carbon\Carbon::now(),
        ];
        $comment = PornCommentModel::create($data);
        test_assert($comment,'系统异常,异常码:1001');

        return true;
    }

    public function createPostComment(MemberModel $member, $id, $content, $cityname)
    {
        $game = PornGameModel::detail($id);
        test_assert($game,'此黄游不存在');

        $data = [
            'porn_id'       => $game->id,
            'pid'           => 0,
            'aff'           => $member->aff,
            'comment'       => $content,
            'status'        => PornCommentModel::STATUS_WAIT,
            'refuse_reason' => '',
            'ipstr'         => USER_IP,
            'is_top'        => PornCommentModel::TOP_NO,
            'cityname'      => $cityname,
            'created_at'    => \Carbon\Carbon::now(),
            'updated_at'    => \Carbon\Carbon::now(),
        ];
        $comment = PornCommentModel::create($data);
        test_assert($comment,'系统异常,异常码:1001');

        return true;
    }

    public function like(MemberModel $member, $porn_id){
        $game = PornGameModel::detail($porn_id);
        test_assert($game, '黄游不存在');
        $record = PornLikeModel::where('aff', $member->aff)->where('porn_id', $porn_id)->first();
        $flag = transaction(function () use ($member, $record, $game, $porn_id) {
            $key = sprintf(PornLikeModel::PORN_GAME_LIKE_SET, $member->aff);
            if ($record) {
                $itOk1 = PornLikeModel::where(['aff' => $member->aff, 'porn_id' => $porn_id])->delete();
                test_assert($itOk1, '操作失败');
                //从redis里面移除
                redis()->sRem($key, $porn_id);
                bg_run(function () use ($porn_id){
                    PornGameModel::decrByFavorite($porn_id);
                });
                return 0;
            } else {
                $record = PornLikeModel::create([
                    'aff' => $member->aff,
                    'porn_id' => $porn_id,
                    'created_at' => \Carbon\Carbon::now(),
                    'updated_at' => \Carbon\Carbon::now(),
                ]);
                test_assert($record, '操作失败');
                //添加进redis
                redis()->sAdd($key, $porn_id);
                bg_run(function () use ($porn_id){
                    PornGameModel::incrByFavorite($porn_id);
                });
                return 1;
            }
        });
        return [
            'msg' => $flag ? '点赞成功' : '取消点赞成功',
            'is_like' => $flag,
        ];
    }

    //收藏列表
    public function listLike(MemberModel $member, $page, $limit){
        $list = PornLikeModel::list($member->aff, $page, $limit);
        if ($list) {
            $list = $this->v2format($list);
        }
        return $list;
    }

    public function buy(MemberModel $member, $porn_id, $type){
        return transaction(function () use ($member, $porn_id, $type) {
            /** @var PornGameModel $game */
            $game = PornGameModel::detail($porn_id);
            test_assert($game, '黄游不存在');
            $porn_type = $game->type;
            if ($porn_type == PornGameModel::TYPE_FREE){
                throw new \Exception('此黄游无需解锁');
            }
            $is_pay = PornPayModel::hasBuy($member->aff, $porn_id);
            if ($is_pay) {
                throw new \Exception('请勿重复购买');
            }

            //解锁金币数
            $game_coins = $game->coins;
            if ($type == 1){//次数解锁
                test_assert(PornGameModel::TYPE_MIX == $porn_type, '解锁类型不正确');
                $value = UsersProductPrivilegeModel::hasPrivilege(USER_PRIVILEGE,
                    PrivilegeModel::RESOURCE_TYPE_PORN_GAME,
                    PrivilegeModel::PRIVILEGE_TYPE_UNLOCK);
                test_assert($value, '免费解锁次数不足');
                //扣除次数
                UsersProductPrivilegeModel::hasPrivilegeAndSubTimePrivilege(
                    USER_PRIVILEGE,
                    PrivilegeModel::RESOURCE_TYPE_PORN_GAME,
                    PrivilegeModel::PRIVILEGE_TYPE_UNLOCK,
                    $member->aff
                );
            }else{//金币解锁
                test_assert(in_array($porn_type, [PornGameModel::TYPE_COINS, PornGameModel::TYPE_MIX]), '解锁类型不正确');
                test_assert($game_coins, "黄游解锁配置错误");
                $total = $game_coins;
                //$zhe = UsersProductPrivilegeModel::getUserDiscount(PrivilegeModel::RESOURCE_TYPE_PORN_GAME)/100;
                $zhe = 1;
                $total = ceil($total * $zhe);
                $tips = "[购买黄游]{$game->name}";
                if($zhe!=1){
                    $tips = "[折扣{$zhe}购买黄游]#{$game->name}";
                }
                if ($member->coins < $game_coins) {
                    throw new \Exception('金币余额不足', 1008);
                }

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

                $rs3 = \UsersCoinrecordModel::createForExpend('buyPornGame', $member->uid, 0,
                    $total,
                    0,
                    0,
                    0,
                    0,
                    null,
                    $tips);

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
                    'product_id'            => (string)$game->id,
                    'product_name'          => "黄游:" . $game->name,
                    'coin_consume_amount'   => (int)$game->coins,
                    'coin_balance_before'   => (int)($member->coins),
                    'coin_balance_after'    => (int)$member->coins - $total,
                    'consume_reason_key'    => 'game_purchase',
                    'consume_reason_name'   => '黄游购买',
                    'order_id'              => (string)$rs3->id,
                    'create_time'           => to_timestamp($rs3->addtime),
                ]);
            }

            # 日志记录
            $data = [
                'aff'        => $member->aff,
                'porn_id'    => $game->id,
                'type'       => $type,
                'status'     => 1,
                'coins'      => $type == 2 ? $game_coins : 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $isOK = PornPayModel::create($data);
            test_assert($isOK, '购买黄游记录失败');
            # 打赏记录维护
            $isOK = $game->update([
                'buy_num'   => \DB::raw('buy_num + 1'),
                'buy_coins' => \DB::raw("buy_coins + {$game_coins}"),
                'buy_fake'  => \DB::raw("buy_fake + 5"),
            ]);
            test_assert($isOK, '购买次数和金额统计失败');
            //记录写进redis
            redis()->sAdd(sprintf(PornPayModel::PRON_GAME_BUY_SET_AFF, $member->aff), $game->id);
            //清流用户缓存
            MemberModel::clearFor($member);

            return true;
        });
    }

    public function listBuy(MemberModel $member, $page, $limit){
        $list = PornPayModel::listBuy($member->aff, $page, $limit);
        return $this->v2format($list, $member);
    }

    public function listSearch($word, $page, $limit){
        $list = PornGameModel::listBySearch($word, $page, $limit);
        return $this->v2format($list);
    }

    public function listTag($tag, $sort, $page, $limit){
        $list = PornGameModel::listByTag($tag, $sort, $page, $limit);
        return $this->v2format($list);
    }
}