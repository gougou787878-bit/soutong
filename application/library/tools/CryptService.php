<?php
namespace tools;
class CryptService
{
    const DEBUG     = FALSE;
    const open_url  = '';//外网测试地址
    const appId     = '68ccdfadf6ffddcgbsd2dgc9077f8c62';//appId
    const appKey    = '132f1537f85scxpcm59f7e318b9epa51';//数据签名key
    const encryptKey= 'e79465cfbb39ckcusimcuekd3b066a6e';//数据加密key




    #使用post的传输
    public function api($url,$post){
        #必填参数
        if(!is_array($post))    return;
        $data = array(
            'appId'     => self::appId,
            'ts'        => time(),
        );
        $data['data']= $post;

        if($data){$this->debug('接口调用：'.$url,http_build_query($data));}else{$this->debug('接口调用：'.$url,'');}

        $this->debug('加密前字符串',print_r($data,true));
        $data['data'] = $this->encrypt(json_encode($post));
        $data['sign'] = $this->make_sign($data);
        $this->debug('加密后字符串',print_r($data,true));
        $result = $this->CURL($url,$data);
        $this->debug('result--',print_r($result,true));
        if($result!=''){
            //验证返回数据的签名是否合法 20180805
            if(!$this->check_sign($result)){
                $this->debug('返回数据签名验证失败',print_r($result,true));
                return $result;
            }
            if(isset($result['data'])){
                $this->debug('解密前字符串',$result['data']);
                $result['data']=$this->decrypt($result['data']);
                $this->debug('解密后字符串',$result['data']);
                $result['data']=json_decode($result['data'],true);
                if(is_array($result['data'])){$this->debug('JSON转数组成功',print_r($result['data'],true));}else{$this->debug('JSON转数组成功','失败');}
            }
            unset($result['sign']);
        }
        return $result;
    }

    #使用post的传输
    public function CURL($url,$data){
        //启动一个CURL会话
        $ch = curl_init();
        // 设置curl允许执行的最长秒数
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        //忽略证书
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
        // 获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_URL,self::open_url.$url);
        //发送一个常规的POST请求。
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
        curl_setopt($ch, CURLOPT_HEADER,0);//是否需要头部信息（否）
        // 执行操作
        $result = curl_exec($ch);

        $this->debug('接口返回json数据',$result);

        if($result){
            curl_close($ch);
            #将返回json转换为数组
            $arr_result=json_decode($result,true);
            if(!is_array($arr_result)){
                $arr_result['errcode']=1;
                $arr_result['msg']='服务器返回非数组数据';
                $this->debug('服务器返回数据格式错误',$result);
            }
        }else{
            $err_str=curl_error($ch);
            curl_close($ch);
            $arr_result['errcode']=1;
            $arr_result['msg']='服务器无返回值';
            $this->debug('服务器无响应',$err_str);
        }
        #返回数据
        return $arr_result;
    }
    /*
     * return sign string
     * Date 20150805
     * #签名
     */
    public function make_sign($array){
        if(empty($array)) return '';
        ksort($array);
        //$string = http_build_query($array);

        $arr_temp = array ();
        foreach ($array as $key => $val) {
            $arr_temp[]=$key.'='.$val;
        }
        $string = implode('&', $arr_temp);

        $string = $string.self::appKey;
        #先sha256签名 在md5签名
        $sign_str = md5(hash('sha256',$string));
        return $sign_str;
    }
    #验证签名
    public function check_sign($array){
        if(!isset($array['sign']) || $array['sign']==''){
            return false;
        }
        $sign = $array['sign'];
        unset($array['sign']);
        return $this->make_sign($array)==$sign;
    }
    #@aes-256-cfb 加解密
    #加密
    public static function encrypt($input) {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cfb'));
        $key_iv = self::EVPBytesToKey(self::encryptKey);
        $data = openssl_encrypt($input, 'aes-256-cfb',$key_iv[0], OPENSSL_RAW_DATA, $iv);
        $data = $iv.$data;
        $data = strtoupper(bin2hex($data));
        return $data;
    }
    //解密
    public static function decrypt($input) {
        $input   = hex2bin($input);
        $iv     = substr($input,0,16);
        $input   = substr($input,16);
        $key_iv = self::EVPBytesToKey(self::encryptKey);
        $decrypted = openssl_decrypt($input, 'aes-256-cfb',$key_iv[0], OPENSSL_RAW_DATA, $iv);
        return $decrypted;
    }
    public static function EVPBytesToKey($password, $key_len='32', $iv_len='16') {
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

        return array($key,$iv);
    }
    #日志记录
    protected function debug($tempType,$tempStr){
        if(self::DEBUG){
            $log_name = 'log.txt';
            $tempStr=date('Y-m-d H:i:s').' '.$tempType."\r\n".$tempStr."\r\n\r\n";
            $myfile = fopen($log_name, "a");
            fwrite($myfile, $tempStr);
            fclose($myfile);
        }
    }
}
