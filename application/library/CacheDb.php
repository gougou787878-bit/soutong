<?php


/**
 * Class CacheDb
 * @author xiongba
 * @date 2019-12-13 19:27:09
 */
class CacheDb
{

    /**
     * @var \Redis
     */
    private $driver;

    const TYPE_STRING = 1;
    const TYPE_HASH = 2;

    const SERIALIZER_NONE = 0;
    const SERIALIZER_PHP = 1;
    const SERIALIZER_JSON = 2;


    protected $expired = 120;
    /**
     * @var string 缓存的key
     */
    private $key;
    /**
     * @var int 使用的缓存类型
     */
    private $type = self::TYPE_STRING;
    /**
     * @var string|null 如果缓存是hash类型，需要的hashKey
     */
    private $hashKey;

    private $serializer = self::SERIALIZER_NONE;
    /**
     * @var bool 空数据是否也保存
     */
    public $saveEmpty = false;
    /**
     * @var bool 二级缓存启用状态
     */
    public $enableDeep = false;

    private $suffix = '';
    private $groupBy = '';
    private $search = [];
    private $replace = [];
    private $replaceSearch = [];


    /**
     * CacheDb constructor.
     * @param $driver
     * @author xiongba
     * @date 2019-11-16 10:58:12
     */
    public function __construct($driver = null)
    {
        $this->driver = $driver;
    }

    /**
     * 事例一个对象
     * @param $driver
     * @return CacheDb
     * @author xiongba
     * @date 2019-11-18 19:00:52
     */
    public static function make($driver)
    {
        return new self($driver);
    }


    /**
     * 设置数据库查询builder
     * @param object|\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Query\Builder $builder 据库查询builder
     * @return CacheBuilder
     * @author xiongba
     * @date 2019-11-18 19:00:43
     */
    public function builder($builder)
    {
        return new CacheBuilder($this, $builder);
    }


    /**
     * 设置空数据是否也缓存
     * @param bool $saveEmpty
     * @return $this
     * @author xiongba
     * @date 2019-12-13 19:38:05
     */
    public function setSaveEmpty(bool $saveEmpty)
    {
        $this->saveEmpty = $saveEmpty;
        return $this;
    }


    /**
     * 设置过期时间
     * 参数：$expired 过期时间。
     *               可以接受一个回调函数，回调函数圆形 callback($data)
     *
     * @param int|callable $expired $expired 过期时间,如果是回调函数，会使用回调函数计算一个时间,回调函数接受一个参数，参数为
     * @return $this
     * @author xiongba
     * @date 2019-12-13 17:21:33
     */
    public function expired($expired)
    {
        $this->expired = $expired;
        return $this;
    }

    /**
     * 选择压缩的字符串
     * @param array|string $search 需要压缩的值，如果值的字符串长度小于等于10个，将没有压缩的意义
     * @return $this
     * @author xiongba
     * @date 2020-03-09 10:31:10
     */
    public function compress($search)
    {
        $this->search = (array)$search;
        $this->replaceSearch = array_map(function ($str){
            return str_replace('/' ,'\\/' , addslashes($str));
        }, $this->search);
        foreach ($this->search as $k => $search) {
            $this->replace[] = '{%!A' . $k . '_^!%}';
        }
        return $this;
    }

    /**
     * 获取缓存key
     * @return string
     * @author xiongba
     * @date 2019-12-13 17:23:27
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * 设置缓存的key
     * @param null $key
     * @return $this
     * @author xiongba
     * @date 2019-12-13 17:23:27
     */
    public function setKey($key)
    {
        $this->key = $key;
        return $this;
    }


    /**
     * 设置或者获取 缓存类型
     * @param null $type
     * @param null $hashKey
     * @return $this|int
     * @author xiongba
     * @date 2019-12-13 17:26:50
     */
    public function setType($type, $hashKey = null)
    {
        if (!in_array($type, [self::TYPE_HASH, self::TYPE_STRING])) {
            throw new InvalidArgumentException('不支持的类型');
        }
        if ($type == self::TYPE_HASH) {
            if ($hashKey === false || $hashKey === null || strlen($hashKey) === 0) {
                throw new InvalidArgumentException('hash类型必须设置hashKey');
            }
            $this->hashKey = $hashKey;
        }
        $this->type = $type;
        return $this;
    }


    public function hash($hashKey)
    {
        return $this->setType(self::TYPE_HASH , $hashKey);
    }

    /**
     * 缓存处理
     * @param $data
     * @param string $suffix
     * @return mixed
     * @author xiongba
     * @date 2019-12-13 19:44:41
     */
    public function setCache($data, $suffix = '')
    {
        $expired = $this->expired;
        if (is_callable($expired)) {
            $expired = call_user_func($expired, $data);
        }
        if ($expired < 0) {
            return $data;
        }
        $this->validatorKey();
        $data = $this->callSerializer($data);
        $key = $this->key . (empty($suffix) ? $this->suffix : $suffix);
        switch ($this->type) {
            case self::TYPE_STRING:
                $this->driver->set($key, $data, $expired);
                break;
            case self::TYPE_HASH:
                $this->driver->hSet($key, $this->hashKey, $data);
                $this->driver->expire($key, $expired);
                break;
            default:
                throw new \InvalidArgumentException('缓存类型错误');
                break;
        }

        if ($this->groupBy) {
            $this->driver->sAdd(self::__GROUP_KEY.$this->groupBy, $key);
        }

        if ($this->_chinese) {
            try {
                if (class_exists('\service\CacheKeyService')) {
                    \service\CacheKeyService::adder($this->_chinese, $this->groupBy);
                }
            } catch (\Throwable $e) {
            }
        }

        return $data;
    }


    /**
     * 执行查询
     * @param callable $fetchFn 查询的回调方法
     * @param array $args 回调方法的参数
     * @param bool $refreshCache 是否刷新缓存
     * @return mixed
     * @author xiongba
     * @date 2019-12-16 14:33:53
     */
    public function fetch(callable $fetchFn, array $args = [], $refreshCache = false)
    {
        if (!$refreshCache && $this->expired) {
            $data = $this->getCache();
            if ($data !== null) {
                return $data;
            }
        }
        $args[] = $this;
        $data = call_user_func_array($fetchFn, $args);
        if ($this->expired){
            //$data = call_user_func($this->deepCachedCallback($fetchFn), $args);
            if ($this->saveEmpty) {
                $this->setCache($data);
            } elseif (is_object($data)) {
                if (method_exists($data, 'isEmpty')) {
                    if (!$data->isEmpty()) {
                        $this->setCache($data);
                    }
                } else {
                    $this->setCache($data);
                }
            } elseif (!empty($data)) {
                $this->setCache($data);
            }
        }
        return $data;
    }


    protected function deepCachedCallback($fetchCb)
    {
        return function ($args) use ($fetchCb) {
            $data = call_user_func_array($fetchCb, $args);
            if ($this->enableDeep) {
                $cached = clone $this;
                $cached->setKey($this->key . ':deep')->expired(999999999)->enableDeep(false);
                if (empty($data)) {
                    $data = $cached->getCache();
                    if (empty($data) && is_callable($this->enableDeep)) {
                        $data = call_user_func_array($this->enableDeep, $args);
                    }
                    return $data;
                }
                $cached->setCache($data);
            }
            return $data;
        };
    }

    /**
     * @param bool|callable $stateOrCb 深度缓存状态或者深度缓存都没有命中后的回调，函数
     * @return $this
     * @author xiongba
     */
    public function enableDeep($stateOrCb)
    {
        $this->enableDeep = $stateOrCb;
        return $this;
    }



    private function callSerializer($data)
    {
        if ($this->serializer === self::SERIALIZER_PHP) {
            return $this->replace(serialize($data));
        } elseif ($this->serializer === self::SERIALIZER_JSON) {
            return $this->replace(json_encode($data));
        } else {
            return $this->replace($data);
        }
    }

    private function replace($data)
    {
        $data = str_replace($this->replaceSearch, $this->replace, $data);
        if (!function_exists('gzcompress')) {
            return $data;
        }
        if (isset($data[20480])) {
            return pack('nnn', 122, 222, 231).gzcompress($data);
        }
        return pack('nnn', 1, 0, 0).$data;
    }

    private function unreplace($data)
    {
        $data = str_replace($this->replace, $this->search, $data);
        if (!function_exists('gzuncompress')) {
            return $data;
        }
        $verify = unpack('na/nb/nc', $data);
        $buffer = substr($data, 6);
        if ($verify['a'] == 122 && $verify['b'] == 222 && $verify['c'] == 231) {
            return gzuncompress($buffer);
        } elseif ($verify['a'] == 1 && $verify['b'] == 0 && $verify['c'] == 0) {
            return $buffer;
        } else {
            return $data;
        }
    }

    private function callUnSerializer($data)
    {
        $cals = [
            self::SERIALIZER_PHP => function ($d) {
                $data = @unserialize($d);
                return $data;
            },
            self::SERIALIZER_JSON => function ($d) {
                return @json_decode($d, 1);
            },
        ];


        if (isset($cals[$this->serializer])) {
            $data = call_user_func($cals[$this->serializer], $this->unreplace($data));
            return $data;
        }
        return $this->unreplace($data);
    }

    /**
     * @param string $suffix
     * @return mixed
     * @author xiongba
     * @date 2019-12-13 17:00:34
     */
    public function getCache($suffix = '')
    {
        $this->validatorKey();
        $key = $this->key . (empty($suffix) ? $this->suffix : $suffix);
        switch ($this->type) {
            case self::TYPE_STRING:
                $data = $this->driver->get($key);
                break;
            case self::TYPE_HASH:
                $data = $this->driver->hGet($key, $this->hashKey);
                break;
            default:
                throw new \InvalidArgumentException('缓存类型错误');
                break;
        }
        if ($data !== false) {
            return $this->callUnSerializer($data);
        }
        return null;
    }


    /**
     * 清除缓存
     * @return $this
     * @author xiongba
     * @date 2019-12-13 18:17:17
     */
    public function clearCached()
    {
        $this->validatorKey();
        $key = $this->key . $this->suffix;
        switch ($this->type) {
            case self::TYPE_STRING:
                $this->driver->del($key);
                break;
            case self::TYPE_HASH:
                $this->driver->hDel($key, $this->hashKey);
                break;
            default:
                throw new \InvalidArgumentException('缓存类型错误');
                break;
        }
        return $this;
    }


    /**
     * 验证key
     * @author xiongba
     * @date 2019-12-13 17:26:15
     */
    private function validatorKey()
    {
        if (empty($this->key)) {
            throw new \InvalidArgumentException('请设置缓存key');
        }
        if ($this->type == self::TYPE_HASH && empty($this->hashKey)) {
            throw new \InvalidArgumentException('hash缓存请设置hasKey');
        }
    }

    /**
     * @return int
     * @author xiongba
     */
    public function getSerializer(): int
    {
        return $this->serializer;
    }

    /**
     * @param int $serializer
     * @return $this
     * @author xiongba
     * @date 2019-12-13 17:47:01
     */
    public function serializer(int $serializer)
    {
        $this->serializer = $serializer;
        return $this;
    }

    public function serializerPHP()
    {
        return $this->serializer(self::SERIALIZER_PHP);
    }

    public function serializerJSON()
    {
        return $this->serializer(self::SERIALIZER_JSON);
    }

    /**
     * @param string $suffix
     * @return CacheDb
     * @author xiongba
     */
    public function suffix(string $suffix)
    {
        $this->suffix = $suffix;
        return $this;
    }

    public function fetchJson($cb, $expired = 3600)
    {
        return $this->serializerJSON()
            ->expired($expired)
            ->fetch($cb);
    }

    public function fetchPhp($cb, $expired = 3600)
    {
        return $this->serializerPHP()
            ->expired($expired)
            ->fetch($cb);
    }

    /**
     * 将缓存进行分组
     *
     * @param string $group
     *
     * @return $this
     *
     * ```php
     * cached('page:' . $page)->group('group1')->fetch(function(){return 111;});
     * ```
     */
    public function group(string $group): CacheDb
    {
        $this->groupBy = $group;
        return $this;
    }

    protected $_chinese;

    /**
     * 使用中文分组，清理之后在后台清理
     * @param string $chineseGroup
     *
     * @return $this
     */
    public function chinese(string $chineseGroup): CacheDb
    {
        $this->_chinese = $chineseGroup;
        return $this;
    }

    /**
     * 清理分组的缓存
     *
     * @param ...$groups
     *
     * ```php
     * cached('')->clearGroup('group1' ,'group2')
     * ```
     */
    public function clearGroup(...$groups)
    {
        foreach ($groups as $group) {
            $ary = $this->driver->sMembers(self::__GROUP_KEY . $group);
            foreach ($ary as $key) {
                $this->driver->expire($key,3);
            }
            $this->driver->del(self::__GROUP_KEY . $group);
        }
    }

    const __GROUP_KEY = '__key:group_';

}