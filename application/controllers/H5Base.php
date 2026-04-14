<?php

class H5BaseController extends \Yaf\Controller_Abstract
{
    public function showJson($data, $status = 200, $msg = null)
    {
        $data = [
            'data'   => $data,
            'msg'    => $msg,
            'status' => $status,
        ];
        $response = $this->getResponse();
        $response->setBody(json_encode($data));
        $response->setHeader('content-Type', 'application/json', true);
        return $response;
    }

    public function errorJson($msg, $status = 0, $data = null)
    {
        return $this->showJson($data, $status, $msg);
    }

}