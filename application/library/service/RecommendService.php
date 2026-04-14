<?php
namespace service;

use MemberModel;
use MvModel;
use tools\RedisService;
use UserLikeModel;

/**
 * Class RecommendService
 * @package service
 */
class RecommendService
{
    /**
     * 获取首页列表
     * @throws \RedisException
     */
    public function getIndexList(MemberModel $member)
    {
        $uid = $member->uid;
        // 优先推荐点赞高的视频
        $recommendLike = RedisService::redis()->sMembers(sprintf(MvModel::RECOMMEND_USER_MV_LIST, $uid));
        $userWatchList = RedisService::redis()->sMembers(sprintf(MemberModel::REDIS_USER_WATCH_LIST_ALL, $uid));

        $list = array_diff($recommendLike, $userWatchList);
        if (count($list) < 20) {
            $newLikeMvIds = cached('mv:short:like')
                ->fetchPhp(function (){
                    return UserLikeModel::query()
                        ->where('type', MvModel::TYPE_SHORT)
                        ->orderBy('id', 'desc')
                        ->limit(200)
                        ->pluck('mv_id')
                        ->toArray();
                }, 600);
            $newLikeMvIds = array_unique($newLikeMvIds);
            $temps = array_diff($newLikeMvIds, $userWatchList);
            $temps = array_values($temps);
            foreach ($temps as $id) {
                if (count($list) >= 25) {
                    break;
                }
                $list[] = $id;
            }
        }

        if (count($list) < 21) { // 防止数量不够
            $list = [];
        }

        if (empty($list)){
            //缓存池中随机 200 去掉已经看了的
            $list = redis()->sRandMember(MvModel::RECOMMEND_FEE_KEY, 200);
            $list = array_diff($list, $userWatchList);
            $list = array_values($list);
            $list = array_slice($list, 0, 20);

            if (count($list) < 20){

                $id = RedisService::get(MvModel::REDIS_MV_MAX_ID);
                if (!$id) {
                    $video = MvModel::queryBase()->where('type', MvModel::TYPE_SHORT)->orderBy('id', 'desc')
                        ->select('id')->first();
                    $id = $video->id - MvModel::MV_RAND_LIMIT;
                    RedisService::set(MvModel::REDIS_MV_MAX_ID, $id, 7200);
                }
                $randID = random_int(MvModel::MV_BEGIN_ID, $id);
                $list = MvModel::queryBase()
                    ->where('id', '>', $randID)
                    ->where('is_aw', MvModel::AW_NO)
                    ->where('type', MvModel::TYPE_SHORT)
                    ->limit(200)
                    ->get()
                    ->toArray();
                if (count($list) == 0){
                    return [];
                }
                array_multisort(array_column($list, 'like'), SORT_DESC, $list);
                $list = array_column($list, 'id');
                $temp = [];
                foreach ($list as $key => $item) {
                    if ($key > 19) {
                        continue;
                    }
                    $temp[] = $item;
                }
                $list = $temp;
            }
        }

        //随机获取金币视频
        $gold = redis()->sRandMember(MvModel::COINS_SHORT_MV_ID_LIST_KEY, 200);
        $gold = array_diff($gold, $userWatchList);
        $gold = array_values($gold);
        if (count($gold) >= 4){
            $list[5] = $gold[0];
            $list[8] = $gold[1];
            $list[11] = $gold[2];
            $list[12] = $gold[3];
        }

        $data = MvModel::queryBase()
                    ->with('user:uid,nickname,thumb,aff,expired_at,vip_level,uuid,sexType')
                    ->whereIn('id', $list)
                    ->limit(20)
                    ->get();
        $data = collect($data)->shuffle();

        // 保存观看记录
        $expire = 86400 * 7;
        RedisService::sAddArray(sprintf(MemberModel::REDIS_USER_WATCH_LIST_ALL, $member->uid), $list, $expire);

        return (new MvService())->v2format($data, $member);
    }

    public function discover($sort, $page, $limit)
    {
        if ($sort == "rand"){
            $data = MvModel::randShortMvs($page, $limit);
        }elseif ($sort == "see"){
            $data = MvModel::listSeeShortMv($page, $limit);
        }elseif ($sort == "recommend"){
            $data = MvModel::listRecommendShortMv($page, $limit);
        }else{
            $data = MvModel::getAllMvList(MvModel::TYPE_SHORT, $sort, $page, $limit);
        }
        if ($data) {
            return (new \service\MvService())->v2format($data);
        }

        return $data;
    }
}