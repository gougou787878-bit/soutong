<?php


namespace App\console;


use Carbon\Carbon;
use MemberMakerModel;
use MemberModel;
use NavigationModel;
use TabModel;
use MvModel;

class TabWorkNumConsole extends AbstractConsole
{

    public $name = 'tab-work-num';

    public $description = '作品数更新;eg:php yaf tab-work-num';

    public function process($argc, $argv)
    {
        $hour = date('H');
//        if ($hour >= 2 && $hour <= 10){
            $bg = date('Y-m-d H:i:s');
            echo "#################  start [ {$bg} ]##############".PHP_EOL;

            $this->updateCreatorWorks(MvModel::TYPE_SHORT);

            $this->updateCreatorWorks(MvModel::TYPE_LONG);

            $end = date('Y-m-d H:i:s');
            echo "##################  over [ {$end} ]##############".PHP_EOL;
//        }
    }

    public function updateCreatorWorks($type) {
        trigger_log('用户作品数更新开始时间：'. Carbon::now());
        MvModel::queryBase()
            ->selectRaw('uid, count(uid) as ct')
            ->where('type', $type)
            ->groupBy(['uid'])
            ->having('ct', '>', 0)
            ->orderBy('ct', 'desc')
            ->chunk(100, function ($items) use ($type) {
                collect($items)->each(function ($item) use ($type) {
                    $uid = $item->uid;
                    $member = MemberModel::find($uid);
                    if (!$member) {
                        echo "未找到用户 uid: $uid", PHP_EOL;
                        return;
                    }
                    if ($item->ct == 0){
                        return;
                    }
                    $s = "长";
                    if ($type == MvModel::TYPE_LONG){
                        $member->videos_count = $item->ct;
                    } elseif ($type == MvModel::TYPE_SHORT){
                        $member->short_videos_count = $item->ct;
                        $s = "短";
                    }
                    if ($member->isDirty()){
                        $member->save();
                    }
                    echo $member->nickname, "|{$s}数量:", $item->ct, PHP_EOL;
                });
            });
        trigger_log('用户作品数更新结束时间：'. Carbon::now());
    }
    


}