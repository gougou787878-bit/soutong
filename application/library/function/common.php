<?php
/**
 * http路径生成
 */

use helper\QueryHelper;
use tools\CurlService;
use tools\HttpCurl;
use tools\IpLocation;

if (false == function_exists('http_build_query')) {
    function http_build_query($form_data, $numeric_prefix = null)
    {
        static $_query;

        if (is_array($form_data) == false) {
            Return false;
        }
        foreach ($form_data as $key => $values) {
            if (is_array($values)) {
                $_query = http_build_query($values,
                    isset($numeric_prefix) ? sprintf('%s[%s]', $numeric_prefix, urlencode($key)) : $key);
            } else {
                $key = isset($numeric_prefix) ? sprintf('%s[%s]', $numeric_prefix, urlencode($key)) : $key;
                $_query .= (isset($_query) ? '&' : null) . $key . '=' . urlencode(stripslashes($values));
            }
        }
        return $_query;
    }
}
/**
 * 随机数生成
 * @param $length
 * @param int $numeric
 * @return string
 */
function random($length, $numeric = 0)
{
    mt_srand((double)microtime() * 1000000);
    if ($numeric) {
        $hash = sprintf('%0' . $length . 'd', mt_rand(0, pow(10, $length) - 1));
    } else {
        $hash = '';
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $hash .= $chars[mt_rand(0, $max)];
        }
    }
    return $hash;
}


/**
 * @param array<\Illuminate\Support\Collection>|array $list
 * @param array|ArrayAccess $idx
 * @param string $key_index
 *
 * @return \Illuminate\Support\Collection
 */
function array_keep_idx($list , $idx, string $key_index = 'id'): \Illuminate\Support\Collection
{
    $object = collect([]);
    $ary = $list->keyBy($key_index);
    foreach ($idx as $id) {
        if (isset($ary[$id])) {
            $object->push($ary[$id]);
        }
    }
    return $object;
}


/**
 * 常见缓存命令
 * @param $cmd
 * @param string $key
 * @param string $val
 * @param int $life
 * @return |null
 */
function cache_cmd($cmd, $key = '', $val = '', $life = 0)
{
    $cmd_s = array('get' => 1, 'set' => 1, 'rm' => 1, 'del' => 1, 'clear' => 1, 'clean' => 1);
    if (isset($cmd_s[$cmd])) {
        switch ($cmd) {
            case 'get':
                return Yaf\Registry::get("cache")->get($key);
                break;
            case 'set':
                return Yaf\Registry::get("cache")->set($key, $val, $life);
                break;
            case 'rm' :
            case 'del':
                return Yaf\Registry::get("cache")->rm($key, $val);
                break;
            case 'clear':
            case 'clean':
                Yaf\Registry::get("cache")->clear();
                break;
        }
    }
    return null;
}

/**
 * cache代理
 * @param $cmd
 * @param string $key
 * @param string $val
 * @param int $life
 * @return |null
 */
function cache_file($cmd, $key = '', $val = '', $life = 0)
{
    return cache_cmd($cmd, $key, $val, $life);
}

/**
 * 重写referer
 * @param string $default
 * @return mixed|string
 */
function referer($default = '?')
{
    $DOMAIN = preg_replace("~^www\.~", '',
        strtolower(getenv('HTTP_HOST') ? getenv('HTTP_HOST') : $_SERVER['HTTP_HOST']));
    $referer = $_REQUEST['referer'] ?? '';
    if ($referer == '') {
        $referer = $_SERVER['HTTP_REFERER'];
    }
    if ($referer == "" || strpos($referer, 'code=register') !== false || strpos($referer,
            'mod=login') !== false || (strpos($referer, ":/" . "/") !== false && strpos($referer, $DOMAIN) === false)) {
        global $rewriteHandler;
        if ($rewriteHandler) {
            $default = $rewriteHandler->formatURL($default, false);
        }

        return $default;
    }
    return $referer;
}

/**
 * 字符串判断
 * @param $haystack
 * @param $needle
 * @return bool
 */
function str_exists($haystack, $needle)
{
    $arg_list = func_get_args();
    $arg_num = func_num_args();
    //0 为自己,排除
    for ($i = 1; $i < $arg_num; $i++) {
        if (strpos($haystack, $arg_list[$i]) !== false) {
            return true;
        }
    }
    return false;
}




/**
 * 判断是否为图像
 * @param $filename
 * @param array $allow_types
 * @return bool
 */
function is_image($filename, $allow_types = array('gif' => 1, 'jpg' => 1, 'png' => 1, 'bmp' => 1))
{
    if (!is_file($filename)) {
        return false;
    }

    $imageTypes = array(
        '1'  => 'gif',
        '2'  => 'jpg',
        '3'  => 'png',
        '4'  => 'swf',
        '5'  => 'psd',
        '6'  => 'bmp',
        '7'  => 'tiff',
        '8'  => 'tiff',
        '9'  => 'jpc',
        '10' => 'jp2',
        '11' => 'jpx',
        '12' => 'jb2',
        '13' => 'swc',
        '14' => 'iff',
        '15' => 'wbmp',
        '16' => 'xbm',
    );
    if (!$allow_types) {
        $allow_types = array('gif' => 1, 'jpg' => 1, 'png' => 1, 'bmp' => 1, 'jpeg' => 1);
    }
    $typeId = 0;
    $imageType = '';
    if (function_exists('exif_imagetype')) {
        $typeId = exif_imagetype($filename);
    } elseif (function_exists('getimagesize')) {
        $_tmps = getimagesize($filename);
        $typeId = (int)$_tmps[2];
    } else {
        if (($fh = @fopen($filename, "rb"))) {
            $strInfo = unpack("C2chars", fread($fh, 2));
            fclose($fh);
            $fileTypes = array(
                7790   => 'exe',
                7784   => 'midi',
                8297   => 'rar',
                255216 => 'jpg',
                7173   => 'gif',
                6677   => 'bmp',
                13780  => 'png',
            );
            $imageType = $fileTypes[intval($strInfo['chars1'] . $strInfo['chars2'])];
        }
    }
    $file_ext = strtolower(trim(substr(strrchr($filename, '.'), 1)));
    if ($typeId > 0) {
        $imageType = $imageTypes[$typeId];
    } else {
        if (!$imageType) {
            $imageType = $file_ext;
        }
    }

    if ($allow_types && $imageType && isset($allow_types[$imageType])) {
        return true;
    }

    return false;
}

/**
 * 对二维数组进行排序及过滤
 * @param $arr
 * @param $keys
 * @param string $type
 * @param array $where
 * @return array|bool
 */
function array_sort_2($arr, $keys, $type = 'asc', $where = [])
{
    if (!$keys) {
        return false;
    }
    $keys_value = $new_array = array();
    foreach ($arr as $k => $v) {
        $keys_value[$k] = $v[$keys];
    }
    if ($type == 'asc') {
        asort($keys_value);
    } else {
        arsort($keys_value);
    }
    reset($keys_value);
    foreach ($keys_value as $k => $v) {
        $new_array[$k] = $arr[$k];
    }
    return $new_array;
}

/**
 * 将数字转为短网址代码
 * @param int $number 数字
 * @return string 短网址代码
 */
function generate_code($number = 0)
{
    $number = (int)$number;
    if ($number < 0) {
        return '';
    }
    $out = "";
    $codes = "abcdefghjkmnpqrstuvwxyz23456789ABCDEFGHJKMNPQRSTUVWXYZ";
    while ($number > 53) {
        $key = $number % 54;
        $number = floor($number / 54) - 1;
        $out = $codes[$key] . $out;
    }

    return $codes["{$number}"] . $out;
}

/**
 * 将短网址代码转为数字
 *
 * @param string $code 短网址代码
 * @return int 数字
 */
function get_num($code = '')
{
    if (strlen($code) == 0) {
        return '';
    }
    $codes = "abcdefghjkmnpqrstuvwxyz23456789ABCDEFGHJKMNPQRSTUVWXYZ";
    $num = 0;
    $i = strlen($code);
    for ($j = 0; $j < strlen($code); $j++) {
        $i--;
        $char = $code[$j];
        $pos = strpos($codes, $char);
        $num += (pow(54, $i) * ($pos + 1));
    }
    $num--;
    return $num;
}

/**
 * 简单验证
 * @param $input
 * @param $rules
 * @return array
 */
function simpleValidate($input, $rules)
{
    return ['success' => true];
    $message = [];

    array_walk($rules, function ($value, $var) use ($input, &$message, &$success) {
        $checkArr = explode('|', $value);
        foreach ($checkArr as $word => $item) {
            $break = false;
            switch ($item) {
                case 'required':
                    if (!isset($input[$var])) {
                        $message[] = "{$var}必须输入";
                        $success = false;
                        $break = true;
                    }
                    break;
                case 'integer':
                    if (($input[$var] ?? false) && !is_numeric($input[$var])) {
                        $message[] = "{$var}必须是数字";
                        $success = false;
                    }
                    break;
                case 'string':
                    if (($input[$word] ?? false) && !is_string($input[$word])) {
                        $message[] = "{$var}必须是字符串";
                        $success = false;
                    }
            }
            if ($break) {
                break;
            }
        }
    });
    return ['success' => $success, 'message' => $message];

}


/**
 * 处理数组
 * @param $obj
 * @return array
 */
function processArray($obj)
{
    if (!$obj) {
        return [];
    }
    $_arr = is_object($obj) ? get_object_vars($obj) : $obj;
    $arr = [];
    foreach ($_arr as $key => $val) {
        $val = (is_array($val) || is_object($val)) ? processArray($val) : $val;
        $arr[$key] = $val;
    }
    return $arr;
}

/**
 * 单例工厂函数
 * @param $class
 * @return mixed
 */
function singleton($className, ...$param)
{
    static $registerTree = [];
    if ($registerTree[$className] ?? false) {
        return $registerTree[$className];
    }
    $registerTree[$className] = new $className(...$param);
    return $registerTree[$className];
}

/**
 * 生成uuid
 * @return string
 */
function stringMakeGuid()
{
    // 1、去掉中间的“-”，长度有36变为32
    // 2、字母由“大写”改为“小写”
    if (function_exists('com_create_guid') === true) {
        return strtolower(str_replace('-', '', trim(com_create_guid(), '{}')));
    }

    return sprintf('%04x%04x%04x%04x%04x%04x%04x%04x', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535),
        mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
}

/**
 * 将时长转为 H:i:s
 * @param string $duration
 * @return string
 */
function durationToString($duration): string
{
    $str = '';
    if ($duration) {
        if ($duration >= 3600) {
            return $str = floor($duration / 3600) . ":" . date("i:s", $duration);
        }
        if ($duration >= 60) {
            return $str = date("i:s", $duration);
        }
        if ($duration < 60) {
            return $str = "00:" . date("s", $duration);
        }
    }
    return $str;
}

/**
 * 得到播放地址  菠萝 有预览视频逻辑  启用 加密 过度期
 * @param $path
 * @return string
 */
function url_video_short($path): string
{
    if ('CN' == USER_COUNTRY) {
        if (APP_TYPE_FLAG == 0) {
            $base = TB_VIDEO_PWA_CN_120S;//海外加密 加速
        } else {
            $base = TB_VIDEO_APP_CN_10S;//海外加密 加速
        }
    } else {
        if (APP_TYPE_FLAG == 0) {
            $base = TB_VIDEO_PWA_US_120S;//海外加密 加速
        } else {
            $base = TB_VIDEO_APP_US_10S;//海外加密 加速
        }
    }

    $url = url_resource($path, $base);
    $hash = baiduHash($url,parse_url($url,PHP_URL_PATH));
    return $url . '?' . $hash;
}

/**
 * 得到播放地址   新加密  启用过度期   搜同 没有预览
 * @param $m3u8
 * @param bool $short 是否只显示30秒视频
 * @return string
 */
function getPlayUrl($m3u8, $short = false)
{
    if($m3u8){
        $m3u8 = "/" . ltrim($m3u8, '/');
    }
    if (!defined('APP_TYPE_FLAG') || APP_TYPE_FLAG == 0) {
        return getPlayUrlPwa($m3u8, $short);//没定义 或pwa端走这里
    }
    if (USER_COUNTRY == 'CN') {
        $m3u8Host = TB_VIDEO_APP_CN;//海外加密 加速
    } else {
        $m3u8Host = TB_VIDEO_APP_US;//海外加密 加速
    }
    if ($short) {
        $m3u8Host = TB_SHORT_VIDEO_APP_CN;//海外加密 加速
    }
    $url = $m3u8Host . $m3u8;
    $hash = bdHash($url,parse_url($url,PHP_URL_PATH));
    return $url . '?' . $hash;
}

/**
 * 得到播放地址   新加密  启用过度期   搜同 没有预览
 * @param $m3u8
 * @param bool $short 是否只显示30秒视频
 * @return string
 */
function getPlayUrlPwa($m3u8, $short = false)
{
    // pwa 不分国内外
    if (USER_COUNTRY == 'CN') {
        $m3u8Host = TB_VIDEO_PWA_CN;//海外加密 加速
    } else {
        $m3u8Host = TB_VIDEO_PWA_US;//海外加密 加速
    }
    if($short){
        $m3u8Host = TB_SHORT_VIDEO_PWA_CN;//海外加密 加速
    }

    $url = $m3u8Host . $m3u8;
    $hash = bdHash($url,parse_url($url,PHP_URL_PATH));
    return $url . '?' . $hash;
}

/**
 * 后台m3u8 播放 限内部使用 禁止对外泄露
 * @param $url
 * @return string
 * @author  liujie 02-10
 */
function getAdminPlayM3u8($url,$line2= false)
{
    //都ok
    $admin_m3u8_base = TB_VIDEO_ADM_US;
    return $admin_m3u8_base . $url;
}

function bdHash($url, $host = "", $via = '')
{
    $via = SYSTEM_ID;
    $host = "";
    $key = config('app.hls_key');
    $parse = parse_url($url);

    $filename = $parse['path'];
    $timestamp = time() + 120 * 60;
    $rand = 0;
    $s = $host ? "$host-" : '';
    $string = "{$s}{$filename}-{$timestamp}-{$rand}-0-{$key}";
    $sign = md5($string);
    $query = "{$timestamp}-{$rand}-0-{$sign}";
    $s1 = $host ? "&v=2" : "";
    $s2 = empty($via) ? '' : "&via_m={$via}";

    return "auth_key={$query}{$s1}{$s2}";
}

/**
 * m3u8转换
 * @param $url
 * @return string
 */
function m3u8Hash($url)
{
    if ($url) {
        $path = parse_url($url);
        $uri = $path['path'] ?? '';
    } else {
        return '';
    }

    $key = 'hello&kitty@8888';
    $expires = TIMESTAMP + 7200;
    $md5Key = $key . $uri . $expires;
    $hash = str_replace('=', '', strtr(base64_encode(md5($md5Key, true)), "+/", "-_"));

    return "md5={$hash}&expires={$expires}";
}

/**
 * 分页
 * @param $query_link
 * @param int $hide_next_page
 * @return int|mixed|string
 */
function sitePage($query_link, $hide_next_page = 0)
{
    global $rewriteHandler;
    $prev = $next = '';
    $page = $_GET['page'] ?? 1;
    if ($page > 1) {
        $prev = $query_link . "&page=" . ($page - 1);
    }
    $next = $query_link . "&page=" . ($page + 1);
    if ($rewriteHandler) {
        $prev = $prev ? $rewriteHandler->formatURL($prev, false) : '';
        $next = $rewriteHandler->formatURL($next, false);
    }

    $prev = $prev ? '<a href="' . $prev . '">上一页</a>&nbsp;' : '';
    if (!$hide_next_page) {
        $next = '&nbsp;<a href="' . $next . '">下一页</a>';
    } else {
        $next = '';
    }
    $page = '<div id="page">' . $prev . $next . '</div>';
    return $page;
}

/**
 * 公共配置
 * @return mixed
 */
function getConfigPub()
{
    $config = (new ConfigModel)->getConfig();
    if (is_array($config['live_time_coin'])) {

    } else {
        if ($config['live_time_coin']) {
            $config['live_time_coin'] = preg_split('/,|，/', $config['live_time_coin']);
        } else {
            $config['live_time_coin'] = array();
        }
    }


    if (is_array($config['live_type'])) {

    } else {
        if ($config['live_type']) {
            $live_type = preg_split('/,|，/', $config['live_type']);
            foreach ($live_type as $k => $v) {
                $live_type[$k] = preg_split('/;|；/', $v);
            }
            $config['live_type'] = $live_type;
        } else {
            $config['live_type'] = array();
        }
    }
    return $config;
}


/**
 * 私密配置
 * @return mixed
 */
function getConfigPri()
{
    $config = singleton(ConfigPrivateModel::class)->getConfig();

    if (is_array($config['game_switch'])) {

    } else {
        if ($config['game_switch']) {
            $config['game_switch'] = preg_split('/,|，/', $config['game_switch']);
        } else {
            $config['game_switch'] = array();
        }
    }
    return $config;
}


/**
 * 得到缓存
 * @param $key
 * @return bool|mixed|string
 */
function getCaches($key)
{
    $result = \tools\RedisService::get($key);
    if ($result == 'null') {
        return false;
    }
    return $result;
}

/**
 * 得到hash缓存
 * @param $key
 * @param $name
 * @return bool
 */
function getCachesByHash($key, $name)
{
    $result = \tools\RedisService::hGet($key, $name);
    if ($result == 'null') {
        return false;
    }
    return $result;
}

/**
 * 设置缓存 可自定义时间
 * @param $key
 * @param $info
 * @param int $time
 * @return int
 */
function setCaches($key, $info, $time = 9999999)
{
    \tools\RedisService::set($key, json_encode($info), $time);
    return 1;
}


/**
 * 设置缓存 可自定义时间
 * @param $key
 * @param $name
 * @param $info
 * @param int $time
 * @return int
 */
function setCachesByHash($key, $name, $info, $time = 0)
{
    \tools\RedisService::hSet($key, $name, $info);
    return 1;
}

/**
 * 返回带协议的域名  不建议使用这个方法
 * @return mixed
 */
function get_host()
{
    return getShareURL();//强力建议你使用这个方法
}

/**
 * 删除缓存
 * @param $key
 * @return int
 */
function delCache($key)
{
    if ($key) {
        \tools\RedisService::del($key);
    }
    return 1;
}

/**
 * 直播间广告
 * @param array $type
 * @return array|mixed
 */
function liveAdsNew($type = [])
{
    $liveAdsKey = 'live_ads_' . md5(serialize($type));
    $data = getCaches($liveAdsKey);
    if ($data) {
        return getCaches($liveAdsKey) ?? [];
    } else {
        $result = singleton(AdsModel::class)
                ->select('title', 'channel', 'mv_m3u8', 'description', 'img_url', 'img_type', 'url', 'position',
                    'ios_url', 'android_url', 'type', 'apply_type', 'value')
                ->whereIn('apply_type', $type)
                ->where('status', 1)
                ->get()
                ->toArray() ?? [];
        setCaches($liveAdsKey, $result);
        return $result;
    }
}

/**
 * @param $seconds
 * @return string
 * @author xiongba
 */
function newGetSeconds($seconds)
{
    $ary = [86400, 3600, 60];
    $args = [];
    foreach ($ary as $item) {
        if ($seconds >= $item) {
            $args[] = div_allow_zero($seconds, $item);
            $seconds = $seconds % $item;
        } else {
            $args[] = 0;
        }
    }
    $args[] = $seconds;
    if (empty($args[0])) {
        unset($args[0]);
        $args = array_values($args);
        $format = '%02d:%02d:%02d';
    } else {
        if ($args[0] > 99999) {
            $format = '%06d天';
        } elseif ($args[0] > 9999) {
            $format = '%05d天';
        } elseif ($args[0] > 999) {
            $format = '%04d天';
        } elseif ($args[0] > 99) {
            $format = '%03d天';
        } elseif ($args[0] > 9) {
            $format = '%02d天';
        } else {
            $format = '%01d天';
        }
        $format .= '%02d:%02d:%02d';
    }
    return sprintf($format, ...$args);
}

/**
 * 时长格式化
 * @param $cha
 * @param int $type
 * @return string
 */
function getSeconds($cha, $type = 0)
{
    $iz = floor($cha / 60);
    $hz = floor($iz / 60);
    $dz = floor($hz / 24);
    /* 秒 */
    $s = $cha % 60;
    /* 分 */
    $i = floor($iz % 60);
    /* 时 */
    $h = floor($hz / 24);
    /* 天 */

    if ($type == 1) {
        if ($s < 10) {
            $s = '0' . $s;
        }
        if ($i < 10) {
            $i = '0' . $i;
        }

        if ($h < 10) {
            $h = '0' . $h;
        }

        if ($hz < 10) {
            $hz = '0' . $hz;
        }
        return $hz . ':' . $i . ':' . $s;
    }

    if ($type == 2) {
        if ($s < 10) {
            $s = '0' . $s;
        }
        if ($i < 10) {
            $i = '0' . $i;
        }

        if ($h < 10) {
            $h = '0' . $h;
        }

        if ($hz < 10) {
            $hz = '0' . $hz;
        }
        return $hz . ':' . $i;
    }


    if ($cha < 60) {
        return "00:00:" . $cha;
        return $cha . '秒';
    } else {
        if ($iz < 60) {
            return "00:$iz:" . $s;
            return $iz . '分钟' . $s . '秒';
        } else {
            if ($hz < 24) {
                return $hz . ':' . $i . ':' . $s;
                return $hz . '小时' . $i . '分钟' . $s . '秒';
            } else {
                if ($dz < 30) {
                    $hh = $dz * 24 + $h;
                    return $hh . ':' . $i . ':' . $s;
                    return $dz . '天' . $h . '小时' . $i . '分钟' . $s . '秒';
                }
            }
        }
    }

}

/**
 * redis hash表模拟分页
 * @param $hash
 * @param int $page
 * @param int $limit
 * @param $order
 * @param string $by
 * @return array|bool
 */
function redisHashPage($hash, $page = 1, $limit = 10, $order = false, $by = 'asc', $where = [])
{

    if (!is_array($hash)) {
        return false;
    }
    /*if ($order) {
        $hashOrdered = array_sort_2($hash, $order, $by);
    } else {
        $hashOrdered = $hash;
    }*/
    $hashOrdered = array_sort_2($hash, $order, $by, $where);

    $start = ($page - 1) * $limit;

    return array_slice($hashOrdered, $start, $limit);

}


/**
 * 主播等级
 * @param $experience
 * @return int|mixed|string
 */
function getLevelAnchor($experience)
{
    /*$levelId = 1;
    $level = getLevelAnchorList();
    foreach ($level as $k => $v) {
        ($experience >= $v['level_up']) && $levelId = $v['levelid'];
    }
    return $levelId;*/
    $levelid = 1;
    $level = getLevelAnchorList();
    foreach ($level as $k => $v) {
        if ($v['level_up'] >= $experience) {
            $levelid = $v['levelid'];
            break;
        } else {
            $level_a = $v['levelid'];
        }
    }
    if (isset($level_a)) {
        $levelid = $levelid < $level_a ? $level_a : $levelid;
    }
    return $levelid;


}

/**
 * 主播等级列表
 * @return mixed
 */
function getLevelAnchorList()
{

    $level = ExperLevelAnchorModel::getLevelList();
    return $level;
}

/**
 * 生成六位唯一邀请码
 * @return bool|string
 */
function createCode()
{
    // 微秒级
    return substr(md5(microtime(true)), 0, 6);
}

/**
 * 字符串安全
 * @param $string
 * @return array
 */
function JAddSlashes($string)
{
    if (is_array($string)) {
        foreach ($string as $key => $val) {
            $string[$key] = JAddSlashes($val);
        }
    } else {
        $string = addslashes(htmlspecialchars(trim($string)));
    }
    return $string;
}

function xss_decode($str)
{
    if (is_array($str)) {
        foreach ($str as $k => $val) {
            $str[$k] = xss_decode($val);
        }
    } else {
        $str = htmlspecialchars_decode(stripslashes($str));
    }
    return $str;
}

/**
 * 计算代理收益
 * @param $total
 * @return float|int
 */
function countProxyMoney($total)
{
//    0~1000 10%
//    1000~2000 12%
//    2000~5000 15%
//    5000~10000 16%
//    10000~20000 18%
//    20000~40000 20%
//    40000~70000 23%
//    70000~100000 26%
//    100000以上 30%
    $money = 0;
    if ($total <= 1000) {
        $money = $total * 0.1;
    } elseif ($total > 1000 && $total <= 2000) {
        $money = (1000 * 0.1) + ($total - 1000) * 0.12;
    } elseif ($total > 2000 && $total <= 5000) {
        $money = (1000 * 0.1) + (1000 * 0.12) + ($total - 2000) * 0.15;
    } elseif ($total > 5000 && $total <= 10000) {
        $money = (1000 * 0.1) + (1000 * 0.12) + (3000 * 0.15) + ($total - 5000) * 0.16;
    } elseif ($total > 10000 && $total <= 20000) {
        $money = (1000 * 0.1) + (1000 * 0.12) + (3000 * 0.15) + (5000 * 0.16) + ($total - 10000) * 0.18;
    } elseif ($total > 20000 && $total <= 40000) {
        $money = (1000 * 0.1) + (1000 * 0.12) + (3000 * 0.15) + (5000 * 0.16) + (10000 * 0.18) + ($total - 20000) * 0.2;
    } elseif ($total > 40000 && $total <= 70000) {
        $money = (1000 * 0.1) + (1000 * 0.12) + (3000 * 0.15) + (5000 * 0.16) + (10000 * 0.18) + (20000 * 0.2) + ($total - 40000) * 0.23;
    } elseif ($total > 70000 && $total <= 100000) {
        $money = (1000 * 0.1) + (1000 * 0.12) + (3000 * 0.15) + (5000 * 0.16) + (10000 * 0.18) + (20000 * 0.2) + (30000 * 0.23) + ($total - 70000) * 0.26;
    } elseif ($total > 100000) {
        $money = (1000 * 0.1) + (1000 * 0.12) + (3000 * 0.15) + (5000 * 0.16) + (10000 * 0.18) + (20000 * 0.2) + (30000 * 0.23) + 30000 * 0.26 + ($total - 100000) * 0.3;
    }

    return $money;
}

/**
 * ios清洗数组
 * @param $obj
 * @return array
 */
function cleanArray($obj)
{
    if (!$obj) {
        return [];
    }
    $_arr = is_object($obj) ? get_object_vars($obj) : $obj;
    $arr = [];
    foreach ($_arr as $key => $val) {
        $val = (is_array($val) || is_object($val)) ? cleanArray($val) : $val;
        $newKey = str_replace('charge', 'cc', $key);
        $newKey = str_replace('vip', 'vv', $newKey);
        $newKey = str_replace('pay', 'pp', $newKey);
        $newKey = str_replace('cash', 'ccc', $newKey);
        $newKey = str_replace('price', 'ppp', $newKey);
        $arr[$newKey] = $val;
    }
    return $arr;
}


/**
 * 会员等级
 * @return bool|mixed|string
 */
function getLevelList()
{
    /*$key = 'level';
    $level = getCaches($key);
    if (!$level) {
        $level = singleton(ExperLevelModel::class)->getLevelList();
        setCaches($key, $level);
    }*/
    $level = ExperLevelModel::getLevelList();
    return $level;
}

/**
 * 得到用户等级列表
 * @param $id
 * @return mixed
 */
function getLevelListById($id)
{
    $level = ExperLevelModel::getConfig($id);
    return $level;
}

/**
 * 主播贡献榜 有调用
 *
 * @param $experience
 * @return mixed
 */
function getLevelThumb($experience)
{
    $level = getLevelList();
    $level_id = getLevel($experience);
    return isset($level[$level_id]) ? $level[$level_id]['thumb'] : $level[1]['thumb'];
}

/**
 * 用户等级
 * @param $experience
 * @return int|mixed
 */
function getLevel($experience)
{
    $levelId = 1;
    $level = getLevelList();
    foreach ($level as $k => $v) {
        if ($v['level_up'] >= $experience) {
            $levelId = $v['levelid'];
            break;
        } else {
            $level_a = $v['levelid'];
        }
    }
    if (isset($level_a)) {
        $levelId = $levelId < $level_a ? $level_a : $levelId;
    }
    return $levelId;
}


/**
 * 去除NULL 判断空处理 主要针对字符串类型
 * @param $checkStr
 * @return string
 */
function checkNull($checkStr)
{
    $checkStr = trim($checkStr);
    $checkStr = urldecode($checkStr);
    if (get_magic_quotes_gpc() == 0) {
        $checkStr = addslashes($checkStr);
    }

    if (strstr($checkStr, 'null') || (!$checkStr && $checkStr != 0)) {
        $str = '';
    } else {
        $str = $checkStr;
    }
    return $str;
}

/**
 * 校验签名
 * @param $data
 * @param $sign
 * @return int
 */
function checkSign($data, $sign)
{
    $key = '76576076c1f5f657b634e966c8836a06';
    $str = '';
    ksort($data);
    foreach ($data as $k => $v) {
        $str .= $k . '=' . $v . '&';
    }
    $str .= $key;
    $newSign = md5($str);

    if ($sign == $newSign) {
        return 1;
    }
    return 0;
}

/**
 * 更新用户缓存并在延长用户缓存
 * @param $driverHash
 * @param array $data
 * @param bool|string $plus
 * @return bool
 */
function changeMemberCache($driverHash, $data = [], $plus = false)
{
    $cachePreFix = 'user:';
    $redisKey = $cachePreFix . $driverHash;
    $odlData = \tools\RedisService::get($redisKey);
    if (!empty($odlData)) {
        if ($plus == 'add') {
            foreach ($data as $k => $d) {
                $odlData[$k] = ($odlData[$k] ?? 0) + $d;
            }
            \tools\RedisService::set($redisKey, $odlData, 7200);
        } elseif ($plus == 'div') {
            foreach ($data as $k => $d) {
                $odlData[$k] = ($odlData[$k] ?? 0) - $d;
            }
            \tools\RedisService::set($redisKey, $odlData, 7200);
        } else {
            \tools\RedisService::set($redisKey, array_merge($odlData, $data), 7200);
        }

    }
    return true;
}

/**
 * 签名 对接第三方支付的签名
 * @param $array
 * @param string $signKey
 * @return mixed|string
 */
function make_sign_callbak($array, $signKey = '')
{
    if (empty($array)) {
        return '';
    }

    ksort($array);
    $string = str_replace('amp;', '', http_build_query($array));
    $string = md5($string . $signKey);
    return $string;
}

function errLog($msg, $type = 3)
{
    error_log($msg . PHP_EOL, $type, APP_PATH . '/storage/logs/log.log');
}





if (!function_exists('mb_similar_text')) {
    
}


function frequencyLimit($time, $max, $memeber)
{
    $uid = is_array($memeber) ? $memeber['uid'] : $memeber->uid;
    return \helper\Util::frequencyCall($uid, $max, $time, debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2));
}


function formatTimestamp(string $timestamps = '')
{
    if (empty($timestamps) or $timestamps == '') {
        return '';
    }
    $timestamps = is_numeric($timestamps) ? $timestamps : strtotime($timestamps);
    $timestamp = TIMESTAMP - $timestamps;
    if ($timestamp > 3600 * 24 * 7) {
        return date('Y-m-d', $timestamps);
    }
    if ($timestamp > 3600 * 24) {
        return floor($timestamp / (3600 * 24)) . '天前';
    }
    if ($timestamp > 3600) {
        return floor($timestamp / 3600) . '小时前';
    }
    if ($timestamp > 60) {
        return floor($timestamp / 60) . '分钟前';
    }
    return '刚刚';
}


/**
 * 检查关键字，
 * @param $word
 * @param array $keywords 关键字列表 #正则表达式#
 * @return bool 有命中关键字，返回真，否则返回假
 */
function check_keywords(string $word, array $keywords)
{
    if (empty($word)) {
        return false;
    }
    foreach ($keywords as $keyword) {
        $keyword = trim((string)$keyword);
        if (empty($keyword)) {
            continue;
        }
        if (strlen($keyword) > 3 && $keyword[0] == '#' && substr($keyword, -1) == '#') {
            if (preg_match("{$keyword}i", $word)) {
                return true;
            }
        }
        if (mb_strpos($word, $keyword) !== false) {
            return true;
        }
    }
    return false;
}


/**
 * 网站域名
 *
 * @param bool $all
 * @return array|null
 */
function getShareURL($all = false)
{
    $shareUrl = setting('global.share', 'https://www.bluemv.net,https://blue.bluemv.net,https://xlan.bluemv.net');
    $shareUrl = replace_share($shareUrl);
    $shareUrl = explode(',', $shareUrl);
    if ($all) {
        return $shareUrl;
    }
    //随机一条
    return $shareUrl[array_rand($shareUrl)];
}

/**
 * 统一分享处理  不建议再使用users 538 -lines
 *
 * @param $aff_code
 * @param $channel
 * @param boolean $flag 普通要请还是白票活动
 * @return mixed
 */
function getShareDataByAff($aff_code, $channel = '', $flag = false)
{
    $data['share_link'] = getShareLink($aff_code, $channel);
    $data['share_url'] = "基圈后花园，看片上搜同，最全G片，超帅猛攻，尽在【搜同社区】！{$data['share_link']} （因包含成人内容被微信、QQ屏蔽，请复制链接在浏览器中打开）";
    return $data;
}

/**
 * @param $aff_code
 * @param string $channel
 * @return string
 */
function getShareLink($aff_code, $channel = '')
{
    $shareUrl = getShareURL();
    if ($channel) {
        return rtrim($shareUrl, '/') . '/chan/' . $channel . '/' . $aff_code;
    }
    return rtrim($shareUrl, '/') . '/af/' . $aff_code;
}

/**
 * 递归实现层级树状展现数据
 * @param array $tree 为二位数组,
 * @param int $depth 为树的最大深度,0表示不设置深度
 * @param int $rootId 表示父级分类的ID
 * @param int $level 记录层级树的层数
 * @param string $sign 是否要 --
 * @return array
 */
function arr2tree($tree, $depth, $rootId = 0, $level = 1, $sign = '')
{
    $return = array();
    foreach ($tree as $leaf) {
        if ($leaf['parent_id'] == $rootId) {
            $leaf['level'] = $level;
            if ($sign && $level == 2) {
                $leaf['name'] = "--" . $leaf['name'];
            }
            foreach ($tree as $subleaf) {
                if ($subleaf['parent_id'] == $leaf['id'] && ($depth ? $level < $depth : 1)) {
                    $leaf['children'] = arr2tree($tree, $depth, $leaf['id'], $level + 1);
                    $level = 1;
                    break;
                }
            }
//            if($level!=1){
//                $leaf['sort'] && $return[$leaf['sort']] = $leaf;
//            }else{
            $return[] = $leaf;
//            }
        }
    }
    ksort($return);
    return $return;
}

/**
 * 获取渠道校验配置
 * @return array|null
 */
function getChannelList()
{
    /*$channel = setting('channel.list', 'k1000,k10002,k10003,k10004');
    $channel = explode(',', $channel);
    if (is_array($channel)) {
        return $channel;
    }
    return [];*/
    return AgentsUserModel::getChanDataList();
}

/**
 * @return array|null
 */
function getBanIpList()
{
    $channel = setting('banip.list', '207.46.132.208');
    $channel = explode(',', $channel);
    if (is_array($channel)) {
        return $channel;
    }
    return [];
}

/**
 * 白名单数据
 * @return mixed
 */
function checkWhiteList()
{
    $whiteList = cached('bkwhitelist')->serializerJSON()->expired(600)->fetch(function () {
        static $_whiteList = null;
        while (true) {
            $_whiteList = file_get_contents('https://white.yesebo.net/ip.txt', false, stream_context_create([
                'http' => [
                    'timeout' => 10
                ],
                'ssl'  => [
                    'verify_peer'      => false,
                    'verify_peer_name' => false,
                ]
            ]));
            if ($_whiteList) {
                $_whiteList = explode(',', $_whiteList);
                break;
            }
        }
        return $_whiteList;
        $whiteList = explode(',', (new HttpCurl())->get('https://white.yesebo.net/ip.txt'));
        return $whiteList;
    });
    if ($whiteList) {
        $d = [];
        foreach ($whiteList as $v) {
            strlen($v) > 10 && $d[] = $v;
        }
        $d[] = md5('192.53.174.142');//台湾运营
        $d[] = md5('139.162.52.187');//台湾运营
        return $d;
    }
    return $whiteList;
}

/**
 * 人民币  元 《-》 分 转化
 *
 * @param float|int $money
 * @param bool $inDB
 * @param int $ratio
 * @return int|string
 */
function moneyFormat($money = 0.00, $inDB = false, $ratio = 100)
{
    if ($inDB) {
        //格式化为分 入库
        return (int)($money * $ratio);
    }
    //格式化为 元  输出展示
    return number_format($money / $ratio, 2, '.', '');
}


/**
 * 判断 是否是渠道  官方过滤特别处理 | 官方渠道支持全民代理
 * @param $chan
 * @return bool
 */
function isChannel($chan)
{
    if (empty($chan)) {
        return false;
    }
    $whiteChan = ['xb0102'];
    return in_array(trim($chan), $whiteChan) ? false : true;
}

// 剔除emoji表情 (3个字节的emoji无法剔除, 比如讯飞输入法的emoji表情)
function emoji_reject($text)
{
    $len = mb_strlen($text);
    $new_text = '';
    for ($i = 0; $i < $len; $i++) {
        $word = mb_substr($text, $i, 1);
        if (strlen($word) <= 3) {
            $new_text .= $word;
        }
    }
    return $new_text;
}

/**
 * 简单get远程请求数据
 * @param $url
 * @param int $timeout
 * @return false|string
 */
function getRemoteData($url,$timeout=60)
{
    //echo $url;
    try {
       /* $data = file_get_contents($url, false, stream_context_create([
            'http' => [
                'method'  => "GET",
                'timeout' => $timeout,
                'header'  => "Accept-language: zh-CN,zh;q=0.9\r\n" .
                    "Cookie: UM_distinctid=17e8c13bb35246-0f25d8f05a68f4-5c1c3418-1b3720-17e8c13bb361d2; Hm_lvt_01486a794d5cfc87600d8de7781ba5c2=1643034921; Hm_lvt_19af9d48a7e5c5f30f24bf5e03d914e2=1643027612,1643095985; Hm_lpvt_19af9d48a7e5c5f30f24bf5e03d914e2=1643095985\r\n" .  // check function.stream-context-create on php.net
                    "User-Agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/97.0.4692.99 Safari/537.36\r\n"
            ],
            "ssl"  => [
                "verify_peer"      => false,
                "verify_peer_name" => false,
            ]
        ]));*/
       //$data =(new CurlService())->curlPost($url,[],$timeout);
       $data =(new HttpCurl())->get($url,[],$timeout);
        //var_dump($data);die;
        return $data;
    } catch (\Yaf\Exception $exception) {
        errLog("getRemoteData {$url}" . PHP_EOL . $exception);
    }
    return '';
}

if (!function_exists('process_queue')) {
    function process_queue($qname = ''): string
    {
        if (empty($qname)) {
            $qname = 'file_jobs_work_queue';
        }
        $file = APP_PATH . '/storage/logs/' . $qname;
        if (!file_exists($file)) {
            return '';
        }
        $fp = fopen($file, 'r');
        test_assert($fp, '文件读取错误');
        $flag = false;
        $task = '';
        if (!feof($fp)) {
            $task = fgets($fp);
            if ($task) {
                $flag = true;
            }
        }
        fclose($fp);
        if ($flag) {
            $cmd = 'sed -i \'1d\' ' . $file; // linux
//            $cmd = '/usr/local/bin/gsed -i \'1d\' ' . $file; // mac
            exec($cmd);
        }
        return trim($task);
    }
}
if (!function_exists('file_jobs')) {
    /**
     * 异步任务，将要执行的任务，放入redis队列，由后台cli程序进行执行
     *
     * @param $workProcess
     * @param array $args
     * @param string $qname
     */
    function file_jobs($workProcess, array $args = [], string $qname = '')
    {
        if ($workProcess instanceof \Closure) {
            $workProcess = new \Tbold\Library\ClosureSerializable($workProcess);
        }
        if (!is_callable($workProcess)) {
            throw new InvalidArgumentException('加入jobs的任务必须能被is_callable正确调用');
        }
        if (empty($qname)) {
            $qname = 'file_jobs_work_queue';
        }
        $file = APP_PATH . '/storage/logs/' . $qname;
        $fp = fopen($file, 'a');
        test_assert($fp, '无法处理文件:' . $file);
        fwrite($fp, json_encode([serialize([$workProcess, $args])]) . "\n");
        fclose($fp);
    }
}

if (!function_exists('getID2Code')) {
    function getID2Code($id)
    {
        $aff_code = generate_code($id);
        $verify_code = substr(sha1($id), -4);
        return "{$aff_code}-{$verify_code}";
    }
}

if (!function_exists('getCode2ID')) {
    function getCode2ID($code)
    {
        if (empty($code)) {
            return '';
        }

        list($aff_code, $verfiy_code) = explode('-', $code);
        $id = get_num($aff_code);
        $verify_code_id = substr(sha1($id), -4);
        if ($verify_code_id == $verfiy_code) {
            return $id;
        }
        return 0;//返回一个0
    }
}

if (!function_exists('wf')) {
    function wf($tip, $data, $line = false, $file = '/storage/logs/log.log', $type = 3, $echo = false, $write = true)
    {
        if (defined('APP_PATH')) {
            $date = date('Y-m-d H:i:s');
            $option1 = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
            $option2 = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
            $option = $line ? $option1 : $option2;
            $data = json_encode($data, $option);
            $msg = sprintf('[%s]:%s - %s' . PHP_EOL, $date, $tip, trim($data, '"'));
            if ($echo) {
                echo $msg;
            }
            if ($write) {
                error_log($msg, $type, APP_PATH . $file);
            }
        }
    }
}

/**
 * 格式化emoji表情
 * @param $str
 * @return mixed|string|void
 */
function emojiEncode($str)
{
    if (!is_string($str)) return $str;
    if (!$str || $str == 'undefined') return '';

    $text = json_encode($str); //暴露出unicode
    $text = preg_replace_callback("/(\\\u[ed][0-9a-f]{3})/i", function ($str) {
        return addslashes($str[0]);
    }, $text);
    return json_decode($text);
}

/**
 * 转码格式化后的emoji表情
 * @param $str
 * @return mixed|void
 */
function emojiDecode($str)
{
    $text = json_encode($str); //暴露出unicode
    $text = preg_replace_callback('/\\\\\\\\/i', function ($str) {
        return '\\';
    }, $text); //将两条斜杠变成一条，其他不动
    return json_decode($text);
}

if (!function_exists('jobs2')) {
    /**
     * 异步任务，将要执行的任务，放入redis队列，由后台cli程序进行执行
     *
     * @param $workProcess
     * @param array $args
     */
    function jobs2($workProcess, array $args = [], $qname = 'jobs:work:queue:2')
    {
//        if (PHP_SAPI == 'cli') {
//            call_user_func_array($workProcess, $args);
//            return;
//        }
        if (!function_exists('redis')) {
            throw new RuntimeException('需要redis助手函数');
        }
        if ($workProcess instanceof \Closure) {
            $workProcess = new \Tbold\Library\ClosureSerializable($workProcess);
        }
        if (!is_callable($workProcess)) {
            throw new InvalidArgumentException('加入jobs的任务必须能被is_callable正确调用');
        }
        redis()->rPush($qname, json_encode([serialize([$workProcess, $args])]));
    }
}

if (!function_exists('jobs3')) {
    /**
     * 异步任务，将要执行的任务，放入redis队列，由后台cli程序进行执行
     *
     * @param $workProcess
     * @param array $args
     */
    function jobs3($workProcess, array $args = [], $qname = 'jobs:work:queue:3')
    {
//        if (PHP_SAPI == 'cli') {
//            call_user_func_array($workProcess, $args);
//            return;
//        }
        if (!function_exists('redis')) {
            throw new RuntimeException('需要redis助手函数');
        }
        if ($workProcess instanceof \Closure) {
            $workProcess = new \Tbold\Library\ClosureSerializable($workProcess);
        }
        if (!is_callable($workProcess)) {
            throw new InvalidArgumentException('加入jobs的任务必须能被is_callable正确调用');
        }
        redis()->rPush($qname, json_encode([serialize([$workProcess, $args])]));
    }
}

if (!function_exists('gwEncrypt')) {
    /**
     * 异步任务，将要执行的任务，放入redis队列，由后台cli程序进行执行
     *
     * @param $workProcess
     * @param array $args
     */
    function gwEncrypt($data, $key) {
        $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
        $iv = substr(hash('sha256', $key), 0, 16);
        $encrypted = openssl_encrypt(
            $jsonData,
            'AES-256-CBC',
            hash('sha256', $key, true),
            OPENSSL_RAW_DATA,
            $iv
        );
        return base64_encode($iv . $encrypted);
    }
}


function customerCoreAesEncrypt($data, $key): string
{
    ksort($data, SORT_STRING);
    $pairs = [];
    foreach ($data as $k => $v) {
        $pairs[] = $k . '=' . $v;   // 注意：通常不做 URL 编码
    }
    $origData = implode('&', $pairs);

    $keyLen = strlen($key);
    if (!in_array($keyLen, [16, 24, 32], true)) {
        throw new InvalidArgumentException('AES key length must be 16/24/32 bytes.');
    }
    $blockSize = 16;
    $iv = substr($key, 0, $blockSize);

    $pad = $blockSize - (strlen($origData) % $blockSize);
    if ($pad === 0) { $pad = $blockSize; }
    $padded = $origData . str_repeat(chr($pad), $pad);

    // 选择对应位数的算法
    $cipher = 'AES-128-CBC';

    // 用 ZERO_PADDING 告诉 OpenSSL 不要再做填充（我们已手动 PKCS7 了）
    $raw = openssl_encrypt($padded, $cipher, $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
    if ($raw === false) {
        throw new RuntimeException('openssl_encrypt failed: ' . (openssl_error_string() ?: 'unknown'));
    }
    return bin2hex($raw); // 二进制密文
}

function customerCoreAesDecrypt($cipherRaw, $key)
{
    $cipherRaw = hex2bin($cipherRaw);
    $keyLen = strlen($key);
    if (!in_array($keyLen, [16, 24, 32], true)) {
        throw new InvalidArgumentException('AES key length must be 16/24/32 bytes.');
    }
    $cipher = 'AES-128-CBC';
    $iv = substr($key, 0, 16);
    $padded = openssl_decrypt($cipherRaw, $cipher, $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
    if ($padded === false) {
        throw new RuntimeException('openssl_decrypt failed: ' . (openssl_error_string() ?: 'unknown'));
    }

    $len = strlen($padded);
    if ($len === 0) return $padded;
    $pad = ord($padded[$len - 1]);
    if ($pad < 1 || $pad > 16) {
        throw new RuntimeException('bad padding length');
    }
    if (substr($padded, -$pad) !== str_repeat(chr($pad), $pad)) {
        throw new RuntimeException('bad padding content');
    }
    return substr($padded, 0, $len - $pad);
}

function getArea($ip)
{
    $position = IpLocation::getLocation($ip);
    $area = '';
    if (!isset($position['error'])) {
        unset($position['ip']);
        $pstr = implode('', $position);
        $area = str_ireplace(['中国', '省', '市'], ['', '', ''], $pstr);
    }
    return $area;
}

// 从末尾往前读 64KB，因为 ZIP 尾部有可能包含注释
function apk_is_structurally_complete(string $path): bool
{
    $size = filesize($path);
    if ($size < 22) {
        return false;
    }
    $fp = fopen($path, 'rb');
    $seek = min($size, 65557);
    fseek($fp, -$seek, SEEK_END);
    $data = fread($fp, $seek);
    fclose($fp);
    return strpos($data, "PK\x05\x06") !== false;
}

function download_apk($url, $tmp_file, $down_file)
{
    try {
        $escapedUrl = escapeshellarg($url);
        $escapedFile = escapeshellarg($tmp_file);
        $cmd = "curl -L --fail -o $escapedFile $escapedUrl";
        exec($cmd, $output, $status);
        test_assert($status === 0, '下载异常:' . implode("", $output));
        test_assert(apk_is_structurally_complete($tmp_file), '文件不完整');
        test_assert(rename($tmp_file, $down_file), '移动文件异常');
    } catch (Throwable $e) {
        file_exists($tmp_file) && @unlink($tmp_file);
        file_exists($down_file) && @unlink($down_file);
        throw $e;
    }
}

if (!function_exists('to_timestamp')) {
    function to_timestamp($value)
    {
        if ($value === null || $value === '') {
            return TIMESTAMP;
        }

        if (is_numeric($value)) {
            $ts = (int) $value;

            // 合理性校验（1970-01-01 ~ 2100-01-01）
            if ($ts > 0 && $ts < 4102444800) {
                return $ts;
            }
            return TIMESTAMP;
        }

        return strtotime($value);
    }

}