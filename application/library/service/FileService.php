<?php

namespace service;

use Throwable;
use LibCryptPwa;

class FileService
{
    const USE_CACHE = false;
    const NO_AUTH_RULES = [
        'home/config',
        'mv/index',
        'mv/list_mvs',
        'mv/recommend_mvs',
        'post/list_posts',
        'post/list_user_posts',
        'manhua/tab_detail',
        'manhua/list',
        'manhua/recommend',
    ];

    public static function genFile($data, $isCli = false)
    {
        if (!self::USE_CACHE) {
            return;
        }

        $script = $_SERVER['REQUEST_URI'];
        switch ($script) {
            case 'home/config':
                $file = 'home/config.json';
                $isCli && writeLog(sprintf('当前生成文件:%s', $file));
                writeStatic($file, $data);
                break;

            case 'mv/index':
                list($sort, $page, $limit) = $_SERVER['SCRIPT_PARAMS'];
                $rule = 'mv/index/%s/%s/%s.json';
                $file = sprintf($rule, $sort, $page, $limit);
                $isCli && writeLog(sprintf('当前生成文件:%s', $file));
                writeStatic($file, $data);
                break;

            case 'mv/tab_detail':
                list($tabId) = $_SERVER['SCRIPT_PARAMS'];
                $rule = 'mv/tab_detail/%s.json';
                $file = sprintf($rule, $tabId);
                $isCli && writeLog(sprintf('当前生成文件:%s', $file));
                writeStatic($file, $data);
                break;

            case 'mv/list_mvs':
                list($tab_id, $sort, $page, $limit) = $_SERVER['SCRIPT_PARAMS'];
                $rule = 'mv/list_mvs/%s/%s/%s-%s.json';
                $file = sprintf($rule, $tab_id, $sort, $page, $limit);
                $isCli && writeLog(sprintf('当前生成文件:%s', $file));
                writeStatic($file, $data);
                break;

            case 'mv/detail':
                list($mvId) = $_SERVER['SCRIPT_PARAMS'];
                $rule = 'mv/detail/%s.json';
                $file = sprintf($rule, $mvId);
                $isCli && writeLog(sprintf('当前生成文件:%s', $file));
                writeStatic($file, $data);
                break;

            case 'mv/recommend_mvs':
                list($mvId) = $_SERVER['SCRIPT_PARAMS'];
                $rule = 'mv/recommend/%s.json';
                $file = sprintf($rule, $mvId);
                $isCli && writeLog(sprintf('当前生成文件:%s', $file));
                writeStatic($file, $data);
                break;

            case 'mv/list_comments':
                list($mvId, $page, $limit) = $_SERVER['SCRIPT_PARAMS'];
                $rule = 'mv/comments/%s/%s-%s.json';
                $file = sprintf($rule, $mvId, $page, $limit);
                $isCli && writeLog(sprintf('当前生成文件:%s', $file));
                writeStatic($file, $data);
                break;

            case 'post/list_posts':
                list($cateId, $sort, $page, $limit) = $_SERVER['SCRIPT_PARAMS'];
                $rule = 'post/construct/%s/%s/%s-%s.json';
                $file = sprintf($rule, $cateId, $sort, $page, $limit);
                $isCli && writeLog(sprintf('当前生成文件:%s', $file));
                writeStatic($file, $data);
                break;

            case 'post/detail':
                list($postId) = $_SERVER['SCRIPT_PARAMS'];
                $rule = 'post/detail/%s.json';
                $file = sprintf($rule, $postId);
                $isCli && writeLog(sprintf('当前生成文件:%s', $file));
                writeStatic($file, $data);
                break;

            case 'post/topic_detail':
                list($topicId) = $_SERVER['SCRIPT_PARAMS'];
                $rule = 'post/topic/%s.json';
                $file = sprintf($rule, $topicId);
                $isCli && writeLog(sprintf('当前生成文件:%s', $file));
                writeStatic($file, $data);
                break;

            case 'user/user_detail':
                list($aff) = $_SERVER['SCRIPT_PARAMS'];
                $rule = 'user/user_detail/%s.json';
                $file = sprintf($rule, $aff);
                $isCli && writeLog(sprintf('当前生成文件:%s', $file));
                writeStatic($file, $data);
                break;

            case 'post/list_user_posts':
                list($aff, $page, $limit) = $_SERVER['SCRIPT_PARAMS'];
                $rule = 'post/user_post/%s/%s-%s.json';
                $file = sprintf($rule, $aff, $page, $limit);
                $isCli && writeLog(sprintf('当前生成文件:%s', $file));
                writeStatic($file, $data);
                break;

            case 'post/list_comments':
                list($type, $relatedId, $page, $limit) = $_SERVER['SCRIPT_PARAMS'];
                $rule = 'post/comments/%s/%s/%s-%s.json';
                $file = sprintf($rule, $type, $relatedId, $page, $limit);
                $isCli && writeLog(sprintf('当前生成文件:%s', $file));
                writeStatic($file, $data);
                break;

            case 'manhua/tab_detail':
                list($tabId) = $_SERVER['SCRIPT_PARAMS'];
                $rule = 'manhua/tab_detail/%s.json';
                $file = sprintf($rule, $tabId);
                $isCli && writeLog(sprintf('当前生成文件:%s', $file));
                writeStatic($file, $data);
                break;

            case 'manhua/list':
                list($tabId,$sort,$page,$limit) = $_SERVER['SCRIPT_PARAMS'];
                $rule = 'manhua/list/%s/%s/%s-%s.json';
                $file = sprintf($rule, $tabId,$sort,$page,$limit);
                $isCli && writeLog(sprintf('当前生成文件:%s', $file));
                writeStatic($file, $data);
                break;

            case 'manhua/detail':
                list($mhId) = $_SERVER['SCRIPT_PARAMS'];
                $rule = 'manhua/detail/%s.json';
                $file = sprintf($rule, $mhId);
                $isCli && writeLog(sprintf('当前生成文件:%s', $file));
                writeStatic($file, $data);
                break;

            case 'manhua/recommend':
                list($mhId) = $_SERVER['SCRIPT_PARAMS'];
                $rule = 'manhua/recommend/%s.json';
                $file = sprintf($rule, $mhId);
                $isCli && writeLog(sprintf('当前生成文件:%s', $file));
                writeStatic($file, $data);
                break;
        }
    }

    public static function getFile($script)
    {
        if (!self::USE_CACHE) {
            return;
        }

        list($page, $limit) = pageLimit();
        switch ($script) {
            case 'home/config':
                $file = 'home/config.json';
                $file = STATIC_PATH . '/' . $file;
                is_file($file) && read_file($file);
                break;

            case 'mv/index':
                $sort = $_POST['sort'] ?? '';
                $rule = 'mv/index/%s/%s/%s.json';
                $file = sprintf($rule, $sort, $page, $limit);
                $file = STATIC_PATH . '/' . $file;
                is_file($file) && read_file($file);
                break;

            case 'mv/tab_detail':
                $mvId = (int)($_POST['id'] ?? 0);
                $rule = 'mv/tab_detail/%s.json';
                $file = sprintf($rule, $mvId);
                $file = STATIC_PATH . '/' . $file;
                is_file($file) && read_file($file);
                break;

            case 'mv/list_mvs':
                $tabId = (int)($_POST['id'] ?? 0);
                $sort = $_POST['sort'] ?? '';
                $rule = 'mv/list_mvs/%s/%s/%s-%s.json';
                $file = sprintf($rule, $tabId, $sort, $page, $limit);
                $file = STATIC_PATH . '/' . $file;
                is_file($file) && read_file($file);
                break;

            case 'mv/detail':
                $mvId = (int)($_POST['id'] ?? 0);
                $rule = 'mv/detail/%s.json';
                $file = sprintf($rule, $mvId);
                $file = STATIC_PATH . '/' . $file;
                is_file($file) && read_file($file);
                break;

            case 'mv/recommend_mvs':
                $mvId = (int)($_POST['id'] ?? 0);
                $rule = 'mv/recommend/%s.json';
                $file = sprintf($rule, $mvId);
                $file = STATIC_PATH . '/' . $file;
                is_file($file) && read_file($file);
                break;

            case 'mv/list_comments':
                $mvId = (int)($_POST['id'] ?? 0);
                $rule = 'mv/comments/%s/%s-%s.json';
                $file = sprintf($rule, $mvId, $page, $limit);
                $file = STATIC_PATH . '/' . $file;
                is_file($file) && read_file($file);
                break;

            case 'post/list_posts':
                $cateId = (int)($_POST['id'] ?? 0);
                $sort = $_POST['sort'] ?? '';
                $rule = 'post/construct/%s/%s/%s-%s.json';
                $file = sprintf($rule, $cateId, $sort, $page, $limit);
                $file = STATIC_PATH . '/' . $file;
                is_file($file) && read_file($file);
                break;

            case 'post/detail':
                $postId = (int)($_POST['id'] ?? 0);
                $rule = 'post/detail/%s.json';
                $file = sprintf($rule, $postId);
                $file = STATIC_PATH . '/' . $file;
                is_file($file) && read_file($file);
                break;

            case 'post/topic_detail':
                $topicId = (int)($_POST['topic_id'] ?? 0);
                $rule = 'post/topic/%s.json';
                $file = sprintf($rule, $topicId);
                $file = STATIC_PATH . '/' . $file;
                is_file($file) && read_file($file);
                break;

            case 'user/user_detail':
                $aff = (int)($_POST['aff'] ?? 0);
                $rule = 'user/user_detail/%s.json';
                $file = sprintf($rule, $aff);
                $file = STATIC_PATH . '/' . $file;
                is_file($file) && read_file($file);
                break;

            case 'post/list_user_posts':
                $aff = (int)($_POST['aff'] ?? 0);
                $rule = 'post/user_post/%s/%s-%s.json';
                $file = sprintf($rule, $aff, $page, $limit);
                $file = STATIC_PATH . '/' . $file;
                is_file($file) && read_file($file);
                break;

            case 'post/list_comments':
                $type = $_POST['type'] ?? '';
                $relatedId = (int)($_POST['related_id'] ?? 0);
                $rule = 'post/comments/%s/%s/%s-%s.json';
                $file = sprintf($rule, $type, $relatedId, $page, $limit);
                $file = STATIC_PATH . '/' . $file;
                is_file($file) && read_file($file);
                break;

            case 'manhua/tab_detail':
                $tabId = $_POST['id'] ?? '';
                $rule = 'post/comments/%s.json';
                $file = sprintf($rule, $tabId);
                $file = STATIC_PATH . '/' . $file;
                is_file($file) && read_file($file);
                break;

            case 'manhua/list':
                $tabId = (int)($_POST['id'] ?? 0);
                $sort = $_POST['sort'] ?? '';
                $rule = 'manhua/list/%s/%s/%s-%s.json';
                $file = sprintf($rule, $tabId, $sort, $page, $limit);
                $file = STATIC_PATH . '/' . $file;
                is_file($file) && read_file($file);
                break;

            case 'manhua/detail':
                $mhId = (int)($_POST['id'] ?? 0);
                $rule = 'manhua/detail/%s.json';
                $file = sprintf($rule, $mhId);
                $file = STATIC_PATH . '/' . $file;
                is_file($file) && read_file($file);
                break;

            case 'manhua/recommend':
                $mhId = (int)($_POST['id'] ?? 0);
                $rule = 'manhua/recommend/%s.json';
                $file = sprintf($rule, $mhId);
                $file = STATIC_PATH . '/' . $file;
                is_file($file) && read_file($file);
                break;
        }
    }

    public static function processMsg($msg)
    {
        switch ($msg->action) {
            case 'delete':
                DelService::processFile($msg);
                break;
            case 'replace':
                GenService::processFile($msg);
                break;
        }

        $rsp = clear_yac();
        test_assert($rsp === '1', '更新yac缓存失败');
        $msg = '清理yac缓存返回:' . $rsp;
        writeLog($msg);
        $msg = '已全部清理成功';
        writeLog($msg);
    }

    public static function readStatic()
    {
        // 清理yac
        if (($_GET['_yac'] ?? '') == '_reload') {
            exit(yac2()->flush());
        }
        // 不为POST直接禁止
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            exit('');
        }
        // 基础验证
        if (!isset($_POST['data']) || !isset($_POST['timestamp']) || !isset($_POST['sign'])) {
            exit('');
        }
        // 加解密参数
        //$_ver = $_POST['_ver'] ?? 'v0'; 未使用
//        define('API_CRYPT_KEY', '2acf7e91e9864673');
//        define('API_CRYPT_SIGN', '5589d41f92a597d016b037ac37db243d');
//        define('API_CRYPT_IV', '1c29882d3ddfcfd6');
//        define('API_ENCRYPT', 'z2iTT2nx4oXxEk60yUWKCT3wUu9CNh0j');

        $crypt = new LibCryptPwa();
        $_POST = $crypt->checkInputData($_POST);


//        error_log('POST:' . $_SERVER['REQUEST_URI'] . PHP_EOL, 3, APP_PATH . '/public/1.log');
//        error_log('POST:' . print_r($_POST, true) . PHP_EOL, 3, APP_PATH . '/public/1.log');

        // 已登录部分不走缓存 重写路由
        $script = $_SERVER['REQUEST_URI'];
        $search = "/m.php/wapi/";
        $script = str_replace($search, "", $script);
        $script = trim($script);
        $_SERVER['REQUEST_URI'] = $script;
        $token = trim($_POST['token'] ?? '');
        if ($token && !in_array($script, self::NO_AUTH_RULES)) {
            return;
        }
        self::getFile($script);
    }

    public static function onSubscribe($msg)
    {
        writeLog('回调数据:' . $msg);
        $msg = json_decode($msg);
        self::processMsg($msg);
    }

    public static function processQueue()
    {
        try {
            $cache_channel = 'site_file_cache';
            $fn = function ($redis, $channel, $msg) {
                writeLog('订阅数据:' . $msg);
                file_jobs([self::class, 'onSubscribe'], [$msg]);
            };
            redis()->subscribe([$cache_channel], $fn);
        } catch (Throwable $e) {
            trigger_log($e);
        }
        exit(0);
    }

    public static function processCache()
    {
        $lockKey = 'sup:lock:process:cache';
        while (true) {
            try {
                $task = redis()->lock($lockKey, function () {
                    return process_queue();
                });
                if (!$task) {
                    sleep(1);
                    continue;
                }
                list($buffer) = json_decode($task, true);
                $ary = @unserialize($buffer);
                list($func, $args) = $ary;
                call_user_func_array($func, $args);
                usleep(2000);
            } catch (Throwable $e) {
                trigger_log($e);
                //sleep(10);
            }
        }
    }

    public static function publishJob($action, $obj, $params = [])
    {
        $cache_channel = 'site_file_cache';
        $job = json_encode([
            'action' => $action,
            'obj'    => $obj,
            'params' => $params
        ]);
        redis()->publish($cache_channel, $job);
    }
}