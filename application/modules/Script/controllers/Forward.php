<?php

/**
 * 路由映射
 * Class IndexController
 */
class ForwardController extends \Yaf\Controller_Abstract
{


    public function indexAction()
    {
        if (PHP_SAPI == 'cli') {
            if (isset($_SERVER['argv'][1])) {
                $mvc = explode('/', $_SERVER['argv'][1]);
                $module = isset($mvc[0]) ? $mvc[0] : '';
                $controller = isset($mvc[1]) ? $mvc[1] : '';
                $action = isset($mvc[2]) ? $mvc[2] : '';
                $params = isset($_SERVER['argv'][2]) ? $this->convertUr($_SERVER['argv'][2]) : [];
                $this->forward($module, $controller, $action, $params);
            }
        }

    }

    function convertUr($query)
    {
        $queryParts = explode('&', $query);
        $params = array();
        foreach ($queryParts as $param) {
            $item = explode('=', $param);
            $params[$item[0]] = $item[1];
        }
        return $params;
    }
}