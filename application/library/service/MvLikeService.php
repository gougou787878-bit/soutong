<?php


namespace service;


use MemberModel;
use MessageModel;
use MvModel;
use MvTotalModel;
use tools\CurlService;
use tools\HttpCurl;
use tools\RedisService;
use UserLikeModel;

class MvLikeService extends \AbstractBaseService
{


    /**
     * 视频点赞
     * @param int $id
     * @param MemberModel $member
     * @return bool
     * @throws \Exception  如果视频不存在抛出异常
     */
    public function toggleLikeMv(MemberModel $member, int $id)
    {
        /** @var MvModel $mv */
        $mv= MvModel::queryWithUser()->where('id', $id)->first();
        test_assert($mv, '视频不存在');

        $_mvUser = $mv->user;
        $creator_id = $mv->uid;
        $uid = $member->uid;

        $has = UserLikeModel::where(['uid' => $uid, 'mv_id' => $id])->exists();
        if ($has) {
            MemberModel::incrMultiLine([
                $creator_id => ['fabulous_count' => -1], //更新 $creator_id 用户的 fabulous_count - 1
                $uid        => ['likes_count' => -1], //更新 $uid 用户的 likes_count - 1
            ]);
            MvModel::where('id', $id)->decrement('like', 1);
            UserLikeModel::where(['uid' => $uid, 'mv_id' => $id])->delete();
            bg_run(function () use ($id){
                MvTotalModel::incrLike($id, -1);
            });

            redis()->sRem(MemberModel::REDIS_USER_LIKING_LIST . $uid, $id); // 点赞记录
            //redis()->zIncrBy(MvModel::REDIS_USER_LIKE_TODAY_COUNT, -1, date('Ymd')); // 每日点赞数
            (new RankingService())->incInviteByDay(-1, $creator_id);

            $msg = '取消点赞成功';
        } else {
            MemberModel::incrMultiLine([
                $creator_id => ['fabulous_count' => 1],
                $uid        => ['likes_count' => 1],
            ]);
            MvModel::where('id', $id)->increment('like', 1);
            UserLikeModel::create(['uid' => $uid, 'mv_id' => $id]);

            jobs([MvTotalModel::class, 'incrLike'], [$id]);

            //最多点赞
//            if (!$mv->is_aw){
//                jobs([\MvModel::class, 'addWeekRank'], [\MvModel::WEEK_LIKE_TYPE, $mv->id]);
//            }

            //排行榜
            MvTotalModel::addCacheData($mv->id, $mv->is_aw, MvTotalModel::FIELD_LIKE, $mv->type, 1);

            //进入推荐队列
            if ($member->is_reg) {
                RedisService::redis()->lPush(MemberModel::RECOMMEND_USER_LIST, $member->uid);
            }

            MessageModel::createMessage($member->uuid, $_mvUser->uuid, "[{$member->nickname}]赞了您的视频~", $mv->title, $id,
                MessageModel::TYPE_MV_LIKE);

            redis()->sAdd(MemberModel::REDIS_USER_LIKING_LIST . $uid, $id);
            //redis()->zIncrBy(MvModel::REDIS_USER_LIKE_TODAY_COUNT, 1, date('Ymd'));
            (new RankingService())->incInviteByDay(1, $creator_id);
            (new TopCreatorService())->incrLike($creator_id);//视频点赞排行统计

            $msg = '点赞成功';
            $this->sendBrandGroup($member,$id);//白名单验证品宣资源发送
        }
        cached('')->clearGroup('like:'.$member->uid);
        cached('tb_ul:idv-' . $member->uid)->clearCached();

        //公司上报
        (new EventTrackerService(
            $member->oauth_type,
            $member->invited_by,
            $member->uid,
            $member->oauth_id,
            $_POST['device_brand'] ?? '',
            $_POST['device_model'] ?? ''
        ))->addTask([
            'event'                 => EventTrackerService::EVENT_VIDEO_LIKE,
            'video_title'           => $mv->title,
            'video_id'              => (string)$mv->id,
            'video_type_id'         => '',
            'video_type_name'       => '',
            'flag'                  => empty($has) ? 1 : 2,
        ]);

        return $msg;
    }

    /**
     * 品宣资源发送
     * @param $member
     * @param $mv_id
     */
    function sendBrandGroup($member, $mv_id)
    {
        $brand_uid = setting('brandgroup.uiddata', '');
        if (empty($brand_uid)) {
            return;
        }
        $brand_uid_data = explode('#', $brand_uid);
        if (!in_array($member->uid, $brand_uid_data)) {
            return;
        }
        /** @var MvModel $mvData */
        $mvData = MvModel::query()->where('id', $mv_id)->first();
        if (is_null($mvData)) {
            return;
        }
        $now = time();
        $postData = [
            'app_name'  => SYSTEM_ID,
            'title'     => $mvData->title,
            'rating'    => $mvData->rating,
            'm3u8'      => $mvData->m3u8 ? $mvData->m3u8 : $mvData->full_m3u8,
            'nickname'  => $mvData->user ? $mvData->user->nickname : '官方',
            'tags'      => is_string($mvData->tags) ? $mvData->tags : join(',',$mvData->tags),
            'timestamp' => $now,
            'sign'      => md5($now . config('upload.mp4_key'))
        ];
        $url = 'https://tomp4.91tv.tv/';
        trigger_log('brandData:' . var_export([$url, $postData], 1));
        $rs = (new CurlService())->curlPost($url, $postData);
        \AdminLogModel::addLog($member->uid, 'm3u8', var_export([$postData, $rs], 1));

    }


}