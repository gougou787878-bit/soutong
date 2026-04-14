<?php

namespace tools;


class CurlService
{
    protected $curlVerbose = false;
    /**
     * post
     * @param $url
     * @param $data
     * @param $header
     * @return bool|mixed|string
     */
    public static function post($url, $data, $header = [],$timeout = 30)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, $url);

        if (!empty($header)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        }
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        $result = curl_exec($curl);

        if (curl_errno($curl)) {
            errLog(curl_error($curl));
            return "false";
        }
        $resultJson = json_decode($result, true);
        return ($resultJson === null) ? $result : $resultJson;
    }

    //*** post
    public function curlPost($url, $params = array() , $timeout = 30)
    { // 模拟提交数据函数
        $post = htmlspecialchars_decode(!empty($params) ? http_build_query($params) : '');
        $ch = curl_init();
        //curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);//可post多维数组

        $result = curl_exec($ch);
        //print_r($result);die;
        /* if($result === false) {
             echo 'Curl error: ' . curl_error($ch);
         }*/
        curl_close($ch);
        return $result;
    }

    public function request($url, $params = array(), $header = array())
    {
        return $this->deleteMp4($url, $params, $header);
    }

    public function deleteMp4($url, $params = array(), $header = array())
    { // 模拟提交数据函数
        /*
         * $header = array (
            "Content-Type:application/json",
            "Content-Type:x-www-form-urlencoded",
            "Content-type: text/xml",
			"Content-Type:multipart/form-data"
        )*/

        //启动一个CURL会话
        $ch = curl_init();
        // 设置curl允许执行的最长秒数
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        //忽略证书
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        // 获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch , CURLOPT_VERBOSE,$this->curlVerbose);
        if (!empty($header)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        //发送一个常规的POST请求。
        $str = is_array($params) ? http_build_query($params) : $params;
        $str = str_replace("amp;", '', $str);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $str);//可post多维数组
        //curl_setopt($ch, CURLOPT_HEADER,0);//是否需要头部信息（否）
        // 执行操作
        $result = curl_exec($ch);
        curl_close($ch);
        #返回数据
        return $result;
    }
    /**
     * 上传图片到图片服务器
     * @param string $uuid
     * @param string $filePath 路径
     * @param string $remoteUrl 服务器上传url地址
     * @return array {code:1,msg:"09159db1a99acb773ecf8490c01973ee.jpeg"}
     * @throws Exception
     */
    public static function uploadMp42Remote($uuid, $filePath, $remoteUrl = null)
    {
        if ($remoteUrl === null) {
            $remoteUrl = config('upload.mp4_upload');
        }
        //$cover = new \CURLFile(realpath($filePath), mime_content_type(realpath($filePath)));
        $cover = curl_file_create(realpath($filePath),mime_content_type(realpath($filePath)),'video');
        //var_dump($cover);die;
        $timestamp = time();
        $data = [
            'uuid'     => $uuid,
            'video'    => $cover,
            'timestamp' => $timestamp,
            'sign' => md5($timestamp . config('upload.mp4_key')),
        ];
        return self::post($remoteUrl,$data,[],1000);
    }
}