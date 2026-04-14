<?php

use traits\RBACVerifyTrait;

/**
 * 基类
 * Class Controller
 */
class AdminController extends \Yaf\Controller_Abstract
{

    use RBACVerifyTrait;

    protected $get;
    protected $post;
    protected $request;
    public $id;
    public $session = [];
    public $SessionExists = false;
    public $MemberFields;
    public $_Error;
    public $perPageNum;
    
    public $pageStart;

    public $Config = [];
    /**
     * @var string
     */
    private $Module;
    /**
     * @var string
     */
    private $Code;

    public function init()
    {
        define('SADMIN', 99999);
        define('TABLE_PREFIX', 'ks_');
        $this->Config = \Yaf\Registry::get('config');

        $this->get = &$_GET;
        $this->post = &$_POST;
        // 后台使用session
        $this->Session = \Yaf\Session::getInstance();

        $this->request = &$_REQUEST;
        $this->Server = &$_SERVER;
        $this->Module = isset($this->request['mod']) ? trim($this->request['mod']) : "";
        $this->Code = isset($this->request['code']) ? trim($this->request['code']) : "";

        if (defined('MEMBER_ID')) {
            return;
        }
        defined('USER_COUNTRY') or define("USER_COUNTRY", "US");
        $_data = [
            USER_IP,
            md5(USER_IP),
            checkWhiteList()
        ];
        //errLog("dd:".var_export($_data,true));
        if ('product' == APP_ENVIRON && MODULE_NAME == 'admin' && !in_array(md5(USER_IP), checkWhiteList())) {
            header("404 Page Not Found");
            exit(0);
        }

        if ('product' == APP_ENVIRON) {
            $googleBanIP = AdminLogModel::getBlackIPList();
            if ($googleBanIP && in_array(USER_IP, $googleBanIP)) {
                errLog("googleBanIP:" . var_export([$_REQUEST, USER_IP], true));
                header("Status: 503 Service Unavailable");
                exit(0);
            }
        }

        $this->Member = $this->FetchMember();
        //未登录就跳到登陆页面
        if (MEMBER_ROLE == 0) {
            if ($this->Code != 'login' && $this->Code != 'dologin') {
                $this->Messager('您没有权限', 'd.php?mod=login&code=login');
                exit(0);
            }
        }

        $controller = $this->getRequest()->getControllerName();
        $action = $this->getRequest()->getActionName();
        try {
            $this->verifyRabc($controller, $action, $this->getUser()->uid);
            $this->assign('adminName', $this->getUser()->username);
        } catch (\exception\ErrorPageException $e) {
            $this->Messager($e->getMessage(), null);
            exit(0);
        }

        $page = $_GET['page'] ?? 1;
        $page = $page < 1 ? 1 : intval($page);
        $this->perPageNum = 24;
        $this->pageStart = ($page - 1) * $this->perPageNum;
        if ($this->getUser()){
            $_SERVER['username'] = $this->getUser()['username'];
        }

    }

    /**
     * @param $id
     * @param $pass
     * @return array
     */
    function FetchMember()
    {
        if (empty($_SESSION)) {
            $this->MemberFields = null;
        } else {
            $_code1 = $_COOKIE['_code'] ?? '';
            $_code2 = $_SESSION['_code'] ?? '';
            if ('product' == APP_ENVIRON) {
                if (empty($_code1) || empty($_code2) || $_code1!=$_code2){
                    $_SESSION['t'] = 0;
                }
            }
            
            $id = $_SESSION['uid'];
            $pass = $_SESSION['pass'];
            $this->id = max(0, (int)$id);
            if ($_SESSION['t'] > time() - 7200) {
                $this->MemberPassword = trim($pass);
                $this->MemberFields = $this->GetMember();
            } else {
                Yaf\Session::getInstance()->del('uid');
                Yaf\Session::getInstance()->del('pass');
                Yaf\Session::getInstance()->del('t');
                $_SESSION = [];
            }
        }

        if (!empty($this->MemberFields)) {
            $role_name = '';
            if (isset($this->MemberFields['role_id'])) {
                $role_name = RoleModel::where('role_id', '=', $this->MemberFields['role_id'])
                    ->first(['role_name'], false)['role_name'];
            }
            if (isset($this->MemberFields['uid']) && $this->MemberFields['uid'] > 0) {
                define("MEMBER_ID", $this->MemberFields['uid']);
                define("MEMBER_NAME", $this->MemberFields['username']);
                define("MEMBER_ROLE", $this->MemberFields['role_id']);
                define("MEMBER_AFF", $this->MemberFields['aff']);
            } else {
                define("MEMBER_ID", 0);
                define("MEMBER_NAME", null);
                define("MEMBER_ROLE", 0);
                define("MEMBER_EMAIL", null);
                define("MEMBER_ROLE_NAME", null);
            }
            define("MEMBER_ROLE_NAME", $role_name);
        } else {
            define("MEMBER_ID", 0);
            define("MEMBER_NAME", null);
            define("MEMBER_ROLE", 0);
            define("MEMBER_EMAIL", null);
            define("MEMBER_ROLE_NAME", null);
        }

        return $this->MemberFields;
    }


    /**
     * @param $user
     * @param $password
     * @return ManagersModel
     */
    function CheckMember($user, $password)
    {
        $this->SetMember($user);
        /** @var ManagersModel $admin */
        $admin = ManagersModel::where([
            ["username", '=', $user],
            //["validate", '=', ManagersModel::STATUS_SUCCESS],
        ])->first();
        if (is_null($admin)) {
            $this->Messager("无效管理用户", -1);
            exit(0);
        }
        if ($admin->validate == ManagersModel::STATUS_FAIL) {
            $this->Messager("账号被停用", -1);
            exit(0);
        }
        $password_hash = $this->MakePasswordHash($password);
        if ($admin->password != $password_hash) {
            $this->Messager("用户名或密码错误", -1);
            exit(0);
        }
        return $admin;
    }

    function SetMember($user)
    {
        if (trim($user) != '') {
            $this->MemberName = $user;
        } else {
            Return false;
        }
    }

    function MakePasswordHash($password)
    {
        return md5($password);
    }

    function GetMemberFields()
    {
        return $this->MemberFields;
    }

    /**
     * 用户权限
     * @param $mod
     * @param $act
     * @param int $is_admin
     * @return bool
     */
    function HasPermission($mod, $act, $is_admin = 0)
    {

        if ($is_admin == 0) {
            return true;
        }//对前台不做权限控制

        //允许访问的控制器
        $loginMod = 'login';
        $indexMod = 'index';
        $allowed_mod = [$indexMod, $loginMod];
        //初始化控制器和方法
        $mod = !empty(trim($mod)) ? trim($mod) : 'index';
        $action = !empty(trim($act)) ? trim($act) : 'index';
        //得到用户ID
        $role_id = $this->MemberFields['role_id'] ?? 0;
        //得到角色访问权限列表信息
        if (!defined('ACTION_LIST') || empty(ACTION_LIST)) {
            // $sql = "select role_action_ids from `" . TABLE_PREFIX . "role` where role_id = '{$role_id}'";
            // $row = $this->Db->FetchFirst($sql);
            $row = RoleModel::where('role_id', '=', $role_id)
                ->select('role_action_ids')
                ->first(['*'], false);
            $role_actions = '';
            $actionList = [];
            if (!empty($row['role_action_ids'])) {
                $role_actions = $row['role_action_ids'];
                //*代表所有控制器
                if ($role_actions == '*') {
                    define('ACTION_LIST', SADMIN);
                } else {
                    $role_actions = explode(',', $role_actions);
                    //$role_actions = "'" . implode("','", $role_actions) . "'";
                    //缓存可访问action

                    // $a_sql = "select * from `" . TABLE_PREFIX . "role_action` where `id` in({$role_actions})";
                    // $allow_action = $this->Db->FetchAll($a_sql);

                    $allow_action = RoleActionModel::whereIn('id', $role_actions)->get(['*'], false)->toArray();

                    foreach ($allow_action as $datum) {
                        if ($datum['action'] == '*') {
                            $ac = 'mod=' . $datum['module'];
                        } else {
                            $ac = 'mod=' . $datum['module'] . '&code=' . $datum['action'];
                        }
                        if (!in_array($ac, $actionList)) {
                            $actionList[] = $ac;
                        }
                    }
                    define('ACTION_LIST', json_encode($actionList, JSON_UNESCAPED_UNICODE));
                }
            } else {

                define('ACTION_LIST', '');
            }
        }
        //超级管理员
        if ('admin' == $this->MemberFields['role_type']) {
            return true;
        }
        //判断在不在允许访问的控制器
        if (!$mod || in_array($mod, $allowed_mod)) {
            return true;
        }
        if (empty(ACTION_LIST)) {
            $this->_SetError("未到找到您对应的权限,请联系超级管理员");
            return false;
        }
        $action_sql = RoleActionModel::where('module', '=', $mod)
            ->where(function ($query) use ($action) {
                $query->where('action', '=', $action);
                $query->orWhere('action', '=', '*');
            });
        if (!empty($role_actions)) {
            $action_sql->whereIn('id', $role_actions);
        }

        $action = $action_sql->get(['*'], false)->toArray();

        if (empty($action)) {
            $error = "操作模块:{$mod}<br>操作指令:{$act}<br><br>";
            $error .= "您暂时没有权限执行该操作,请联系网站的超级管理员。";
            $this->_SetError($error);
            return false;
        } else {
            return true;
        }
    }

    /**
     * 得到用户
     * @return array
     */
    function GetMember()
    {


        $this->SessionExists = ((isset($this->session['uid']) && $this->session['uid'] == $this->id) ? true : false);


        if (!$this->SessionExists) {
            $result = ManagersModel::where([
                'uid'      => $this->id,
                'password' => $this->MemberPassword
            ]);
            if ($result->exists()) {
                $this->session = $result->first(['*'], false)->toArray();
            } else {
                $this->session = [];
            }
        } else {
            $this->session = [];
        }

        return $this->session;
    }

    function _SetError($error)
    {
        $this->_Error[] = $error;
    }

    function GetError()
    {
        Return $this->_Error;
    }

    public function Messager($message, $redirectto = '', $time = -1, $return_msg = false, $js = null)
    {
        global $rewriteHandler;
        $url_redirect = '';
        ob_start();
        if ($time === -1) {
            $time = (isset($this->Config['msg_time']) ? $this->Config['msg_time'] : 3);
        }
        $to_title = ($redirectto === '' or $redirectto == -1) ? "返回" : "跳转";
        if ($redirectto === null) {
            $return_msg = $return_msg === false ? "&nbsp;" : $return_msg;
        } else {
            $redirectto = ($redirectto !== '') ? $redirectto : ($from_referer = referer());
            if (str_exists($redirectto, 'mod=login', 'code=register', '/login', '/register')) {
                $urlPart = $_SERVER['QUERY_STRING'] ?? 'mod=login&code=login';
                $referer = '&referer=' . urlencode('d.php?' . $urlPart);
                // $this->CookieObj->Setvar('referer', 'd.php?' . $_SERVER['QUERY_STRING']);
            }
            if (is_numeric($redirectto) !== false and $redirectto !== 0) {
                if ($time !== null) {
                    $url_redirect = "<script language=\"JavaScript\" type=\"text/javascript\">\r\n";
                    $url_redirect .= sprintf("window.setTimeout(\"history.go(%s)\",%s);\r\n", $redirectto,
                        $time * 1000);
                    $url_redirect .= "</script>\r\n";
                }
                $redirectto = "javascript:history.go({$redirectto})";
            } else {
                if ($rewriteHandler && null !== $message) {
                    $redirectto .= $referer;
                    if (!$from_referer && !$referer) {
                        $redirectto = $rewriteHandler->formatURL($redirectto, true);
                    }
                }
                if ($message === null) {
                    $redirectto = rawurldecode(stripslashes(($redirectto)));
                    @header("Location: $redirectto"); #HEADER跳转
                }
                if ($time !== null) {
                    $url_redirect = ($redirectto ? '<meta http-equiv="refresh" content="' . $time . '; URL=' . $redirectto . '">' : null);
                }
            }
        }
        $title = "消息提示:" . (is_array($message) ? implode(',', $message) : $message);

        $title = strip_tags($title);
        if ($js != "") {
            $js = "<script language=\"JavaScript\" type=\"text/javascript\">{$js}</script>";
        }
        // $additional_str = $url_redirect . $js;

        $this->setViewpath(__DIR__ . '/../modules/Admin/views');
        $this->getView()
            ->assign('url_redirect', $url_redirect)
            ->assign('message', $message)
            ->assign('time', $time)
            ->assign('to_title', $to_title)
            ->assign('redirectto', $redirectto)
            ->assign('return_msg', $return_msg)
            ->display('component/messager.phtml');
    }

    public function showJson($data, $status = 1)
    {
        $return_data = [
            'data'   => $data ?? [],
            'status' => $status,
        ];

        header('Content-Type: application/json');
        echo json_encode($return_data, JSON_UNESCAPED_UNICODE);
    }

    public function upload_bak($img, $position = 'ads', $name = 'img')
    {
        $img_id = '91_ads_' . date('YmdHis') . rand(1, 999);
        $typeArr = explode("/", $img["type"]);
        $type = end($typeArr);
        $image_name = $img_id . "." . $type;

        $image_path = 'data/images/';
        $image_file = $image_path . $image_name;
        $upload = new \tools\UploadService();
        $upload->init($image_path, $name, true);
        $upload->setNewName($image_name);
        $result = $upload->doUpload();
        if ($result) {
            $result = is_image($image_file);
        }
        if (!$result) {
            unlink($image_file);
            return ['success' => false, 'msg' => '图片上传本地失败，请稍后重试'];
        }
        $return = $this->uploadImg($img_id, $image_file, $type, $position, config('upload.img_upload'));
        if ($return['code'] == 1) {
            unlink($image_file);
            return ['success' => true, 'msg' => '图片上传成功', 'cover' => $return['msg']];
        } else {
            return ['success' => false, 'msg' => '图片上传远程失败，请稍后重试', 'cover' => $return['msg']];
        }
    }

    /**上传图片到图片服务器
     * @param $id
     * @param $img '图片文件'
     * @param $type '图片格式'
     * @param $position '存放位置'
     * @param $url '脚本地址'
     * @return array|mixed
     */
    public function uploadImg_bak($id, $img, $type, $position, $url)
    {
        $img = new CURLFile(realpath($img));
        $img->setMimeType("images/" . $type);
        $position == 'ads' && $id .= time() . mt_rand(1, 999);
        $data = array(
            'id'       => $id,
            'position' => $position,
        );
        $crypt = new \tools\CryptService();
        $sign = $crypt->make_sign($data);
        $data['cover'] = $img;
        $data['sign'] = $sign;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $dataReturn = curl_exec($ch);
        $error = curl_error($ch);
        if ($error) {
            $return_data = array('code' => 0, 'msg' => $error);
        } else {
            $return_data = json_decode($dataReturn, true);
        }
        curl_close($ch);
        return $return_data;
    }


    public function upload($img, $position = 'ads', $name = 'img')
    {
        $img_id = '91_ads_' . date('YmdHis') . rand(1, 999);
        $typeArr = explode("/", $img["type"]);
        $type = end($typeArr);

        $return = $this->uploadImg($img_id, $img, $type, $position, config('upload.img_upload'));

        if ($return['code'] == 1) {
            return ['success' => true, 'msg' => '图片上传成功', 'cover' => $return['msg']];
        } else {
            return ['success' => false, 'msg' => '图片上传远程失败，请稍后重试', 'cover' => $return['msg']];
        }
    }

    /**上传图片到图片服务器
     * @param $id
     * @param $img '图片文件'
     * @param $type '图片格式'
     * @param $position '存放位置'
     * @param $url '脚本地址'
     * @return array|mixed
     */
    public function uploadImg($id, $img, $type, $position, $url)
    {
        $img = new CURLFile(realpath($img['tmp_name']));
        $img->setMimeType("images/" . $type);
        $position == 'live' && $id .= time() . mt_rand(1, 999);
        $data = array(
            'id'       => $id,
            'position' => $position,
        );
        $crypt = new \tools\CryptService();
        $sign = $crypt->make_sign($data);
        $data['cover'] = $img;
        $data['sign'] = $sign;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $dataReturn = curl_exec($ch);
        $error = curl_error($ch);
        if ($error) {
            $return_data = array('code' => 0, 'msg' => $error);
        } else {
            $return_data = json_decode($dataReturn, true);
        }
        curl_close($ch);
        return $return_data;
    }

    /**
     *
     * @return false|\ManagersModel
     * @author xiongba
     * @date 2019-11-08 19:04:20
     */
    protected function getUser()
    {
        static $user = null;
        if ($user === null) {
            $user = ManagersModel::make($this->MemberFields);
            $user->uid = $this->MemberFields['uid'];
        }
        return $user;
    }

    protected function assign($name, $value)
    {
        return $this->getView()->assign($name, $value);
    }
}


