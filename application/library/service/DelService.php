<?php

namespace service;

class DelService
{
    private static function recursiveDelete($dir)
    {
        if ($handle = @opendir($dir)) {
            while (($file = readdir($handle)) !== false) {
                if (($file == ".") || ($file == "..")) {
                    continue;
                }
                if (is_dir($dir . '/' . $file)) {
                    // 递归
                    self::recursiveDelete($dir . '/' . $file);
                } else {
                    $msg = '删除文件:' . $dir . '/' . $file;
                    writeLog($msg);
                    unlink($dir . '/' . $file); // 删除文件
                }
            }
            @closedir($handle);
            // 只删文件 不删除目录
            // @rmdir($dir);
            // $msg = '删除目录:' . $dir;
            // writeLog($msg);
        }
    }

    public static function config()
    {
        $file = STATIC_PATH . '/home/config.json';
        writeLog('删除文件:' . $file);
        is_file($file) && file_exists($file) && unlink($file);
    }

    public static function mvIndex($params)
    {
        $sort = (int)($params[0] ?? 0);
        $dirname = STATIC_PATH . '/mv/index';
        if ($sort) {
            $dirname = $dirname . '/' . $sort;
        }
        is_dir($dirname) && file_exists($dirname) && self::recursiveDelete($dirname);
    }

    public static function mvListMvs($params)
    {
        $tabId = (int)($params[0] ?? 0);
        $dirname = STATIC_PATH . '/mv/list_mvs';
        if ($tabId) {
            $dirname = $dirname . '/' . $tabId;
        }
        is_dir($dirname) && file_exists($dirname) && self::recursiveDelete($dirname);
    }

    public static function mvTabDetail($params)
    {
        $tabId = (int)($params[0] ?? 0);
        $dirname = STATIC_PATH . '/mv/tab_detail';
        if ($tabId) {
            $file = $dirname . '/' . $tabId . '.json';
            writeLog('删除文件:' . $file);
            is_file($file) && file_exists($file) && unlink($file);
        } else {
            is_dir($dirname) && file_exists($dirname) && self::recursiveDelete($dirname);
        }
    }

    public static function mvDetail($params)
    {
        $mvId = (int)($params[0] ?? 0);
        $dirname = STATIC_PATH . '/mv/detail';
        if ($mvId) {
            $file = $dirname . '/' . $mvId . '.json';
            writeLog('删除文件:' . $file);
            is_file($file) && file_exists($file) && unlink($file);
        } else {
            is_dir($dirname) && file_exists($dirname) && self::recursiveDelete($dirname);
        }
    }

    public static function mvRecommend($params)
    {
        $mvId = (int)($params[0] ?? 0);
        $dirname = STATIC_PATH . '/mv/recommend';
        if ($mvId) {
            $dirname = $dirname . '/' . $mvId;
        }
        is_dir($dirname) && file_exists($dirname) && self::recursiveDelete($dirname);
    }

    public static function mvComment($params)
    {
        $mvId = (int)($params[0] ?? 0);
        $dirname = STATIC_PATH . '/mv/comments';
        if ($mvId) {
            $dirname = $dirname . '/' . $mvId;
        }
        is_dir($dirname) && file_exists($dirname) && self::recursiveDelete($dirname);
    }

    public static function postConstruct($params)
    {
        $constructId = (int)($params[0] ?? 0);
        $dirname = STATIC_PATH . '/post/construct';
        if ($constructId) {
            $dirname = $dirname . '/' . $constructId;
        }
        is_dir($dirname) && file_exists($dirname) && self::recursiveDelete($dirname);
    }

    public static function postDetail($params)
    {
        $postId = (int)($params[0] ?? 0);
        $dirname = STATIC_PATH . '/post/detail';
        if ($postId) {
            $file = $dirname . '/' . $postId . '.json';
            writeLog('删除文件:' . $file);
            is_file($file) && file_exists($file) && unlink($file);
        } else {
            is_dir($dirname) && file_exists($dirname) && self::recursiveDelete($dirname);
        }
    }

    public static function postTopicDetail($params)
    {
        $topicId = (int)($params[0] ?? 0);
        $dirname = STATIC_PATH . '/post/topic';
        if ($topicId) {
            $file = $dirname . '/' . $topicId . '.json';
            writeLog('删除文件:' . $file);
            is_file($file) && file_exists($file) && unlink($file);
        } else {
            is_dir($dirname) && file_exists($dirname) && self::recursiveDelete($dirname);
        }
    }

    public static function postUserPost($params)
    {
        $aff = (int)($params[0] ?? 0);
        $dirname = STATIC_PATH . '/post/user_post';
        if ($aff) {
            $dirname = $dirname . '/' . $aff;
        }
        is_dir($dirname) && file_exists($dirname) && self::recursiveDelete($dirname);
    }

    public static function postComment($params)
    {
        $postId = (int)($params[0] ?? 0);
        $dirname = STATIC_PATH . '/post/comments';
        if ($postId) {
            $dirname = $dirname . '/post/' . $postId;
        }
        is_dir($dirname) && file_exists($dirname) && self::recursiveDelete($dirname);
    }

    public static function postComment2($params)
    {
        $commentId = (int)($params[0] ?? 0);
        $dirname = STATIC_PATH . '/post/comments';
        if ($commentId) {
            $dirname = $dirname . '/comment/' . $commentId;
        }
        is_dir($dirname) && file_exists($dirname) && self::recursiveDelete($dirname);
    }

    public static function manhuaList($params)
    {
        $tabId = (int)($params[0] ?? 0);
        $dirname = STATIC_PATH . '/manhua/list';
        if ($tabId) {
            $dirname = $dirname . '/' . $tabId;
        }
        is_dir($dirname) && file_exists($dirname) && self::recursiveDelete($dirname);
    }

    public static function manhuaDetail($params)
    {
        $mhId = (int)($params[0] ?? 0);
        $dirname = STATIC_PATH . '/manhua/detail';
        if ($mhId) {
            $file = $dirname . '/' . $mhId . '.json';
            writeLog('删除文件:' . $file);
            is_file($file) && file_exists($file) && unlink($file);
        } else {
            is_dir($dirname) && file_exists($dirname) && self::recursiveDelete($dirname);
        }
    }

    public static function manhuaRecommend($params)
    {
        $mhId = (int)($params[0] ?? 0);
        $dirname = STATIC_PATH . '/manhua/recommend';
        if ($mhId) {
            $dirname = $dirname . '/' . $mhId;
        }
        is_dir($dirname) && file_exists($dirname) && self::recursiveDelete($dirname);
    }

    public static function manhuaTabDetail($params)
    {
        $tabId = (int)($params[0] ?? 0);
        $dirname = STATIC_PATH . '/manhua/tab_detail';
        if ($tabId) {
            $dirname = $dirname . '/' . $tabId . '.json';
        }
        is_dir($dirname) && file_exists($dirname) && self::recursiveDelete($dirname);
    }

    public static function all()
    {
        $dirname = STATIC_PATH . '/';
        is_dir($dirname) && file_exists($dirname) && self::recursiveDelete($dirname);
    }

    public static function processFile($msg)
    {
        switch ($msg->obj) {
            case 'config':
                self::config();
                break;
            case 'mv_index':
                self::mvIndex($msg->params);
                break;
            case 'mv_list_mvs':
                self::mvListMvs($msg->params);
                break;
            case 'mv_detail':
                self::mvDetail($msg->params);
                break;
            case 'mv_tab_detail':
                self::mvTabDetail($msg->params);
                break;
            case 'mv_recommend':
                self::mvRecommend($msg->params);
                break;
            case 'mv_comment':
                self::mvComment($msg->params);
                break;
            case 'post_construct':
                self::postConstruct($msg->params);
                break;
            case 'post_detail':
                self::postDetail($msg->params);
                break;
            case 'post_topic_detail':
                self::postTopicDetail($msg->params);
                break;
            case 'post_user_post':
                self::postUserPost($msg->params);
                break;
            case 'post_comment':
                self::postComment($msg->params);
                break;
            case 'post_comment2':
                self::postComment2($msg->params);
                break;
            case 'manhua_list':
                self::manhuaList($msg->params);
                break;
            case 'manhua_detail':
                self::manhuaDetail($msg->params);
                break;
            case 'manhua_recommend':
                self::manhuaRecommend($msg->params);
                break;
            case 'manhua_tab_detail':
                self::manhuaTabDetail($msg->params);
                break;
            case 'all':
                self::all();
                break;
        }
    }
}