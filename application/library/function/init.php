<?php
define('TIMESTAMP', time());  // 全局时间戳
define('USER_IP', client_ip()); // 用户IP
define('IP_LOCATION_KEY', 'ip:pos:' . substr(md5(client_ip()), 0, 16)); // 用户区域redis key
define('LAY_UI_STATIC', '/static/backend/');
define('HT_JE_BEI' , 100);
define('CHAT_SALT','ad!@#$^&*()9527');
defined('SYSTEM_SHARE_LINK') or define('SYSTEM_SHARE_LINK', config('cfg.json'));
defined('APP_ENVIRON') or define('APP_ENVIRON', ini_get("yaf.environ"));
defined('ADMIN_TITLE') or define('ADMIN_TITLE', config('system.cn_name'));
defined('SYSTEM_ID') or define('SYSTEM_ID', config('pay.app_name'));
defined('NOTIFY_BACK_URL') or define('NOTIFY_BACK_URL', config('pay.notify'));
defined('SYNC_GTV_URL') or define('SYNC_GTV_URL', 'https://app.gtav.info/index.php?&m=mv&a=sync');//同步GTV请求地址
defined('SYNC_BLUED_URL') or define('SYNC_BLUED_URL', 'http://blued.we-cname.com/index.php?m=mv&a=syncBlueData');//同步BLUED请求地址
defined('SYSTEM_NOTIFY_SLICE_URL') or define('SYSTEM_NOTIFY_SLICE_URL', NOTIFY_BACK_URL . '/index.php?&m=mv&a=index');
defined('SYSTEM_NOTIFY_SLICE_POST_URL') or define('SYSTEM_NOTIFY_SLICE_POST_URL', NOTIFY_BACK_URL . '/index.php?m=mv&a=post_media');//社区资源切片回调
defined('SYSTEM_NOTIFY_SLICE_GIRL_URL') or define('SYSTEM_NOTIFY_SLICE_GIRL_URL', NOTIFY_BACK_URL . '/index.php?m=mv&a=girl_media');//约炮资源切片回调
defined('SYSTEM_NOTIFY_WITHDRAW_URL') or define('SYSTEM_NOTIFY_WITHDRAW_URL', NOTIFY_BACK_URL . '/index.php?&m=pay&a=notifywithraw');
define('ILLEGAL_ORG_VIDEO', '/play/429aea396f5c5fc46a9e8d577050e01b/429aea396f5c5fc46a9e8d577050e01b.m3u8');
define("FAKE_IMG",'/new/ads/20211222/2021122217523176932.png');//非法访问视频封面
defined('APP_TYPE_FLAG') or define('APP_TYPE_FLAG', 1);
defined('APP_STORE_URL') or define('APP_STORE_URL',config('novel.visit'));//小说路径域名
// 入口加解密
$request = new Yaf\Request\Simple();
//errLog("preData:".var_export([$request->getQuery(),$request->getParams(),$request->getPost()],1));
$_POST = $request->getPost();
//errLog("data:".var_export([$_POST],1));

///=================密钥更新 start
$_k1 = 'ljhlksslgkjfhlksuo8472rju6p2od03';
$_s1 = 'kihfks3kjdhfksjh3kdjfs745dkslfh4';
$keyData = [
    'v0' => [
        'key'  => $_k1,
        'sign' => $_s1,
    ],
    'v1' => [
        'key'  => '005d87a15d42ac27705b664a391e868a',
        'sign' => '3527d6927e3c306b3796203010775df0',
    ],
    'v2' => [
        'key'  => 'fdly8xjwcmcadi8ckxnywenjgpj0y9yc',
        'sign' => 'wgwcnn78kww9vftfuv4zxv0xmad1rlwi',
    ],
];

$_ver = $_POST['_ver'] ?? 'v0';

defined('API_CRYPT_KEY') or define('API_CRYPT_KEY', $keyData[$_ver]['key'] ?? $_k1) ;
defined('API_CRYPT_SIGN') or define('API_CRYPT_SIGN', $keyData[$_ver]['sign'] ?? $_s1) ;
//=================密钥更新 end

if (MODULE_NAME_TEST && !isset($_POST['crypt'])) {
    if (APP_TYPE_FLAG == 1) {
        $crypt = new LibCrypt();
    } else {
        $crypt = new LibCryptPwa();
    }
    $_POST = $crypt->checkInputData($_POST);
}
//errLog("data:".var_export([$_POST],1));
if (isset($_POST['oauth_new_id']) && $_POST['oauth_new_id'] && strpos($_POST['oauth_new_id'], '00000000') === false) {
    //00000000-0000-0000-0000-000000000000  不含有的就替换
    $_POST['oauth_id'] = $_POST['oauth_new_id'];
}
$_POST = JAddSlashes($_POST);
$_GET = JAddSlashes($_GET);
$_COOKIE = JAddSlashes($_COOKIE);
$_REQUEST = JAddSlashes($_REQUEST);


if(($_POST['oauth_type']??'') == 'android'){
    if($_POST['oauth_id'] != '55f3b5912205862a25f0dbc1001147df'){
       // errLog('ip:'.USER_IP.PHP_EOL.' server:'.var_export([$_SERVER['HTTP_USER_AGENT'],$_SERVER['REQUEST_URI']],1).PHP_EOL.'data:'.var_export($_POST,1));
    }
}
//errLog("init:".var_export($_POST,1));
//print_r($_POST);die;
//标识版本控制是否加密播放替换
defined('IS_PWA') or define('IS_PWA', is_pwa());

defined('NEW_PLAY_FLAG') or define('NEW_PLAY_FLAG', 1);

//加密控制的域名
defined('NEW_PLAY_REPLACE_HOST') or define('NEW_PLAY_REPLACE_HOST', 'm3u8.aidouyin.me');
//是否生效
defined('NEW_PLAY_CONF_ENABLE') or define('NEW_PLAY_CONF_ENABLE', true);

//傻逼渠道 不要福利导航 不要应用中心
defined('BLACK_CHANNEL') or define('BLACK_CHANNEL', [
    'xb0124'
]);

if (!defined('IP_POSITION')) {
    $position = \tools\IpLocation::getLocation(USER_IP);
    $position = !is_array($position) || empty($position) ? [] : $position;
    $position['country'] = $position['country'] ?? '中国';
    $position['city'] = $position['city'] ?? '火星';
    $position['province'] = $position['province'] ?? '火星';
    define("IP_POSITION", $position);
}

if (!defined('USER_COUNTRY')) {
    if (!isset($_SERVER['HTTP_CF_IPCOUNTRY'])) {
        $_SERVER['HTTP_CF_IPCOUNTRY'] = (IP_POSITION['country'] ?? '中国') == '中国' ? 'CN' : 'US';
    }
    define('USER_COUNTRY', $_SERVER['HTTP_CF_IPCOUNTRY']);
}
//defined('IS_NEW_VER') or define('IS_NEW_VER',version_compare(($_POST['version']??'3.8.0'), '3.9.1', '>='));//最新ver 2 密钥
defined('IS_NEW_VER') or define('IS_NEW_VER', 1);//最新ver 2 密钥

defined('FREE_SEE_LIMIT') or define('FREE_SEE_LIMIT', 2);//图片 漫画 小说 最小免费看的 章节 或图片