<?php

use Yaf\Request_Abstract;
use Yaf\Response_Abstract;

/**
 * 对所有接口调用进行访问人次的统计
 * Class CountPagePlugin
 * @author xiongba
 * @date 2020-05-14 14:33:45
 */
class CountPagePlugin extends Yaf\Plugin_Abstract
{


    public function postDispatch(Request_Abstract $request, Response_Abstract $response)
    {
       /* $moduleName = strtolower($request->getModuleName());
        if (in_array($moduleName, ['v2', 'api'])) {
            try {
                $controllerObject = register('controller');
                if (!is_object($controllerObject) || !property_exists($controllerObject , 'member')){
                    return;
                }
                $member = register('controller')->member;
                $member = MemberModel::make($member);
                if ($member->uid) {
                    $pageName = $moduleName . ':' . strtolower($request->getControllerName()) . ':' . strtolower($request->getActionName());
                    $key = 'count:page:' . $pageName . ':' . date('Ymd');
                    redis()->sAdd($key, $member->uid);
                    redis()->expireAt($key, 43200 + strtotime(date('Y-m-d 23:59:59')));
                }
            } catch (\Throwable $e) {
            }
        }*/
    }



}