<?php
namespace service;
use Elasticsearch\ClientBuilder;
use tools\Elasticsearch;

/**
 * ```php
 * $es->matchInKey(index , 关键字 , 多个字段[] , page , size );
 * $es->matchIn(index , 字段 , 多个值[] , page , size ); // 不会自动分词
 *
 * // where条件
 * $es->matchAnd(index ,where[] , page , size ); // [id="aaa" , name="aaa"]
 * $es->matchOr(index ,where[] , page , size ); // [id="aaa" , name="aaa"]
 *
 * // 操作数据
 * $es->drop(index  );
 * $es->delete(index , $data , $pkName  );
 * $es->insert(index , $data , $pkName  );
 * $es->update(index , $data , $pkName  );
 *
 *
 * ```
 *
 */
class EsLib
{
    const ELK_API_HOST = 'http://elk.peachav.club:8080';

    const ELK_INDEX = 'xblue';//表

    const ELK_TYPE = 'doc';//类型
    public $hosts = [
        self::ELK_API_HOST
        // 第一个节点配置
       /* [
            'host' => '192.168.147.96', // 必填项
            'port' => 9200, // 不设置，默认9200,
            'scheme' => 'http', // 不设置， 默认http
            'user' => 'elastic',
            'pass' => 'ZzdEFVwSpfpBiV4yHHWj',
        ],*/
        // .... 其他节点配置
    ];
    public $client;
    protected $old_arg_separator = '&';

    public function __construct()
    {
        $this->old_arg_separator = ini_get('arg_separator.output');
        ini_set('arg_separator.output', '&');
        $this->client = ClientBuilder::create()->setHosts($this->hosts)->build();
    }


    public function insert(string $index, array $data, string $pkName)
    {
        $values = $this->array_is_list($data) ? $data : [$data];
        $body = [];
        foreach ($values as $value) {
            if (isset($value[$pkName])) {
                $body[] = ['create' => ['_id' => $value[$pkName]]];
            }
            $body[] = $value;
        }
        $params = [
            'index' => $index,
            'body' => $body,
        ];

        return $this->client->bulk($params);
    }


    public function update(string $index, array $data, string $pkName)
    {
        $body = [];
        $values = $this->array_is_list($data) ? $data : [$data];
        foreach ($values as $value) {
            if (isset($value[$pkName])) {
                $body[] = ['update' => ['_id' => $value[$pkName]]];
                $body[] = ['doc' => $value];
            }
        }
        if (empty($body)) {
            return null;
        }
        $params = [
            'index' => $index,
            'body' => $body,
        ];
        $this->client->bulk($params);
    }


    public function delete(string $index, array $data, string $pkName)
    {
        $body = [];
        $values = $this->array_is_list($data) ? $data : [$data];
        foreach ($values as $value) {
            if (isset($value[$pkName])) {
                $body[] = ['delete' => ['_id' => $value[$pkName]]];
            }
        }
        if (empty($body)) {
            return null;
        }
        $params = [
            'index' => $index,
            'body' => $body,
        ];
        $this->client->bulk($params);
    }

    public function drop($index)
    {
        $promise = $this->client->transport->performRequest(
            'DELETE',
            sprintf("/%s", $index)
        );
        return $this->client->transport->resultOrFuture($promise, []);
    }


    public function get($index, $id)
    {
        $params = [
            'index' => $index,
            'id' => $id,
        ];
        return $this->client->get($params);
    }

    public function mget($index, array $ids)
    {
        $params = [
            'index' => $index,
            'body' => [
                'id' => $ids,
            ],
        ];
        return $this->client->mget($params);
    }


    public function matchIn($index, string $field, array $value, $page, $size = 10, array $option = [])
    {
        $query = [
            'terms' => [
                $field => $value
            ],
        ];
        return $this->queryRaw($index, $query, $page, $size, $option);
    }

    public function matchInKey($index, string $value, array $field, $page, $size = 10, array $option = [])
    {
        $query = [
            'multi_match' => [
                'query' => $value,
                "type" => 'best_fields',
                'fields' => $field,
            ],
        ];
        return $this->queryRaw($index, $query, $page, $size, $option);
    }

    public function matchAnd($index, array $where, int $page, int $size = 10, array $option = [])
    {
        return $this->matchWhere($index, $where, 'and', $page, $size, $option);
    }

    public function matchOr($index, array $where, int $page, int $size = 10, array $option = [])
    {
        return $this->matchWhere($index, $where, 'or', $page, $size, $option);
    }

    public function matchWhere($index, array $where, $operate, int $page, int $size = 10, array $option = [])
    {
        $condition = [];
        foreach ($where as $k => $value) {
            if (!is_array($value)) {
                if (is_numeric($k)) {
                    continue;
                }
                $value = [$k, '=', $value];
            }
            list($k, , $value) = $value;
            $condition[] = [
                ['match' => [$k => $value,]]
            ];
        }
        $operate = strcasecmp($operate, 'and') ? 'must' : 'should';
        $query = ['bool' => [
            $operate => $condition
        ]];
        return $this->queryRaw($index, $query, $page, $size, $option);
    }


    /**
     * @param $index
     * @param string $field
     * @param string|int $value
     * @param int $page
     * @param int $size
     * @param array $option
     * @return array|callable
     */
    public function match($index, string $field, $value, int $page, int $size = 10, array $option = [])
    {
        $query = [
            'match' => [
                $field => $value,
            ],
        ];
        return $this->queryRaw($index, $query, $page, $size, $option);
    }


    public function queryRaw($index, array $query, int $page, int $size = 10, array $option = [])
    {
        $from = (($page <= 1) ? 0 : $page - 1) * $size;
        $params = [
            'index' => $index,
            'size' => $size,
            'from' => $from,
            'body' => [
                'query' => $query,
            ],
        ];
        $params = array_merge($params, $option);
        // hits.hits
        return $this->client->search($params);
    }

    public function __destruct()
    {
        unset($this->client);
        ini_set('arg_separator.output', $this->old_arg_separator);
    }

    protected function array_is_list($data): bool
    {
        if (function_exists('array_is_list')) {
            return array_is_list($data);
        }
        $keys = array_keys($data);
        for ($i = count($keys) - 1; $i >= 0; $i--) {
            $_i = array_pop($keys);
            if ($_i != $i) {
                return false;
            }
        }

        return true;
    }

}