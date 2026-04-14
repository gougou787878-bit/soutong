<?php


namespace helper;


use Illuminate\Database\Eloquent\Collection;

class RestQuery
{

    protected $key;

    /**
     * @var \Redis
     */
    protected $redis;

    /**
     * @var \Redis
     */
    protected $pkName;
    /**
     * @var array
     */
    private $data;
    /**
     * @var int
     */
    private $size;
    /**
     * @var bool
     */
    private $enableNext;
    /**
     * @var bool
     */
    private $hasPage;
    /**
     * @var int
     */
    private $lastId;
    /**
     * @var int
     */
    private $nextPage;
    /**
     * @var int
     */
    private $curPage;
    /**
     * @var int
     */
    private $curTotal;
    /**
     * @var callable
     */
    private $filterCallback;


    const LIMIT = 100;
    const SIZE = 20;

    /**
     * 1800秒过期
     */
    const EXPIRE_AT = 1800;
    /**
     * @var callable
     */
    private $count;
    private $totalSize;
    /**
     * @var array
     */
    private $filterCallbackArgs;
    private $sort = self::SORT_ASC;

    const SORT_DESC = 1;
    const SORT_ASC = 2;
    const SORT = [
        self::SORT_DESC => 'desc',
        self::SORT_ASC  => 'desc',
    ];

    /**
     * RestQuery constructor.
     * @param string $key
     * @param array $params
     * @param \Redis $redis
     * @param string $pkName
     * @author xiongba
     * @date 2019-12-17 16:54:10
     */
    public function __construct($key, $params, $redis, $pkName)
    {
        $this->key = $key;
        $this->redis = $redis;
        $this->pkName = $pkName;
        $this->filterCallback = $this->getFilter();
        $this->parseParams($params);
    }


    /**
     * 实例对象，忽略redis和params参数
     * @param string $key redis要保存的key
     * @param string $pkName 数据的pk名称
     * @return RestQuery
     * @author xiongba
     * @date 2019-12-17 19:03:59
     */
    public static function newIgnore($key, $pkName)
    {
        return new self($key, json_decode(request()->input(), 1), redis(), $pkName);
    }


    /**
     * 统计
     * @param callable $count
     * @return $this
     * @author xiongba
     * @date 2019-12-17 19:00:53
     */
    public function count(callable $count)
    {
        $this->count = $count;
        return $this;
    }


    public function setSort($sort)
    {
        $this->sort = $sort;
        return $this;
    }

    /**
     * 获取数据
     * @param callable $dataCallback redis没有获取到数据时候，湖调用设置的回调 函数圆形, fn(&$offset, &$limit) : Collection | array
     * @return array
     * @throws \Exception
     * @author xiongba
     * @date 2019-12-17 17:15:37
     */
    public function all(callable $dataCallback)
    {
        $offset = $this->enableNext ? $this->nextPage : $this->curPage;
        $offset = $offset <= 1 ? 0 : $offset - 1;
        $limit = self::LIMIT;
        $offset *= $limit;
        $hashKey = "offset.$offset";

        if ($this->totalSize === null) {
            $this->totalSize = call_user_func($this->count);
        }
        if ($this->totalSize > $offset) {
            $data = $this->redis->hGet($this->key, $hashKey);
            if (!empty($data)) {
                $data = json_decode($data, 1);
            } else {
                $data = call_user_func_array($dataCallback, [&$offset, &$limit]);
                if ($data instanceof Collection && $data->isNotEmpty()) {
                    $array = $data->toArray();
                } elseif (!is_array($data)) {
                    throw new \Exception('数据类型错误');
                } else {
                    $array = $data;
                }
                if (!empty($array)) {
                    $this->redis->hSet($this->key, "offset.$offset", json_encode($array, JSON_UNESCAPED_UNICODE));
                    $ttl = $this->redis->ttl($this->key);
                    if ($ttl == -1) {
                        $this->redis->expire($this->key, self::EXPIRE_AT);
                    }
                }
            }
            $list = $this->filterData($data);
        } else {
            $list = [];
        }


        $hasNext = $this->totalSize > $offset + $limit;

        if ($this->enableNext === true && $hasNext === false) {
            $hasNext = false;
        } else {
            $hasNext = true;
        }


        $result = [
            'lastId'     => $this->lastId,
            'nextPage'   => $this->nextPage,
            'curTotal'   => $this->curTotal,
            'curPage'    => $this->curPage,
            'totalSize'  => $this->totalSize,
            'totalPage'  => ceil($this->totalSize / $limit),
            'hasNext'    => $hasNext,
            //'$offset'    => $offset,
            'enableNext' => $this->enableNext,
            'sort'       => self::SORT [$this->sort],
            'list'       => $list,
        ];
        return $result;
    }


    /**
     * 设置过滤器
     * @param callable $callback 函数原型 fn(array $item):bool
     * @param array $args
     * @return $this
     * @author xiongba
     * @date 2019-12-17 17:15:27
     */
    public function filter(callable $callback, array $args = [])
    {
        $this->filterCallback = $callback;
        $this->filterCallbackArgs = $args;
        return $this;
    }


    /**
     * @return \Closure
     * @author xiongba
     * @date 2019-12-17 17:39:32
     */
    protected function getFilter()
    {
        return function () {
            return false;
        };
    }

    /**
     * @param array $data
     * @author xiongba
     * @date 2019-12-17 17:39:36
     */
    protected function parseParams(array $data)
    {
        $this->lastId = $data['lastId'] ?? 0;
        $this->nextPage = $data['nextPage'] ?? 2;
        $this->curPage = $data['curPage'] ?? 1;
        $this->hasPage = $data['hasPage'] ?? false;
        $this->enableNext = $data['enableNext'] ?? false;
        $this->size = $data['size'] ?? self::SIZE;
        $this->curTotal = 0;
        $this->totalSize = $data['totalSize'] ?? null;
    }


    /**
     * 过滤数据
     * @param $data
     * @return array
     * @author xiongba
     * @date 2019-12-17 17:15:16
     */
    protected function filterData($data)
    {
        $count = count($data);
        $list = [];
        $lastId = $this->lastId;
        $this->curTotal = 0;
        for ($i = 0; $i < $count; $i++) {
            $item = $data[$i];
            if ($this->curTotal >= $this->size) {
                break;
            }
            $lastId = $item[$this->pkName];
            if ($this->sort == self::SORT_ASC) {
                if ($item[$this->pkName] <= $this->lastId) {
                    continue;
                }
            } else {
                if ($item[$this->pkName] >= $this->lastId) {
                    continue;
                }
            }


            if (true === call_user_func($this->filterCallback, $item, ...$this->filterCallbackArgs)) {
                continue;
            }
            $this->curTotal++;
            $list[] = $item;
        }
        $this->lastId = $lastId;


        if ($i === $count) {
            $this->enableNext = true;
        } elseif ($this->enableNext) {
            $this->curPage = $this->nextPage;
            $this->nextPage++;
            $this->enableNext = false;

        }
        return $list;
    }

}