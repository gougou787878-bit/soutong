<?php

namespace tools;

/**
 *
 * @mixin \Yac
 */
class LibYac
{
    /** @var \Yac */
    protected $yac = null;
    protected $prefix = '';

    public function __construct(string $prefix = "")
    {
        $this->yac = new \Yac($prefix);
    }

    public function __call($name, $arguments)
    {
        $key = $arguments[0];
        if (isset($key[25])){
            $arguments[0] = substr(md5($arguments[0]) , 0 , 20);
        }
        return call_user_func_array([$this->yac , $name] , $arguments);
    }


    public function incr(string $key, int $value = 1): int
    {
        $v = (int)$this->get($key);
        $v += $value;
        $this->set($key, $value);
        return $v;
    }


    public function expire(string $key, int $ttl = 1): ?bool
    {
        $v = $this->get($key);
        if ($v === null) {
            return null;
        }
        return $this->set($key, $v, $ttl);
    }


    public function fetch($key, $closure, $ttl = 7200)
    {
        $tmp = $this->get($key);
        if (empty($tmp)) {
            $value = lib_value($closure);
            $this->set($key, serialize($value), $ttl);
        } else {
            $value = unserialize($tmp);
        }
        return $value;
    }


}