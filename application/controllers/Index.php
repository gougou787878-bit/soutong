<?php

/**
 * H5页面父类
 * Class IndexController
 */
class IndexController extends BaseController
{
    /**
     * @var \Yaf\View_Interface
     */
    protected $view;
    public $config;
    public $post;

    /**
     * 初始化数据
     */
    public function init()
    {
        register('controller',$this);
        $this->initPosition();
        $this->view = $this->getView();
        $this->post = &$_POST;
        $this->config = \Yaf\Registry::get('config');
        defined('USER_COUNTRY') or define('USER_COUNTRY', ($this->position['country'] ?? '') == '中国' ? 'CN' : 'US');
    }

    /**
     * 得到用户信息,处理因为$this->member取不到值的情况
     * @param $uid
     */
    public function getMemberInfo($uid)
    {
        if (empty($this->member)) {
            $this->member = MemberModel::find($uid);
        }
    }
    static function getMember($uid)
    {
        return cached('bp:usr:' . $uid)->serializerJSON()->expired(900)->fetch(function () use ($uid) {
            $row =  MemberModel::query()->where('uid', $uid)->first();
            if(is_null($row)){
                return [];
            }
            return $row->toArray();
        });
    }

    protected function assign($k, $v){
        return $this->getView()->assign($k , $v);
    }

    /**
     * 检查token
     */
    protected function checkLogin()
    {
        $validator = \helper\Validator::make($_REQUEST , [
            'uid' => 'required|integer|min:1',
            'token' => 'required|string'
        ]);

        if ($validator->fail($msg)) {
            //$this->show('error', ['message' => $msg]);
        }
        $uid = $_REQUEST['uid'];
        $token = $_REQUEST['token'];

        $member = MemberModel::find($uid);
        if ($member->token() != $token){
            //$this->show('error', ['message' => "您的登陆状态失效，请重新登陆！"]);
        }


        $this->assign('uid' , $_REQUEST['uid']);
        $this->assign('token' , $_REQUEST['token']);
        /*token 检查
        * $this->show('error',[
           'reason' => '您的登陆状态失效，请重新登陆！'
       ]);*/
    }

    /**
     * 模板输出
     * @param $tpl
     * @param $data
     */
    protected function show($tpl, $data = [])
    {
        $this->setViewpath(__DIR__ . '/../modules/Index/views');
        foreach ($data as $k => $v) {
            $this->assign($k, $v);
        }
        if (strpos($tpl ,'.phtml')){
            $this->getView()->display("{$tpl}");
        }else{
            $this->getView()->display("{$tpl}.phtml");
        }
        exit();
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
        $toCrypt = false;
        if ($toCrypt) {
            $data = json_encode($data, JSON_UNESCAPED_UNICODE);
            $data = str_replace(TB_IMG_APP_US, TB_IMG_APP_CN, $data);
            $data = json_decode($data, true);
        }

        $returnData = [
            'data' => $data,
            'status' => $status,
            'msg' => $msg,
            'crypt' => $toCrypt,
            'isVip' => true,
        ];

        if (\Yaf\Application::app()->environ() == 'product') {
            $crypt = new LibCrypt();
            $returnData = $crypt->replyData($returnData);
        }
        return $this->getResponse()->setBody(json_encode($returnData, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 纯json发送
     * @param $data
     * @return mixed
     */
    public function ej($data)
    {
        @header('Content-Type: application/json');
        $returnData = $data;
        return $this->getResponse()->setBody(json_encode($returnData, JSON_UNESCAPED_UNICODE));
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
            'id' => $id,
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

}