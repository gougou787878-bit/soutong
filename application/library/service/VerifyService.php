<?php
/**
 *验证码相关g
 *
 */

namespace service;

use tools\CurlService;

/**
 * Class VerifyService
 * @package service
 */
class VerifyService
{
    const VERIFY_CODE = 666;
    const VERIFY_CODE_TEXT = '图形验证码失败';
    public function verifyUrl($aff): string
    {
        return sprintf(config('captcha.url'), SYSTEM_ID, (string)$aff);
    }

    public function verifyCheck($aff, $verifyCode): bool
    {
        if (empty($verifyCode)) {
            return false;
        }
        $data = [
            'app_name' => SYSTEM_ID,
            'aff'      => $aff,
            'code'     => $verifyCode,
        ];
        try {
            $result = (new CurlService())->curlPost(config('captcha.check_url'), $data, 5);
            return strcasecmp($result, 'SUCCESS') === 0;
        } catch (\Exception $e) {
            return false;
        }
    }
}