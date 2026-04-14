<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

class BaseModel extends Model
{
    /**
     * 重新依赖注入
     * @param \Illuminate\Database\Query\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder|Model|NewBuilderModel
     */
    const UPDATED_AT = null;
    const CREATED_AT = null;
    public static $cache;

    public function newEloquentBuilder($query)
    {
        return new NewBuilderModel($query);
    }

    public static function instance()
    {
        return new self;
    }


    protected static function cacheInstance()
    {
        if (!isset(static::$cache)) {
            static::$cache = new LibRedis();
        }
        return static::$cache;
    }

    public function sqlWithCache($builder, $all = true, $userCache = true, $cacheType = 'SQL缓存', $cacheTime = 0)
    {
        $sql = $builder->toSql();
        $parameters = serialize($builder->getBindings());
        $hashKey = hash("sha256", ($sql) . $parameters);

        $redis = new \tools\RedisService();

        $oauth_id = $_POST['oauth_id'] ?? '';
        $oauth_type = $_POST['oauth_type'] ?? '';
        $uuid = md5($oauth_type . $oauth_id);
        $redisKey = 'user:' . $uuid;
        if ($userCache && ($data = $redis->hGet($redisKey, 'user_opr_' . $hashKey))) {
            return unserialize($data);
        }

        if ($data = static::cacheInstance()->get($hashKey)) {
            return $data;
        }

        if ($all) {
            $data = $builder->get()->toArray();
        } else {
            $data = $builder->first()->toArray();
        }
        $data = self::processArray($data);
        if ($userCache) {
            $redis->hSet($redisKey, 'user_opr_' . $hashKey, serialize($data));
        } else {
            static::cacheInstance()->set($hashKey, $data, Yaf\Registry::get('config')->sqlCacheExpire);
            self::syncCacheKeys($cacheType, $hashKey);
        }

        return $data;

    }


    public static function syncCacheKeys($cacheType, $hashKey)
    {
        CacheKeysModel::where([
            'name' => $cacheType
        ])->delete();

        CacheKeysModel::insert([
            'name' => $cacheType,
            'key' => $hashKey,
        ]);
    }


    public static function processArray($obj)
    {
        if (!$obj) {
            return [];
        }
        $_arr = is_object($obj) ? get_object_vars($obj) : $obj;
        $arr = [];
        foreach ($_arr as $key => $val) {
            $val = (is_array($val) || is_object($val)) ? self::processArray($val) : $val;
            $arr[$key] = $val;
        }
        return $arr;
    }


}