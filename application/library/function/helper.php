<?php

use tools\RedisService;
use Yaf\Registry;





/**
 * @return \Redis|\RedisCluster|\RedisCached|\tools\RedisService
 * @author xiongba
 * @date 2019-11-28 15:29:12
 */
function redis()
{
    static $redis = null;
    if ($redis === null) {
        $config = Registry::get('database.conf');
        $host = $config->redis->host;
        if (is_string($host)) {
            //性能yaf 有兼容问题
            $hosts = [$host];
        } else {
            $hosts = $config->redis->host->toArray();
        }
        $prefix = $config->redis->prefix;
        $redis = RedisCached::instance($hosts,$prefix);
    }
    return $redis;
}


function cached($key)
{
    return CacheDb::make(redis())->setKey($key);
}


/**
 * 数组转为树
 * @param $list
 * @param string $pk
 * @param string $pid
 * @param string $child
 * @param int $root
 * @return array
 * @author xiongba
 * @date 2019-11-08 10:01:18
 */
function arrayToTree($list, $pk = 'id', $pid = 'pid', $child = 'children', $root = 0)
{
    $tree = [];
    if (is_array($list)) {
        $refer = [];
        foreach ($list as $key => $data) {
            $refer[$data[$pk]] = &$list[$key];
        }
        foreach ($list as $key => $data) {
            // 判断是否存在parent
            $parentId = $data[$pid];
            if ($root == $parentId) {
                $tree[] = &$list[$key];
            } else {
                if (isset($refer[$parentId])) {
                    $parent = &$refer[$parentId];
                    $parent[$child][] = &$list[$key];
                }
            }
        }
    }

    return $tree;
}

/**
 * @param null $path
 * @param array $parameters
 * @param bool $domain
 * @param string $scirpt
 * @return string
 * @author xiongba
 * @date 2019-12-05 18:53:11
 */
function url($path = null, $parameters = [], $domain = true, $scirpt = null)
{

    $request = dispatcher()->getRequest();
    if (is_null($path)) {
        return $request->getRequestUri();
    }

    $path = trim($path, '/\\ ');


    $ary = explode('/', $path, 4);
    switch (count($ary)) {
        case 4:
        case 5:
        case 3:
            list($module, $controller, $action) = $ary;
            break;
        case 2:
            list($controller, $action) = $ary;
            $module = lcfirst($request->getModuleName());
            break;
        default:
            list($action) = $ary;
            $controller = lcfirst($request->getControllerName());
            $module = lcfirst($request->getModuleName());
            break;
    }


    if (empty($scirpt)) {
        $DOCUMENT_URI = dispatcher()->getRequest()->getServer('SCRIPT_NAME', '/');
    } else {
        $DOCUMENT_URI = "/$scirpt";
    }


    if ($domain) {
        return sprintf("%s/%s/%s/%s/%s%s", getHttpBaseUrl(), trim($DOCUMENT_URI, '/'), $module, $controller, $action,
            empty($parameters) ? '' : '?' . urldecode(http_build_query($parameters, '', '&')));
    } else {
        return sprintf("/%s/%s/%s/%s%s", trim($DOCUMENT_URI, '/'), $module, $controller, $action,
            empty($parameters) ? '' : '?' . urldecode(http_build_query($parameters, '', '&')));
    }


}


function url_backend($path = null, $parameters = [], $domain = true, $scirpt = null)
{

    $request = dispatcher()->getRequest();
    if (is_null($path)) {
        return $request->getRequestUri();
    }

    $path = trim($path, '/\\ ');


    $ary = explode('/', $path, 4);
    switch (count($ary)) {
        case 4:
        case 5:
        case 3:
            list($module, $controller, $action) = $ary;
            break;
        case 2:
            list($controller, $action) = $ary;
            $module = lcfirst($request->getModuleName());
            break;
        default:
            list($action) = $ary;
            $controller = lcfirst($request->getControllerName());
            $module = lcfirst($request->getModuleName());
            break;
    }
    if (empty($scirpt)) {
        $DOCUMENT_URI = dispatcher()->getRequest()->getServer('SCRIPT_NAME', '/');
    } else {
        $DOCUMENT_URI = "/$scirpt";
    }


    if ($domain) {
        return sprintf("%s/%s?mod=%s&code=%s%s", getHttpBaseUrl(), trim($DOCUMENT_URI, '/'), $controller, $action,
            empty($parameters) ? '' : '&' . urldecode(http_build_query($parameters, '', '&')));
    } else {
        return sprintf("/%s?mod=%s&code=%s%s", trim($DOCUMENT_URI, '/'), $controller, $action,
            empty($parameters) ? '' : '&' . urldecode(http_build_query($parameters, '', '&')));
    }
}


/**
 * @return string
 * @author xiongba
 * @date 2019-12-05 18:52:37
 */
function getHttpBaseUrl()
{
    $request = dispatcher()->getRequest();
    $host = $request->getServer('HTTP_HOST');
    $port = $request->getServer('SERVER_PORT');
    $scheme = $request->getServer('REQUEST_SCHEME');
    $port = $port == 80 ? '' : ':' . $port;
    $scheme = strtolower($scheme) == 'http' ? 'http' : 'https';

    if ($scheme == 'http') {
        $scheme = $request->getServer('HTTP_X_FORWARDED_PROTO', 'http');
    }
    return sprintf("%s://%s%s", $scheme, $host, $port);
}


/**
 * @param $file
 * @author xiongba
 * @date 2019-11-08 10:01:18
 */
function importView($file)
{
    $trace = debug_backtrace();
    if (isset($trace[2])){
        try {
            $object = $trace[2]['object'] ?? null;
            $reflect = new ReflectionObject($object);
            $prop = $reflect->getProperty('_tpl_vars');
            $prop->setAccessible(true);
            extract($prop->getValue($object));
        } catch (ReflectionException $e) {
        }
    }
    require(VIEW_PATH . '/' . $file);
}


if (!function_exists('ctype_xdigit')) {
    /**
     * @param $string
     * @return bool
     * @author xiongba
     * @date 2019-12-05 18:51:32
     */
    function ctype_xdigit($string)
    {
        return preg_match("/^[a-f0-9]{2,}$/i", $string) && !(strlen($string) & 1);
    }
}

/**
 * @param $string
 * @return bool
 * @author xiongba
 * @date 2019-12-05 18:51:26
 */
function isHex($string)
{
    return ctype_xdigit($string);
}


/**
 * @return \Yaf\Application|NULL
 * @author xiongba
 * @date 2019-11-09 16:44:35
 */
function app()
{
    return \Yaf\Application::app();
}

/**
 * @return \Yaf\Dispatcher
 * @author xiongba
 * @date 2019-11-09 16:44:32
 */
function dispatcher()
{
    return app()->getDispatcher();
}


/**
 * @return LibRequest
 * @author xiongba
 * @date 2019-11-09 16:44:29
 */
function request()
{
    static $request = null;
    if ($request === null) {
        $_request = dispatcher()->getRequest();
        $request = new LibRequest($_request->getRequestUri(), $_request->getBaseUri());
    }
    return $request;
}


/**
 * @param $name
 * @param null $value
 * @return bool|mixed|void
 * @author xiongba
 * @date 2019-12-05 18:51:20
 */
function register($name, $value = null)
{
    if (func_num_args() === 1) {
        return \Yaf\Registry::get($name);
    }
    if ($value === null) {
        return \Yaf\Registry::del($name);
    } else {
        return \Yaf\Registry::set($name, $value);
    }
}





function array_sort_by_idx($ary , $idx , $key_index){
    $new_ary = [];
    $ary = collect($ary)->keyBy($key_index);
    foreach ($idx as $id){
        if (isset($ary[$id])){
            $new_ary[] = $ary[$id];
        }
    }
    return $new_ary;
}

function model_json(\Illuminate\Database\Eloquent\Model  $model){
    return json_encode($model->getAttributes() , JSON_UNESCAPED_UNICODE);
}


function collect2raw($collectModel  ){
    $results = [];
    foreach ($collectModel as $model){
        $results[] = $model->getAttributes();
    }
    return $results;
}


function maximum($num, $max)
{
    if ($num > $max) {
        return $max;
    }
    return $num;
}

function least($num, $least)
{
    if ($num < $least) {
        return $least;
    }
    return $num;
}








function setting($varName, $default = null)
{
    static $setting = null;
    if ($setting === null) {
        $setting = yac()->fetch("system:setting", function (){
            return redis()->hGetAll('system:setting');
        }, 300);
    }

    return $setting[$varName] ?? $default;
}


/**
 * 使用.在多维数组中取值
 * @param $array
 * @param $key
 * @param null $default
 * @return mixed|null
 * @author xiongba
 * @date 2019-12-04 11:23:13
 */
function array_dot_value($array, $key, $default = null)
{
    $key = explode('.', $key);
    foreach ($key as $k) {
        if (!array_key_exists($k, $array)) {
            return $default;
        }
        if (!is_array($array[$k])) {
            return $array[$k];
        }
        $array = $array[$k];
    }
    return $default;
}

function array_group($array, $field, callable $formatCallback = null)
{
    $ary = [];
    foreach ($array as $item) {
        $ary[$item[$field]][] = $formatCallback ? $formatCallback($item) : $item;
    }
    return $ary;
}

/**
 * 二维数组排序
 * ```php
 * array_orderBy($data, 'is_top', SORT_DESC, 'id', SORT_DESC);
 * ```
 * @param array $array 要排序的二维数组
 * @return array
 * @author xiongba
 */
function array_orderBy($array)
{
    $args = func_get_args();
    $data = array_shift($args);
    if (!is_array($data)){
        return $array;
    }
    foreach ($args as $n => $field) {
        if (is_string($field)) {
            $tmp = array();
            foreach ($data as $key => $row) {
                $tmp[$key] = $row[$field];
            }
            $args[$n] = $tmp;
        }
    }
    $args[] = &$data;
    call_user_func_array('array_multisort', $args);
    return array_pop($args);
}










function array_reindex($array, $indexField, callable $formatCallback = null)
{
    $ary = [];
    foreach ($array as $item) {
        $ary[$item[$indexField]] = $formatCallback ? $formatCallback($item) : $item;
    }
    return $ary;
}


function array_keep_column(array $array, array $columns)
{
    $result = [];
    foreach ($columns as $column) {
        $result[$column] = $array[$column] ?? null;
    }
    return $result;
}

function array_diff_unset(array $array1, array &$array2)
{
    $resultArray = array_diff($array1, $array2);
    if (count($resultArray) == count($array2)) {
        $array2 = [];
    } else {
        $array3 = array_flip($array2);
        foreach ($array1 as $k => $v) {
            unset($array3[$v]);
        }
        $array2 = array_keys($array3);
    }
    return $resultArray;
}


function array_unset(array &$array, $key)
{
    $data = $array[$key] ?? null;
    unset($array[$key]);
    return $data;
}


/**
 * 不区分大小写检查数组中是否存在某个值
 * @param string $needle 待搜索的值。
 * @param array $haystack 待搜索的数组。
 * @return bool 如果找到 needle 则返回 TRUE，否则返回 FALSE。
 * @author xiongba
 * @date 2019-12-11 15:53:55
 */
function in_array_case($needle, array $haystack)
{
    $haystack = array_flip($haystack);
    $haystack = array_change_key_case($haystack, CASE_LOWER);
    return isset($haystack[strtolower($needle)]);
}

/**
 * 给数组的键填充指定的前缀
 * @param $array
 * @param $prefix
 * @return array
 * @author xiongba
 * @date 2019-12-21 14:10:13
 */
function array_prefix($array, $prefix)
{
    $ary = [];
    foreach ($array as $k => $value) {
        $ary[] = "$prefix" . $value;
    }
    return $ary;
}

/**
 * 合并数组，保持索引
 * @param $array
 * @param $array1
 * @return array
 * @author xiongba
 * @date 2019-12-21 14:10:13
 */
function merge_array(array $array, array $array1)
{
    $args = func_get_args();
    while (count($args) > 1) {
        $a1 = array_pop($args);
        $a2 = array_pop($args);
        foreach ($a1 as $k => $v) {
            $a2[$k] = $v;
        }
        array_push($args, $a2);
    }
    $ary = array_pop($args);
    return $ary;
}

/**
 * 合并数组，使用$operator的回调合并数组
 * @param callable $operator
 * @param mixed ...$array
 * @return array
 * @author xiongba
 * @date 2020-01-04 11:22:03
 */
function array_merge_of_operate(callable $operator, ...$array)
{
    $data = [];
    foreach ($array as $result) {
        if (!is_array($array)) {
            continue;
        }
        foreach ($result as $key => $value) {
            if (!isset($data[$key])) {
                $data[$key] = 0;
            }
            $data[$key] = call_user_func($operator, $data[$key], $value);
        }
    }
    return $data;
}


/**
 * 给数组的键填充指定的前缀
 * @param $array
 * @param $prefix
 * @return array
 * @author xiongba
 * @date 2019-12-21 14:10:13
 */
function array_key_prefix($array, $prefix)
{
    $ary = [];
    foreach ($array as $k => $value) {
        $ary["$prefix$k"] = $value;
    }
    return $ary;
}


function array_incr($array, ...$arrays)
{
    if (func_num_args() == 1) {
        return $array;
    }
    $results = [];
    $array2 = array_shift($arrays);
    $keys = array_keys(array_merge($array, $array2));
    foreach ($keys as $key) {
        $results[$key] = ($array2[$key] ?? 0) + ($array[$key] ?? 0);
    }
    $results = array_incr($results, ...$arrays);
    return $results;
}

/**
 * 解析路由
 * @param $string
 * @param string $module_split
 * @param string $controller_split
 * @return array
 * @author xiongba
 * @date 2019-12-11 16:29:20
 */
function parse_route($string, $module_split = '@', $controller_split = ':')
{
    $ary = explode($module_split, $string);
    if (!isset($ary[1])) {
        $ary = [null, $ary[0]];
    }
    list($module, $route) = $ary;
    $ary = explode($controller_split, $route);
    if (!isset($ary[1])) {
        $ary = [null, $ary[0]];
    }
    list($controller, $action) = $ary;
    return [$module, $controller, $action];
}


function parse_input($input)
{
    $post = json_decode($input, 1);
    if (json_last_error()) {
        parse_str($input, $post);
        if (empty($post)) {
            $post = [];
        }
    }
    return $post;
}

/**
 * 打印sql语句
 * @param \Illuminate\Database\Eloquent\Builder|object $builder
 * @param bool $dump
 * @return string
 * @author xiongba
 * @date 2019-12-06 13:43:25
 */
function dumpSql($builder, $dump = true)
{
    $sql = sprintf(str_replace("?", "'%s'", $builder->toSql()), ...$builder->getBindings());
    if ($dump) {
        print_r($sql . "\r\n");
    }
    return $sql;

}


/**
 * 获取指定model类的表名称
 * @param $class
 * @return mixed
 * @author xiongba
 * @date 2019-12-14 18:27:18
 */
function table_name($class)
{
    return $class::make()->getTable();
}


/**
 * 广告图片
 * @param $url
 * @return string
 * @author xiongba
 * @date 2019-12-26 15:05:38
 */
function url_ads($url)
{
    $base = __get_url_base('img.img_ads_url');
    /*if (stripos($url, 'new') !== false) {
        $base = str_ireplace('/img.ads/', '', $base);
    }*/
    return url_resource($url, $base);
    //return url_resource($url, config('img.img_ads_url'));
}

/**
 * 广告图片
 * @param $path
 * @return string
 * @author xiongba
 * @date 2019-12-26 15:05:38
 */
function url_h5($path)
{
    $site = setting('HTML5.site');
    return sprintf("%s/%s", rtrim($site, '/'), ltrim($path));
    //return url_resource($url, config('img.img_ads_url'));
}


/**
 * 用户头像
 * @param $url
 * @return string
 * @author xiongba
 * @date 2019-12-26 15:05:15
 */
function url_avatar($url)
{
    $base = __get_url_base('img.img_head_url');

    if (strlen($url) < 10) {
        return config('img.default_jd_thumb');
        ///new/head/20200729/2020072920265415312.jpeg
    }
    //errLog('url:'.$url.'ck:'.stripos($url,'new'));
    if (stripos($url, 'new') !== false) {
        $base = str_ireplace('/img.head/', '', $base);
    }
    return url_resource($url, $base);
}


function __get_url_base($configName){
    static $base = [];
    if (!isset($base[$configName])){
        $moduleName =  Yaf\Application::app()->getDispatcher()->getRequest()->getModuleName();
        if (strcasecmp($moduleName , 'admin') === 0){
            $_base = 'https://imgpublic.ycomesc.live/';
        }else{
            $_base = config($configName);
        }
        $base[$configName] = $_base;
    }
    return $base[$configName];
}

function url_image($url): string
{
    return url_cover($url);
}


/**
 * 视频封面
 * @param $url
 * @return string
 * @author xiongba
 * @date 2019-12-26 15:05:21
 */
function url_cover($url)
{
    $base = __get_url_base('img.img_xiao_url');
    if (empty($url)){
        return $url;
    }
    //errLog(var_export([$base,$url],true));
    //return $base.$url;
    return url_resource($url, $base);

}

function url_live($url)
{
    return url_cover($url);
}


/**
 * 其他图片
 * @param $url
 * @return string
 * @author xiongba
 * @date 2020-01-13 10:33:18
 */
function url_upload($url)
{
    return url_resource($url, __get_url_base('img.img_upload_url'));
}


/**
 * 视频地址
 * @param $url
 * @param bool $isLocal true  限后台使用 无签名不外泄
 * @return string
 * @author xiongba
 * @date 2019-12-26 15:05:29
 */
function url_video($url, $isLocal = false)
{
    if (!$url) {
        return;
    }
    $extension = pathinfo($url, PATHINFO_EXTENSION);

    if ($extension == 'mp4') {
        return url_videoMP4($url);
    }
    if ($isLocal) {
        // 返回的是 http的   限内部使用
        return url_resource($url, config('video.local_url'));
    }

    if (!request()->isCli()) {
        //创作中心
        if ((stripos(request()->getActionName(),
                    'forweb') !== false) || (is_null(request()->getDevice()->oauth_type))) {
            return url_video_creator($url);
        }
    }

    //new
    $com_url = url_resource($url, config('video.cdn_url'));
    //$return_url = nginxHashNew($com_url);
    //old
    //$hashkey = nginxHash($url);
    //baidu
    $hashkey = baiduHash($url,parse_url($com_url,PHP_URL_PATH));
    $return_url = $com_url . '?' . $hashkey;
    return $return_url;
}


/**
 *  创作中心 播放视频地址处理
 * @param $url
 * @return string
 */
function url_video_creator($url)
{

    $com_url = url_resource($url, 'https://m3u8.xstsny.com');
    $hashkey = baiduHash($com_url,parse_url($com_url,PHP_URL_PATH));
    $return_url = $com_url . '?' . $hashkey;
    return $return_url;

    /*$com_url = url_resource($url,'http://mmmmm.tiansex.net');
    $hashkey = nginxHash($com_url);
    $return_url = $com_url . '?' . $hashkey;
    return  $return_url;*/
}

function baiduHash($url, $host = "")
{
    $host = '';
    $key = config('app.hls_key');
    $parse = parse_url($url);

    $filename = $parse['path'];
    $timestamp = time() + 120 * 60;
    $rand = 0;
    $s = $host ? "$host-" :'';
    $string = "{$s}{$filename}-{$timestamp}-{$rand}-0-{$key}";
    $sign = md5($string);
    $query = "{$timestamp}-{$rand}-0-{$sign}";
    $s1 = $host ? "&v=2" :"";

    return "auth_key={$query}{$s1}";
}

/**
 *  暂时未用
 * @param string $url
 * @param int $uid
 * @return string
 */
function nginxHashNew(string $url, $uid = 0): string
{
    $parse = parse_url($url);
    $key = config('app.hls_key');
    $domain = $parse['host'];
    $filename = $parse['path'];
    $timestamp = time() + 30 * 60;
    $rand = 0;
    $string = "{$filename}-{$timestamp}-{$rand}-{$uid}-{$key}";
    $sign = md5($string);
    $query = "{$timestamp}-{$rand}-{$uid}-{$sign}";
    return "{$parse['scheme']}://{$domain}{$filename}?auth_key={$query}";
}

/**
 *  暂时未用
 * @param string $ip
 * @param string $url
 * @return string
 */
function nginxHash($url = '')
{
    $uri = '';
    if ($url) {
        $path = parse_url($url);
        $uri = $path['path'] ?? '';
    } else {
        return '';
    }
    $key = 'hello&kitty@8888';
    $expires = TIMESTAMP + 7200;
    $md5Key = $key . $uri . $expires;
    $hash = str_replace("=", "", strtr(base64_encode(md5($md5Key, true)), "+/", "-_"));
    return "md5={$hash}&expires={$expires}&via=kekaoyun";
}

/**
 * 视频地址  MP4 限内部使用
 * @param $url
 * @return string
 * @author xiongba
 * @date 2019-12-26 15:05:29
 */
function url_videoMP4($url)
{
   return url_auth_payMP4($url);
}

/**
 *  auth 认证播放连接
 * @param $url
 * @return string
 */
function url_auth_payMP4($url)
{
    return TB_CHECK_VIDEO . '/' . trim($url, '/');
}

function url_resource($url, $baseUrl)
{
    if (empty($url)) {
        return $url;
    }
    if (strpos($url, '://') !== false) {
        return $url;
    }
    //老项目，新路经兼容问题
    $url_replace['/img.ads/'] = '';
    $url_replace['/img.xiao/'] = '';
    $url_replace['/img.head/'] = '';
    $url_replace['/img.upload/'] = '';
    $url_replace['/img.im/'] = '';
    $url_replace['/img.actors/'] = '';
    $url_replace['/img.icos/'] = '';
    $url_replace['/img.live/'] = '';
    //新路经兼容的目录
    $check_path_1 = 'new/';
    $check_path_2 = 'upload/';
    $check_path_3 = '/img.gv';
    $check_path_4 = 'upload_01/';
    //兼容替换处理
    if (stripos($url, $check_path_1) !== false || stripos($url, $check_path_2) !== false || stripos($url, $check_path_3) !== false || stripos($url, $check_path_4) !== false) {
        $baseUrl = str_ireplace(array_keys($url_replace), array_values($url_replace), $baseUrl);
    }
    $baseUrl = rtrim($baseUrl, '/');
    $url = ltrim($url, '/');
    return $baseUrl . '/' . $url;
}


function mb_substr_replace($string, $replacement, $start, $length)
{
    if ($string === null) {
        return $string;
    }
    $s1 = mb_substr($string, 0, $start);
    $s2 = $replacement;
    $s3 = mb_substr($string, $length);
    return $s1 . $s2 . $s3;
}







/**
 *  testFlight Env check
 *
 * @return bool
 */

function isTestFlightStore()
{
    $build = request()->getDevice()->build;
    if ($build && $build >= TF_BUILD) {
        return true;
    }
    return false;
}

/**
 * 获取官方账号
 * @return |null
 */
function getOfficialUID()
{
    return setting('official.uid', 99);
}

/**
 * 获取socket聊天校验token
 * @param $uuid
 * @param string $liveUid
 * @return string
 */
function getChatToken($uuid, $liveUid = '')
{
    //ad!@#$^&*()9527
    return sha1(CHAT_SALT . $uuid . $liveUid);
}

/**
 * @param $delimiter  分割
 * @param $dataStr    数据
 * @param bool $isBackAll 是否返回全部数据
 * @return array|string
 */
function getDataByExplode($delimiter,$dataStr,$isBackAll = false)
{
    $dataArr = explode($delimiter, $dataStr);
    if ($isBackAll) {
        return $dataArr;
    }
    return $dataArr[array_rand($dataArr)];
}

/**
 * @param $delimiter
 * @param $string
 * @param null $limit
 * @param string $fn
 * @return array
 */
function explode_map($delimiter, $string, $limit = null, $fn = 'trim|filter|unique')
{
    if (is_string($limit)) {
        list($limit, $fn) = [null, $limit];
    }
    $array = explode($delimiter, $string, $limit);
    $fns = explode('|', $fn);
    $fns = array_unique($fns);
    if (($key = array_search('trim', $fns)) !== false) {
        $array = array_map('trim' , $array);
        unset($fns[$key]);
    }
    if (($key = array_search('filter', $fns)) !== false) {
        $array = array_filter($array);
        unset($fns[$key]);
    }
    if (($key = array_search('unique', $fns)) !== false) {
        $array = array_unique($array);
        unset($fns[$key]);
    }
    foreach ($fns as $fn){
        $array = array_map($fn , $array);
    }
    return $array;
}

/**
 * pwa 端识别
 * @return bool
 */
function is_pwa(){
   if(!defined('APP_TYPE_FLAG') || APP_TYPE_FLAG ==0){
       return 1;
   }
   return 0;
}


/**
 * 特殊设置 48小时用户或非会员用户 列表都展示免费视频 ；其他的正常显示
 * @param MemberModel $member
 * @return bool
 */
function showVideoStyle(MemberModel $member)
{
    return 1;
    if (is_null($member)) {//免费
        return 0;
    } elseif (($member->regdate + 2 * 86400) > TIMESTAMP) {//48小时内 列表免费视频
        return 0;
    } elseif ($member->coins) {//正常展示列表视频
        return 1;
    } elseif ($member->expired_at < TIMESTAMP) {//非会员 列表免费视频
        return 0;
    }
    return 1;
}


/**
 * 小说完整地址
 * @param $url
 * @return string
 */
function url_story($url){
    return APP_STORE_URL.'/'.ltrim($url,'/');
}