<?php


namespace service;


use tools\CurlService;
use tools\HttpCurl;

class ServerService
{

    public function verifyUrl($aff): string
    {
        return sprintf('https://pay.hyys.info/captcha/%s/%s', SYSTEM_ID, (string)$aff);
    }

    public function verifyCheck($aff, $verifyCode): bool
    {
        if ($verifyCode) {
            return false;
        }
        $data = [
            'app_name' => SYSTEM_ID,
            'aff'      => $aff,
            'code'     => $verifyCode,
        ];
        try {
            $curl = new CurlService();
            $result = $curl->curlPost('https://pay.hyys.info/captcha/check', $data);
            $result = trim($result);
            return strcasecmp($result, 'SUCCESS') === 0;
        } catch (\Exception $e) {
            return false;
        }
    }
}