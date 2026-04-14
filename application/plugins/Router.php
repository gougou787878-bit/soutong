<?php



class RouterPlugin extends Yaf\Plugin_Abstract
{
    public function routerShutdown(Yaf\Request_Abstract $request, Yaf\Response_Abstract $response)
    {
        $script = str_replace('/', '', $request->getServer()['SCRIPT_NAME']);
        //$controller = "Index";
        //$action = 'index';
        switch ($script) {
            case 'pro.php':
                $module = "Pro";
                break;
            case 'api.php':
            case 'pwa.php':
                $module = $request->getModuleName();
                $uriAry = explode('/', $_SERVER['PATH_INFO'] ?? '');
                $uriAry = array_values(array_filter($uriAry));
                if ('Api' == $request->getModuleName() && count($uriAry) == 4) {
                    $module = ucfirst($uriAry[1] ?? 'Api');
                    $controller = ucfirst($uriAry[2] ?? 'index');
                    $action = $uriAry[3] ?? 'index';
                }

                if ($module == 'V2' && !isset($controller)){
                    $controller = ucfirst($uriAry[1]);
                }

                break;
            case 'm.php':
                $module = $request->getModuleName();
                $uriAry = explode('/', $_SERVER['PATH_INFO'] ?? '');
                $uriAry = array_values(array_filter($uriAry));
                $controller = 'index';
                $action = 'index';
                if ($uriAry) {
                    if ('Wapi' == $request->getModuleName() && count($uriAry) == 3) {
                        $module = ucfirst($uriAry[0] ?? 'Wapi');
                        $controller = ucfirst($uriAry[1] ?? 'index');
                        $action = ucfirst($uriAry[2] ?? 'index');
                    }
                }
                break;
            case 'admin.php':
            case 'd.php':
                $module = "Admin";
                $controller = ucfirst($_GET['mod'] ?? ($_POST['mod'] ?? 'index'));
                $action = $_GET['code'] ?? ($_POST['code'] ?? 'index');
                break;
            case 'index.php':
                $module = "Index";
                $path_info = $_SERVER['PATH_INFO'];
                $path_info = str_replace('/index.php', '', $path_info);
                if (in_array($path_info, ['/customer/user/recharge', '/customer/user/backpack'])){
                    $controller = ucfirst('customer');
                    $action = array_values(array_filter(explode('/', $path_info)))[2];
                }else{
                    $controller = ucfirst($_GET['m'] ?? ($_POST['m'] ?? 'Index'));
                    $action = $_GET['a'] ?? ($_POST['a'] ?? 'index');
                }
                break;
            case 'transit.php':
                $module = "Transit";
                $controller = ucfirst($_GET['m'] ?? ($_POST['m'] ?? 'Index'));
                $action = $_GET['a'] ?? ($_POST['a'] ?? 'index');
                if (!$controller) {
                    $controller = $request->getControllerName();
                    $action = $request->getActionName();
                }
                break;
            default:
                $module = "Index";
        }
        if (isset($module)) {
            $request->setModuleName($module);
        }

        if (isset($controller)) {
            $request->setControllerName($controller);
        }

        if (isset($action)) {
            $request->setActionName($action);
        }
    }

}