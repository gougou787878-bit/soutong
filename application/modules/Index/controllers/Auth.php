<?php
/**
 * 会员认证
 */

class AuthController extends IndexController
{
    private $uploadPath = './upload/auth';
    private $urlPrefix = '/upload/auth/';

    public function indexAction()
    {
        if(!isset($_REQUEST['uid']) || !$_REQUEST['uid']){
            exit('参数不完整,尝试重新进入');
        }
        $this->checkLogin();
        $this->getMemberInfo($_REQUEST['uid']);
        if (empty($this->member)){
            exit('参数不完整,尝试重新进入');
        }
        $authInfo = AuthModel::where('uid',$this->member['uid'])->first();
        if (1 == $this->member['auth_status']) {
            //$this->view->assign('video_url',  \Yaf\Registry::get('config')->upload->mp4_play_url);
            //$this->view->assign('pic_url',  \Yaf\Registry::get('config')->img->img_live_url);
            $this->show('auth_ok', ['auth' => $authInfo]);
        } elseif ($authInfo && 0 == $authInfo->status) {
            $this->show('auth_success', ['auth' => $authInfo->toArray()]);
        }
        $this->show('auth');
    }

    /**
     * 图片上传
     */
    public function uploadActionBak()
    {
        $result = singleton(ToolsModel::class)->uploadFile($_FILES["image"],'live',config('upload.mp4_upload'));
        $fileName = stringMakeGuid();
        $ext = last(explode('.', $_FILES["image"]["name"]));
        $name = "{$fileName}.{$ext}";
        try {
            $re = move_uploaded_file($_FILES["image"]["tmp_name"],
                "{$this->uploadPath}/" . $name);
        } catch (\Exception $e) {
            return $this->ej([
                "ret" => 0, 'file' => '', 'msg' => $e->getMessage()
            ]);
        }


        if ($re) {
            return $this->ej([
                "ret" => 200, 'data' => ['url' => $this->urlPrefix . $name], 'msg' => ''
            ]);
        } else {
            return $this->ej([
                "ret" => 0, 'file' => '', 'msg' => '上传失败'
            ]);
        }
    }

    /***
     * 认证页面
     */
    public function authstepAction()
    {
        $this->checkLogin();
        $this->getView()->assign('mp4_key',config('upload.mp4_key'));
        //$this->getView()->assign('mp4_url',\Yaf\Registry::get('config')->upload->mp4_upload);
        $this->getView()->assign('mp4_url',config('upload.mp4_upload'));//注意跨越
        $this->getView()->assign('mp4_play',config('mp4.visit'));
        $this->show('auth_step');
    }

    /**
     * 认证保存
     */
    public function authsaveAction()
    {
        $where = [
            'uid' => $_POST['uid']
        ];
        $data['real_name'] = $_POST["real_name"];
        $data['mobile'] = $_POST["mobile"];
        $data['cer_no'] = $_POST["cer_no"];
        $data['wechat_or_qq'] = $_POST["wechat_or_qq"];
        $data['front_view'] = $_POST["front_view"];
        $data['back_view'] = $_POST["back_view"];
        $data['handset_view'] = $_POST["handset_view"];
        $data['uid'] = $_POST['uid'];
        $data['status'] = 0;
        $data['addtime'] = time();

        $result = AuthModel::updateOrCreate($where, $data);

        if ($result !== false) {
            return $this->ej(["ret" => 200, 'data' => array(), 'msg' => '']);
        } else {
            return $this->ej(["ret" => 0, 'data' => array(), 'msg' => '提交失败，请重新提交']);
        }
    }

    /**
     * 图片上传
     */
    public function uploadAction()
    {
        $img = $_FILES['image'];
        $result = $this->upload($img,'live','image');
        if ($result['success']??false) {
            return $this->ej([
                "ret" => 200, 'data' => ['url' =>$result['cover'] ], 'msg' => ''
            ]);
        } else {
            return $this->ej([
                "ret" => 0, 'file' => '', 'msg' => '上传失败'
            ]);
        }
    }

    /**
     * 成功
     */
    public function successAction()
    {
        $this->show('auth_success');
    }

    /**
     *失败
     */
    public function errorAction()
    {
        $this->show('auth_error');
    }
}