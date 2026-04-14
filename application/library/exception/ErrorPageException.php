<?php


namespace exception;


use Throwable;
use Yaf\Exception;

class ErrorPageException extends Exception
{


    public function __construct($message = "", $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }

}