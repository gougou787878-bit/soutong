<?php


/**
 * Class RedisCached
 * @author xiongba
 * @date 2019-11-28 15:36:23
 */
class RedisProxy
{
    /**
     * @var Redis|RedisCluster
     */
    protected $redis;
    /**
     * @var bool
     */
    private $isCluster;

    /**
     * @param Redis|RedisCluster $redis
     * @return RedisProxy
     * @author xiongba
     */
    public function setRedis($redis): RedisProxy
    {
        $this->redis = $redis;
        $this->isCluster = !($redis instanceof Redis);
        return $this;
    }




    /**
     * @see https://redis.io/commands/sdiff
     * @param $key1
     * @param mixed ...$otherKeys
     * @return array
     */
    public function sDiffWithPHP($key1, ...$otherKeys)
    {
        $redis = $this->getRedis();
        $arrays[] = $redis->sMembers($key1);
        foreach ($otherKeys as $otherKey) {
            $arrays[] = $redis->sMembers($otherKey);
        }
        return array_diff(...$arrays);
    }

    /**
     * @see https://redis.io/commands/sdiffstore
     * @param $dstKey
     * @param $key1
     * @param mixed ...$otherKeys
     * @return bool|false|int
     */
    public function sDiffStoreWithPHP($dstKey, $key1, ...$otherKeys)
    {
        $arrayDiff = $this->sDiffWithPHP($key1, ...$otherKeys);
        return $this->getRedis()->sAddArray($dstKey, $arrayDiff);
    }

    /**
     * @see https://redis.io/commands/sdiff
     * @param $key1
     * @param mixed ...$otherKeys
     * @return array
     */
    public function sUnionWithPHP($key1, ...$otherKeys)
    {
        $redis = $this->getRedis();
        $arrays[] = $redis->sMembers($key1);
        foreach ($otherKeys as $otherKey) {
            $arrays[] = $redis->sMembers($otherKey);
        }
        return array_merge(...$arrays);
    }

    /**
     * @see https://redis.io/commands/sdiffstore
     * @param $dstKey
     * @param $key1
     * @param mixed ...$otherKeys
     * @return false|int
     */
    public function sUnionStoreWithPHP($dstKey, $key1, ...$otherKeys)
    {
        $arrayDiff = $this->sUnionWithPHP($key1, ...$otherKeys);
        return $this->getRedis()->sAddArray($dstKey, $arrayDiff);
    }

    /**
     * @see https://redis.io/commands/sdiff
     * @param $key1
     * @param mixed ...$otherKeys
     * @return array|false
     */
    public function sUnion($key1, ...$otherKeys)
    {
        if (isset($this->hosts[1])) {
            return $this->sUnionWithPHP($key1, ...$otherKeys);
        } else {
            return $this->getRedis()->sUnion($key1, ...$otherKeys);
        }
    }

    /**
     * @see https://redis.io/commands/sdiffstore
     * @param $dstKey
     * @param $key1
     * @param mixed ...$otherKeys
     * @return false|int
     */
    public function sUnionStore($dstKey, $key1, ...$otherKeys)
    {
        if (isset($this->hosts[1])) {
            return $this->sUnionStoreWithPHP($dstKey, $key1, ...$otherKeys);
        } else {
            return $this->getRedis()->sUnionStore($dstKey, $key1, ...$otherKeys);
        }
    }

    /**
     * @see https://redis.io/commands/sdiff
     * @param $key1
     * @param mixed ...$otherKeys
     * @return array
     */
    public function sInterWithPHP($key1, ...$otherKeys)
    {
        $redis = $this->getRedis();
        $arrays[] = $redis->sMembers($key1);
        foreach ($otherKeys as $otherKey) {
            $arrays [] = $redis->sMembers($otherKey);
        }
        return array_intersect(...$arrays);
    }

    /**
     * @see https://redis.io/commands/sdiffstore
     * @param $dstKey
     * @param $key1
     * @param mixed ...$otherKeys
     * @return bool|false|int
     */
    public function sInterStoreWithPHP($dstKey, $key1, ...$otherKeys)
    {
        $arrayDiff = $this->sInterWithPHP($key1, ...$otherKeys);
        return $this->getRedis()->sAddArray($dstKey, $arrayDiff);
    }


    /**
     * @see https://redis.io/commands/sdiffstore
     * @param $key1
     * @param mixed ...$otherKeys
     * @return array
     */
    public function sInter($key1, ...$otherKeys)
    {
        if ($this->isCluster) {
            return $this->sInterWithPHP($key1, ...$otherKeys);
        } else {
            return $this->getRedis()->sInter($key1, ...$otherKeys);
        }
    }


    /**
     * @see https://redis.io/commands/sdiffstore
     * @param $dstKey
     * @param $key1
     * @param mixed ...$otherKeys
     * @return bool|false|int
     */
    public function sInterStore($dstKey, $key1, ...$otherKeys)
    {
        if ($this->isCluster) {
            return $this->sInterStoreWithPHP($dstKey, $key1, ...$otherKeys);
        } else {
            return $this->getRedis()->sInterStore($dstKey, $key1, ...$otherKeys);
        }
    }


    /**
     * 从key1的set中取出一部分进行差比较
     * @param int $count 每次扫描多少来进行差异
     * @param int|null &$iterator 迭代器，如果把$iterator保存。下一次使用本函数在传递回去。效率比较高效
     * @param string $key1 需要diff的
     * @param string[] ...$otherKeys 要和哪些set进行diff
     * @return array
     */
    public function sDiffOfScan($count, &$iterator, $key1, ...$otherKeys)
    {
        $redis = $this->getRedis();
        $diffArray = [];
        foreach ($otherKeys as $otherKey) {
            $diffArray[] = $redis->sMembers($otherKey);
        }
        $diffArray = array_merge(...$diffArray);

        $iterator = null;
        $arrays = [];
        do {
            $array = $redis->sScan($key1, $iterator, null, $count);
            if (empty($array)) {
                break;
            }
            $diffResult = array_diff($array, $diffArray);
            $arrays = array_merge($arrays, $diffResult);
            if (count($arrays) >= $count) {
                break;
            }
        } while (!empty($iterator));
        return $arrays;
    }

    /**
     * 从key1的set中取出一部分进行差比较,并将结果保存到dstKey中
     * @param int $count 取多少
     * @param $iterator
     * @param string $dstKey
     * @param string $key1
     * @param string[] ...$otherKeys
     * @return false|int
     */
    public function sDiffOfScanStore($count, &$iterator, $dstKey, $key1, ...$otherKeys)
    {
        $arrayDiff = $this->sDiffOfScan($count, $iterator, $key1, ...$otherKeys);
        try {
            return $this->getRedis()->sAddArray($dstKey, $arrayDiff);
        } catch (Throwable $e) {
            return false;
        }
    }


    /**
     * @see https://redis.io/commands/sdiff
     * @param $key1
     * @param mixed ...$otherKeys
     * @return array
     */
    public function sDiff($key1, ...$otherKeys)
    {
        if ($this->isCluster) {
            return $this->sDiffWithPHP($key1, ...$otherKeys);
        } else {
            return $this->getRedis()->sDiff($key1, ...$otherKeys);
        }
    }

    /**
     * 已兼容集群
     * @see https://redis.io/commands/sdiffstore
     * @param $dstKey
     * @param $key1
     * @param mixed ...$otherKeys
     * @return false|int
     */
    public function sDiffStore($dstKey, $key1, ...$otherKeys)
    {
        $redis = $this->getRedis();
        return $this->singleCluster([], function () use ($redis, $dstKey, $key1, $otherKeys) {
            return $redis->sDiffStore($dstKey, $key1, ...$otherKeys);
        }, function () use ($redis, $dstKey, $key1, $otherKeys) {
            return $this->sDiffStoreWithPHP($dstKey, $key1, ...$otherKeys);
        });
    }

    /**
     * @return Redis|RedisCluster
     * @author xiongba
     */
    public function getRedis()
    {
        return $this->redis;
    }


    public function singleCluster($args, \Closure $single, \Closure $cluster)
    {
        if ($this->isCluster) {
            return $cluster(...$args);
        } else {
            return $single(...$args);
        }
    }


}