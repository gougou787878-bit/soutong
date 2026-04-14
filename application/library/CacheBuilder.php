<?php


/**
 * Class CacheDb
 * @author xiongba
 * @date 2019-12-13 19:27:09
 */
class CacheBuilder
{
    /**
     * @var CacheDb
     */
    protected $driver;
    /**
     * @var \Illuminate\Database\Query\Builder
     */
    protected $builder;


    /**
     * CacheBuilder constructor.
     * @param $cached
     * @param $builder
     * @author xiongba
     * @date 2020-02-21 20:10:04
     */
    public function __construct($cached, $builder)
    {
        $this->driver = $cached;
        $this->builder = $builder;
    }


    /**
     * 统计
     * @param string $column 需要统计的字段
     * @param bool $refreshCache 是否刷新缓存
     * @return int
     * @author xiongba
     * @date 2019-12-13 17:18:50
     */
    public function count($column = '*', $refreshCache = false)
    {
        $count = null;
        if (!$refreshCache) {
            $count = $this->getDriver();
        }
        if ($count === null) {
            $count = $this->builder->count($column);
            if ($this->getDriver()->saveEmpty || !empty($count)) {
                $this->getDriver()->setCache($count);
            }
        }
        return (int)$count;
    }


    /**
     * 获取builder数据
     * @param string $columns 字段
     * @param bool $refreshCache 是否穿透缓存
     * @return mixed
     * @author xiongba
     * @date 2019-11-16 14:44:26
     */
    public function _get($columns = '*', $refreshCache = false)
    {
        return Pipeline::send($this->builder)
            ->through(function ($builder, $next) use ($refreshCache) {
                if ($refreshCache) {
                    return $next($builder);
                }
                return $this->getDriver() ?? $next($builder);
            })
            ->then(function ($builder) use ($columns) {
                /** @var \Illuminate\Database\Query\Builder $builder */
                $data = $builder->get($columns);
                if ($this->getDriver()->saveEmpty || $data && !$data->isEmpty()) {
                    $this->getDriver()->setCache($data);
                }
                return $data;
            });
    }

    /**
     * 获取builder数据
     * @param string $columns 字段
     * @param bool $refreshCache 是否穿透缓存
     * @return mixed
     * @author xiongba
     * @date 2019-11-16 14:44:26
     */
    public function get($columns = '*', $refreshCache = false)
    {
        return $this->getDriver()->fetch(function () use ($columns) {
            return $this->builder->get($columns);
        }, [], $refreshCache);
    }


    /**
     * 取一条数据
     * @param array|string $columns 字段
     * @param bool $refreshCache 是否穿透缓存
     * @return mixed
     * @author xiongba
     * @date 2019-11-16 14:44:26
     */
    public function first($columns = '*', $refreshCache = false)
    {
        return $this->getDriver()->fetch(function () use ($columns) {
            return $this->builder->first($columns);
        }, [], $refreshCache);
    }


    /**
     * @return CacheDb
     * @author xiongba
     */
    public function getDriver(): CacheDb
    {
        return $this->driver;
    }


}