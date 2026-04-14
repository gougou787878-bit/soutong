<?php

namespace repositories;

trait StreamRepository
{
    /**
     * 获取推拉流地址
     * @param $host $host 协议，如:http、rtmp
     * @param $stream $stream 流名,如有则包含 .flv、.m3u8
     * @param string|int $type 类型，0表示播流，1表示推流
     * @return mixed|string
     */
    function PrivateKeyA($host, $stream, $type)
    {
        $configPri = getConfigPri();
        $cdn_switch = $configPri['cdn_switch'];
        switch ($cdn_switch) {
            case '1':
                $url = $this->PrivateKey_ali($host, $stream, $type);
                break;
            case '2':
                $url = $this->PrivateKey_tx($host, $stream, $type);
                break;
            case '3':
                $url = $this->PrivateKey_qn($host, $stream, $type);
                break;
            case '4':
                $url = $this->PrivateKey_ws($host, $stream, $type);
                break;
            case '5':
                $url = $this->PrivateKey_wy($host, $stream, $type);
                break;
            case '0':
                $url = $this->PrivateKey_bdy($host, $stream, $type);
                break;
            default:
                $url = '';
                break;
        }

        return $url;
    }


    /**
     * lumeier流体
     * @param $host
     * @param $stream
     * @param $type
     * @return string
     */
    function PrivateKey_bdy($host, $stream, $type)
    {
        $configPri = getConfigPri();
        $push = $configPri['bdy_push'];
        $pull = $configPri['bdy_pull'];

        $filename = "/lumeier/" . $stream;

        if ($type == 1) {
            $url = $push . $filename;
        } else {
            $privateKey = $configPri['bdy_private_key'];
            if (!empty($privateKey)) {
                $url = $pull . $filename;
            } else {
                $url = $pull . $filename;
            }
        }
        return $url;
    }

    /**
     * 阿里云直播A类鉴权
     * @param $host $host 协议，如:http、rtmp
     * @param $stream  流名,如有则包含 .flv、.m3u8
     * @param $type 类型，0表示播流，1表示推流
     * @return string
     */
    function PrivateKey_ali($host, $stream, $type)
    {
        $configPri = getConfigPri();
        $push = $configPri['push_url'];
        $pull = $configPri['pull_url'];
        $key_push = $configPri['auth_key_push'];
        $length_push = $configPri['auth_length_push'];
        $key_pull = $configPri['auth_key_pull'];
        $length_pull = $configPri['auth_length_pull'];

        if ($type == 1) {
            $domain = $host . '://' . $push;
            $time = time() + $length_push;
        } else {
            $domain = $host . '://' . $pull;
            $time = time() + $length_pull;
        }

        $filename = "/5showcam/" . $stream;

        if ($type == 1) {
            if ($key_push != '') {
                $sstring = $filename . "-" . $time . "-0-0-" . $key_push;
                $md5 = md5($sstring);
                $auth_key = "auth_key=" . $time . "-0-0-" . $md5;
            }
            if ($auth_key) {
                $auth_key = '?' . $auth_key;
            }
            $url = $domain . $filename . $auth_key;
        } else {
            if ($key_pull != '') {
                $sstring = $filename . "-" . $time . "-0-0-" . $key_pull;
                $md5 = md5($sstring);
                $auth_key = "auth_key=" . $time . "-0-0-" . $md5;
            }
            if ($auth_key) {
                $auth_key = '?' . $auth_key;
            }
            $url = $domain . $filename . $auth_key;
        }

        return $url;
    }

    /**
     * 腾讯云推拉流地址
     * @param $host 协议，如:http、rtmp
     * @param $stream 流名,如有则包含 .flv、.m3u8
     * @param $type 类型，0表示播流，1表示推流
     * @return string
     */
    function PrivateKey_tx($host, $stream, $type)
    {
        $configpri = getConfigPri();
        $bizid = $configpri['tx_bizid'];
        $push_url_key = $configpri['tx_push_key'];
        $push = $configpri['tx_push'];
        $pull = $configpri['tx_pull'];
        $stream_a = explode('.', $stream);
        $streamKey = $stream_a[0];

        $live_code = $streamKey;
        $now_time = time() + 3 * 60 * 60;
        $txTime = dechex($now_time);

        $txSecret = md5($push_url_key . $live_code . $txTime);
        $safe_url = "&txSecret=" . $txSecret . "&txTime=" . $txTime;

        if ($type == 1) {
            $url = "rtmp://{$push}/live/" . $live_code . "?bizid=" . $bizid . "" . $safe_url;
        } else {
            $url = "http://{$pull}/live/" . $live_code . ".flv";
        }

        return $url;
    }

    /**
     * 七牛云直播
     * @param $host
     * @param $stream
     * @param $type
     * @return mixed
     */
    function PrivateKey_qn($host, $stream, $type)
    {

        $configPri = getConfigPri();
        $ak = $configPri['qn_ak'];
        $sk = $configPri['qn_sk'];
        $hubName = $configPri['qn_hname'];
        $push = $configPri['qn_push'];
        $pull = $configPri['qn_pull'];
        $stream_a = explode('.', $stream);
        $streamKey = $stream_a[0];
        $ext = $stream_a[1];

        if ($type == 1) {
            $time = time() + 60 * 60 * 10;
            //RTMP 推流地址
            $url = \Qiniu\Pili\RTMPPublishURL($push, $hubName, $streamKey, $time, $ak, $sk);
        } else {
            if ($ext == 'flv') {
                $pull = str_replace('pili-live-rtmp', 'pili-live-hdl', $pull);
                //HDL 直播地址
                $url = \Qiniu\Pili\HDLPlayURL($pull, $hubName, $streamKey);
            } else if ($ext == 'm3u8') {
                $pull = str_replace('pili-live-rtmp', 'pili-live-hls', $pull);
                //HLS 直播地址
                $url = \Qiniu\Pili\HLSPlayURL($pull, $hubName, $streamKey);
            } else {
                //RTMP 直播放址
                $url = \Qiniu\Pili\RTMPPlayURL($pull, $hubName, $streamKey);
            }
        }

        return $url;
    }

    /**
     * 网宿推拉流
     * @param $host
     * @param $stream
     * @param $type
     * @return string
     */
    function PrivateKey_ws($host, $stream, $type)
    {
        $configPri = getConfigPri();
        if ($type == 1) {
            $domain = $host . '://' . $configPri['ws_push'];
        } else {
            $domain = $host . '://' . $configPri['ws_pull'];
        }

        $filename = "/" . $configPri['ws_apn'] . "/" . $stream;

        $url = $domain . $filename;

        return $url;
    }

    /**
     * 网易cdn获取拉流地址
     * @param $host
     * @param $stream
     * @param $type
     * @return mixed
     */
    function PrivateKey_wy($host, $stream, $type)
    {
        $configPri = getConfigPri();
        $appkey = $configPri['wy_appkey'];
        $appSecret = $configPri['wy_appsecret'];
        $nonce = rand(1000, 9999);
        $curTime = time();
        $var = $appSecret . $nonce . $curTime;
        $checkSum = sha1($appSecret . $nonce . $curTime);

        $header = array(
            "Content-Type:application/json;charset=utf-8",
            "AppKey:" . $appkey,
            "Nonce:" . $nonce,
            "CurTime:" . $curTime,
            "CheckSum:" . $checkSum,
        );
        if ($type == 1) {
            $url = 'https://vcloud.163.com/app/channel/create';
            $paramarr = array(
                "name" => $stream,
                "type" => 0,
            );
        } else {
            $url = 'https://vcloud.163.com/app/address';
            $paramarr = array(
                "cid" => $stream,
            );
        }
        $paramarr = json_encode($paramarr);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $paramarr);
        $data = curl_exec($curl);
        curl_close($curl);
        $rs = json_decode($data, 1);
        return $rs;
    }

}