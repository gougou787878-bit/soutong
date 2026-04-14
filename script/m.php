<?php

define('IN_APP', true);
define('MODULE_NAME','wapi');
define('MODULE_NAME_TEST',true);
define('RELATIVE_ROOT_PATH', './');
// define('MAGIC_QUOTES_GPC', get_magic_quotes_gpc());
ini_set('magic_quotes_runtime', 0);
ini_set('arg_separator.output', '&amp;');
define("APP_PATH", realpath(dirname(__FILE__) . '/../')); // public 上级目录
defined('APP_TYPE_FLAG') or define('APP_TYPE_FLAG',0);//PC入口
date_default_timezone_set('Asia/Shanghai');
@header("Content-Type: text/html; charset=utf-8");
@header('P3P: CP="CAO PSA OUR"');
@header('Access-Control-Allow-Origin: *');
@header('Access-Control-Allow-Headers: *');
@header('Access-Control-Allow-Methods: GET, POST, PUT,DELETE,OPTIONS,PATCH');
error_reporting(0);

// SEO相关不走接口
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (!in_array($uri, ['/m.php/wapi/seo/robots', '/m.php/wapi/seo/sitemap'])) {
    (function () {
        require APP_PATH . "/application/library/Yac.php";
        require APP_PATH . "/application/library/tools/LibYac.php";
        require APP_PATH . '/vendor/tb-old/library/src/Constant.php';
        require APP_PATH . '/application/library/service/FileService.php';
        require_once APP_PATH . '/vendor/tb-old/library/src/FileCache.php';
        require APP_PATH . '/application/library/function/func.php';
        require APP_PATH . "/application/library/LibCryptPwa.php";
        \service\FileService::readStatic();
    })();
}

define("T_ENV", ini_get('yaf.environ'));
$app = new Yaf\Application(APP_PATH . "/conf/app.ini");
$app->bootstrap()->run();
