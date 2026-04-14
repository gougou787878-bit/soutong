<?php

namespace helper;


use Exception;
use exception\FrequencyException;

class Util
{

    public static function frequencyCall($identify, $max = 3, $ttl = 3, $backtrace = null)
    {
        if ($backtrace === null) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        }
        if (!isset($backtrace[1])) {
            return true;
        }
        if (!isset($backtrace[1]['file']) || !isset($backtrace[1]['line'])){
            if (isset($backtrace[0]['file']) && isset($backtrace[0]['file'])){
                array_unshift($backtrace , []);
            }
        }
        $identify = md5($backtrace[1]['file'] . ':' . $backtrace[1]['line'] . ':' . $identify);
        return self::frequency($identify, $max, $ttl);
    }


    public static function frequency($identify, $max = 1, $ttl = 1)
    {
        $redis = redis();
        $key = ':frequency:' . $identify;
        $incr = $redis->incr($key);
        if ($incr == 1) {
            $redis->expire($key, $ttl);
        }
        if ($incr > $max) {
            return false;
        }
        return true;
    }

    /**
     * @param $identify
     * @param int $limit
     * @param int $ttl
     * @param string $msg
     * @throws \Throwable
     * @author xiongba
     */
    public static function PanicFrequency($identify,int $limit = 2,int $ttl = 5 ,string $msg = '操作太频繁')
    {
        if (!self::frequencyCall($identify, $limit, $ttl, debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2))) {
            throw new FrequencyException($msg, 422);
        }
    }


    /**
     * 只获取关键字
     * @param $text
     * @return array
     * @throws Exception
     * @author xiongba
     * @date 2020-03-17 10:25:12
     */
    public static function keyword($text)
    {
        $array = self::participle($text);
        $result = [];
        foreach ($array as $item) {
            $result[] = $item[0];
        }
        return $result;
    }

    /**
     * 分词
     * @param $text
     * @return array 返沪分词结果 [[关键字,权重]]
     * @throws Exception
     */
    public static function participle($text)
    {
        if (empty($text)) {
            return [];
        }
        if (!function_exists('scws_new')) {
            throw new Exception('服务器没有开启scws扩展');
        }
        $scws = scws_new();
        $scws->set_charset('utf-8');
        $scws->set_dict(config('application.directory') . '/../conf/dict.utf8.xdb');
        $scws->send_text($text);
        $tops = $scws->get_tops(100);
        $scws->close();
        $array = [[$text, 1000000]];
        foreach ($tops as $item) {
            $emp = trim($item['word'], '.');
            if (empty($emp)) {
                continue;
            }
            $array[] = [$item['word'], $item['times'] * $item['weight']];
        }
        return $array;
    }

}