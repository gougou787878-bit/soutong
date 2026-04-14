<?php

namespace service;

use LibEs;
use MvModel;
use PostModel;
use Throwable;
use tools\Elasticsearch;

//use SeedPostModel;

class EsService
{
    const CK_ES_MV_SEARCH = 'ck:es:mv:search:v2:%s:%s:%s:%s';
    const GP_ES_MV_SEARCH = 'gp:es:mv:search';
    const CN_ES_MV_SEARCH = '视频-ES搜索';

    const CK_ES_POST_SEARCH = 'ck:es:post:search:v3:%s:%s:%s';
    const GP_ES_POST_SEARCH = 'gp:es:post:search';
    const CN_ES_POST_SEARCH = '帖子-ES搜索';

//    const CK_ES_SEED_SEARCH = 'ck:es:seed:search:v2:%s:%s:%s';
//    const GP_ES_SEED_SEARCH = 'gp:es:seed:search';
//    const CN_ES_SEED_SEARCH = '种子-ES搜索';

    public static function syncMv(\MvModel $model): bool
    {
        if ($model->is_hide == MvModel::IS_HIDE_NO && $model->status = MvModel::STAT_CALLBACK_DONE && $model->is_aw == MvModel::AW_NO && $model->is_18 == MvModel::IS_18_YES) {
            $data = [
                'id'           => $model->id,
                'title'        => $model->title,
                'tags'         => $model->tags,
                'is_aw'        => $model->is_aw,
                'type'         => $model->type,
            ];
            return self::sync('mv', 'add', $model->id, $data);
        }
        return self::sync('mv', 'del', $model->id);
    }

    public static function search_mv($word, $type, $page, $limit): ?array
    {
        try {
            if (in_array((int)date('H'), [21,22,23,0])){
                $expire_second = 10800;
            }else{
                $expire_second = 1800;
            }
            $cacheKey = sprintf(self::CK_ES_MV_SEARCH, $word, $type, $page, $limit);
            $ids = cached($cacheKey)
                ->group(self::GP_ES_MV_SEARCH)
                ->chinese(self::CN_ES_MV_SEARCH)
                ->fetchJson(function () use ($word, $type) {
                    $query = 'sel';
                    $query .= "ect id from @{mv} where is_aw = 0 and type = '$type' and (title like '%$word%' or tags like '%$word%')";
                    //$query .= "ect id from @{mv} where is_aw = 0 and type = '$type' and title like '%$word%'";
                    $results = Elasticsearch::querySql($query, 1000);
                    return collect($results['rows'])->flatten()->toArray();
                }, $expire_second);

            $ids = collect($ids)->forPage($page, $limit)->values()->toArray();
        } catch (Throwable $e) {
            $msg = '[获取ES中的视频失败]' . PHP_EOL;
            $msg .= '时间:' . date('Y-m-d H:i:s', TIMESTAMP) . PHP_EOL;
            $msg .= '搜索关键字:' . $word . PHP_EOL;
            $msg .= 'ES报错:' . $e->getMessage() . PHP_EOL;
            trigger_log($msg);
            $ids = null;
        }
        return $ids;
    }

    public static function syncPost($model): bool
    {
        if ($model->is_deleted == PostModel::DELETED_NO && $model->is_finished == PostModel::FINISH_OK && $model->status == PostModel::STATUS_PASS && $model->is_open == PostModel::OPEN_YES) {
            $data = [
                'id'        => $model->id,
                'title'     => $model->title,
                'is_open'   => $model->is_open,
            ];
            return self::sync('post', 'add', $model->id, $data);
        }
        return self::sync('post', 'del', $model->id);
    }

    public static function search_post($word, $page, $limit): ?array
    {
        try {
            $cacheKey = sprintf(self::CK_ES_POST_SEARCH, $word, $page, $limit);
            $ids = cached($cacheKey)
                ->group(self::GP_ES_POST_SEARCH)
                ->chinese(self::CN_ES_POST_SEARCH)
                ->fetchJson(function () use ($word) {
                    $query = "select id from @{post} where title like '%$word%'";
                    $results = Elasticsearch::querySql($query, 1000);
                    return collect($results['rows'])->flatten()->toArray();
                });
            $ids = collect($ids)->forPage($page, $limit)->values()->toArray();
        } catch (Throwable $e) {
            $msg = '[获取ES中的帖子失败]' . PHP_EOL;
            $msg .= '时间:' . date('Y-m-d H:i:s', TIMESTAMP) . PHP_EOL;
            $msg .= '搜索关键字:' . $word . PHP_EOL;
            $msg .= 'ES报错:' . $e->getMessage() . PHP_EOL;
            trigger_log($msg);
            $ids = null;
        }
        return $ids;
    }

//    public static function syncSeed($model): bool
//    {
//        if ($model->status == SeedPostModel::STATUS_ON && $model->is_finished == SeedPostModel::FINISHED_OK) {
//            $data = [
//                'id'    => $model->id,
//                'title' => $model->title
//            ];
//            return self::sync('seed', 'add', $model->id, $data);
//        }
//        return self::sync('seed', 'del', $model->id);
//    }
//
//    public static function search_seed($word, $page, $limit): ?array
//    {
//        try {
//            $cacheKey = sprintf(self::CK_ES_SEED_SEARCH, $word, $page, $limit);
//            $ids = cached($cacheKey)
//                ->group(self::GP_ES_SEED_SEARCH)
//                ->chinese(self::CN_ES_SEED_SEARCH)
//                ->fetchJson(function () use ($word) {
//                    $query = "select id from @{seed} where title like '%$word%'";
//                    $results = Elasticsearch::querySql($query, 1000);
//                    return collect($results['rows'])->flatten()->toArray();
//                });
//            $ids = collect($ids)->forPage($page, $limit)->values()->toArray();
//        } catch (Throwable $e) {
//            $msg = '[获取ES中的帖子失败]' . PHP_EOL;
//            $msg .= '时间:' . date('Y-m-d H:i:s', TIMESTAMP) . PHP_EOL;
//            $msg .= '搜索关键字:' . $word . PHP_EOL;
//            $msg .= 'ES报错:' . $e->getMessage() . PHP_EOL;
//            trigger_log($msg);
//            $ids = null;
//        }
//        return $ids;
//    }

    public static function sync($space, $action, $id, $data = []): bool
    {
        try {
            if ($action == 'del') {
                LibEs::space($space, function (LibEs $es) use ($id) {
                    $es->exists($id) && $es->delete($id);
                });
                return true;
            }
            LibEs::space($space, function (LibEs $es) use ($data) {
                $es->updateOrCreate($data);
            });
            return true;
        } catch (Throwable $e) {
            $msg = '[远程同步' . $space . '数据-同步至ES失败]' . PHP_EOL;
            $msg .= '时间:' . date('Y-m-d H:i:s', TIMESTAMP) . PHP_EOL;
            $msg .= '数据:' . print_r($data, true) . PHP_EOL;
            $msg .= 'ES报错:' . $e->getMessage() . PHP_EOL;
            trigger_log($msg);
            return false;
        }
    }
}
