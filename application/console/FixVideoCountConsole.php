<?php


namespace App\console;


use App\console\Queue\QueueOption;
use \DB;
use LiveModel;

class FixVideoCountConsole extends AbstractConsole
{

    public $name = 'fix-video-count';

    public $description = '修复用户的视频统计';


    public function process($argc, $argv)
    {

        $all = \DB::select("select uid from ks_members where videos_count>0");
        $ids = array_map(function ($object) {
            return $object->uid;
        }, $all);
        \MemberModel::whereIn('uid', $ids)->update(['videos_count' => 0]);

        foreach ($ids as $uid){
            $object = \DB::select("select count(uid) as cc from ks_mv where uid=$uid");
            $cc = $object[0]->cc;
            DB::update("update ks_members set videos_count=$cc where uid=$uid");
        }

    }

}