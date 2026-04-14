<?php


namespace traits;


use exception\ExitException;
use Yaf\Response_Abstract;

trait AjaxResponseTrait
{


    /**
     * 返回错误
     * @param $msg
     * @param int $code
     * @param null $data
     * @return bool
     * @author xiongba
     * @date 2019-11-05 20:29:23
     */
    public function ajaxError($msg, $code = -9999, $data = null)
    {
        return $this->ajaxSuccess($data, $code, $msg);
    }


    public function ajaxSuccessMsg($msg, $code = 0, $data = null)
    {
        return $this->ajaxSuccess($data, $code, $msg);
    }


    public function ajaxSuccess($data, $code = 0, $msg = 'ok')
    {

        return $this->_jsonResponse([
            'code' => $code,
            'data' => $data,
            'msg'  => $msg
        ]);
    }

    public function ajaxReturn($data)
    {

        return $this->_jsonResponse($data);
    }

    protected function _jsonResponse($json)
    {
        /** @var Response_Abstract $response */
        $response = $this->getResponse();
        return $response->setBody(json_encode($json));
    }


}