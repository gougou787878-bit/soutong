<?php
define('IN_APP', true);
define('MODULE_NAME','api');
define('MODULE_NAME_TEST',true);
define('RELATIVE_ROOT_PATH', './');
// define('MAGIC_QUOTES_GPC', get_magic_quotes_gpc());
ini_set('magic_quotes_runtime', 0);
ini_set('arg_separator.output', '&amp;');
define("APP_PATH", realpath(dirname(__FILE__) . '/../')); // public 上级目录
defined('APP_TYPE_FLAG') or define('APP_TYPE_FLAG', 1);//手机app入口
date_default_timezone_set('Asia/Shanghai');
@header("Content-Type: text/html; charset=utf-8");
@header('P3P: CP="CAO PSA OUR"');
@header('Access-Control-Allow-Origin: *');
error_reporting(0);

$app = new Yaf\Application(APP_PATH . "/conf/app.ini");
$app->bootstrap()->run();
