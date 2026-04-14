<?php

namespace service;

use PcTabModel;
use PcMvModel;
use MemberModel;
use PcCommentModel;
use CommentModel;
use \tools\RedisService;

class PcMvService
{

    public function tabDetail($tab_id){
        return PcTabModel::getDetail($tab_id);
    }

    public function homeMvs($sort, $page, $limit)
    {
        //第一页获取配置视频
        if ($sort == 'recommend'){
            if ($page <= 1){
                //获取配置视频
                $data = PcMvModel::getRecommendData($sort,$limit);
                $data['list'] = $this->v2format($data['list']);
                return $data;
            }
        }
        $data = PcMvModel::listMvs(0,'',$sort, $page, $limit);

        $data['list'] = $this->v2format($data['list']);

        return $data;
    }

    public function listMvs($tab_id, $sort, $page, $limit)
    {
        /** @var PcTabModel $tab */
        $tab = PcTabModel::getDetail($tab_id);
        test_assert($tab, '导航不存在');

        $tags = str_replace(',', ' ', $tab->tags_str);
        if ($sort == 'recommend'){
            $data = PcMvModel::listRecommend($tab_id, $page, $limit);
        }else{
            $data = PcMvModel::listMvs($tab_id, $tags, $sort, $page, $limit);
        }

        $data['list'] = $this->v2format($data['list']);
        if ($tab_id == 5 && $sort == 'new'){
            error_log(var_export($data, true), 3, APP_PATH . '/storage/logs/web-total.log');
        }

        return $data;
    }

    public function getMvDetail($mvId,MemberModel $member = null)
    {
        $mv = PcMvModel::getDetail($mvId);
        test_assert($mv, '视频已经被删除');

        return [
            'detail' => $this->formatItem($mv, $member),
            'prev'   => PcMvModel::prevMv($mvId),
            'next'   => PcMvModel::nextMv($mvId)
        ];
    }

    // 推荐一般不会翻页 直接默认第一页
    public function listRecommendMvs($mvId)
    {
        // 获取相同结构的数据进行推荐
        $mv = PcMvModel::getDetail($mvId);
        test_assert($mv, '视频已被删除');

        return $this->v2format(PcMvModel::listRecommendMvs($mvId));
    }

    public function listMvComments($mvId, $page, $limit)
    {
        return PcCommentModel::listComments($mvId, $page, $limit);
    }

    public function favorite(MemberModel $member, $mvId)
    {
        /** @var \MvModel $mv */
        $mv= \MvModel::queryWithUser()->where('id', $mvId)->first();
        test_assert($mv,'视频不存在');

        $_mvUser = $mv->user;
        $creator_id = $mv->uid;
        $uid = $member->uid;

        $has = \UserLikeModel::where(['uid' => $uid, 'mv_id' => $mvId])->exists();
        if ($has) {
            \MemberModel::incrMultiLine([
                $creator_id => ['fabulous_count' => -1], //更新 $creator_id 用户的 fabulous_count - 1
                $uid        => ['likes_count' => -1], //更新 $uid 用户的 likes_count - 1
            ]);
            \MvModel::where('id', $mvId)->decrement('like', 1);
            \UserLikeModel::where(['uid' => $uid, 'mv_id' => $mvId])->delete();
            bg_run(function () use ($mvId){
                \MvTotalModel::incrLike($mvId, -1);
            });

            redis()->sRem(\MemberModel::REDIS_USER_LIKING_LIST . $uid, $mvId); // 点赞记录
            redis()->zIncrBy(\MvModel::REDIS_USER_LIKE_TODAY_COUNT, -1, date('Ymd')); // 每日点赞数
            (new RankingService())->incInviteByDay(-1, $creator_id);

            $result = ['is_favorite' => 0,$msg = '取消点赞成功'];
        } else {
            \MemberModel::incrMultiLine([
                $creator_id => ['fabulous_count' => 1],
                $uid        => ['likes_count' => 1],
            ]);
            \MvModel::where('id', $mvId)->increment('like', 1);
            \UserLikeModel::create(['uid' => $uid, 'mv_id' => $mvId]);
            bg_run(function () use ($mvId){
                \MvTotalModel::incrLike($mvId);
            });
            \MessageModel::createMessage($member->uuid, $_mvUser->uuid, "[{$member->nickname}]赞了您的视频~", $mv->title, $mvId,
                \MessageModel::TYPE_MV_LIKE);

            redis()->sAdd(\MemberModel::REDIS_USER_LIKING_LIST . $uid, $mvId);
            redis()->zIncrBy(\MvModel::REDIS_USER_LIKE_TODAY_COUNT, 1, date('Ymd'));
            (new RankingService())->incInviteByDay(1, $creator_id);
            (new TopCreatorService())->incrLike($creator_id);//视频点赞排行统计
            $result = ['is_favorite' => 1,$msg = '点赞成功'];
        }

        return $result;
    }

    public function comment(MemberModel $member, $mvId, $cId, $content)
    {
        /** @var PcMvModel $mv */
        $mv = PcMvModel::queryWithUser()->where('id','=',$mvId)->first();
        test_assert($mv,'视频不存在或已下架处理');

        PcCommentModel::createComment($member, $mv, $cId, $content);
        return true;
    }

    public function listFavorite(MemberModel $member, $page, $limit)
    {
        $list = \UserLikeModel::listMvFavorite($member, $page, $limit);
        return $this->v2format($list,$member);
    }

    public function listSearch($tab_id, $word, $page, $limit)
    {
        $tags = null;
        if ($tab_id){
            /** @var PcTabModel $tab */
            $tab = PcTabModel::queryBase()->where('tab_id',$tab_id)->first();
            test_assert($tab,'导航不存在');
            $tags = $tab->tags_str;
        }
        $list = PcMvModel::listSearch($tags, $word, $page, $limit);
        return $this->v2format($list);
    }

    public function commentLike(MemberModel $member, $id)
    {
        $comment = PcCommentModel::getDetail($id);
        test_assert($comment, '评论已经被删除');

        $has = RedisService::sIsMember(CommentModel::REDIS_COMMENT_LIKED . $member->uuid, $id);
        if ($has) {
            RedisService::redis()->sRem(CommentModel::REDIS_COMMENT_LIKED . $member->uuid, $id);
            CommentModel::query()->where('id', $id)->decrement('like_num');
        } else {
            RedisService::redis()->sAdd(CommentModel::REDIS_COMMENT_LIKED . $member->uuid, $id);
            CommentModel::query()->where('id', $id)->increment('like_num');
        }
        return true;
    }

    public function formatItem($datum, $watchByMember = null)
    {
        //构造user
        if ($datum->user == null){
            $datum->user = MemberModel::virtualByForDelele();
        }
        if ($watchByMember !== false) {
            $datum->watchByUser($watchByMember);
            if ($datum->user) {
                $datum->user->watchByUser($watchByMember);
            }
        }
        $datum->is_free = ($datum->coins <= 0) ? 1 : 0;
        $attributes = $datum->getAttributes();
        if (isset($attributes['full_m3u8']) && !empty($attributes['full_m3u8'])) {
            $m3u8 = $attributes['full_m3u8'];
        } else {
            $m3u8 = $attributes['m3u8'] ?? '';
        }
        //预览时间
        if ($datum->is_pay) {
            $preview = 0;
        }else{
            if ($datum->duration) {
                if ($datum->duration < 600) {
                    $preview = 10;
                } else {
                    $preview = 120;
                }
            }
        }
        $datum->paly_url = '/' . trim(parse_url($m3u8, PHP_URL_PATH), '/') . '?t=' . $preview;

        return $datum;
    }

    public function formatList($items, $watchByMember = null)
    {
        return $this->v2format($items, $watchByMember);
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
}