<?php

use Yaf\Controller_Abstract;

class ErrorController extends Controller_Abstract
{
    public function errorAction($exception)
    {
        @header('Content-Type: application/json');
        /** @var \Exception $exception */
        $file = $exception->getFile();
        $code = $exception->getCode();
        $message = $exception->getMessage();
        $line = $exception->getLine();
        if (!($exception instanceof \exception\FrequencyException)) {
            $errStr = '[' . date('Y-m-d h:i:s') . "] \r\n";
            $errStr .= '  错误级别：' . $code . "\r\n";
            $errStr .= '  错误信息：' . $message . "\r\n";
            $errStr .= '  错误文件：' . $file . "\r\n";
            $errStr .= '  错误行数：' . $line . "\r\n";
            $errStr .= "\r\n";
            // error_log — 发送错误信息到某个地方
            $errStr .= "-------------------------------------------------\r\n";
            $errStr .= "\r\n";
            $errStr .= Debug::getHttpContext();
            $errStr .= "\r\n";
            $errStr .= $exception;
            $errStr .= "\r\n-------------------------------------------------\r\n";

            if ($code != 422 and \Yaf\Application::app()->environ() == 'product') {
                $message = '系统错误';
                $code != 516 and error_log($errStr, 3, APP_PATH . '/storage/logs/log.log');
            }
        }
        /* if (!defined('USER_COUNTRY')) {
             define('USER_COUNTRY','US');
         }*/
        // define('USER_COUNTRY','US');
        $toCrypt = false;
        $returnData = [
            'data'   => [],
            'status' => 0,
            'msg'    => $message,
            'crypt'  => $toCrypt,
            //'isVip' => true,
            'isVV'   => true,
        ];

        //if (\Yaf\Application::app()->environ() == 'product') {
        // test
        if (MODULE_NAME_TEST and !isset($_POST['crypt'])) {
            $crypt = APP_TYPE_FLAG ? (new LibCrypt()) : (new LibCryptPwa());
            //$crypt = new LibCrypt();
            $returnData = $crypt->replyData($returnData);
            return $this->getResponse()->setBody($returnData);
        } else {
            return $this->getResponse()->setBody(json_encode($returnData, JSON_UNESCAPED_UNICODE));
        }
    }
}