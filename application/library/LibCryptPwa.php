<?php

/*
 * pwa/h5/web 签名与数据加密机制
 */

class LibCryptPwa
{
    public $app_id = '68ccdfadf6ffddcgbsd2dgc9077f8c62';
    public $encryptKey;
    public $appKey;
    public $tokenKey;
    public $channel_id;
    private static $debug;
    const APP_KEY = 'cc88ddc9357ff461e08f047aedee692b';
    const ENCRYPT_KEY = 'e89225cfbbimgkcu';
    const PC_TOKEN_KEY = 'z2iTT2nx4oXxEk60yUWKCT3wUu9CNh0j';

    public function __construct($appId = '', $debug = false)
    {
        self::$debug = $debug;
        $this->appKey = self::APP_KEY;//数据签名key
        $this->encryptKey = self::ENCRYPT_KEY;//数据加密key
        $this->tokenKey = self::PC_TOKEN_KEY;//token key
        $this->channel_id = "123";//渠道ID
        $appId && $this->app_id = $appId;
    }

    #签名
    public static function make_sign($array, $signKey)
    {
        if (empty($array)) {
            return '';
        }
        ksort($array);
        //$string = http_build_query($array);
        $arr_temp = array();
        foreach ($array as $key => $val) {
            $arr_temp[] = $key . '=' . $val;
        }
        $string = implode('&', $arr_temp);
        $string = $string . $signKey;
        #先sha256签名 再md5签名
        $sign_str = md5(hash('sha256', $string));
        return $sign_str;
    }

    #验证
    public static function check_sign($array, $signKey)
    {
        if (!isset($array['sign']) || $array['sign'] == '') {
            return false;
        }
        $sign = $array['sign'];
        unset($array['sign']);
        $msg = "我方计算签名，{$signKey} # " . self::make_sign($array, $signKey);
        //   echo "对方签名，$sign";
        // echo  "我方计算签名，".self::make_sign($array,$signKey);
        self::$debug && trigger_error($msg, E_USER_NOTICE);
        self::$debug && trigger_error("对方签名，$sign", E_USER_NOTICE);
        return self::make_sign($array, $signKey) === $sign;
    }
    #@todo AES加解密
    #加密
    public static function encrypt($input, $cryptKey)
    {
        $data = openssl_encrypt($input, 'aes-256-cfb', self::APP_KEY, OPENSSL_RAW_DATA, self::ENCRYPT_KEY);
        $data = strtoupper(bin2hex($data));
        return $data;
    }

    //解密
    public static function decrypt($input, $cryptKey)
    {
        $input = hex2bin($input);
        $decrypted = openssl_decrypt($input, 'aes-256-cfb', self::APP_KEY, OPENSSL_RAW_DATA, self::ENCRYPT_KEY);
        return $decrypted;
    }

    public function replyData($data = '', $errcode = 0)
    {
        $return['errcode'] = (int)$errcode;
        $return['timestamp'] = time();
        $return['data'] = $data;
        $msg = "我方，返回数据：" . print_r($return, true);
        self::$debug && trigger_error($msg, E_USER_NOTICE);
        if (!empty($return['data'])) {
            // $return['data'] = self::encrypt(json_encode($return['data'],JSON_UNESCAPED_UNICODE),$this->encryptKey);
            $return['data'] = self::encrypt(json_encode($return['data'], JSON_UNESCAPED_UNICODE), $this->encryptKey);
            $return['sign'] = self::make_sign($return, $this->appKey);
            // return json_encode($return,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            return json_encode($return, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return '';
    }

    /*
     *
     * 验证第三方过来的数据是否合法，以及解密
     */
    public function checkInputData($data)
    {
        self::$debug && trigger_error("我方收到未验证的数据：" . print_r($data, true), E_USER_NOTICE);
        if (!self::check_sign($data, $this->appKey)) {
            self::$debug && trigger_error("我方收到，数据--签名验证失败", E_USER_NOTICE);
            return false;
        }
        $json = self::decrypt($data['data'], $this->encryptKey);
        self::$debug && trigger_error("我方收到，解密后的 json 数据--\n" . print_r($json, true), E_USER_NOTICE);
        $tmpData = json_decode($json, true);
        self::$debug && trigger_error("我方收到，解密后的 json_decode 数据--\n" . print_r($tmpData, true), E_USER_NOTICE);
        return $tmpData;
    }

    /**
     * @description pc token加密
     * @param $aff
     * @param $oauthId
     * @param $oauthType
     * @return string
     * @throws RedisException
     */
    public function encryptToken($aff, $oauthId, $oauthType): string
    {
        $token = self::encrypt(serialize([$aff, $oauthId, $oauthType, time()]),  $this->tokenKey);
        redis()->hSet('user:token', $aff, $token);

        return $token;
    }

    /**
     * @description PC token解密
     * @param $token
     * @return false|mixed
     */
    public function decryptToken($token)
    {
        if (empty($token) || !isset($token[10])){
            return false;
        }
        try{
            $tokenInfo = self::decrypt($token, $this->tokenKey);
            if (empty($tokenInfo)){
                return false;
            }
            list($aff , $oauthId , $oauthType) = $ary = unserialize($tokenInfo);
            $existToken = redis()->hGet('user:token', $aff);
            if ($token != $existToken) {
                return false;
            }
            return $ary;
        }catch (\Throwable $e){
            return false;
        }
    }
}
