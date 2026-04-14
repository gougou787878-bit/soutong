<?php

use tools\LibYac;
use Tbold\Library\Constant;

function writeLog($msg)
{
    $msg = '[' . date('Y-m-d H:i:s') . ']-' . $msg;
    error_log($msg . PHP_EOL, 3, APP_PATH . '/storage/logs/cache_file.log');
}

// 替换所有域名
function replace_all($data)
{
    $num = date('H') % 5 + 1;
    $site = sprintf("https://w%d.%s", $num, web_site2('xlp'));
    $data = replace_share2($data);

    //图片域名替换
    $cnBase = parse_url(SNS_IMG_WEB_CN, PHP_URL_HOST);
    $url_replace['images.91tv.tv'] = $cnBase;
    $url_replace['imgpublic.ycomesc.com'] = $cnBase;
    $url_replace['imgpublic.ycomesc.live'] = $cnBase;
    $data = str_ireplace(array_keys($url_replace), array_values($url_replace), $data);

    $data = str_replace('WEB_APP_SITE', $site, $data);
    if (false === strpos($data, ".m3u8")) {
        return $data;
    }

    return preg_replace_callback('#"([^"]+\.m3u8)\?t=(\d+)"#Ui', function ($ary) {
        $url = $ary[1];
        $t = $ary[2];
        return '"' . url_pc_video($url, $t, '3') . '"';
    }, $data);
}

function writeStatic($path, $content)
{
    $file = STATIC_PATH . "/$path";
    $dirname = dirname($file);
    if (!is_dir($dirname)) {
        @mkdir($dirname, 0755, true);
    }
    file_put_contents($file, $content);
}

function __client_ip()
{
    if (PHP_SAPI == "cli") {
        return '127.0.0.1';
    }
    if (isset($_SERVER['HTTP_CLOUDFRONT_VIEWER_ADDRESS'])) {
        $ip = $_SERVER['HTTP_CLOUDFRONT_VIEWER_ADDRESS'];
    } else if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
    } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $xForwardedForArray = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = $xForwardedForArray[0];
    } elseif (isset($_SERVER['X-REAL-IP'])) {
        $ip = $_SERVER['X-REAL-IP'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

function url_pc_video($path, $t1 = '0', $v = '3'): string
{
    $hlsKey = 'RnOxyCIc5eDPFpJY';
    $hlsUrl = SNS_VIDEO_WEB_CN;
    $path = parse_url($path, PHP_URL_PATH);
    $timeNow = time();
    $rand = uniqid();
    $uid = '0';
    if ($v == '1') {
        $data = sprintf("%s-%s-%s-%s-%s-%s", $path, $timeNow, $rand, $uid, __client_ip(), $hlsKey);
    } elseif ($v == '2') {
        $data = sprintf("%s-%s-%s-%s-%s", $path, $timeNow, $rand, $uid, $hlsKey);
    } elseif ($v == '3') {
        $uid = $t1;
        $data = sprintf("%s-%s-%s-%s-%s", $path, $timeNow, $rand, $uid, $hlsKey);
    } else {
        $data = sprintf("%s-%s-%s-%s-%s-%s", $path, $timeNow, $rand, $uid, $_SERVER['HTTP_USER_AGENT'] ?? '', $hlsKey);
    }
    $sign = md5($data);
    return trim($hlsUrl, "/") . "$path?auth_key=$timeNow-$rand-$uid-$sign&v=$v&time=$t1&via=ap";
}

function read_file($file)
{
    @header('Content-Type: application/json');
    $val = yac2()->get($file);
    if (empty($val)) {
        $val = 0;
    }
    yac2()->set($file, ++$val, 60);
    if ($val < 60) {
        header("file-status: hit");
        $html = file_get_contents($file);
    } else {
        header("yac-status: hit");
        $html = yac2()->get("{$file}.c");
        if (empty($html)) {
            $html = file_get_contents($file);
            yac2()->set("{$file}.c", $html, 87600);
        }
    }
    $html = replace_all($html);
    $crypt = new LibCryptPwa();
    $html = $crypt->replyData(json_decode($html, true));
    exit($html);
}

function clear_yac()
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1/m.php?_yac=_reload');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Host: www.anwang.com"]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $rsp = curl_exec($ch);
    curl_close($ch);
    return $rsp;
}

function yac2(): LibYac
{
    static $yac = null;
    if ($yac === null) {
        $yac = new LibYac();
    }
    return $yac;
}

function pageLimit(int $defaultLimit = 20, string $pageName = 'page', string $limitName = 'limit'): array
{
    $data = $_POST;
    //当前多少页
    $page = $_POST[$pageName] ?? 1;
    $page = $page <= 1 ? 1 : $page;
    //每页限时
    $limit = (int)($_POST[$limitName] ?? $defaultLimit);
    if ($limit > 50) {
        $limit = 50;
    }
    if ($limit < 1) {
        $limit = 1;
    }
    $last_ix = $data['last_ix'] ?? null;
    if (empty($last_ix)) {
        $last_ix = null;
    }
    return [$page, $limit, $last_ix];
}

function filecached2(): \Tbold\Library\FileCache
{
    static $object = null;
    if ($object === null) {
        $config = function_exists('opcache_get_status') ? \opcache_get_status() : [];
        $enableOpcache = $config['opcache_enabled'] ?? false;
        $path = APP_PATH . '/storage/cached';
        $object = new Tbold\Library\FileCache($path, $enableOpcache);
    }
    return $object;
}

function replace_share2($text)
{
    static $data = null;
    if ($data === null) {
        $cache = Constant::redis();
        if (empty($cache)) {
            $cache = filecached2();
        }
        try {
            $data = $cache->get("vv-1-old-config");
            $data = unserialize($data);
        } catch (\Throwable $e) {
            $data = [];
        }
        $expired = $data['__expired__'] ?? 0;
        if ($expired < time()) {
            $write = function () use ($cache) {
                $url = 'https://config.microservices.vip/2020090623125271421-share.json';
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_TIMEOUT, 2);
                curl_setopt($curl, CURLOPT_URL, $url);
                $content = curl_exec($curl);
                curl_close($curl);
                if (empty($content)) {
                    throw new RuntimeException('操作失败');
                }
                $data = json_decode($content, true);
                if (empty($data)) {
                    throw new RuntimeException('操作失败');
                }
                $data['__expired__'] = time() + 3600;
                $cache->set('vv-1-old-config', serialize($data), 86400);
                return $data;
            };
            try {
                if ($expired === 0) {
                    $data = $write();
                } else {
                    register_shutdown_function(function () use ($write) {
                        try {
                            $write();
                        } catch (\Throwable $e) {
                        };
                    });
                }
            } catch (\Throwable $e) {
                $data = [];
            }
        }
    }
    if (empty($data)) {
        return $text;
    }
    return str_replace(array_keys($data), array_values($data), $text);
}

/**
 * 避免变量污染，只用自动执行函数
 */
(function () {
    if (defined('TB_IMG_PWA_CN')
        && defined('TB_IMG_PWA_US')
        && defined('TB_VIDEO_APP_CN')
        && defined('TB_SVGA_APP_CN')
        && defined('TB_SVGA_APP_US')
        && defined('TB_VIDEO_PWA_CN_10S')
        && defined('TB_IMG_PWA_US')
        && defined('TB_IMG_ADM_US')
    ) {
        return;
    }
    $ary = null;
    try {
        if (!function_exists('_array_map_deep')) {
            function _array_map_deep($fn, $array)
            {
                foreach ($array as $k => $item) {
                    if (is_array($item)) {
                        $array[$k] = _array_map_deep($fn, $item);
                    } else {
                        $array[$k] = $fn($item);
                    }
                }
                return $array;
            }
        }

        $fn = function () {
            $url = 'https://config.microservices.vip/2020090623125271421.txxxx';
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_TIMEOUT, 3);
            curl_setopt($curl, CURLOPT_URL, $url);
            $json = curl_exec($curl);
            curl_close($curl);
            $local = __DIR__ . '/local.json';
            if (empty($json)) {
                if (!file_exists($local)) {
                    throw new RuntimeException('获取失败');
                } else {
                    $json = file_get_contents($local);
                }
            } else {
                if (is_writable($local)) {
                    file_put_contents($local, $json);
                } else {
                    trigger_error("文件[$local]没有权限写入", E_USER_NOTICE);
                }
            }
            return _array_map_deep('trim', json_decode($json, true));
        };
        $ary = filecached2()->fetch('config_center', $fn, 600);
    } catch (\Throwable $e) {
    }
    if (empty($ary) || !is_array($ary)) {
        $ary = [];
    }

    defined('TB_IMG_PWA_CN') or define("TB_IMG_PWA_CN", $ary['img.pwa_cn'] ?? ''); //国内pwa的图片域名
    defined('TB_IMG_APP_CN') or define("TB_IMG_APP_CN", $ary['img.app_cn'] ?? ''); //	国内app的图片域名
    defined('TB_IMG_PWA_US') or define("TB_IMG_PWA_US", $ary['img.pwa_us'] ?? ''); //国外pwa的图片域名
    defined('TB_IMG_APP_US') or define("TB_IMG_APP_US", $ary['img.app_us'] ?? ''); //国外app的图片域名
    defined('TB_IMG_ADM_US') or define("TB_IMG_ADM_US", $ary['img.adm_us'] ?? ''); //后台的图片地址
    defined('TB_VIDEO_PWA_CN') or define("TB_VIDEO_PWA_CN", $ary['video.pwa_cn'] ?? ''); //国内pwa的视频域名
    defined('TB_VIDEO_PWA_US') or define("TB_VIDEO_PWA_US", $ary['video.pwa_us'] ?? ''); //国外pwa的视频域名
    defined('TB_VIDEO_APP_CN') or define("TB_VIDEO_APP_CN", $ary['video.app_cn'] ?? ''); //国内app的视频域名
    defined('TB_VIDEO_APP_US') or define("TB_VIDEO_APP_US", $ary['video.app_us'] ?? ''); //国外app的视频域名
    defined('TB_VIDEO_ADM_US') or define("TB_VIDEO_ADM_US", $ary['video.adm_us'] ?? ''); //后台的视频域名
    defined('TB_APP_DOWN_URL') or define("TB_APP_DOWN_URL", $ary['app.down_url'] ?? ''); //app的下载域名
    defined('TB_SVGA_APP_CN') or define("TB_SVGA_APP_CN", $ary['svga.app_cn'] ?? ''); // svga的国内APP域名
    defined('TB_SVGA_APP_US') or define("TB_SVGA_APP_US", $ary['svga.app_us'] ?? ''); // svga的国外APP域名

    defined('TB_SHORT_VIDEO_PWA_CN') or define("TB_SHORT_VIDEO_PWA_CN", $ary['video.pwa_cn_short'] ?? ''); //国内pwa的视频域名 预览视频
    defined('TB_SHORT_VIDEO_PWA_US') or define("TB_SHORT_VIDEO_PWA_US", $ary['video.pwa_us_short'] ?? ''); //国外pwa的视频域名 预览视频
    defined('TB_SHORT_VIDEO_APP_CN') or define("TB_SHORT_VIDEO_APP_CN", $ary['video.app_cn_short'] ?? ''); //国内app的视频域名 预览视频
    defined('TB_SHORT_VIDEO_APP_US') or define("TB_SHORT_VIDEO_APP_US", $ary['video.app_us_short'] ?? ''); //国外app的视频域名 预览视频

    defined('TB_VIDEO_PWA_CN_10S') or define("TB_VIDEO_PWA_CN_10S", $ary['video.pwa_cn_10s'] ?? ''); //国内pwa的视频域名 10秒预览
    defined('TB_VIDEO_PWA_US_10S') or define("TB_VIDEO_PWA_US_10S", $ary['video.pwa_us_10s'] ?? ''); //国外pwa的视频域名 10秒预览
    defined('TB_VIDEO_PWA_CN_120S') or define("TB_VIDEO_PWA_CN_120S", $ary['video.pwa_cn_120s'] ?? ''); //国内pwa的视频域名 120秒预览
    defined('TB_VIDEO_PWA_US_120S') or define("TB_VIDEO_PWA_US_120S", $ary['video.pwa_us_120s'] ?? ''); //国外pwa的视频域名 120秒预览

    defined('TB_VIDEO_APP_CN_10S') or define("TB_VIDEO_APP_CN_10S", $ary['video.app_cn_10s'] ?? ''); //国内pwa的视频域名 10秒预览
    defined('TB_VIDEO_APP_US_10S') or define("TB_VIDEO_APP_US_10S", $ary['video.app_us_10s'] ?? ''); //国外pwa的视频域名 10秒预览
    defined('TB_VIDEO_APP_CN_120S') or define("TB_VIDEO_APP_CN_120S", $ary['video.app_cn_120s'] ?? ''); //国内pwa的视频域名 120秒预览
    defined('TB_VIDEO_APP_US_120S') or define("TB_VIDEO_APP_US_120S", $ary['video.app_us_120s'] ?? ''); //国外pwa的视频域名 120秒预览

    defined('TB_WEB_OSS_CN') or define("TB_WEB_OSS_CN", $ary['web.oss_cn'] ?? ''); //官网 国内
    defined('TB_WEB_OSS_US') or define("TB_WEB_OSS_US", $ary['web.oss_us'] ?? ''); //官网 国外

    defined('TB_CHECK_VIDEO') or define("TB_CHECK_VIDEO", $ary['video.adm_check'] ?? 'https://play.xmyy8.co'); //后台待审核的播放地址

    defined('TB_IMG_UPLOAD') or define("TB_UPLOAD_IMG", $ary['img.upload'] ?? ''); //图片上传
    defined('TB_VIDEO_UPLOAD') or define("TB_UPLOAD_VIDEO", $ary['video.upload'] ?? ''); //视频上传
    defined('TB_VIDEO_DESTROY') or define("TB_VIDEO_DESTROY", $ary['video.destroy'] ?? ''); //释放视频（删除）
    defined('TB_VIDEO_SLICE') or define("TB_VIDEO_SLICE", $ary['video.slice'] ?? ''); //视频切片

    defined('SNS_IMG_WEB_CN') or define("SNS_IMG_WEB_CN", $ary['sns_img.web_cn'] ?? ''); // sns 域名配置
    defined('SNS_IMG_PWA_CN') or define("SNS_IMG_PWA_CN", $ary['sns_img.pwa_cn'] ?? ''); // sns 域名配置
    defined('SNS_IMG_APP_CN') or define("SNS_IMG_APP_CN", $ary['sns_img.app_cn'] ?? ''); // sns 域名配置
    defined('SNS_IMG_WEB_US') or define("SNS_IMG_WEB_US", $ary['sns_img.web_us'] ?? ''); // sns 域名配置
    defined('SNS_IMG_PWA_US') or define("SNS_IMG_PWA_US", $ary['sns_img.pwa_us'] ?? ''); // sns 域名配置
    defined('SNS_IMG_APP_US') or define("SNS_IMG_APP_US", $ary['sns_img.app_us'] ?? ''); // sns 域名配置

    defined('SNS_VIDEO_WEB_CN') or define("SNS_VIDEO_WEB_CN", $ary['sns_video.web_cn'] ?? ''); // sns 域名配置
    defined('SNS_VIDEO_PWA_CN') or define("SNS_VIDEO_PWA_CN", $ary['sns_video.pwa_cn'] ?? ''); // sns 域名配置
    defined('SNS_VIDEO_APP_CN') or define("SNS_VIDEO_APP_CN", $ary['sns_video.app_cn'] ?? ''); // sns 域名配置
    defined('SNS_VIDEO_WEB_US') or define("SNS_VIDEO_WEB_US", $ary['sns_video.web_us'] ?? ''); // sns 域名配置
    defined('SNS_VIDEO_PWA_US') or define("SNS_VIDEO_PWA_US", $ary['sns_video.pwa_us'] ?? ''); // sns 域名配置
    defined('SNS_VIDEO_APP_US') or define("SNS_VIDEO_APP_US", $ary['sns_video.app_us'] ?? ''); // sns 域名配置
    defined('TB_PWA_SITE') or define("TB_PWA_SITE", $ary['pwa_site'] ?? []);
    defined('TB_WEB_SITE') or define("TB_WEB_SITE", $ary['web_site'] ?? []);
    defined('TB_GLOBALS') or define('TB_GLOBALS', $ary);

})();

function web_site2($key)
{
    return TB_WEB_SITE[$key] ?? null;
}

function trigger_json2($msg)
{
    $msg = sprintf('[%s]-数据:' . PHP_EOL . '%s' . PHP_EOL, date('Y-m-d H:i:s', time()), json_encode($msg, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    error_log($msg . PHP_EOL, 3, '../storage/logs/log.log');
}

const STATIC_PATH = APP_PATH . "/storage/html";