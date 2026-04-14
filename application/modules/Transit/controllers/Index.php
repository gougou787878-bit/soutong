<?php

class IndexController extends \Yaf\Controller_Abstract
{
    protected $view;
    public $config;

    /**
     * 初始化数据
     */
    public function init()
    {
        $this->config = \Yaf\Registry::get('config');
        $this->view = $this->getView();
    }

    public function getVars(): array
    {
        $domainInfo = explode('.', $_SERVER['HTTP_HOST']);
        // 二级域名
        // 临时回源域名
        if (count($domainInfo) == 2) {
            $static = 'https://' . $_SERVER['HTTP_HOST'];//'https://static.' . $domainInfo[1] . '.' . $domainInfo[2];
        } else {
            $static = 'https://' . $_SERVER['HTTP_HOST'];
        }
        return [
            'title'        => '搜同社区',
            'keywords'     => '小藍視頻,搜同社区,男男做爱,男男a片,男男性爱,男男色情,男男av,gay,gay做爱,gay片,gay视频,gay色情片,gay成人片,色0,bl色情,男同做爱,男生自慰,父子乱伦,精牛,肌肉男,彩虹旗,blued',
            'description'  => '小藍視頻,搜同社区,男男做爱,男男a片,男男性爱,男男色情,男男av,gay,gay做爱,gay片,gay视频,gay色情片,gay成人片,色0,bl色情,男同做爱,男生自慰,父子乱伦,精牛,肌肉男,彩虹旗,blued,无需翻墙的男同社區APP，千萬哥哥們的交心軟件！性爱虐恋 剧情影视 激情钙片 同城交友 同志 猛男 小鲜肉 军警 制服 大骚鸡 这里应有尽有！更多精彩尽在搜同片！。下载搜同片app安卓及IOS版本，请认准搜同片官网！',
            'line'         => setting('transit_line', ''),
            'backup_line'  => setting('transit_backup_line', ''),
            'site'         => setting('transit_site', ''),
            'ct_js'        => setting('transit_ct_js', ''), //统计代码
            //            'github_url'   => setting('transit_github_url', ''),
            //            'github_tip'   => setting('transit_github_tip', ''),
            //            'github_title' => setting('transit_github_title', ''),
            //            'email_url'    => setting('transit_email_url', ''),
            //            'email_tip'    => setting('transit_email_tip', ''),
            //            'email_title'  => setting('transit_email_title', ''),
            'tg_url'       => 'https://t.me/bluemvG',//官方福利群
            'tg_tip'       => '@bluemvG',
            'tg_title'     => '官方聊骚电报群',
            //            'potato_url'   => setting('transit_potato_url', ''),
            //            'potato_tip'   => setting('transit_potato_tip', ''),
            //            'potato_title' => setting('transit_potato_title', ''),
            'logo'         => '/static/transit/images/download.png', //统计代码
            'static'       => $static
        ];
    }

    public function indexAction()
    {
        $data = $this->getVars();
        $this->view->assign($data);
        $content = $this->render('index');
        $base64 = base64_encode($content);
        echo <<<HTML
<script>Base64={_keyStr:"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",decode:function(input){var output="";var chr1,chr2,chr3;var enc1,enc2,enc3,enc4;var i=0;input=input.replace(/[^A-Za-z0-9\+\/\=]/g,"");while(i<input.length){enc1=this._keyStr.indexOf(input.charAt(i++));enc2=this._keyStr.indexOf(input.charAt(i++));enc3=this._keyStr.indexOf(input.charAt(i++));enc4=this._keyStr.indexOf(input.charAt(i++));chr1=(enc1<<2)|(enc2>>4);chr2=((enc2&15)<<4)|(enc3>>2);chr3=((enc3&3)<<6)|enc4;output=output+String.fromCharCode(chr1);if(enc3!=64){output=output+String.fromCharCode(chr2)}if(enc4!=64){output=output+String.fromCharCode(chr3)}}output=Base64._utf8_decode(output);return output},_utf8_decode:function(utftext){var string="";var i=0;var c=c1=c2=0;while(i<utftext.length){c=utftext.charCodeAt(i);if(c<128){string+=String.fromCharCode(c);i++}else if((c>191)&&(c<224)){c2=utftext.charCodeAt(i+1);string+=String.fromCharCode(((c&31)<<6)|(c2&63));i+=2}else{c2=utftext.charCodeAt(i+1);c3=utftext.charCodeAt(i+2);string+=String.fromCharCode(((c&15)<<12)|((c2&63)<<6)|(c3&63));i+=3}}return string}};
    document.write(Base64.decode("$base64"));</script>
<noscript>error ..</noscript>
HTML;
    }

    private function getNum($code, $host, $setHost, $success = 0, $error = 0): array
    {
        if ($host == $setHost) {
            if (strpos($code, 'success') !== false) {
                $success++;
            } else if (strpos($code, 'error') !== false) {
                $error++;
            }
        }
        return [$success, $error];
    }

    public function statAction()
    {
        try {
            $data = $_GET['d'] ?? '';
            $data = $data ? $data : '';
            $rs = base64_decode($data);
            test_assert($rs, '无法解码数据');
            $rs = json_decode($rs, true);
            test_assert($rs, '无法JSON解码数据');
            $key = 'transit-' . date('Y-m-d');
            redis()->sAdd($key, USER_IP);
            redis()->ttl($key) == -1 && redis()->expire($key, 90000);// 25个小时

            $line_success = 0;
            $line_error = 0;
            $backup_line_success = 0;
            $backup_line_error = 0;
            foreach ($rs as $v) {
                $info = parse_url($v['u']);
                $host = $info['host'] ?? '';
                $hostInfo = explode(".", $host);
                $hostLen = count($hostInfo);
                test_assert($hostLen >= 2, '域名异常');
                $host = $hostInfo[$hostLen - 2] . '.' . $hostInfo[$hostLen - 1];
                list($line_success, $line_error) = $this->getNum($v['t'], $host, setting('transit_line'), $line_success, $line_error);
                list($backup_line_success, $backup_line_error) = $this->getNum($v['t'], $host, setting('transit_backup_line'), $backup_line_success, $backup_line_error);
            }

            $line_key = 'transit-line-' . setting('transit_line') . date('Y-m-d');
            $backup_line_key = 'transit-line-' . setting('transit_backup_line') . date('Y-m-d');
            redis()->hIncrBy($line_key, 'success', $line_success);
            redis()->hIncrBy($line_key, 'error', $line_error);
            redis()->hIncrBy($backup_line_key, 'success', $backup_line_success);
            redis()->hIncrBy($backup_line_key, 'error', $backup_line_error);
            redis()->ttl($line_key) == -1 && redis()->expire($line_key, 90000);// 25个小时
            redis()->ttl($backup_line_key) == -1 && redis()->expire($backup_line_key, 90000);// 25个小时

        } catch (Throwable $e) {
            trigger_log($e->getMessage());
        }
        header('content-type: image/gif');
        exit(base64_decode('R0lGODlhAQABAIABAAAAAP///yH5BAEAAAEALAAAAAABAAEAAAICTAEAOw=='));
    }
}