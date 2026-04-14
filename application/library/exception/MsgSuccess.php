<?php


namespace exception;


class MsgSuccess extends HttpResponseException
{


    /**
     * SuccessMsg constructor.
     * @param $data
     * @param null $msg
     * @param int $dataStatus
     * @param \Throwable|null $previous
     * @author xiongba
     * @date 2019-12-10 14:17:01
     */
    public function __construct($data, $msg = null, $dataStatus = 200, \Throwable $previous = null)
    {
        parent::__construct(is_string($msg) ? $msg : '', 200, $dataStatus, $previous);
        $this->data = [
            'status' => $dataStatus,
            'data'   => $data,
            'msg'    => $msg
        ];
    }


    public static function thrToast($msg)
    {
        throw new self($msg, null, \Constant::SUCCESS_MSG_TOAST);
    }

    public static function thrConfirm($msg)
    {
        throw new self($msg, null, \Constant::SUCCESS_MSG_CONFIRM);
    }


}