<?php


namespace App\console;


use DB;

class FixLikeConsole extends AbstractConsole
{

    public $name = 'update-likes-for-mv-del';

    public $description = '修复用户点赞视频之后，视频被删除，用户的点赞对不上的bug';


    public function process($argc, $argv)
    {
        $all = DB::select("select mv_id from (select mv_id from ks_user_likes group by mv_id) as a  where mv_id not in (select id from ks_mv)");
        $ids = array_map(function ($object) {
            return $object->mv_id;
        }, $all);
        $all = DB::select('select id from ks_mv where status=1 and coins=0 order by rand() limit ' . (count($all) + 1));
        $randIds = array_map(function ($object) {
            return $object->id;
        }, $all);

        foreach ($ids as $id) {
            $newId = array_pop($randIds);
            DB::update("update ks_user_likes set mv_id=$newId where mv_id=$id");
        }
    }


}