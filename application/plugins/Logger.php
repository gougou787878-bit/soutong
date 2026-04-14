<?php

use Yaf\Request_Abstract;
use Yaf\Response_Abstract;


class LoggerPlugin extends Yaf\Plugin_Abstract
{

    /**
     * @var AdminLogModel
     */
    protected $adminLog = null;

    /**
     * @return ManagersModel|object
     * @author xiongba
     * @date 2020-04-16 22:13:11
     */
    protected function fetchMember()
    {
        static $member = null;
        if ($member === null) {
            \Yaf\Session::getInstance();
            if (!empty($_SESSION)) {
                $member = $this->GetMember();
            }
        }
        return $member ?? new stdClass();
    }

    /**
     * 得到用户
     * @return ManagersModel|object
     */
    protected function GetMember()
    {
        return ManagersModel::where(['uid' => $_SESSION['uid'] ?? null])->first();
    }


    public function preDispatch(Request_Abstract $request, Response_Abstract $response)
    {
        if (0 === strcasecmp($request->getModuleName(), 'admin')) {
            $log = $this->logText($request) . ',派发{Dispatch}开始';
            $this->adminLog = AdminLogModel::addOther($member->username ?? '游客',$log);
        }
        //echo 4;
    }

    public function postDispatch(Request_Abstract $request, Response_Abstract $response)
    {
        if (0 === strcasecmp($request->getModuleName(), 'admin')) {
            $this->adminLog->log = $this->logText($request) . ',派发{Dispatch}结束';
            $this->adminLog->save();
        }
        //echo 5;
    }

    protected function logText($request){
        $member = $this->fetchMember();
        if ($this->adminLog){
            $this->adminLog->username = $member->username ?? '-游客-';
        }
        return $logText = sprintf("用户{%s}试图操作:%sController::%s()" , $member->username ?? '-游客-' , $request->getControllerName() , $request->getActionName());
    }


}