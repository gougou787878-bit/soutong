<?php

namespace App\console;

use tools\RedisService;

class RecommendConsole extends AbstractConsole
{
    public $name = 'recommend';
    public $description = '用户视频推荐队列';

    public function process($argc, $argv)
    {
        $items = RedisService::redis()->blPop(\MemberModel::RECOMMEND_USER_LIST, 10);

        if (!empty($items)) {
            $this->recommend($items[1]);
            sleep(10);
        }else{
            //不能退出太快
            sleep(20);
        }
    }

    private function recommend(string $uid)
    {
        echo '开始计算：' . $uid . PHP_EOL;
        //找出用户喜欢的3个视频
        $ids = \UserLikeModel::where('uid', $uid)->orderByDesc('id')->limit(3)->pluck('mv_id')->toArray();
        if (!$ids){
            return;
        }

        //找出同样喜欢上面3个视频的用户
        $ids_info = \UserLikeModel::selectRaw('uid, count(uid) as uct')
            ->whereIn('mv_id', $ids)
            ->where('uid', '!=', $uid)
            ->groupBy('uid')
            ->having('uct', 3)
            ->limit(50)
            ->get()
            ->toArray();

        if (count($ids_info) == 0){
            return;
        }
        $uids = [];
        foreach ($ids_info as $v){
            $uids[] = $v['uid'];
        }

        $begin = \MvModel::MV_BEGIN_ID;
        $mvIDs = \UserLikeModel::whereIn('uid', $uids)
            ->where('mv_id', '>', $begin)
            ->orderByDesc('id')
            ->limit(250)
            ->pluck('mv_id')
            ->toArray();

        if (count($mvIDs) == 0){
            return;
        }

        $mvIDs = array_unique($mvIDs);
        $watchLists = RedisService::redis()->sMembers(sprintf(\MemberModel::REDIS_USER_WATCH_LIST_ALL, $uid));
        $mvIDs = array_diff($mvIDs, $watchLists);

        if (count($mvIDs) < 100) {
            echo '没有推荐视频' . PHP_EOL;
            return;
        }

        $diff = \MvModel::queryBase()
            ->select('id')
            ->whereIn('id', $mvIDs)
            ->where('is_aw', \MvModel::AW_NO)
            ->where('type', \MvModel::TYPE_SHORT)
            ->orderBy('like', 'desc')
            ->limit(100)
            ->pluck('id')
            ->toArray();

        RedisService::redis()->del(sprintf(\MvModel::RECOMMEND_USER_MV_LIST, $uid));

        //$diff = array_splice($diff, 0, 100);
        echo '用户：' . $uid . ' - ' . json_encode($diff) . PHP_EOL;
        $expire = 86400 * 7;
        RedisService::sAddArray(sprintf(\MvModel::RECOMMEND_USER_MV_LIST, $uid), $diff, $expire);
    }
}