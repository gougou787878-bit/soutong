<?php


namespace helper;


use Illuminate\Database\Query\Builder;
use Yaf\Application;

/**
 * Class QueryHelper
 * @package App\library\helper
 * @author xiongba
 * @date 2019-11-02 15:23:44
 */
class QueryHelper
{

    /**
     * @param Builder $builder
     * @param string $offsetName
     * @param string $limitName
     * @return Builder
     * @author xiongba
     * @date 2019-11-02 15:21:37
     */
    public function pagination($builder, $offsetName = 'pageNumber', $limitName = 'pageSize')
    {
        /** @var \Yaf\Request\Http $request */
        $request = Application::app()->getDispatcher()->getRequest();
        //当前多少页
        $offset = $request->get($offsetName, 1);
        $offset = $offset <= 1 ? 0 : $offset - 1;
        //每页限时
        $limit = $request->get($limitName, 10);
        return $builder->offset($offset * $limit)->limit($limit);
    }


    /**
     * @param Builder $builder
     * @param string $offsetName
     * @param string $limitName
     * @return Builder
     * @author xiongba
     * @date 2019-11-02 15:21:37
     */
    public function restPagination($builder, $offsetName = 'page', $limitName = 'size')
    {
        list($limit, $offset) = self::restLimitOffset($offsetName, $limitName);
        return $builder->offset($offset * $limit)->limit($limit);
    }


    /**
     * 返回分页参数
     * @param int $defaultLimit
     * @param string $pageName
     * @param string $limitName
     * @return array [$page, $limit , $last_ix]
     */
    public static function pageLimit($defaultLimit = 20, $pageName = 'page', $limitName = 'limit')
    {
        $data = $_POST;
        //当前多少页
        $page = $_POST[$pageName] ?? 1;
        $page = $page <= 1 ? 1 : $page;
        //每页限时
        $limit = (int)($_POST[$limitName] ?? $defaultLimit);
        $last_ix = $data['last_ix'] ?? -1;
        return [$page, $limit, $last_ix];
    }

    /**
     * 返回分页参数
     * @param string $offsetName
     * @param string $limitName
     * @param int $defaultLimit
     * @return array [每页条数, offset , 第多少页]
     */
    public static function restLimitOffset($offsetName = 'page', $limitName = 'limit', $defaultLimit = 20)
    {
        $data = $_POST;
        //当前多少页
        $page = $data[$offsetName] ?? 1;
        $page = $page <= 1 ? 0 : $page - 1;
        //每页限时
        $limit = (int)($data[$limitName] ?? $defaultLimit);
        $limit = $limit <= 0 ? $defaultLimit : $limit;

        return [$limit, $page * $limit, $page];

    }

    public static function restLimitSize($page, $size = 50)
    {
        $page = ($page <= 1 ? 1 : $page) - 1;
        $offset = $page * $size;
        $limit = $size;
        return array($limit, $offset);
    }


    /**
     * @param Builder $builder
     * @param \Closure $iteration 原型 function($item){return $item;}
     * @return array
     * @author xiongba
     * @date 2019-11-02 15:21:37
     */
    public function bootstrapTable($builder, \Closure $iteration)
    {
        $srcBuilder = clone $builder;
        $result = $this->pagination($builder, 'pageNumber', 'pageSize')->get();
        $data = [];
        if (!empty($result)) {
            foreach ($result as $item) {
                $data[] = $iteration($item);
            }
        }
        $result = [
            'total' => empty($data) ? 0 : $srcBuilder->count(),
            'rows'  => $data,
            'code'  => 200
        ];;
        return $result;

    }

    /**
     * @param Builder $builder
     * @param \Closure $iteration 原型 function($item){return $item;}
     * @return array
     * @author xiongba
     * @date 2019-11-02 15:21:37
     */
    public function layuiTable($builder, \Closure $iteration)
    {
        $srcBuilder = clone $builder;
        $result = $this->pagination($builder, 'page', 'limit')->get();
        $data = [];
        if (!empty($result)) {
            foreach ($result as $item) {
                $data[] = $iteration($item, $result);
            }
        }
        $result = [
            'count' => 1,
            'data'  => $data,
            "msg"   => '',
            'code'  => 0
        ];;
        return $result;

    }


    /**
     * @param $query
     * @param callable $filterCallback
     * @param int $lastIndex
     * @param string $lastFieldName
     * @param string $orderBy
     * @return array
     * @author xiongba
     * @date 2020-03-19 17:43:10
     */
    public static function AppStruct($query, callable $filterCallback, $lastIndex = 0, $lastFieldName = 'id' , $orderBy = 'desc')
    {
        list($limit, $offset) = QueryHelper::restLimitOffset();
        $total = $query->count();
        if ($lastIndex) {
            $query->where($lastFieldName, '<', $lastIndex);
        }


        $all = $query
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->map(function ($item) use (&$lastIndex, $filterCallback, $lastFieldName) {
                $lastIndex = $item->{$lastFieldName};
                return $filterCallback($item);
            });
        return [
            'total'     => $total,
            'list'      => $all,
            'lastIndex' => (int)$lastIndex
        ];

    }

}