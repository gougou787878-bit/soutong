<?php


namespace exception;


class MsgError extends HttpResponseException
{

    /**
     * ErrorMsg constructor.
     * @param $message
     * @param $dataStatus
     * @param \Throwable|null $previous
     * @author xiongba
     * @date 2019-12-10 14:17:05
     */
    public function __construct($message, $dataStatus, \Throwable $previous = null)
    {
        parent::__construct($message, 200, $dataStatus, $previous);
        $this->data = [
            'status' => $dataStatus,
            'data'   => null,
            'msg'    => $message
        ];
    }


    public static function thrToast($msg)
    {
        throw new self($msg, \Constant::ERROR_MSG_TOAST);
    }

    public static function thrConfirm($msg)
    {
        throw new self($msg, \Constant::ERROR_MSG_CONFIRM);
    }

}