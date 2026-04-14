<?php

use Illuminate\Database\Eloquent\Builder;

class NewBuilderModel extends Builder
{
    public static $cache;

    //

    /**
     * 重写get()
     * @param array $columns
     * @param bool $userCache
     * @param bool $cacheType
     * @param int $cacheTime
     * @return array|Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function get($columns = ['*'], $userCache = true, $cacheType = false, $isUser = false, $cacheTime = 0)
    {

        $builder = $this->applyScopes();

        // 以sql字符串的hash值为缓存的key
        $sql = $this->toSql();
        $parameters = serialize($this->getBindings());

        $hashKey = hash("sha256", ($sql) . $parameters);

        if ($cacheTime <= 0) {
            $cacheTime = Yaf\Registry::get('config')->sqlCacheExpire;
        }

        // If we actually found models we will also eager load any relationships that
        // have been specified as needing to be eager loaded, which will solve the
        // n+1 query issue for the developers to avoid running a lot of queries.
        if (count($models = $builder->getModels($columns)) > 0) {
            $models = $builder->eagerLoadRelations($models);
        }

        if (!$userCache) {
            return $builder->getModel()->newCollection($models);
        }

        if ($data = static::cacheInstance()->get($hashKey)) {
            return $data;
        }

        $redis = new \tools\RedisService();
        $oauth_id = $_POST['oauth_id'] ?? '';
        $oauth_type = $_POST['oauth_type'] ?? '';
        $uuid = md5($oauth_type . $oauth_id);
        $redisKey = 'user:' . $uuid;
        if ($userCache && ($data = $redis->hGet($redisKey, 'user_opr_' . $hashKey))) {
            return unserialize($data);
        }



        $data = $builder->getModel()->newCollection($models)->toArray();

        if ($isUser) {
            $redis->hSet($redisKey, 'user_opr_' . $hashKey, serialize($data));
        } else {
            static::cacheInstance()->set($hashKey, $data, $cacheTime);
            BaseModel::syncCacheKeys($cacheType, $hashKey);
        }

        return $data;
    }

    /**
     * 重写first()
     * Execute the query and get the first result.
     *
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Model|object|static|null
     */
    public function first($columns = ['*'], $userCache = true, $cacheType = 'SQL缓存', $cacheTime = 0)
    {
        if (!$userCache) {
            return $this->take(1)->get($columns, false)->first();
        }
        $data = $this->take(1)->get($columns, true);
        return !empty($data) ? $data[0] : [];
    }


    public function find($id, $columns = ['*'], $userCache = true, $cacheType = 'sql', $cacheTime = 0)
    {
        if (!$userCache) {
            return $this->where('id', '=', $id)->first($columns, false);
        }
        return $this->where('id', '=', $id)->first($columns);
    }


    protected static function cacheInstance()
    {
        if (!isset(static::$cache)) {
            static::$cache = new LibRedis();
        }
        return static::$cache;
    }

}