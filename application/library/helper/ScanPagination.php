<?php


namespace helper;


class ScanPagination
{
    /**
     * @var \Redis
     */
    protected $redis;
    protected $key;
    protected $iterator = null;
    protected $offset = 0;
    protected $limit = 20;
    protected $curPage = 0;
    /**
     * @var int
     */
    private $totalSize;
    private $hasNext;

    /**
     * ScanPagination constructor.
     * @param $redis
     * @param $key
     * @param array $config
     * @author xiongba
     * @date 2020-03-04 20:52:24
     */
    public function __construct($redis, $key, $config)
    {
        $this->redis = $redis;
        $this->key = $key;
        $this->iterator = empty($config['iterator']) ? null : $config['iterator'];
        $this->limit = intval($config['limit'] ?? '0');
        $this->totalSize = intval($config['totalSize'] ?? '0');
        $this->hasNext = intval($config['hasNext'] ?? '0');
        $this->curPage = intval($config['curPage'] ?? '0');
    }


    /**
     * 获取数据
     * @return array
     * @author xiongba
     * @date 2019-12-17 17:15:37
     */
    public function get()
    {
        if (empty($this->totalSize)) {
            $this->totalSize = $this->redis->sCard($this->key);
        }

        $all = [];
        if ($this->hasNext) {
            $all = $this->redis->sScan($this->key, $this->iterator, null, $this->limit);
        } else {
            $this->iterator = 1;
        }
        if (!empty($all) && empty($this->iterator)) {
            $this->offset = $this->curPage * $this->limit;
            $this->hasNext = count($all) > $this->offset + 20 ? 1 : 0;
            $all = array_slice($all, $this->offset, $this->limit);
        } elseif (empty($this->iterator)) {
            $this->hasNext = 0;
        } else {
            $this->hasNext = 1;
        }
        return $all;
    }

    /**
     * @return array
     */
    public function config(): array
    {
        return [
            'totalSize' => $this->totalSize,
            'curPage'   => $this->curPage + 1,
            'hasNext'   => $this->hasNext,
            'iterator'  => $this->iterator,
            'limit'     => $this->limit
        ];
    }


}