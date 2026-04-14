<?php

namespace service;

use PcPostTopicModel;
use PcTabModel;
use PcMhTabModel;

class GenService
{
    private static function toFile($data)
    {
        $returnData = [
            'data'   => $data,
            'status' => 1,
            'msg'    => '',
        ];
        $data = json_encode($returnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        FileService::genFile($data, true);
    }

    public static function config()
    {
        $service = new PcService();
        $config = $service->getConfig();
        $_SERVER['REQUEST_URI'] = 'home/config';
        $_SERVER['SCRIPT_PARAMS'] = [];
        self::toFile($config);
    }

    public static function mvIndex()
    {
        $service = new PcMvService();
        $_SERVER['REQUEST_URI'] = 'mv/index';
        $max = 100;
        $limit = 20;

        collect(PcService::MV_NAVS)
            ->map(function ($item) use ($service, $max, $limit) {
                $sort = $item['value'];
                for ($i = 1; $i <= $max; $i++) {
                    $data = $service->homeMvs($sort, $i, $limit);
                    $_SERVER['SCRIPT_PARAMS'] = [$sort, $i, $limit];
                    self::toFile($data);
                }
            });
    }

    protected static function genMvConstructFile($id, $service, $max, $limit)
    {
        collect(PcService::MV_NAVS)
            ->map(function ($item) use ($id, $service, $max, $limit) {
                $sort = $item['value'];
                for ($i = 1; $i <= $max; $i++) {
                    $data = $service->listMvs($id, $sort, $i, $limit);
                    $_SERVER['SCRIPT_PARAMS'] = [$id, $sort, $i, $limit];
                    self::toFile($data);
                }
            });
    }

    public static function mvListMvs($params)
    {
        $service = new PcMvService();
        $_SERVER['REQUEST_URI'] = 'mv/list_mvs';
        $constructId = (int)($params[0] ?? 0);
        $max = 100;
        $limit = 20;

        if ($constructId) {
            self::genMvConstructFile($constructId, $service, $max, $limit);
            return;
        }

        // 版块
        PcTabModel::listItems()
            ->map(function ($item) use ($service, $max, $limit) {
                self::genMvConstructFile($item->id, $service, $max, $limit);
            });
    }

    protected static function genPostConstructFile($id, $service, $max, $limit)
    {
        collect(PcService::POST_NAVS)
            ->map(function ($item) use ($id, $service, $max, $limit) {
                $sort = $item['value'];
                for ($i = 1; $i <= $max; $i++) {
                    $data = $service->listPosts($id, $sort, $i, $limit);
                    $_SERVER['SCRIPT_PARAMS'] = [$id, $sort, $i, $limit];
                    self::toFile($data);
                    if ($data->count() < 1) {
                        return;
                    }
                }
            });
    }

    public static function postConstruct($params)
    {
        $service = new PcPostService();
        $_SERVER['REQUEST_URI'] = 'post/list_posts';
        $constructId = (int)($params[0] ?? 0);
        $max = 100;
        $limit = 20;

        if ($constructId) {
            self::genPostConstructFile($constructId, $service, $max, $limit);
            return;
        }

        //社区模块
        PcPostTopicModel::listItems()
            ->map(function ($item) use ($service, $max, $limit) {
                self::genPostConstructFile($item->id, $service, $max, $limit);
            });
    }

    protected static function genMhConstructFile($id, $service, $max, $limit)
    {
        collect(PcService::MH_NAVS)
            ->map(function ($item) use ($id, $service, $max, $limit) {
                $sort = $item['value'];
                for ($i = 1; $i <= $max; $i++) {
                    $data = $service->list($id, $sort, $i, $limit);
                    $_SERVER['SCRIPT_PARAMS'] = [$id, $sort, $i, $limit];
                    self::toFile(['list' => $data]);
                    if ($data->count() < 1) {
                        return;
                    }
                }
            });
    }

    public static function manhuaConstruct($params)
    {
        $service = new PcManhuaService();
        $_SERVER['REQUEST_URI'] = 'manhua/list';
        $constructId = (int)($params[0] ?? 0);
        $max = 100;
        $limit = 20;

        if ($constructId) {
            self::genMhConstructFile($constructId, $service, $max, $limit);
            return;
        }

        //导航
        PcMhTabModel::listItems()
            ->map(function ($item) use ($service, $max, $limit) {
                self::genMhConstructFile($item->id, $service, $max, $limit);
            });
    }

    public static function processFile($msg)
    {
        switch ($msg->obj) {
            case 'config':
                self::config();
                break;
            case 'mv_index':
                self::mvIndex();
                break;
            case 'mv_list_mvs':
                self::mvListMvs($msg->params);
                break;
            case 'post_construct':
                self::postConstruct($msg->params);
                break;
            case 'manhua_construct':
                self::manhuaConstruct($msg->params);
                break;
        }
    }
}