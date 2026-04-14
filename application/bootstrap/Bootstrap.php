<?php

use Yaf\Dispatcher;
use Yaf\Application;
use Yaf\Bootstrap_Abstract;
use \Illuminate\Database\Capsule\Manager;
use Yaf\Registry;

class Bootstrap extends Bootstrap_Abstract
{
    private $config;

    public function _initErrors()
    {
        if (Yaf\ENVIRON === 'develop') {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
        }
    }

    public function _initLoader()
    {
    }

    public function _initConfig()
    {
        require APP_PATH . "/vendor/autoload.php";
        $this->config = Application::app()->getConfig();
        Yaf\Registry::set('config', $this->config);
    }

    public function _initOne(Dispatcher $dispatcher)
    {
        Yaf\Loader::import('function/common.php');
        Yaf\Loader::import('function/helper.php');
        Yaf\Loader::import('function/init.php');
        Yaf\Loader::import('function/func.php');
      
        Registry::set('encrypt', new LibCrypt());
        //Registry::set("cache", new LibCache());
        //Registry::set("redis", new LibRedis());
        //$dispatcher->registerPlugin(new InitPlugin);
        $dispatcher->disableView();
    }

    public static function error_handError($errno, $errStr, $errFile, $errLine)
    {
        if ($errno == E_WARNING){
            if (strpos($errFile , 'Traits/Localization.php')){
                return true;
            }
        }
        $error = '[' . date('Y-m-d H:i:s') . ']' . "\r\n";
        $error .= '  错误级别：' . $errno . "\r\n";
        $error .= '  错误信息：' . $errStr . "\r\n";
        $error .= '  错误文件：' . $errFile . "\r\n";
        $error .= '  错误行数：' . $errLine . "\r\n";
        try{
            throw new Exception($errStr , $errno);
        }catch (\Throwable $e){
            $error .= "--------------------------------";
            $error .= "\r\n";
            $error .= Debug::getHttpContext();
            $error .= "\r\n";
            $error .= $e;
            $error .= "--------------------------------\r\n";
        }
        $error .= "\r\n";

        error_log($error, 3, APP_PATH . '/storage/logs/log.log');
        if ($errno == E_NOTICE || $errno == E_USER_NOTICE){
            return true;
        }
        return false;
    }

    public function _initErrorHandle(Dispatcher $dispatcher)
    {
        $dispatcher->setErrorHandler([get_class($this), 'error_handError']);
    }

    public function _initDefaultDbAdapter()
    {
        try {
            $object = new \Yaf\Config\Ini(APP_PATH . '/conf/database.ini',APP_ENVIRON);
            $database = $object->database;
            register('database.conf', $object);
            //$spider_sync = $object->spider_sync;
            if ($object->es) {
                class_alias(tools\Elasticsearch::class, '\LibEs');
                \LibEs::registerConfig([$object->es->toArray()]);
            }
        } catch (\Throwable $e) {
            $object = $this->config;
            $database = $this->config->database;
            //$spider_sync = $this->config->spider_sync;
        }
        Registry::set('database' , $object);
        $database = $database->toArray();
        $read = explode(',' , $database['read']['host']);
        if(count($read) > 1){
            $database['read']['host'] = $read;
        }
        $capsule = new Manager;
        $capsule->addConnection($database);
        //add spider con
        //$capsule->addConnection($spider_sync->toArray(),'spider_sync');
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        class_alias(Manager::class, 'DB');
    }


    //**** 后台路由重写
    public function _initPlugins(Dispatcher $dispatcher)
    {
 

        $dispatcher->registerPlugin(new CountPagePlugin());
      
        if (in_array(MODULE_NAME, ['index', 'admin' , 'api', 'wapi', 'transit'])) {
            $dispatcher->registerPlugin(new RouterPlugin);
            $dispatcher->registerPlugin(new LoggerPlugin);
        }
        if (MODULE_NAME == 'script') {
            $dispatcher->registerPlugin(new ScriptMapPlugin);
        }
    }
    public function _initRoute(Yaf\Dispatcher $dispatcher)
    {
        $dispatcher->catchException(true);
        $router = $dispatcher->getRouter();
        $router->addRoute('channel',
            new Yaf\Route\Rewrite('chan/:chan/:code',[
                'controller'=>'index',
                'action'=>'index',
            ])
        );
        $router->addRoute('invite',
            new Yaf\Route\Rewrite('af/:code',[
                'controller'=>'index',
                'action'=>'index',
            ])
        );

    }

}