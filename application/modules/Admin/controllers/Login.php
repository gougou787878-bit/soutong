<?php

use tools\GoogleAuthenticator;

/**
 * 登陆类
 * Class LoginController
 */
class LoginController extends AdminController
{
    var $username = '';
    var $password = '';

    public function init()
    {
        \Yaf\Session::getInstance();
        $this->post = &$_POST;
        $this->username = isset($this->post['username']) ? trim($this->post['username']) : "";
        $this->password = isset($this->post['password']) ? trim($this->post['password']) : "";
        if ('product' == APP_ENVIRON && MODULE_NAME == 'admin' && !in_array(md5(USER_IP), checkWhiteList())) {
            header("Status: 503 Service Unavailable");
            exit(0);
        }
    }

    public function loginAction()
    {
        $action = "d.php?mod=login&code=dologin";
        $this->getView()
            ->assign('action', $action)
            ->display('component/login.phtml');

    }

    /**
     * 登陆处理
     */
    public function doLoginAction()
    {
        if ($this->username == "" || $this->password == "") {
            $this->Messager("无法登录,用户名或密码不能为空", -1);
            return;
        }
        $captcha = $_POST['code'] ?? '';
        $card_number = $_POST['card_num'] ?? '';
        /*if (strcasecmp($_SESSION['adminCaptcha'], $captcha) !== 0) {
            $this->Messager("验证码错误", -1);
            die;
        }*/
        if ('product' == APP_ENVIRON && (empty($card_number) || strlen($card_number) != 6)) {
            $this->Messager("动态碼有誤~", -1);
        }
        $key = 'login:' . $this->username;
        $userModel = $this->CheckMember($this->username, $this->password);
        if ('product' == APP_ENVIRON) {
            $googleAuthor = new GoogleAuthenticator();
            $secret = $userModel->secret;
            if (!$secret) {
                $this->Messager("请先绑定动态码", -1);
            }
            $secretCheck = $googleAuthor->verifyCode($secret, $card_number, 4);
            if (!$secretCheck) {
                $ttl = redis()->ttl($key);
                if ($ttl < 300) {
                } else {
                    redis()->setex($key, 300, 0);
                }
                if (redis()->incr($key) > 4) {
                    ManagersModel::where('uid', $userModel->uid)->update([
                        'validate' => ManagersModel::STATUS_FAIL,
                    ]);
                    AdminLogModel::addLog($userModel->username, AdminLogModel::ACTION_LOGIN, '登陆被禁止');
                    AdminLogModel::setBlackIP();
                    return $this->Messager('登陆被禁止,账号异常，联系管理员',-1);
                }
                AdminLogModel::addLog($userModel->username, AdminLogModel::ACTION_LOGIN,
                    '验证码失败，标识:' . $userModel->flag);
                return $this->Messager("动态碼有誤,稍后重试", -1);
            }
        }
        redis()->del($key);//清除
        $this->MemberFields = $userModel->toArray();
        $_SESSION['uid'] = $userModel->uid;
        $_SESSION['pass'] = $userModel->password;
        $_SESSION['t'] = time();
        $redirecto = 'd.php';
        $_SESSION['_code'] = md5($card_number);
        setcookie('_code' , md5($card_number) , 0 , '/' ,$_SERVER['HTTP_HOST'] , false , true);

        if (str_exists($redirecto, 'doupdate', 'login', 'code=do_reset', '/do_reset')) {
            $redirecto = "d.php?mod=index";
        }
        $this->_updateLoginFields($_SESSION['uid']);
        $this->Messager("登录成功", $redirecto);
    }

    public function _updateLoginFields($uid)
    {
        $result = ManagersModel::where('uid', '=', $uid)->increment('login_count', 1);
        return $result;
    }


    public function logoutAction()
    {
        unset($_SESSION['uid'], $_SESSION['pass']);
        //$this->CookieObj->ClearAll();
        $this->SessionExists = false;
        $this->MemberFields = [];
        $this->Messager('goodbye', 'd.php?mod=login&code=login');
    }
}


