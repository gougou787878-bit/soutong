<?php

class ToolsModel
{
    /**
     * 封面上传
     * @param $img
     * @param string $position
     * @return array
     */
    public function uploadFile($img,$position = 'live',$server){
        $img_id = 'live_' . date('YmdHis') . rand(1, 999);
        $typeArr = explode("/", $img["type"]);
        $type = end($typeArr);
        $image_name = $img_id . "." . $type;
        $image_path = 'upload';
        $image_file = $image_path . $image_name;
        $upload = new \tools\UploadService();
        $upload->init($image_path, 'image', true);
        $upload->setNewName($image_name);
        $result = $upload->doUpload();
      /*  if ($result) {
            $result = is_image($image_file);
        }*/
        if (!$result) {
            unlink($image_file);
            return ['success'=>false,'msg'=>'图片上传本地失败，请稍后重试'];
        }
        $return = self::uploadImg($img_id, $image_file, $type, $position, $server);

        if ($return['code'] == 1) {
            unlink($image_file);
            return ['success'=>false,'msg'=>'图片上传成功','cover'=>$return['msg']];
        } else {
            return ['success'=>false,'msg'=>'图片上传远程失败，请稍后重试','cover'=>$return['msg']];
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
    public static function uploadImg($id, $img, $type, $position, $url)
    {
        $img = new CURLFile(realpath($img));
        $img->setMimeType("images/" . $type);
        $position == 'ads' && $id .= time() . mt_rand(1, 999);
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