<?php
define('IN_APP', true);
define('MODULE_NAME','script');
define('MODULE_NAME_TEST',false);
define('RELATIVE_ROOT_PATH', './');
define('MAGIC_QUOTES_GPC', get_magic_quotes_gpc());
ini_set('magic_quotes_runtime', 0);
ini_set('arg_separator.output', '&amp;');
define("APP_PATH", realpath(dirname(__FILE__) . '/../')); // public 上级目录

date_default_timezone_set('Asia/Shanghai');

$app = new Yaf\Application(APP_PATH . "/conf/app.ini");

$app->bootstrap()->run();