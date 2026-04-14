<?php

use Tbold\Serv\biz\BizAppVisit;
use tools\IpLocation;

/**
 * Class BaseController
 */
class BaseController extends \Yaf\Controller_Abstract
{
    public $member; // 用户信息
    public $config; // 配置信息
    public $post;

    // 分页参数
    public $page;
    public $limit;
    public $offset;
    public $toPage = false;
    public $position; // 位置信息
    public $channel='';
    public $isChanLiveRequest = false;//是否直播接入
    protected $last_ix;

    public function init()
    {
        register('controller', $this);
        defined('TABLE_PREFIX') or define('TABLE_PREFIX', 'ks_');
        $this->post = &$_POST;
        $this->config = \Yaf\Registry::get('config');
        if (!defined('APP_TYPE_FLAG') || APP_TYPE_FLAG == 0) {
            $oauth_type = $this->post['oauth_type'] ?? '';
            if ($oauth_type != 'pwa') {
                errLog("pwa-req-data-post".var_export($_POST, 1));
                errLog("pwa-req-data-get".var_export($_GET, 1));
                exit('非法数据请求...');
            }
        }
        //$this->verifyVersion();
        //白名单放行
        if ($this->getRequest()->action == 'watching'){
            return;
        }
        if (in_array(USER_IP, getBanIpList()) || redis()->sIsMember('ban:ip:list', USER_IP)) {
            header("Status: 503 Service Unavailable");
            exit();
        }
        $this->initMember();

        $this->channel = $this->member['build_id'] ?? '';

        // 分页参数
        $this->initPageConfig();

        // 位置信息
        $this->initPosition();

        //永久拉黑处理
        if ($this->member['role_id'] == 4) {
            exit('当前服务维护中...');
        }
        //APP_ENVIRON == 'product' && $this->verifyUser();
        $this->verifyUser();
        $this->verifyCrackApp();

        /** @var MemberModel $member */
        $member = request()->getMember();
        $userPrivilege = cached(UsersProductPrivilegeModel::REDIS_KEY_USER_PRIVILEGE . $member->aff)
            ->fetchJson(function () use ($member) {
                return UsersProductPrivilegeModel::getUserPrivilege($member);
            });
        if (!defined('USER_PRIVILEGE')) {
            define('USER_PRIVILEGE', $userPrivilege);
        }   
    }

    public function verifyUser()
    {
        //return ;//先不处理 等稳定了 强更后再处理*/
        $_f = false;
        $_ver = $this->post['version']??'5.0.0';

        if (false && $this->post['oauth_type'] == 'android') {
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $channel = $_POST['theme']??'';
            $whiteList = ['xb2514'];//特殊渠道包专用
            if ($channel && in_array($channel, $whiteList)) {

            }else if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                $_f = true;
            } elseif ($ua && strpos($ua, 'Dart') !== false) {
                $_f = true;
            } elseif (version_compare($_ver, '3.7.9', '<')) {
                $_f = true;
            } elseif (!isset($_POST['bundle_id'])) {
                $_f = true;
            } elseif (version_compare($_ver, '3.9.1', '>=')) {
                $__package_name__ = $_POST['__package_name__'] ?? '';
                $__package_hash__ = $_POST['__package_hash__'] ?? '';
                $real_hash = VersionModel::checkBound($__package_name__);
                if ($real_hash && $real_hash == $__package_hash__) {
                } else {
                    $_f = true;
                }
            }
        }
        $_f = false;
        defined('IS_FAKE_CLIENT') or define("IS_FAKE_CLIENT", $_f);
    }


    protected function verifyCrackApp(){

        $_SERVER['is_crack'] = false;
        return ;


        $version = $_POST['version'] ?? '1.0.0';
        $oauthType = $_POST['oauth_type'] ?? 'android';
        if ($this->member['uid'] == 11838968) {
            trigger_log(json_encode($_POST));
        }
        if ($this->member['is_piracy']) {
            $_SERVER['is_crack'] = true;
            return;
        }
        if ($oauthType != 'android') {
            return;
        }
        if (version_compare($version, '3.9.1', '<')) {
            $_SERVER['is_crack'] = true;
            return;
        }
        $__package_name__ = $_POST['__package_name__'] ?? '';
        $__package_hash__ = $_POST['__package_hash__'] ?? '';
        $real_hash = VersionModel::checkBound($__package_name__);
        if ($real_hash && $real_hash == $__package_hash__) {
            return;
        }
        $_SERVER['is_crack'] = true;
    }

    public function initMember()
    {
        if (stripos(($_SERVER['PATH_INFO']??''), 'lanpron') !== false) {//lanpron  暂时不需要用户
            return;
        }
       
        try {
            // errLog(var_export($_POST,true));
            // die;
            $this->member = LibMember::getInstance()->FetchMemberNew();// 获取用户信息
        } catch (\Throwable $e) {
            trigger_log($e);
        }
        if (empty($this->member) || ($this->member['uid'] ?? 0) < 1) {
            header("Status: 503 Service Unavailable");
            exit();
        }
    }

    /**
     * 验证版本
     * @author xiongba
     */
    public function verifyVersion(){
        $firstVersion = setting('first:version' , '0');
        $lastVersion = setting('last:version' , '99999');
        $currentVersion = $_POST['version'] ?? false;
        if (empty($currentVersion) || !\helper\OperateHelper::inVer($currentVersion , $firstVersion , $lastVersion)){
            header("Status: 503 Service Unavailable");
            exit();
        }
    }


    protected function initPageConfig(){
        $this->limit = $_POST['limit'] ?? 24;
        $this->page = $_POST['page'] ?? 1;
        if ($this->page <= 0) {
            $this->page = 1;
        }
        $this->offset = ($this->page - 1) * $this->limit;
        if (isset($_POST['limit']) && isset($_POST['limit'])) {
            $this->toPage = true;
        }
        $this->last_ix = $_POST['last_ix'] ?? -1;
    }

    /**
     * 初始化用户位置信息
     */
    protected function initPosition()
    {
        $this->position = IP_POSITION;
    }



    protected function errLog($msg, $type = 3)
    {
        errLog($msg, $type);
    }

    public function errorJson($msg, $status = 0, $data = null)
    {
        return $this->showJson($data, $status, $msg);
    }

    public function successMsg($msg)
    {
        return $this->showJson('', 1, $msg);
    }

    /**
     * 返回数据
     * @param $data
     * @param int $status
     * @param string $msg
     * @return bool
     */
    public function showJson($data, $status = 1, $msg = '')
    {
        @header('Content-Type: application/json');
        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        //$data = json_encode($data, 320);//不起作用
        $url_replace = [];
        /*$url_replace = [
            'img.av\/\/new'       => 'new',
            'img.xiao\/\/new'     => 'new',
            'img.actors\/\/new'   => 'new',
            'img.head\/\/new'     => 'new',
            'img.ads\/\/new'      => 'new',
            'img.icos\/\/new'     => 'new',
            'm3u8.hode88.com'     => 'm3u8.dsdkh.com',//临时解决
            //'new_img.ycomesc.com' => config('img.cn_base_url' , 'image.cdryafkj.com'),
        ];*/
        if (defined('APP_TYPE_FLAG') && APP_TYPE_FLAG == 1) {
            //区分 国内 国外 加速
            //将国外源换成国内源 避免后台有漏网之鱼，将后台源也换成国内源
            if (USER_COUNTRY == 'CN') {
                $cnBase = config('img.cn_base_url');
                if(1 && IS_NEW_VER){
                    $cnBase = TB_IMG_PWA_CN;
                }
                $cnBase = parse_url($cnBase, PHP_URL_HOST);
            } else {
                $usBase = TB_IMG_APP_US;
                if (1 && IS_NEW_VER) {
                    $usBase = TB_IMG_PWA_US;
                }
                $cnBase = parse_url($usBase, PHP_URL_HOST);
            }
            $url_replace['images.91tv.tv'] = $cnBase;
            $url_replace['imgpublic.ycomesc.com'] = $cnBase;
            $url_replace['imgpublic.ycomesc.live'] = $cnBase;
        } else {
            //pwa端
            //将国外源换成国内源 避免后台有漏网之鱼，将后台源也换成国内源
            if (USER_COUNTRY == 'CN') {
                //$cnBase = parse_url(config('pwa.img.cn.base'), PHP_URL_HOST);
                $cnBase = parse_url(TB_IMG_PWA_CN, PHP_URL_HOST);
            } else {
                $cnBase = parse_url(TB_IMG_PWA_US, PHP_URL_HOST);
            }
            /*if (version_compare($_POST['version'] ?? '1.0.0', '3.0.0', '>=')) {
                $cnBase = parse_url('https://newh5.niqcaok.cn', PHP_URL_HOST);
            }*/
            $url_replace['images.91tv.tv'] = $cnBase;
            $url_replace['imgpublic.ycomesc.com'] = $cnBase;
            $url_replace['imgpublic.ycomesc.live'] = $cnBase;

        }

        $data = str_ireplace(array_keys($url_replace), array_values($url_replace), $data);
        //$data = str_replace($this->config->img->us_base_url, $this->config->img->cn_base_url, $data);
        $data = json_decode($data, true);

        $returnData = [
            'data'      => $data,
            'status'    => $status,
            'msg'       => $msg,
            'crypt'     => true,
            'isVV'      => $this->member['expired_at'] > TIMESTAMP,
            'needLogin' => $this->needLogin(),
            'isLogin'   => ($this->member['username'] ?? '') != '',
            'req_time'  => TIMESTAMP
        ];

        if (MODULE_NAME_TEST and !isset($_POST['crypt'])) {
            $crypt = APP_TYPE_FLAG ? (new LibCrypt()) : (new LibCryptPwa());
            $returnData = $crypt->replyData($returnData);
            return $this->getResponse()->setBody($returnData);

        } else {
            return $this->getResponse()->setBody(json_encode($returnData, JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * 返回含有last_idx的列表
     *
     * 参数传递 两种方式传递参数等效
     *  $this->listJson($list , string $column ,array $extra)
     *  $this->listJson($list , array $extra ,string $column)
     *
     * 返回事例
     * ```php
     * merge( [
     *     'list' : $list,
     *     'last_idx' : last($list)[id],
     * ] , $extra )
     * ```
     *
     *
     * @param $list
     * @param string|array $column
     * @param array|string $extra
     * @return array|bool|mixed
     */
    public function listJson($list, $column = 'id', $extra = [])
    {
        if (is_array($column)) {
            // 当column参数是数组时候，交换column和extra的值，
            if (is_string($extra)) {
                list($extra, $column) = [$column, $extra];
            } else {
                list($extra, $column) = [$column, 'id'];
            }
        }
        if ($list instanceof \Illuminate\Support\Collection) {
            $list = $list->toArray();
        }

        $last_end = collect($list)->last();
        if (is_array($last_end) || $last_end instanceof ArrayAccess) {
            $last_idx = $last_end[$column] ?? '0';
        } else {
            $last_idx = $last_end;
        }
        if (empty($last_idx)) {
            $last_idx = (string)$last_idx;
        }
        $ret = array_merge([
            'list'    => $list,
            'last_ix' => (string)$last_idx
        ], $extra);

        return $this->showJson($ret);
    }

    /**
     * 发放登录token
     * @param string $uuid
     * @return string
     */
    public function token($uuid = ''): string
    {
        $signKey = $this->config->token->login ?? '';
        $uuid = $uuid == '' ? $this->member['uuid'] : $uuid;
        return request()->getMember()->token();
    }

    /**
     * H5 token 验证器
     * @param $uuid
     * @param $token
     * @return bool
     */
    public function verifyToken($uuid, $token)
    {
        $signKey = $this->config->token->login ?? '';
        if ($token != md5($signKey . $uuid . $signKey)) {
            return false;
        }
        return true;
    }


    /**
     * 是否需要登陆
     * @return bool
     * @author xiongba
     */
    public function needLogin(): bool
    {
        if (empty($this->member['is_reg'])) {
            return false;
        }
        return !$this->hasLogin();
    }

    /**
     * 是否登录
     * @return bool
     */
    public function hasLogin(): bool
    {
        $userToken = $this->post['token'] ?? '';
        if (empty($userToken)) {
            return false;
        }
        return ($userToken == $this->token($this->member['uuid']));
    }

    /**
     * 保存缓存key
     * @param $key
     * @param $value
     * @param $memo
     * @param bool $timestamp
     */
    public function setCacheWithSql($key, $value, $memo, $timestamp = false)
    {
        $data = [
            'name' => $memo,
            'key' => $key,
        ];
        CacheKeysModel::updateOrCreate($data);
        \tools\RedisService::set($key, $value, $timestamp);
    }

    /**
     * 格式化时间戳
     * @param string $timestamps
     * @return false|string
     */
    public function formatTimestamp(string $timestamps = '')
    {
        return  formatTimestamp($timestamps);
    }

    /**
     * 系统是维护状态检查.
     * @return bool
     */
    protected function maintainCheck()
    {
        $configPub = getConfigPub();
        if ($configPub['maintain_switch'] == 1) {
            return $this->showJson([], 0, $configPub['maintain_tips']);
        }
    }


}
