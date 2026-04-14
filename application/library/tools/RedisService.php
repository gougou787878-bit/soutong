<?php
namespace tools;

use Yaf\Application;
use Yaf\Registry;

/**
 * @example RedisService::get('123')
 * @example RedisService::redis()->get('123')
 * Class RedisService
 * @package tools
 */
class RedisService
{
    private static $redis;
    private static $instance;
    private static $status;

    public function __construct()
    {
        if (self::$instance) {
            return;
        }

        $config = Registry::get('database.conf');
        $environ = ini_get('yaf.environ');
        $redis = $config->redis->host->toArray();
        if ($environ == 'develop' || $environ == 'test') {
            self::$redis = new \Redis();
            self::$status = self::$redis->connect($redis[0], $config->redis->port);
        } else {
            if (count($redis) == 1) {
                self::$redis = new \Redis();
                self::$status = self::$redis->connect($redis[0], $config->redis->port);
            } else {
                self::$status = self::$redis = new \RedisCluster(null, $redis, null, null, true);
                self::$redis->setOption(\RedisCluster::OPT_SLAVE_FAILOVER, \RedisCluster::FAILOVER_DISTRIBUTE);
            }
        }
        self::$redis->setOption(\Redis::OPT_PREFIX, $config->redis->prefix);
    }

    /**
     * 未知静态方法
     * @param $name
     * @param $arguments
     * @return bool
     */
    public static function __callStatic($name, $arguments)
    {
        self::instance();
        return self::$status ? self::$instance->$name(...$arguments) : false;
    }

    /**
     * 自动调用
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return self::redis()->$name(...$arguments);
    }

    /**
     * 单列
     * @return RedisService
     */
    private static function instance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }



    public static function redis()
    {
        self::instance();
        return self::$redis;
    }

    /**
     * string
     * @param $key
     * @return bool|mixed|string
     */
    public static function get($key)
    {
        $data = self::redis()->get($key);
        $jsonData = json_decode($data, true);

        return ($jsonData === null) ? $data : $jsonData;
    }

    /**
     * 计算上一名的分数
     * @param $key
     * @param $member
     * @return int 前一名的分数 返回0：没有前一名，-1=当前成员没在排名里面
     */
    public static  function zPrevScore($key, $member)
    {
        $rank = self::redis()->zRank($key, $member);
        if ($rank === 0) {
            return 0;
        }
        if (empty($rank)) {
            return -1;
        }
        //这种方式在排名分数都是一样的情况下会存在bug，如下
        // zadd k 2 m1
        // zadd k 1.1 m2
        // zadd k 1.1 m3
        // zadd k 1.1 m4
        // 求m4和m4的前一名的分数差
        $scoreData = self::redis()->zRange($key, $rank - 1, $rank - 1, true);
        if (!empty($scoreData)) {
            return intval(array_pop($scoreData));
        } else {
            return 0;
        }
    }

    /**
     * 计算上一名的分数
     * @param $key
     * @param $member
     * @return int 前一名的分数 返回0：没有前一名，-1=当前成员没在排名里面
     */
    public static function zRevPrevScore($key, $member)
    {
        $rank = self::redis()->zRevRank($key, $member);
        if ($rank === 0) {
            return 0;
        }
        if (empty($rank)) {
            return -1;
        }
        $scoreData = self::redis()->zRevRange($key, $rank - 1, $rank - 1, true);
        if (!empty($scoreData)) {
            return intval(array_pop($scoreData));
        } else {
            return 0;
        }
    }

    /**
     * string
     * @param $key
     * @return bool|mixed|string
     */
    public static function del($key)
    {
        self::redis()->del($key);
    }

    public static function set($key, $data, $time = false)
    {
        $data = is_array($data) ? json_encode($data) : $data;
        $status  = self::redis()->set($key, $data);
        if ($time) {
            self::$redis->expire($key, $time);
        }
        return $status;
    }

    /**
     * 通过array储存hash
     * @param $key
     * @param $data
     * @param $time
     * @return bool
     */
    public static function hMSet($key, $data, $time = false)
    {
        if (!is_array($data)) {
            return false;
        }
        $result = self::$redis->hMset($key, $data);
        if (isset($arg[2]) and self::redis()->exists($key)) {
            self::redis()->expire($key, $time);
        }

        return $result;
    }

    /**
     * 是否在集合中
     * @param $key
     * @param $value
     * @return bool
     */
    public static function sisMember($key, $value)
    {
        $items = self::redis()->sMembers($key);
        $has = in_array($value, $items?$items:[]);
        unset($items);
        return $has;
    }

    /**
     * 集合取差集$
     * @param $key
     * @param $key2
     * @return array
     */
    public static function sDiff($key, $key2)
    {
        $items = self::redis()->sMembers($key);
        $need = self::redis()->sMembers($key2);
        $list = array_diff($items, $need);
        unset($items, $need);
        return $list;
    }

    /**
     * 过期时间
     * @param $key
     * @param $time
     * @return bool
     */
    public static function expire($key, $time)
    {
        if (self::redis()->exists($key)) {
            self::redis()->expire($key, $time);
        }
        return true;
    }

    public function __destruct()
    {
        self::redis()->close();
    }


    /**
     * @throws \RedisException
     */
    public function getWithSerialize($key)
    {
        $data = self::redis()->get($key);
        if (is_null($data)) {
            return false;
        }
        return unserialize($data);
    }


    /**
     * @throws \RedisException
     */
    public function setWithSerialize($key, $val, $time = false): bool
    {
        self::redis()->set($key, serialize($val));
        if ($time) {
            self::redis()->expire($key, $time);
        }
        return true;
    }

    /**
     * 集合 增加多个
     * @param $key
     * @param array $value
     * @param $time
     * @return  bool
     */
    public static function sAddArray($key, array $value, $time = false)
    {
        $status = self::redis()->sAddArray($key, $value);
        if ($time) {
            self::redis()->expire($key, $time);
        }
        return $status;
    }
}