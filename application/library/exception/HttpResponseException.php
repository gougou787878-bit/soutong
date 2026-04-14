<?php


namespace exception;


use Throwable;
use Yaf\Exception;

class HttpResponseException extends Exception
{

    protected $data;

    protected $dataStatus;

    /**
     * HttpResponseException constructor.
     * @param $message
     * @param int $httpStatusCode
     * @param int $dataStatus
     * @param Throwable|null $previous
     * @author xiongba
     * @date 2019-12-03 16:04:46
     */
    public function __construct($message, $httpStatusCode, $dataStatus = 0, Throwable $previous = null)
    {
        parent::__construct(is_string($message) ? $message : '', $httpStatusCode, $previous);
        $this->data = $message;
        $this->dataStatus = $dataStatus;
    }


    public function getData()
    {
        return $this->data;
    }


    public function getHttpStatusCode()
    {
        return $this->code;
    }


    public function getDataStatus()
    {
        return $this->dataStatus;
    }

}