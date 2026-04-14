<?php
/**
 * 文件上传相关操作类
 * This is NOT a freeware, use is subject to license terms
 *
 * @version $Id: upload.class.php 5263 2013-12-13 07:55:28Z  $
 * usage:
 *    $image_name = $id.".gif";
 *    $image_path = RELATIVE_ROOT_PATH . 'images/role/';
 *    $image_file = $image_path . $image_name;
 *    //icon 为field 表单里面的name值
 *    Load::C('tools/upload')->init($image_path,'icon',true);
 *    //Load::C('tools/upload')->setMaxSize(1000);
 *    Load::C('tools/upload')->setNewName($image_name);
 *    $result=Load::C('tools/upload')->doUpload();
 *
 * if($result)
 * {
 *      $result = is_image($image_file);
 * }
 * if(!$result){
 *      unlink($image_file);
 *      return false;
 * }else{
 *      DB::update('role', array('icon' => $image_file), array('id' => $id));
 * }
 */


class LibUpload
{
    protected $_error;
    protected $_new_name;
    protected $_save_name;
    protected $_path;
    protected $_field;
    protected $_max_size;
    protected $_image;
    protected $_ext;
    protected $_ext_types;
    protected $_image_types;
    /**
     * @var array
     */
    private $_attach_types;
    /**
     * @var bool
     */
    private $_attach;
    private $_error_no;


    /**
     * 上传图片到图片服务器
     * @param string $id 唯一标识
     * @param string $imgPath 图片路径
     * @param string $position 存放位置 actors,ads,av,head,icons,lusir,pay,upload,xiao,youtube,im
     * @param string $remoteUrl 服务器上传url地址
     * @param string $_id 番号
     * @return array {code:1,msg:"09159db1a99acb773ecf8490c01973ee.jpeg"}
     * @throws Exception
     */
    public static function upload2Remote($id, $imgPath, $position, $remoteUrl = null, $_id = '')
    {

        if ($remoteUrl === null) {
            $remoteUrl = config('upload.img_upload');
        }
        $cover = new CURLFile(realpath($imgPath), mime_content_type($imgPath));
        if ($position == 'ads') {
            $id .= time() . mt_rand(1, 999);
        }
        $data = [
            'id'       => $id,
            '_id'      => $_id,
            'position' => $position,
        ];
        $sign = (new LibCrypt())->make_sign($data, config('upload.img_key'));
        $data['cover'] = $cover;
        $data['sign'] = $sign;
        return self::execCurl($remoteUrl , $data);
    }


    public static function execCurl($url , $data){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $dataReturn = curl_exec($ch);
        $error = curl_error($ch);
        if ($error) {
            $errno = curl_errno($ch);
            curl_close($ch);
            throw new \Exception($error, $errno);
        } else {
            $return_data = json_decode($dataReturn, true);
        }
        curl_close($ch);
        return $return_data;
    }

    /**
     * 上传图片到图片服务器
     * @param string $uuid
     * @param string $filePath 路径
     * @param string $remoteUrl 服务器上传url地址
     * @return array {code:1,msg:"09159db1a99acb773ecf8490c01973ee.jpeg"}
     * @throws Exception
     */
    public static function uploadMp42Remote($uuid, $filePath, $remoteUrl = null)
    {
        if ($remoteUrl === null) {
            $remoteUrl = config('upload.mp4_upload');
        }
        $cover = new CURLFile(realpath($filePath), mime_content_type($filePath));
        $timestamp = time();
        $data = [
            'uuid'     => $uuid,
            'video'    => $cover,
            'timestamp' => $timestamp,
            'sign' => md5($timestamp . config('upload.mp4_key')),
        ];
        return self::execCurl($remoteUrl, $data);
    }


    function init($path, $field = 'upload', $image = false, $attach = false)
    {

        if (!is_dir($path)) {
            $io = new LibIo();
            $io->makeDir($path);
        }
        $this->_path = $path;
        $this->_field = $field;
        $this->_max_size = 2048 * 500;
        $this->_image = $image;
        $this->_attach = $attach;
        $this->_ext = '';
        $this->_new_name = '';
        $this->_save_name = '';

        $this->_attach_types = explode('|', config('config.attach_file_type' , ''));
        $this->_attach_types = array_filter($this->_attach_types);

        $this->_ext_types = array('cgi', 'pl', 'js', 'asp', 'php', 'html', 'htm', 'jsp', 'jar', 'txt', 'rar', 'zip');
        $this->_image_types = array('gif', 'jpg', 'jpeg', 'png', 'webm', 'mp4');
    }


    function setMaxSize($size)
    {
        $this->_max_size = (int)$size;
        return true;
    }


    function setExtTypes($array)
    {
        if (false == is_array($array)) {
            return false;
        }

        $this->_ext_types =& $array;
        return true;
    }


    function setImgTypes($array)
    {
        if (false == is_array($array)) {
            return false;
        }

        $this->_image_types =& $array;
        return true;
    }


    function setAttachTypes($array)
    {
        if (false == is_array($array)) {
            return false;
        }

        $this->_attach_types =& $array;
        return true;
    }


    function setNewName($name)
    {
        $this->_new_name = trim($name);
        return true;
    }


    function getExt()
    {
        return $this->_ext;
    }


    function getSaveName()
    {
        return $this->_save_name;
    }


    function doUpload()
    {
        if (false == is_writable($this->_path)) {
            $this->_setError(504);
            return false;
        }

        if (false == isset($_FILES[$this->_field])) {
            $this->_setError(501);
            return false;
        }

        $name = $_FILES[$this->_field]['name'];
        $size = $_FILES[$this->_field]['size'];
        $type = $_FILES[$this->_field]['type'];
        $temp = $_FILES[$this->_field]['tmp_name'];

        $type = preg_replace("/^(.+?);.*$/", "\\1", $type);

        if (false == $name || $name == 'none') {
            $this->_setError(501);
            return false;
        }

        $_exts = explode('.', $name);
        $this->_ext = strtolower(end($_exts));

        if (false == $this->_ext) {
            $this->_setError(502);
            return false;
        }
        if (false == $this->_image) {
            if (false == $this->_attach) {
                if (false == in_array($this->_ext, array_merge($this->_image_types, $this->_ext_types))) {
                    $this->_setError(502);
                    return false;
                }
            } else {
                if (false == in_array($this->_ext, $this->_attach_types)) {
                    $this->_setError(508);
                    return false;
                }
            }
        } else {
            if (false == in_array($this->_ext, $this->_image_types)) {
                $this->_setError(507);
                return false;
            }
        }

        if ($this->_max_size && $this->_max_size * 1000 < $size) {
            $this->_setError(503);
            return false;
        }
        if (false == $this->_new_name) {
            $this->_save_name = $name;
            $full_path = $this->_path . $name;
        } else {
            $this->_save_name = $this->_new_name;
            $full_path = $this->_path . $this->_save_name;
        }

        if (false == move_uploaded_file($temp, $full_path)) {
            if (false == copy($temp, $full_path)) {
                $this->_setError(505);
                return false;
            }
        }

        if ($this->_image && !is_image($full_path, array_flip($this->_image_types))) {
            @unlink($full_path);
            $this->_setError(507);
            return false;
        }

        $this->_setError(506);
        return true;
    }

    function getError()
    {
        return $this->_error;
    }

    function _getError($val = '未知错误')
    {
        $type = $_FILES[$this->_field]['error'];
        $error_types = array(
            0 => '没有错误发生，文件上传成功。',
            1 => '上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值。',
            2 => '上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值。',
            3 => '文件只有部分被上传。',
            4 => '没有文件被上传。',
            6 => '找不到临时文件夹。',
            7 => '文件写入失败'
        );
        if (!isset($error_types[$type])) {
            $error_types[$type] = $val;
        }
        $this->_error = $error_types[$type];
        return true;
    }

    function _setError($type, $val = '')
    {

        $error_types = array(
            501 => '没有上载的文件',
            502 => '不允许的扩展名',
            503 => '上载的文件超过了服务器最大限制的值，上载失败！' . $val,
            504 => '目录不可写',
            505 => '移动文件时出错！' . $val,
            506 => '上载成功',
            507 => '上载的图片文件不是有效的图片文件',
            508 => '上载的文件不是有效的附件文件',
        );

        if (false == isset($error_types[$type])) {
            $error_types[$type] = $val;
        }
        $this->_error_no = $type;

        $this->_error = $error_types[$type];
        return true;
    }

    function getErrorNo()
    {
        return $this->_error_no;
    }
}