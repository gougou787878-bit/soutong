<?php

$_SERVER['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'] ?? 'http-client';
define('MODULE_NAME', '');
define('MODULE_NAME_TEST', '');
define('appId', '');
define('appKey', 'kihfks3kjdhfksjh3kdjfs745dkslfh4');
define('encryptKey', 'ljhlksslgkjfhlksuo8472rju6p2od03');
$global = [
    'SystemVersion' => 13.3,
    'bundleId'      => 'com.kaiyuan.gv',
    'version'       => '1.0.3',
    'channel'       => '',
    'via'           => '',
    'theme'         => 'AoLiGeiF',
    'build_id'      => 108,
    'Language'      => 2,
    'deviceModel'   => 'iPhone12,5',
    'bundle_id'     => 'com.AoLiGeiF.www',
    'oauth_type'    => 'android',
    'deviceBrand'   => 'iPhone 11 Pro Max',
    'app_type'      => 'flt',
//    'oauth_id'      => '38a4ccf9261e65aba7b44df48fa95fb1',
    'oauth_id'      => 'b0adeae89c031f8a3ff697ec16daa33a',
];
//$input = file_get_contents("php://input");
//$a = json_decode($input, 1);
//
//if (is_array($a)) {
//    $_POST = $a;
//}


define('IN_APP', true);
define('RELATIVE_ROOT_PATH', './');
define('MAGIC_QUOTES_GPC', get_magic_quotes_gpc());
ini_set('magic_quotes_runtime', 0);
ini_set('arg_separator.output', '&amp;');
define("APP_PATH", realpath(dirname(__FILE__) . '/../')); // public 上级目录
date_default_timezone_set('Asia/Shanghai');
define('APP_ENVIRON', ini_get("yaf.environ"));
header("Content-Type: text/html; charset=utf-8");
header('P3P: CP="CAO PSA OUR"');
header('Access-Control-Allow-Origin: *');
$app = new Yaf\Application(APP_PATH . "/conf/app.ini");
$app->bootstrap();

parse_input_post();

switch ($_POST['env'] ?? '') {
    case 'prod':
        define('SERVER_IP', '45.118.132.110');
        define('SERVER_URL', 'http://45.118.132.110');
        define('SERVER_HOST', '45.118.132.110');
        break;
    case 'test':
        define('SERVER_IP', '139.162.66.189');
        define('SERVER_URL', 'http://139.162.66.189');
        define('SERVER_HOST', 'gv.hyys.info');
        break;
    default:
        define('SERVER_IP', '172.18.0.6');
        define('SERVER_URL', 'http://stapi.com');
        define('SERVER_HOST', 'stapi.com');
        break;
}

$uri = str_replace(basename(__FILE__), "/api.php", ltrim($_SERVER['REQUEST_URI'], '/'));

$url = SERVER_URL . $uri;

$_POST = array_merge($global, $_POST);

$res = (new bbsdk())->api($url, $_POST);
if (is_string($res)) {
    echo $res;
} else {
    header('Content-type: application/json');
    //var_export($res);
    echo json_encode($res);
}


/**
 * Class  数据交互加密与签名机制
 * Date 20170805
 */
class bbsdk
{

    const DEBUG = true;

    const appId = appId;//商户id
    const appKey = appKey;//数据签名key

    const encryptKey = encryptKey;//数据加密key st


    #使用post的传输
    public function api($url, $post)
    {
        #必填参数
        if (!is_array($post)) {
            return;
        }
        $data = array(
            'appId' => self::appId,
            'timestamp' => time(),
        );
        $data['data'] = $post;

        if ($data) {
            $this->debug('接口调用：' . $url, http_build_query($data));
        } else {
            $this->debug('接口调用：' . $url, '');
        }

        $this->debug('加密前字符串', print_r($data, true));
        $data['data'] = $this->encrypt(json_encode($post));
        $data['sign'] = $this->make_sign($data);
        $this->debug('加密后字符串', print_r($data, true));

        $result = $this->CURL($url, $data);
        $this->debug('result--', print_r($result, true));
        if ($result != '') {
            //验证返回数据的签名是否合法 20180805
            if (!$this->check_sign($result)) {
                $this->debug('返回数据签名验证失败', print_r($result, true));
                return $result;
            }
            if (isset($result['data'])) {
                $this->debug('解密前字符串', $result['data']);
                $result['data'] = $this->decrypt($result['data']);
                $this->debug('解密后字符串', $result['data']);
                $result['data'] = json_decode($result['data'], true);
                if (is_array($result['data'])) {
                    $this->debug('JSON转数组成功', print_r($result['data'], true));
                } else {
                    $this->debug('JSON转数组成功', '失败');
                }
            }
            unset($result['sign']);
        }
        return $result;
    }

    #使用post的传输
    public function CURL($url, $data)
    {

        //启动一个CURL会话
        $ch = curl_init();
        // 设置curl允许执行的最长秒数
        curl_setopt($ch, CURLOPT_TIMEOUT, 30000);
        //忽略证书
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        // 获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $host = parse_url($url, PHP_URL_HOST);
        if (!empty(SERVER_IP)) {
            $url = str_replace($host, SERVER_IP, $url);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        //发送一个常规的POST请求。
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        foreach ($_SERVER as $key => $item) {
            if ($key == 'HTTP_CONTENT_LENGTH' || $key == 'HTTP_HOST') {
                continue;
            }
            if (substr($key, 0, 5) === 'HTTP_') {
                $header[] = sprintf("%s: %s", substr($key, 5), $item);
            }
        }
        $header[] = sprintf("Host: " . SERVER_HOST);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
//        curl_setopt($ch, CURLOPT_HEADER,0);//是否需要头部信息（否）
        // 执行操作
        $result = curl_exec($ch);
//var_dump($result);die;
        $this->debug('接口返回json数据', $result);
        $curlInfo = curl_getinfo($ch);
        $errStr = curl_error($ch);
        curl_close($ch);
        if ($result !== false) {

            #将返回json转换为数组
            $arr_result = json_decode($result, true);
            $error = json_last_error();

            http_response_code($curlInfo['http_code']);

            if ($error === 0 && !is_array($arr_result)) {
                $arr_result['errcode'] = 1;
                $arr_result['msg'] = '服务器返回非数组数据';
                $arr_result['data'] = $result;
                $this->debug('服务器返回数据格式错误', $result);
            } elseif ($error !== 0) {
                return $result;
            }
        } else {
            $arr_result['errcode'] = 1;
            $arr_result['msg'] = '服务器无返回值';
            $this->debug('服务器无响应', $errStr);
        }
        #返回数据
        return $arr_result;
    }

    /*
     * return sign string
     * Date
     * #签名
     */
    public function make_sign($array)
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


        $string = $string . self::appKey;
        #先sha256签名 在md5签名
        $sign_str = md5(hash('sha256', $string));
        return $sign_str;
    }

    #验证签名
    public function check_sign($array)
    {
        if (!isset($array['sign']) || $array['sign'] == '') {
            return false;
        }
        $sign = $array['sign'];
        unset($array['sign']);
        return $this->make_sign($array) == $sign;
    }
    #@aes-256-cfb 加解密
    #加密
    public static function encrypt($input)
    {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cfb'));
        $key_iv = self::EVPBytesToKey(self::encryptKey);
        $data = openssl_encrypt($input, 'aes-256-cfb', $key_iv[0], OPENSSL_RAW_DATA, $iv);
        $data = $iv . $data;
        $data = strtoupper(bin2hex($data));
        return $data;
    }

    //解密
    public static function decrypt($input)
    {
        $input = hex2bin($input);
        $iv = substr($input, 0, 16);
        $input = substr($input, 16);
        $key_iv = self::EVPBytesToKey(self::encryptKey);
        $decrypted = openssl_decrypt($input, 'aes-256-cfb', $key_iv[0], OPENSSL_RAW_DATA, $iv);
        return $decrypted;
    }

    public static function EVPBytesToKey($password, $key_len = '32', $iv_len = '16')
    {
        $cache_key = "$password:$key_len:$iv_len";
        $m = array();
        $i = 0;
        $count = 0;
        while ($count < $key_len + $iv_len) {
            $data = $password;
            if ($i > 0) {
                $data = $m[$i - 1] . $password;
            }
            $d = md5($data, true);
            $m[] = $d;
            $count += strlen($d);
            $i += 1;
        }
        $ms = '';
        foreach ($m as $buf) {
            $ms .= $buf;
        }
        $key = substr($ms, 0, $key_len);
        $iv = substr($ms, $key_len, $key_len + $iv_len);

        return array($key, $iv);
    }

    #日志记录
    public function debug($tempType, $tempStr)
    {
        if (self::DEBUG) {
            $log_name = APP_PATH . '/storage/log.txt';
            $tempStr = date('Y-m-d H:i:s') . ' ' . $tempType . "\r\n" . $tempStr . "\r\n\r\n";
            $myfile = fopen($log_name, "a");
            fwrite($myfile, $tempStr);
            fclose($myfile);
        }
    }
}
