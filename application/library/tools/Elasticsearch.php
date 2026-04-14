<?php

namespace tools;

use Elasticsearch\ClientBuilder;
use Yaf\Registry;

class Elasticsearch
{
    private static $instance;
    private $index ;
    public static $hosts = [];

    protected static $builder = null;

    protected function __construct()
    {
        ini_set('arg_separator.output', '&');
    }


    protected function getBuild(): ?\Elasticsearch\Client
    {
        if (self::$builder === null) {
            self::$builder = ClientBuilder::create()->setHosts(self::$hosts)->build();
        }
        return self::$builder;
    }


    public static function new(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }


    public static function registerConfig(array $hosts)
    {
        self::$hosts = $hosts;
    }

    private static function init(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function table(string $index): self
    {
        $hosts = self::$hosts[0] ?? self::$hosts;
        $index = sprintf("%s%s", $hosts['table_prefix'] ?? '', $index);
        $object = self::new();
        $object->index = $index;
        return $object;
    }


    public static function space($table, \Closure $closure, ...$args)
    {
        if (empty($table)) {
            throw new \RuntimeException('es的index不能为空');
        }
        $object = self::table($table);
        try {
            array_unshift($args, $object);
            $result = $closure(...$args);
        } catch (\Throwable $e) {
        } finally {
        }

        return $result??null;
    }

    /**
     * 在{table}空间里面执行匿名函数，遇见异常时候，直接停止匿名函数的执行，并且将异常丢弃
     * 执行完成后。将会恢复之前的{table}空间
     *
     * ```php
     *
     * LibEs::spaceTry("mv" , fn(LibEs $es){
     *    // 这里 table 为 mv
     *    $es->delete(1); // 删除 mv 下面id=1的数据
     *
     * })
     *
     * ```
     *
     * @param $table
     * @param \Closure $closure
     * @param ...$args
     *
     * @return mixed|void
     */
    public static function spaceTry($table, \Closure $closure, ...$args)
    {
        try {
            return self::space($table, $closure, ...$args);
        } catch (\Throwable $e) {
        }
    }

    public function exists($id): bool
    {
        $params = [
            'index' => $this->index,
            'id'    => $id,
        ];

        return $this->getBuild()->exists($params);
    }


    public function updateOrCreate($data, $primaryKey = 'id')
    {
        $id = $data[$primaryKey];
        if ($this->exists($id)) {
            return $this->update($data, $primaryKey);
        } else {
            return $this->create($data, $primaryKey);
        }
    }

    public function create(array $data, string $primaryKey = 'id')
    {
        $esBody['index'] = $this->index;
        $esBody['body'][] = [
            'create' => [
                '_id' => $data[$primaryKey],
            ],
        ];
        $esBody['body'][] = $data;

        return $this->getBuild()->bulk($esBody);
    }

    public function createArray(array $data, string $primaryKey = 'id')
    {
        $esBody['index'] = $this->index;
        $esBody['body'] = [];
        $count = 0;
        foreach ($data as $datum) {
            $esBody['body'][] = [
                'create' => ['_id' => $datum[$primaryKey]],
            ];
            $esBody['body'][] = $datum;
            ++$count;
            if ($count % 100 === 0) {
                $this->getBuild()->bulk($esBody);
                $esBody['body'] = [];
                $count = 0;
            }
        }
        $this->getBuild()->bulk($data);
    }

    public function update(array $data, string $primaryKey = 'id')
    {
        $body[] = ['update' => ['_id' => $data[$primaryKey]]];
        $body[] = ['doc' => $data];

        $esBody['index'] = $this->index;
        $esBody['body'] = $body;

        return $this->getBuild()->bulk($esBody);
    }

    public function delete($id)
    {
        $param = [
            'index' => $this->index,
            'id'    => $id,
        ];

        return $this->getBuild()->delete($param);
    }

    public static function tryDelete($id)
    {
        try {

            self::delete($id);
        } catch (\Throwable $e) {
        }
    }

    public function search(
        string $keywords,
        int $offset = 0,
        int $limit = 15
    ): array {
        $params = [
            'index' => $this->index,
            'from'  => $offset,
            'size'  => $limit,
            'body'  => [
                'query' => [
                    'bool' => [
                        'should'               => [
                            [
                                'bool' => [
                                    'should'               => [
                                        [
                                            'match_phrase' => [
                                                'nickname' => [
                                                    'query' => "{$keywords}",
                                                    'slop'  => 10,
                                                ],
                                            ],
                                        ],
                                        [
                                            'match_phrase' => [
                                                'tags' => [
                                                    'query' => "{$keywords}",
                                                    'slop'  => 15,
                                                ],
                                            ],
                                        ],
                                        [
                                            'match_phrase' => [
                                                'title' => [
                                                    'query' => "{$keywords}",
                                                    'slop'  => 20,
                                                ],
                                            ],
                                        ],
                                    ],
                                    'adjust_pure_negative' => true,
                                    'boost'                => 20,
                                ],
                            ]
                            ,
                            ['match' => ['title' => $keywords]],
                            ['match' => ['nickname' => $keywords]],
                            ['match' => ['tags' => $keywords]],
                        ],
                        'adjust_pure_negative' => true,
                    ],
                ],
            ],
        ];
        $result = $this->getBuild()->search($params);
        $temp = [];
        if (isset($result['hits']['hits'])
            && count($result['hits']['hits']) > 0
        ) {
            foreach ($result['hits']['hits'] as $hit) {
                isset($hit['_source']['fanhao'])
                && $hit['_source']['_id'] = $hit['_source']['fanhao'];
                $temp[] = $hit['_source'];
            }
        }

        return $temp;
    }


    /**
     * @param string $field
     * @param string|int $value
     * @param int $page
     * @param int $size
     * @param array $option
     *
     * @return array|callable
     */
    public function match(
        string $field,
        $value,
        int $page,
        int $size = 10,
        array $option = []
    ) {
        $query = [
            'match' => [
                $field => $value,
            ],
        ];

        return self::queryRaw($query, $page, $size, $option);
    }


    public function queryRaw(
        array $query,
        int $page,
        int $size = 10,
        array $option = []
    ) {
        $from = (($page <= 1) ? 0 : $page - 1) * $size;
        $params = [
            'index' => $this->index,
            'size'  => $size,
            'from'  => $from,
            'body'  => [
                'query' => $query,
            ],
        ];
        $params = array_merge($params, $option);

        // hits.hits
        return $this->getBuild()->search($params);
    }


    public function get(int $id)
    {
        $params = [
            'index' => $this->index,
            'id'    => $id,
        ];

        return $this->getBuild()->get($params);
    }

    public static function querySql(string $query, int $limit = 5000): array
    {
        $hosts = self::$hosts[0] ?? self::$hosts;
        $query = preg_replace_callback("#@\{([^\}]*)\}#i",
            function ($v) use ($hosts) {
                return ($hosts['table_prefix'] ?? '').$v[1];
            }, $query);

        return (self::new())->getBuild()->sql()->query([
            'format' => 'JSON',
            'body'   => ['query' => $query, 'fetch_size' => $limit],
        ]);
    }

}