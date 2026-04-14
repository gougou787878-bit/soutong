<?php

use service\AppFeedSystemService;
use tools\CurlService;

/**
 * 工单系统远程处理 ，调用逻辑控制
 * @class FeedsyncController
 * @author https://blue.bluemv.info/index.php?m=feedsync&a=index
 */
class FeedsyncController extends SiteController
{

    public $post = null;

    public function init()
    {
        if ($this->getRequest()->isPost()) {
            $data = $_POST;
           // errLog('req:' . var_export($data, 1));
            $requestData = (new AppFeedSystemService())->crypt()->checkInputData($data, false);
           // errLog('req-data:' . var_export($requestData, 1));
            $action = $requestData['action'] ?? '';
            $result = (new AppFeedSystemService())->processData($action, $requestData);
            //errLog('resp-data:'.var_export([$data,$requestData,$result],1));
            echo is_array($result) ? json_encode($result) : $result;
        } else {
            echo 'no access~';
        }
    }

    public function indexAction(){

    }


}