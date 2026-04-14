<?php

/**
 * 使用redis业务基类
 */
class RedisBizModel
{
    protected $redisMainKey;
    protected $where = [];
    protected $select = [];


    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * @var array
     */
    protected $changes = [];

    protected static $_instance = [];

    protected $primaryKey = null;

    protected $_primaryKeyValue = null;

    protected $keyType = 'string';

    /**
     * @return static
     * @author xiongba
     * @date 2020-02-26 16:07:21
     */
    public static function instance()
    {
        $class = get_called_class();
        if (!isset(self::$_instance[$class])) {
            self::$_instance[$class] = new static();
        }
        return self::$_instance[$class];
    }

    /**
     * @param $id
     * @return static|null
     * @author xiongba
     */
    public static function find($id)
    {
        $that = new static();
        if ($that->keyType == 'hash') {
            $that->_primaryKeyValue = $id;
            $string = self::getRedis()->hGet($that->primaryKey, $id);
        } else {
            $that->_primaryKeyValue = $that->primaryKey . ':' . $id;
            $string = self::getRedis()->get($that->_primaryKeyValue);
        }
        if (empty($string)) {
            return null;
        }
        $that->attributes = unserialize($string);
        return $that;
    }

    /**
     * @param $a
     * @return static
     * @author xiongba
     */
    public static function make($a)
    {
        $that = new static();
        $that->attributes = $a;
        return $that;
    }

    /**
     * @return RedisCluster
     * @author xiongba
     */
    public static function getRedis()
    {
        return redis();
    }

    public static function create($id, array $data)
    {
        $that = new static();
        if ($that->keyType == 'hash') {
            if (self::getRedis()->hExists($that->primaryKey, $id)) {
                return null;
            }
        } else {
            $that->_primaryKeyValue = $that->primaryKey . ':' . $id;
            if (self::getRedis()->exists($that->_primaryKeyValue)) {
                return null;
            }
        }
        $that->update($data);
        return $that;
    }

    public function update($data = null)
    {
        if (empty($data)) {
            $data = [];
        }
        if (!is_array($data)) {
            $data = [];
        }
        $attributes = array_merge($this->attributes, $this->changes, $data);
        $string = serialize($attributes);
        if ($this->keyType == 'hash') {
            $result = self::getRedis()->hSet($this->primaryKey, $this->_primaryKeyValue, $string);
        } else {
            $result = self::getRedis()->set($this->_primaryKeyValue, $string);
        }
        $this->attributes = $attributes;
        return $result;
    }

    public function toArray()
    {
        return $this->attributes;
    }

    public function delete()
    {
        if ($this->keyType == 'hash'){
            return self::getRedis()->hDel($this->primaryKey, $this->_primaryKeyValue);
        }else{
            return self::getRedis()->del($this->_primaryKeyValue);
        }
    }

    public function __get($name)
    {
        return $this->attributes[$name] ?? null;
    }

    public function __set($name, $value)
    {
        $this->attributes[$name] = $value;
    }

}