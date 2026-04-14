<?php


namespace App\console;


use App\console\Queue\QueueOption;

class FixdbConsole extends AbstractConsole
{

    public $name = 'fixdb';

    public $description = '执行队列';


    public function process($argc, $argv)
    {

        return ;
        $str = '46CF9E7D9B89358EFF2030A3BE91BCC4E24D1679F006FD5315BBC7C61B8622448B36B60D6190231FF477E9AA5C2259950D98628AC0B0FF436A170CAC787BE341F1FC69B19CC0F1DCA1FB0144B821859749035328E474388A4404FA750526CC7B12592D2427E2354DDF54975E7E';
        $d = \LibCryptPwa::decrypt($str,'');

        print_r($d);die;

        return ;
        \DB::update("truncate table ks_mv_tags;");
        \DB::update("truncate table ks_mv_words;");
        $cachedTagKeys = [];
        $feeIds = [];
        $VideoIds = [];
        $redis = redis();
        $all = \MvModel::where('status', \MvModel::STAT_CALLBACK_DONE)
            ->get()
            ->map(function (\MvModel $item) use (&$VideoIds, &$cachedTagKeys, &$feeIds) {
                \MvTagModel::createByAll($item->id, $item->tags);
                \MvWordsModel::createForTitle($item->id, $item->title);
                foreach ($item->tags as $tag) {
                    $cachedTagKeys["charge:tag:$tag"] = 0;
                }
                if ($item->coins > 0 && $item->is_recommend) {
                    $feeIds[] = $item->id;
                }
                $VideoIds[] = $item->id;
                return $item;
            });

        foreach ($cachedTagKeys as $key => $_) {
            $redis->del($key);
        }
        /*$redis->del(\MvModel::REDIS_MV_LIST);
        collect(array_chunk($VideoIds , 50))->map(function ($ids) use($redis){
            $redis->sAddArray(\MvModel::REDIS_MV_LIST, $ids);
        });*/


        $redis->del(\MvModel::RECOMMEND_FEE_KEY);
        $redis->sAddArray(\MvModel::RECOMMEND_FEE_KEY, $feeIds);
        $this->fixCount();

    }


    protected function fix()
    {
        $keys = redis()->keys('follow:chargeVideo:*');
        $prefix = config('redis.prefix');
        foreach ($keys as $key) {
            $key = substr($key, strlen($prefix));
            redis()->del($key);
        }

    }

    protected function fixCount()
    {
        $this->resetCount();
        $this->fansCount();
        $this->followCount();
        $this->videoCount();
        $this->likesCount();
    }


    protected function resetCount()
    {
        \DB::update("update ks_members set fans_count=0,followed_count=0,videos_count=0,live_count=0,fabulous_count=0,likes_count=0 where 1");
    }


    protected function fansCount()
    {

        $row = \DB::update("update ks_members m set fans_count=(select count(uid) from ks_member_attention ma where ma.touid=m.uid) where 1");
        $this->logSuccess("fans_count影响了{$row}");
    }

    protected function followCount()
    {

        $row = \DB::update("update ks_members m set followed_count=(select count(uid) from ks_member_attention ma where ma.uid=m.uid) where 1");
        $this->logSuccess("followed_count影响了{$row}");
    }

    protected function videoCount()
    {
        $row = \DB::update("update ks_members m set videos_count=(select count(id) from ks_mv mv where mv.uid=m.uid and status=1) where 1");
        $this->logSuccess("videos_count影响了{$row}");
    }

    protected function likesCount()
    {
        $row = \DB::update("update ks_members m set likes_count=(select count(id) from ks_user_likes uk where uk.uid=m.uid) where 1");
        $this->logSuccess("likes_count影响了{$row}");
    }


}