<?php


namespace traits;


use exception\MsgError;
use helper\QueryHelper;
use Illuminate\Database\Eloquent\Model;
use Yaf\Registry;

trait DefaultActionTrait
{


    /**
     * ajax获取列表数据时，获取到的数据将会递归走一次本函数，数据回调处理
     * 重写本方法以达到逐条处理数据库查询出来的数据
     * @return \Closure
     * @author xiongba
     * @date 2019-11-04 17:16:44
     */
    protected function listAjaxIteration() {
        return function ($item) {
            /** @var \Illuminate\Database\Eloquent\Model $item */
            $result = $item->toArray();
            return $result;
        };
    }


    /**
     * ajax获取列表时初始化的where条件
     * 重写本方法以达到ajax获取列表时使用的sql where条件
     * @return array
     * @author xiongba
     * @date 2019-11-04 17:50:03
     */
    protected function listAjaxWhere() {
        return [];
    }


    /**
     * 搜索时候的like条件
     * @return array
     * @author xiongba
     * @date 2019-11-06 15:54:21
     */
    protected function getSearchPrefixLikeParam() {
        $get = $_GET;
        $get['search'] = $get['search'] ?? [];
        $where = [];
        foreach ($get['search'] as $key => $value) {
            if ($value === '__undefined__'){
                continue;
            }
            $value = $this->formatSearchVal($key, $value);
            $key = $this->formatKey($key);
            if (empty($key)){
                continue;
            }
            $where[] = [$key, 'like', "$value%"];
        }
        return $where;
    }


    /**
     * 搜索时候的like条件
     * @return array
     * @author xiongba
     * @date 2019-11-06 15:54:21
     */
    protected function getSearchLikeParam() {
        $get = $_GET;
        $get['like'] = $get['like'] ?? [];
        $where = [];
        foreach ($get['like'] as $key => $value) {
            if ($value === '__undefined__'){
                continue;
            }
            $value = $this->formatSearchVal($key, $value);
            $key = $this->formatKey($key);
            if (empty($key)){
                continue;
            }
            $where[] = [$key, 'like', "%$value%"];
        }
        return $where;
    }


    /**
     * 格式化要搜索的字符串
     * @param $columnName
     * @param $val
     * @return string
     * @author xiongba
     * @date 2019-11-04 20:45:10
     */
    protected function formatSearchVal($columnName, $val) {
        return trim($val);
    }


    /**
     * 搜索范围条件构造
     * ?between[column][from]=1&between[column][to]=100
     * @return array
     * @author xiongba
     * @date 2019-11-04 20:38:17
     */
    protected function getSearchBetweenParam() {
        $get = $this->getRequest()->getQuery();
        $get['between'] = $get['between'] ?? [];

        $where = [];
        foreach ($get['between'] as $key => $between) {
            list($from, $to) = [
                $this->formatSearchVal($key, $between['from'] ?? ''),
                $this->formatSearchVal($key, $between['to'] ?? ''),
            ];
            if ($from ==='__undefined__'){
                $from = null;
            }
            if ($to ==='__undefined__'){
                $to = null;
            }

            if (empty($from) && empty($to)) {
                continue;
            }

            if (false !== strpos($key, ',')) {
                list($fromKey, $toKey) = explode(',', $key);
                $fromKey = $this->formatKey($fromKey);
                $toKey = $this->formatKey($toKey);
                if (empty($toKey) || empty($fromKey)){
                    continue;
                }
                list($from) = $this->datetime2integer($fromKey, $from, null);
                list(,$to) = $this->datetime2integer($toKey, null, $to);
                if (!empty($from)) {
                    $where[] = [$fromKey, '>=', $from];
                }
                if (!empty($to)) {
                    $where[] = [$toKey, '<=', $to];
                }
            } else {
                list($from, $to) = $this->datetime2integer($key, $from, $to);


                $key = $this->formatKey($key);
                if (empty($key)){
                    continue;
                }
                if (!empty($from)) {
                    $where[] = [$key, '>=', $from];
                }
                if (!empty($to)) {
                    $where[] = [$key, '<=', $to];
                }
            }
        }
        return $where;
    }


    /**
     * 可能需要把datetime转换integer
     * @param $key
     * @param $from
     * @param $to
     * @return array
     * @author xiongba
     * @date 2020-01-02 09:52:38
     */
    protected function datetime2integer($key ,$from ,$to ): array
    {
        $config = Registry::get('database')->database;
        $table_prefix = $config['prefix'] ?? '';
        $db_name = $config['database'] ?? '';
        $table_name = $table_prefix . $this->getModelQuery()->getModel()->getTable();
        $sql = "select COLUMN_TYPE as `c_type` from information_schema.COLUMNS where TABLE_SCHEMA=? and TABLE_NAME=? and COLUMN_NAME=?";
        $data = \DB::selectOne($sql , [$db_name , $table_name , $key]);
        if (empty($data)) {
            $typeName = 'integer';
        } elseif (strpos($data->c_type, 'int') !== false) {
            $typeName = 'integer';
        }else{
            $typeName = '';
        }

        if ($typeName == 'integer'){
            if (!empty($from) && !is_numeric($from)){
                $from = strtotime($from);
            }
            if (!empty($to) && !is_numeric($to)) {
                if (strpos($to, ':') !== false) {
                    $to = strtotime($to);
                } else {
                    $to = strtotime($to . " 23:59:59");
                }
            }
        }
        return [$from , $to];
    }

    protected function formatKey($key){
        if (!preg_match_all("#^([a-zA-Z_\d]+)$#i" , trim($key))){
            return false;
        }
        return $key;
    }

    /**
     * 搜索条件。构造准确的值
     * @return array
     * @author xiongba
     * @date 2019-11-04 21:09:40
     */
    protected function getSearchWhereParam() {
        $get = $this->getRequest()->getQuery();
        $get['where'] = $get['where'] ?? [];
        $where = [];
        foreach ($get['where'] as $key => $value) {
            if ($value ==='__undefined__'){
                continue;
            }
            $key = $this->formatKey($key);
            if (empty($key)){
                continue;
            }
            $value = $this->formatSearchVal($key, $value);
            if ($value !=='') {
                $where[] = [$key, '=', $value];
            }
        }
        return $where;
    }


    protected function builderWhereArray(){
        return array_merge(
            $this->getSearchWhereParam(),
            $this->getSearchLikeParam(),
            $this->getSearchPrefixLikeParam(),
            $this->getSearchBetweenParam()
        );
    }

    protected $_setPost = [];

    /**
     * 过滤 post数据，
     * @param null $setPost
     * @return mixed
     * @author xiongba
     * @date 2019-11-04 16:53:06
     */
    protected function postArray($setPost = null) {
        if ($setPost !== null) {
            $this->_setPost = $setPost;
            $post = $this->_setPost;
        }
        if (empty($post)) {
            $post = request()->getPost();
        }

        return $post;
    }

    protected function convertAff2Num(&$array, $keys, callable $callback)
    {
        $whereParams = ['search', 'where'];
        foreach ($whereParams as $name) {
            if (isset($array[$name]) && is_array($array[$name])) {
                foreach ($array[$name] as $k => &$v) {
                    if (in_array($k , $keys)) {
                        $v = get_num($v);
                    }
                }
            }
        }
    }


    protected function listAjaxOrder()
    {
        $array = $_GET['orderBy'] ??[];
        $orderBy = [];
        foreach ($array as $key => $item) {
            if (in_array($item , ['asc','desc'])){
                $orderBy[$key] = $item;
            }
        }
        return $orderBy;
    }


    /**
     * 获取列表数据
     * @author xiongba
     * @date 2019-11-04 17:31:08
     */
    public function listAjaxAction() {
        if (!$this->getRequest()->isXmlHttpRequest()) {
            return $this->ajaxError('加载错误');
        }

        $pkName = $this->getPkName();
        /** @var \Illuminate\Database\Eloquent\Builder $modelBuilder */
        $modelBuilder = $this->getModelQuery();
        $orderBy = $this->listAjaxOrder();
        if (empty($orderBy)) {
            $modelBuilder->orderBy($pkName, 'desc');
        } else {
            foreach ($orderBy as $column => $direction) {
                $modelBuilder->orderBy($column, $direction);
            }
        }


        $where = array_merge(
            $this->builderWhereArray(),
            $this->listAjaxWhere()
        );
        if (!empty($where)) {
            $modelBuilder->where($where);
        }

        /** @var \Illuminate\Database\Eloquent\Model $modelBuilder */
        //\DB::enableQueryLog();
        $data = (new QueryHelper())->layuiTable($modelBuilder, $this->listAjaxIteration());
        $data['desp'] = $this->listTotalDescp($where , $data);
        //errLog(json_encode(\DB::getQueryLog()));
//        $log = $this->getLogDesc();
//        if (!empty($log)){
//            \AdminLogModel::addCreate($this->getUser()->username , "访问了：".$log);
//        }
        return $this->ajaxReturn($data);
    }

    protected function listTotalDescp(array $where, array $data)
    {
        return '';
    }

    /**
     * 保存数据
     * @return bool
     * @author xiongba
     * @date 2019-11-04 16:08:32
     */
    public function saveAction() {
        if (!$this->getRequest()->isPost()) {
            return $this->ajaxError('请求错误');
        }
        $post = $this->postArray();
        try{
            if ($model = $this->doSave($post)) {
                return $this->ajaxSuccessMsg('操作成功' , 0 , call_user_func($this->listAjaxIteration() , $model));
            } else {
                return $this->ajaxError('操作错误');
            }
        }catch (\Throwable $e){
            return  $this->ajaxError($e->getMessage());
        }
    }


    protected function createAfterCallback($model){

    }
    protected function createBeforeCallback($model){

    }

    protected function updateAfterCallback($model, $oldModel){

    }

    protected function saveAfterCallback($model){

    }

    protected function deleteAfterCallback($model ,$isDelete)
    {

    }


    /**
     * 删除数据
     * 后台全局公共方法
     * @return mixed
     * @author xiongba
     * @date 2019-11-08 11:19:24
     */
    /**
     * 删除数据
     * 后台全局公共方法
     * @return mixed
     *
     * @date 2019-11-08 11:19:24
     */
    public function delAction() {
        if (!$this->getRequest()->isPost()) {
            return $this->ajaxError('请求错误');
        }
        $post = $this->postArray();
        $className = $this->getModelClass();
        $pkName = $this->getPkName();
        $where = [$pkName => $post['_pk']];
        $model = $className::where($where)->first();
        $oldModel = clone  $model;

        if (empty($model) || $model->delete()) {
            //\AdminLogModel::addDelete($this->getUser()->username , "删除了：".$this->getLogDesc());
            $this->deleteAfterCallback($oldModel , true);
            return $this->ajaxSuccessMsg('操作成功');
        } else {
            $this->deleteAfterCallback($model , false);
            return $this->ajaxError('操作错误');
        }
    }

    /**
     * 删除数据
     * 后台全局公共方法
     * @return mixed
     * @author xiongba
     * @date 2019-11-08 11:19:24
     */
    public function delAllAction() {
        if (!$this->getRequest()->isPost()) {
            return $this->ajaxError('请求错误');
        }
        $post = $this->postArray();
        $className = $this->getModelClass();
        $pkName = $this->getPkName();
        $ary = explode(',', $post['value'] ?? '');

        try {
            \DB::beginTransaction();
            foreach ($ary as $id) {
                if (empty($id)) {
                    continue;
                }
                $where = [$pkName => $id];
                $model = $className::where($where)->first();
                if (empty($model) || !$model->delete()) {
                    throw new \Exception('删除失败');
                }
            }
            \DB::commit();
            //\AdminLogModel::addDelete($this->getUser()->username , "批量删除了：".$this->getLogDesc());
            return $this->ajaxSuccessMsg('操作成功');
        } catch (\Exception $e) {
            \DB::rollBack();
            return $this->ajaxError('操作错误');
        }
    }


    protected function doIfUpdate() {
        $data = $this->getRequest()->getPost();
        $data['_pk'] = $data['_pk'] ?? '';
        return empty($data['_pk']) ? false : $data['_pk'];
    }

    /**
     * 爆粗数据的操作
     * @param $data
     * @return bool
     * @author xiongba
     * @date 2019-11-07 20:57:44
     */
    protected function doSave($data) {
        $className = $this->getModelClass();
        $pkName = $this->getPkName();
        $data['_pk'] = $data['_pk'] ?? '';
        /** @var \Illuminate\Database\Eloquent\Model $model */


        foreach ($data as $key=>$value){
            if (method_exists($this , 'set'.$key)){
                $data[$key] = $this->{"set$key"}($value,$data,$data['_pk']);
                if ($data[$key] === null){
                    unset($data[$key]);
                }
            }
        }


        if (empty($data['_pk'])) {
            $model = $className::make($data);
            $this->createBeforeCallback($model);
            $k = $model->save();
            $this->createAfterCallback($model);
            $this->saveAfterCallback($model);
            if (empty($k)) {
                return false;
            }
            //\AdminLogModel::addCreate($this->getUser()->username , "添加了：".$this->getLogDesc());
        } else {
            $where = [[$pkName, '=', $data['_pk']]];
            $model = $className::where($where)->first();
            if (empty($model)) {
                return false;
            }
            try {
                $oldModel = clone $model;
                $model->fill($data)->saveOrFail();
                $this->updateAfterCallback($model, $oldModel);
                $this->saveAfterCallback($model);
               // \AdminLogModel::addUpdate($this->getUser()->username , "修改了：".$this->getLogDesc());
            } catch (\Throwable $e) {
                errLog("save:{$e->getMessage()}");
                $this->updateAfterCallback(null);
                $this->saveAfterCallback(null);
                return false;
            }
        }
        return $model;
    }




    /**
     * 获取对应的model query
     * @return \Illuminate\Database\Eloquent\Builder
     * @author xiongba
     * @date 2019-11-04 17:20:15
     */
    protected function getModelQuery(){
        $className = $this->getModelClass();
        return $className::query();
    }

    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2019-11-04 17:20:15
     */
    abstract protected function getModelClass(): string;

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2019-11-04 17:19:41
     */
    abstract protected function getPkName(): string;

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2019-11-04 17:19:41
     */
    //abstract protected function getLogDesc(): string;


}