<?php
/**
 * 对象存储 初始化请求
 */

namespace service;

use tools\HttpCurl;

class ObjectR2Service
{

    /**
     * 获取新上传R2-token 桶信息相关 按自然天过期
     *
     * @return bool|mixed|string|null
     */

    static function r2UploadInfo()
    {
        /**
         * //error
         *
         * Array
         * (
         * [code] => 500
         * [status] => fail
         * [msg] => 签名错误
         * [data] => Array
         * (
         * [uploadUrl] =>
         * [UploadName] =>
         * [publicUrl] =>
         * )
         *
         * // yes
         * Array
         * (
         * [code] => 200
         * [status] => success
         * [msg] =>
         * [data] => Array
         * (
         * // 上传mp4的地址
         * [uploadUrl] => https://upload.bf585e4e5275f486fd74a897526751e8.r2.cloudflarestorage.com/c5f510a56da033d4d0e0cd64fe72c00e.mp4?X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Credential=4ba6b0f5ff35dba9d94b65a45f6e75f5%2F20221222%2F%2Fs3%2Faws4_request&X-Amz-Date=20221222T032438Z&X-Amz-Expires=10800&X-Amz-SignedHeaders=host&x-id=PutObject&X-Amz-Signature=06723579f7b14cb9fd87bb9e2001a02788798f5decac5c04d25e6ea76a725246
         * // 上传后的文件名
         * [UploadName] => c5f510a56da033d4d0e0cd64fe72c00e.mp4
         * // 远程地址，需发送到切片服务器
         * [publicUrl] => https://upload.ycomesc.live/c5f510a56da033d4d0e0cd64fe72c00e.mp4
         * )
         *
         * )
         */
        $data = self::_getR2UploadInfo();
        if ($data['code'] == 200) {
            return $data['data'];
        }
        errLog("_getR2UploadInfoError:" . var_export($data, true));
        return [];
    }

    private static function _getR2UploadInfo()
    {
        $signKey = config('r2.key');
        $now = time();
        $data['sign'] = md5("{$now}{$signKey}");
        $data['timestamp'] = $now;
        //sign=9f662e835f545edc9d33d5d5715e48c7&amp;timestamp=1671679333%
        //print_r(http_build_query($data));die;
        $string = str_replace('amp;', '', http_build_query($data));
        $result = null;
        try {
            $result = (new HttpCurl())->get(config('r2.old_url') . '?' . $string);
            $result = json_decode($result, true);
        } catch (\Throwable $e) {
            errLog('_getR2UploadInfo:异常:' . $e->getMessage());
        }
        return $result;

    }
}