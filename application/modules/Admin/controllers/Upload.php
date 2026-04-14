<?php


class UploadController extends AdminController
{

    use \traits\AjaxResponseTrait;

    /**
     * 上传文件
     * @throws Exception
     * @author xiongba
     * @date 2019-11-19 17:01:39
     */
    public function uploadAction()
    {
        //_FILES字段的名称
        $fileName = 'file';
        $position = $_POST['position'] ?? 'ads';
        $name = $_FILES[$fileName]['name'];
        $extension = pathinfo($name, PATHINFO_EXTENSION);

        if (!in_array($extension, ['mp4' , 'gif' , 'png' , 'jpeg' , 'jpg' , 'swf' , 'icon' , 'm3u8' ])) {
            return $this->ajaxError('类型错误', -1);
        }


        $id = uniqid();
        $image_name = $id . "." . $extension;
        $image_path = APP_PATH . '/storage/data/images/';
        $image_file = $image_path . $image_name;
        /** @var LibUpload $uploadObject */
        $uploadObject = new  LibUpload;
        $uploadObject->init($image_path, $fileName, true);
        $uploadObject->setNewName($image_name);
        $result = $uploadObject->doUpload();
        if (!$result || !file_exists($image_file)) {
            return $this->ajaxError('文件上传本地失败', -1);
        }
        if (file_exists($image_file)) {
            list($width, $height) = getimagesize($image_file);
        } else {
            $height = $width = 0;
        }
        $return = LibUpload::upload2Remote($id, $image_file, $position);
        unlink($image_file);
        AdminLogModel::addUpload('','长传了文件');
        if ($return['code'] == 1) {
            $cover = $return['msg'];
            $info = array(
                'url'    => $cover,
                'width'  => $width,
                'height' => $height,
            );
            return $this->ajaxSuccess($info, 200);
        } else {
            return $this->ajaxError('文件上传服务器失败', -1, $return);
        }
    }


    public function uploadMp4Action(){
//_FILES字段的名称
        $fileName = 'file';
        $name = $_FILES[$fileName]['name'];
        $extension = pathinfo($name, PATHINFO_EXTENSION);

        if (!in_array($extension, ['mp4', 'm3u8'])) {
            return $this->ajaxError('类型错误', -1);
        }
        $id = uniqid();
        $image_name = $id . "." . $extension;
        $image_path = APP_PATH . '/storage/data/video/';
        $image_file = $image_path . $image_name;
        /** @var LibUpload $uploadObject */
        $uploadObject = new  LibUpload;
        $uploadObject->init($image_path, $fileName, false);
        $uploadObject->setNewName($image_name);
        $result = $uploadObject->doUpload();
        if (!$result || !file_exists($image_file)) {
            return $this->ajaxError('文件上传本地失败', -1);
        }
        $return = LibUpload::uploadMp42Remote($id, $image_file);
        unlink($image_file);
        if ($return['code'] == 1) {
            $cover = $return['msg'];
            $info = array(
                'url'    => $cover
            );
            return $this->ajaxSuccess($info, 200);
        } else {
            return $this->ajaxError('文件上传服务器失败', -1, $return);
        }
    }


    /**
     * @return bool
     * @throws Exception
     * @author xiongba
     * @date 2020-01-01 16:03:04
     */
    public function upload2Action()
    {
        $_POST['position'] ='ads';
        $this->uploadAction();
        $data = $this->getResponse()->getBody();
        $data = json_decode($data, 1);

        if ($data['code'] == 200) {
            $url = $data['data']['url'];
            $title = basename($url);
            return $this->ajaxSuccess([
                'src'   => url_ads($url),
                'title' => $title,
            ], 0);
        } else {
            return $this->ajaxError('上传失败');
        }


    }

    /**
     * @return bool
     * @throws Exception
     * @author xiongba
     * @date 2020-01-01 16:03:04
     */
    public function otherAction()
    {
        $_POST['position'] ='upload';
        $this->uploadAction();
        $data = $this->getResponse()->getBody();
        $data = json_decode($data, 1);

        if ($data['code'] == 200) {
            $url = $data['data']['url'];
            $title = basename($url);
            return $this->ajaxSuccess([
                'src'   => url_upload($url),
                'title' => $title,
            ], 0);
        } else {
            return $this->ajaxError('上传失败');
        }


    }


}