<?php


/**
 * Class RedisCached
 * @author xiongba
 * @date 2019-11-28 15:36:23
 */
class RedisCached
{

    /**
     * @var RedisCached
     */
    private static $instance = null;
    private $hosts = null;
    private $prefix = '';
    private $isConnect = false;
    private $isCluster = false;
    private $proxyArray = [
        'sDiff',
        'sDiffOfScan',
        'sDiffOfScanStore',
        'sDiffStore',
        'sDiffStoreWithPHP',
        'sDiffWithPHP',
        'setRedis',
        'singleCluster',
        'sInter',
        'sInterStore',
        'sInterStoreWithPHP',
        'sInterWithPHP',
        'sUnion',
        'sUnionStore',
        'sUnionStoreWithPHP',
        'sUnionWithPHP'
    ];
    private $proxy = null;
    /**
     * @var null|RedisPipeline
     */
    private static $pipelineInstance = null;
    /**
     * @var RedisCluster
     */
    private $client;

    /**
     * RedisCached constructor.
     * @param array|null $hosts
     * @param $prefix
     * @author xiongba
     * @date 2019-12-12 16:48:25
     */
    private function __construct($hosts, $prefix = '')
    {
        $this->hosts = (array)$hosts;
        $this->prefix = $prefix;
        $this->proxy = new RedisProxy();
    }


    public static function instance($hosts, $prefix = '')
    {
        if (self::$instance === null) {
            self::$instance = new self($hosts, $prefix);
        }
        return self::$instance;
    }


    public static function newInstanceByDriver($driver){
        if (self::$instance === null) {
            self::$instance = new self([]);
            self::$instance->isConnect = true;
            self::$instance->client = $driver;
        }
        return self::$instance;
    }

    public function incrByTtl($key , $by , $ttl){
        $val = $this->getRedis()->incrBy($key , $by);
        if ($val <= 2) {
            $this->getRedis()->expire($key , $ttl);
        }
        return $val;
    }

    public function sAddTtl($key, $value, $ttl)
    {
        $val = $this->getRedis()->sAdd($key, $value);
        if ($value < 2) {
            $this->getRedis()->expire($key, $ttl);
        }
        return $val;
    }


    /**
     * @return RedisCluster|Redis
     * @throws RedisClusterException
     * @throws RedisException
     * @author xiongba
     * @date 2019-11-28 11:03:16
     */
    public function getRedis()
    {
        if (!$this->isConnect) {
            if (empty($this->hosts)) {
                throw new RedisException("Redis{" . json_encode($this->hosts) . "}配置不正确");
            }
            if (count($this->hosts) > 1) {
                $this->client = new \RedisCluster(null, $this->hosts);
                $this->client->setOption(\RedisCluster::OPT_SLAVE_FAILOVER, \RedisCluster::FAILOVER_DISTRIBUTE);
                $this->isCluster = true;
            } else {
                $this->client = $this->getSingleRedis($this->hosts[0]);
            }
            $this->client->setOption(\Redis::OPT_PREFIX, $this->prefix);

            $this->isConnect = true;
        }
        return $this->client;
    }


    public function setClient($client)
    {
        $this->isConnect = true;
        $this->client = $client;
    }


    /**
     * 链接单例redis
     * @param $hostString
     * @return Redis
     * @throws RedisException
     * @author xiongba
     * @date 2019-12-12 16:42:55
     */
    protected function getSingleRedis($hostString)
    {
        $client = new Redis();
        $ary = explode(':', $hostString, 2);
        if (!$client->connect(...$ary)) {
            throw new RedisException("Redis:{{$hostString}}链接失败");
        }
        return $client;
    }


    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws RedisClusterException
     * @throws RedisException
     * @author xiongba
     * @date 2019-12-12 16:38:53
     */
    public function __call($name, $arguments)
    {
        if (self::$pipelineInstance === null) {
            if (in_array_case($name, $this->proxyArray)) {
                return $this->proxy->setRedis($this->getRedis())->{$name}(...$arguments);
            }
            return call_user_func_array([$this->getRedis(), $name], $arguments);
        } else {
            self::$pipelineInstance->push($name, $arguments);
            return $this;
        }
    }

    /**
     * @throws RedisClusterException
     * @throws RedisException
     */
    public function __destruct()
    {
        if ($this->isConnect) {
            $redis = $this->getRedis();
            if (method_exists($redis, 'close')) {
                $redis->close();
            }
        }
    }

    /**
     * @return bool
     * @author xiongba
     * @date 2019-12-12 16:46:19
     */
    public function connect()
    {
        return true;
    }


    /**
     * @return bool
     * @author xiongba
     * @date 2019-12-12 16:46:23
     */
    public function auth()
    {
        return true;
    }

    public function getWithSerialize($key)
    {
        $data = $this->getRedis()->get($key);
        if (is_null($data)) {
            return false;
        }
        return unserialize($data);
    }

    public function setWithSerialize($key, $val, $time = false)
    {
        $this->getRedis()->set($key, serialize($val));
        if ($time) {
            $this->getRedis()->expire($key, $time);
        }
        return true;
    }


    /**
     * 修复 phpredis 的hScan函数
     * @param $key
     * @param $iterator
     * @param null $pattern
     * @param int $count
     * @return array|false
     * @author xiongba
     * @date 2019-12-23 17:20:11
     */
    public function hScan($key, &$iterator, $pattern = null, $count = null)
    {
        try {
            $argv = func_get_args();
            array_shift($argv);
            array_shift($argv);
            return $this->getRedis()->hScan($key, $iterator, ...$argv);
        } catch (Throwable $e) {
            $iterator = 0;
            return false;
        }
//        $data = $this->scanLuaScript("HSCAN", $key, $iterator, $pattern, $count);
//        $array = [];
//        $count = count($data);
//        for ($i = 0; $i < $count; $i += 2) {
//            $array[$data[$i]] = $data[$i + 1];
//        }
//        return $array;
    }

    /**
     * @return bool
     * @author xiongba
     */
    public function isCluster(): bool
    {
        return $this->isCluster;
    }

    /**
     * 获取并将获取到的数据移除集合
     * @param string $key key
     * @param int $count 每次获取多少条数据
     * @param callable|null $callback 如果没有有数据，将会使用该回调函数的返回的值作为集合的数据
     * @param int $timeout 过期期间
     * @return array
     */
    public function sPopx($key, $count, $callback = null, $timeout = 7200)
    {
        try {
            $redis = $this->getRedis();
            $members = $redis->sPop($key, $count);
            if (empty($members)) {
                if (is_callable($callback) === false) {
                    return [];
                }
                $smembers = $callback();
                if (!empty($smembers)) {
                    $result = collect($smembers)->slice(0, $count)->toArray();
                    $redisPipe = $redis->multi();
                    collect($smembers)->slice($count, -1)->chunk(200)->map(function ($item) use ($key, $redisPipe) {
                        $redisPipe->sAddArray($key, $item->toArray());
                    });
                    $redisPipe->expire($key, $timeout);
                    $redisPipe->exec();
                    return $result;
                } else {
                    return [];
                }
            }
        } catch (\Throwable $e) {
            trigger_error((string)$e , E_USER_WARNING);
            $members = [];
        }
        return $members;
    }

    /**
     * 获取并将获取到的数据移除有序列表
     * @param string $key key
     * @param int $start
     * @param int $end
     * @param callable|null $callback 如果没有有数据，将会使用该回调函数的返回的值作为集合的数据
     * @param bool|integer $withScore  如果是int类型，表示需要设置的过期时间。表示$withScore要和$timeout的作用交换
     * @param int|null|bool $timeout 过期期间，如果是null，默认过期时间7200
     * @param bool $isRev 是否倒序
     * @return array
     */
    public function zPopRange(
        string $key,
        int $start,
        int $end,
        callable $callback = null,
        $withScore = false,
        $timeout = null,
        bool $isRev = false
    ) {
        try {
            if (is_int($withScore)) {
                //和timeout的参数交换值
                $timeout = !empty($timeout);
                list($withScore, $timeout) = [$timeout, $withScore];
            }
            if ($timeout === null) {
                $timeout = 7200;
            }

            $redis = $this->getRedis();
            $zCount = $redis->zCard($key);
            if (empty($zCount)) {
                if (is_callable($callback) === false) {
                    return [];
                }
                $members = $callback();
                if (!empty($members)) {
                    $redisPipe = $redis->multi();
                    collect($members)->chunk(200)->map(function ($item) use ($key, $redisPipe) {
                        $args = [];
                        foreach ($item as $member => $score) {
                            $args[] = $score;
                            $args[] = $member;
                        }
                        $redisPipe->zAdd($key , ... $args);
                    });
                    $redisPipe->expire($key, $timeout);
                    $redisPipe->exec();
                } else {
                    return [];
                }
            }
            if ($isRev){
                $members = $redis->zRevRange($key, $start, $end, $withScore);
            }else{
                $members = $redis->zRange($key, $start, $end, $withScore);
            }
            if ($withScore){
                $keys = array_keys($members);
            }else{
                $keys = $members;
            }
            $redis->zRem($key, ...$keys);
        } catch (\Throwable $e) {
            trigger_error((string)$e , E_USER_WARNING);
            $members = [];
        }
        return $members;
    }

    /**
     * 时间复杂度度 O($count)
     * @param $key
     * @param $count
     * @param callable $callback
     * @param int $ttl
     * @return array
     */
    public function lPopCount($key, $count, callable $callback, $ttl = 7200)
    {
        $redis = $this->getRedis();
        $len = $redis->lLen($key);
        if (empty($len)) {
            $value = $callback();
            if (empty($value)) {
                return $value;
            }
            $data = array_slice($value, 0, $count);
            $redisPipe = $redis->multi();
            collect($value)->slice($count)->chunk(30)->each(function ($rows) use ($key, $redisPipe) {
                $redisPipe->rPush($key, ...$rows);
            });
            $redisPipe->expire($key, $ttl);
            $redisPipe->exec();
        } else {
            $data = $redis->lRange($key, 0, $count - 1);
            $redis->lTrim($key, $count, $len);
        }
        return $data;
    }




    /**
     * 获取并将获取到的数据移除有序列表
     * @param string $key key
     * @param int $start
     * @param int $end
     * @param callable|null $callback 如果没有有数据，将会使用该回调函数的返回的值作为集合的数据
     * @param bool|integer $withScore  如果是int类型，表示需要设置的过期时间。表示$withScore要和$timeout的作用交换
     * @param int|null|bool $timeout 过期期间，如果是null，默认过期时间7200
     * @return array
     */
    public function zRevPopRange(
        string $key,
        int $start,
        int $end,
        callable $callback = null,
        $withScore = false,
        $timeout = null
    ) {
        return $this->zPopRange($key, $start, $end, $callback, $withScore, $timeout, true);
    }


    private function scanLuaScript($command, $key, &$iterator, $pattern = null, $count = null)
    {
        static $luaAry = [
            'return redis.call("%command%", KEYS[1], ARGV[1], "MATCH", ARGV[2], "COUNT" , ARGV[3])',
            'return redis.call("%command%", KEYS[1], ARGV[1], "MATCH", ARGV[2])',
            'return redis.call("%command%", KEYS[1], ARGV[1], "COUNT", ARGV[2])',
            'return redis.call("%command%", KEYS[1], ARGV[1])'
        ];
        $args = [$key, intval($iterator)];
        if ($pattern !== null && $count !== null) {
            $lua = $luaAry[0];
            $args[] = $pattern;
            $args[] = $count;
        } elseif ($pattern !== null) {
            $lua = $luaAry[1];
            $args[] = $pattern;
        } elseif ($count !== null) {
            $lua = $luaAry[2];
            $args[] = $count;
        } else {
            $lua = $luaAry[3];
        }
        $lua = str_replace('%command%', $command, $lua);
        list($iterator, $data) = $this->eval($lua, $args, 1);
        return $data;
    }


    /**
     * 修复 phpredis 的hScan函数
     * @param $key
     * @param $iterator
     * @param null $pattern
     * @param int $count
     * @return array|false
     * @author xiongba
     * @date 2019-12-23 17:20:11
     */
    public function sScan($key, &$iterator, $pattern = null, $count = null)
    {
        //return $this->scanLuaScript('SSCAN', $key, $iterator, $pattern, $count);
        try {
            return $this->getRedis()->sScan($key, $iterator, $pattern, $count);
        } catch (Throwable $e) {
            $iterator = 0;
            return false;
        }
    }


    public function sScanDiff($key1, $key2, $count = 50)
    {
        $key3 = $key2 . ':iterator';
        /** @var \Redis $redis */
        $redis = $this->getRedis();
        $oldIterator = $iterator = $redis->get($key3) ?: null;
        $userHistoryVidArray = $redis->sMembers($key2);
        $list = [];
        do {
            $tmpList = $redis->sScan($key1, $iterator, null, $count);
            $_list = array_diff($tmpList, $userHistoryVidArray);
            $list = array_merge($list, $_list);
            if (count($list) >= $count) {
                break;
            }
        } while (!empty($iterator));
        if (empty($iterator)) {
            if (!empty($oldIterator)) {
                $redis->del($key3);
            }
        } else {
            $redis->set($key3, $iterator, 10000);
        }
        return $list;
    }


    public function emptyPrefixExec($codeSpace)
    {
        $redis = clone $this->getRedis();
        $redis->setOption(Redis::OPT_PREFIX, '');
        call_user_func($codeSpace, $redis);
    }


    public function sLength($key)
    {
        try {
            return $this->getRedis()->sCard($key);
        } catch (Throwable $e) {
            return false;
        }
    }


    public function sIsMemberByAry(string $memberKey, array $memberValueArray)
    {
        /** @var RedisCluster $redis */
        $redis = $this->getRedis()->multi(2);
        foreach ($memberValueArray as $member) {
            $redis->sIsMember($memberKey, $member);
        }
        $result = $redis->exec();
        return array_combine($memberValueArray , $result);
    }

    protected function singleCluster($args, \Closure $single, \Closure $cluster)
    {
        if (count($this->hosts) > 1) {
            return $cluster(...$args);
        } else {
            return $single(...$args);
        }
    }

    /**
     * @return \Redis|\RedisCluster
     * @author xiongba
     * @date 2019-12-23 19:44:02
     */
    public function pipeline()
    {
        /** @var RedisCluster $redis */
        $redis = $this->getRedis();
        return $this->singleCluster([$redis], function ($redis) {
            return $redis->pipeline();
        }, function ($redis) {
            if (self::$pipelineInstance === null) {
                self::$pipelineInstance = new RedisPipeline();
            }
            return $this;
        });
    }


    /**
     * @return mixed
     * @author xiongba
     * @date 2020-01-08 17:30:19
     */
    public function exec()
    {
        $redis = $this->getRedis();
        return $this->singleCluster([$redis], function ($redis) {
            /** @var Redis $redis */
            return $redis->exec();
        }, function ($redis) {
            /** @var RedisCluster $redis */
            if (self::$pipelineInstance) {
                $results = [];
                self::$pipelineInstance->each(function ($name, $args) use (&$results, $redis) {
                    $results[] = $redis->{$name}(...$args);
                });
                self::$pipelineInstance = null;
                return $results;
            }
            return $redis->exec();
        });
    }

    /**
     * @param string $file
     * @param null $content
     * @return null
     * @author xiongba
     */
    public function syncFile($file, $content = null)
    {
        $key = str_replace([':', '\\', '/'], '_', $file);
        try {
            $redis = $this->getRedis();
        } catch (RedisException $e) {
            exit('redis 不支持');
        }
        if ($content === null) {
            $time = $redis->hGet($key, "time");
            if (empty($time)) {
                return null;
            }
            if (file_exists($file) && filemtime($file) > $time) {
                return $file;
            }
            $fileDir = dirname($file);
            if (!file_exists($fileDir)) {
                mkdir($fileDir, 0755, true);
            }
            $content = $redis->hGet($key, 'context');
            if (file_put_contents($file, $content) == strlen($content)) {
                return $file;
            }
            return null;
        } else {
            $redis->hSet($key, 'time', time());
            $redis->hSet($key, 'context', $content);
        }
        return $file;
    }


    /**
     * @param mixed $lockKey 锁的名字
     * @param callable $callback 锁住之后做什么
     * @param int $lockExpire 锁过期时间
     * @param null $lockFn
     * @return mixed
     * @throws \RedisClusterException
     * @throws \RedisException
     * @author xiongba
     */
    public function lock($lockKey, callable $callback, $lockExpire = 3 , $lockFn = null)
    {
        $key = 'lk:' . $lockKey;
        $redis = $this->getRedis();
        if ($redis->setnx($key, 1)) {
            $redis->expire($key, $lockExpire);
            try {
                $result = call_user_func($callback);
                $redis->del($key);
                return $result;
            } catch (\Throwable $e) {
                $redis->del($key);
                throw $e;
            }
        } else {
            if (is_callable($lockFn)){
                $lockFn();
            }else{
                throw new \RuntimeException('Lock is occupied ' . $key);
            }
        }
    }

    /**
     * @author xiongba
     * @date 2020-01-08 17:30:09
     */
    public function discard()
    {
        if (self::$pipelineInstance) {
            self::$pipelineInstance = null;
            return;
        }
        $redis = $this->getRedis();
        $redis->discard();
        return;
    }

}