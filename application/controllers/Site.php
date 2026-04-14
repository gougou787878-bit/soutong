<?php

/**
 * H5页面父类
 * Class IndexController
 */
class SiteController extends \Yaf\Controller_Abstract
{
    public $logFile = APP_PATH . '/storage/logs/log.log';
    protected $view;
    public $config;
    public $post;
    public function init()
    {

        $this->view = $this->getView();
        $this->post = &$_POST;
        $this->config = \Yaf\Registry::get('config');


    }



    protected function checkLogin()
    {
        $validator = simpleValidate($_REQUEST, [
            'uid' => 'required|integer|min:1',
            'token' => 'required|string'
        ]);

        if (!$validator['success']) {
            $this->show('error', [
                'message' => $validator['message'][0]
            ]);
        }
        $this->view->assign([
            'uid' => $_REQUEST['uid'],
            'token' => $_REQUEST['token'],
        ]);
        /*token 检查
        * $this->show('error',[
           'reason' => '您的登陆状态失效，请重新登陆！'
       ]);*/
    }

    /**
     * 模板输出
     * @param $tpl
     * @param $data
     */
    protected function show($tpl, $data = [])
    {
        $this->setViewpath(__DIR__ . '/../modules/Index/views');
        foreach ($data as $k => $v) {
            $this->view->assign($k, $v);
        }
        
        $this->view->display("{$tpl}.phtml");
        exit();
    }

    /**
     * 返回数据
     * @param $data
     * @param int $status
     * @param string $msg
     * @return bool
     */
    public function showJson($data, $status = 1, $msg = '')
    {
        @header('Content-Type: application/json');
        $toCrypt = USER_COUNTRY == 'CN';
        if ($toCrypt) {
            $data = json_encode($data, JSON_UNESCAPED_UNICODE);
            $data = str_replace(TB_IMG_APP_US, TB_IMG_APP_CN, $data);
            $data = json_decode($data, true);
        }

        $returnData = [
            'data' => $data,
            'status' => $status,
            'msg' => $msg,
            'crypt' => $toCrypt,
            'isVip' => true,
        ];

        if (\Yaf\Application::app()->environ() == 'product') {
            $crypt = new LibCrypt();
            $returnData = $crypt->replyData($returnData);
        }
        return $this->getResponse()->setBody(json_encode($returnData, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 纯json发送
     * @param $data
     * @return mixed
     */
    public function ej($data)
    {
        @header('Content-Type: application/json');
        $toCrypt = USER_COUNTRY == 'CN';
        if ($toCrypt) {
            $data = json_encode($data, JSON_UNESCAPED_UNICODE);
            $data = str_replace(TB_IMG_APP_US, TB_IMG_APP_CN, $data);
            $data = json_decode($data, true);
        }

        $returnData = $data;

        if (\Yaf\Application::app()->environ() == 'product') {
            $crypt = new LibCrypt();
            $returnData = $crypt->replyData($returnData);
        }
        return $this->getResponse()->setBody(json_encode($returnData, JSON_UNESCAPED_UNICODE));
    }

    #签名 对接第三方支付的签名
    public function make_sign_callbak($array, $signKey = '')
    {
        if (empty($array)) {
            return '';
        }

        ksort($array);
        $string = http_build_query($array) . $signKey;
        $string = str_replace('amp;', '', $string);
        return md5($string);
    }

}