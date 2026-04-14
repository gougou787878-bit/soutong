<?php

namespace tools;

/**
 * Class HttpCurl
 * @package tools
 * echo (new HttpCurl())->setParams(['name' => 'dfh', 'age' => 12])->get('http://www.test.com');
 */
class HttpCurl
{
    public $cookie_file = '';
    public $user_agent;
    public $header;

    function __construct()
    {
        /*伪造ip,伪造 X-FORWARDED-FOR 头部*/
        //$this->header		= $this->makeHeader();
        /*模拟浏览器*/
        $this->user_agent = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36";
        //if( file_exists($cookie) )$this->cookie_file	= $cookie;
    }

    public function get($url, $params = array(), $header = array(), $time=10)
    {

        $ch = curl_init();
        // 设置 curl 相应属性
        curl_setopt($ch, CURLOPT_USERAGENT, $this->user_agent);    // 模拟用户使用的浏览器
        if (empty($params)) {
            curl_setopt($ch, CURLOPT_URL, $url);
        } else {
            curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($params));
        }
        if (str_starts_with($url , 'https')){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        if (!empty($header)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $time);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $returnTransfer = curl_exec($ch);
        curl_close($ch);
        return $returnTransfer;
    }

    public function post($url, $params = array(), $header = array(), $htmlProcess = true , $timeout = 30)
    { // 模拟提交数据函数

        /*$header = array (
           "Content-Type:application/json",
          // "Content-Type:x-www-form-urlencoded",
           "Content-type: text/xml",
           "Content-Type:multipart/form-data"
       );*/


        //启动一个CURL会话
        $ch = curl_init();
        // 设置curl允许执行的最长秒数
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        //忽略证书
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        // 获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        if (!empty($header)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        //发送一个常规的POST请求。
        curl_setopt($ch, CURLOPT_POST, 1);
        if ($htmlProcess) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($params) ? html_entity_decode(http_build_query($params)) : $params);//可post多维数组
        } else {

            curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($params) ? http_build_query($params) : $params);//可post多维数组
        }
        //curl_setopt($ch, CURLOPT_HEADER,0);//是否需要头部信息（否）
        // 执行操作
        $result = curl_exec($ch);
        curl_close($ch);
        #返回数据
        return $result;
    }

    public function remoteGet($url, $params = array())
    {

        $ch = curl_init();
        // 设置 curl 相应属性
        curl_setopt($ch, CURLOPT_USERAGENT, $this->user_agent);    // 模拟用户使用的浏览器
        if (empty($params)) {
            curl_setopt($ch, CURLOPT_URL, $url);
        } else {
            curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($params));
        }
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $returnTransfer = curl_exec($ch);
        curl_close($ch);
        return $returnTransfer;
    }
}

?>