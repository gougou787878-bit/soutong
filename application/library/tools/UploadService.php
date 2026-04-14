<?php
namespace tools;

class UploadService
{
    public $_error;
    public $_new_name;
    public $_save_name;
    public $_path;
    public $_field;
    public $_max_size;
    public $_image;
    public $_ext;
    public $_ext_types;
    public $_image_types;
    function __construct() {
        ;
    }
    function init($path, $field = 'upload', $image = false, $attach = false) {
        $this->_path       =  $path;
        $this->_field      =  $field;
        $this->_max_size   =  2048;
        $this->_image      =  $image;
        $this->_attach     =  $attach;
        $this->_ext        =  '';
        $this->_new_name   =  '';
        $this->_save_name  =  '';
        $this->_attach_types = explode('|','ipa|apk|zip|rar|txt|doc|xls|pdf|ppt|docx|xlsx|pptx');
        $this->_ext_types   = array('cgi', 'pl', 'js', 'asp', 'php', 'html', 'htm', 'jsp', 'jar', 'txt', 'rar', 'zip');
        $this->_image_types = array('gif', 'jpg', 'jpeg', 'png');
    }


    function setMaxSize($size)
    {
        $this->_max_size = (int) $size;
        return true;
    }


    function setExtTypes($array)
    {
        if(false == is_array($array))
        {
            return false;
        }

        $this->_ext_types =& $array;
        return true;
    }


    function setImgTypes($array)
    {
        if(false == is_array($array))
        {
            return false;
        }

        $this->_image_types =& $array;
        return true;
    }


    function setAttachTypes($array)
    {
        if(false == is_array($array))
        {
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
        if(false == is_writable($this->_path))
        {
            $this->_setError(504);
            return false;
        }

        if(false == isset($_FILES[$this->_field]))
        {
            $this->_setError(501);
            return false;
        }

        $name = $_FILES[$this->_field]['name'];
        $size = $_FILES[$this->_field]['size'];
        $type = $_FILES[$this->_field]['type'];
        $temp = $_FILES[$this->_field]['tmp_name'];

        $type = preg_replace("/^(.+?);.*$/", "\\1", $type);

        if(false == $name || $name == 'none')
        {
            $this->_setError(501);
            return false;
        }

        $_exts = explode('.', $name);
        $this->_ext = strtolower(end($_exts));

        if(false == $this->_ext)
        {
            $this->_setError(502);
            return false;
        }
        if(false == $this->_image)
        {
            if(false == $this->_attach) {
                if(false == in_array($this->_ext, array_merge($this->_image_types, $this->_ext_types))) {
                    $this->_setError(502);
                    return false;
                }
            } else {
                if(false == in_array($this->_ext, $this->_attach_types)){
                    $this->_setError(508);
                    return false;
                }
            }
        } else {
            if(false == in_array($this->_ext, $this->_image_types))
            {
                $this->_setError(507);
                return false;
            }
        }

        if($this->_max_size && $this->_max_size * 1000 < $size)
        {
            $this->_setError(503);
            return false;
        }
        if(false == $this->_new_name)
        {
            $this->_save_name = $name;
            $full_path        = $this->_path . $name;
        }else {
            $this->_save_name = $this->_new_name;
            $full_path        = $this->_path     . $this->_save_name;
        }

        if(false == move_uploaded_file($temp, $full_path))
        {
            if(false == copy($temp,$full_path))
            {
                $this->_setError(505);
                return false;
            }
        }


        if($this->_image && !is_image($full_path)) {
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

    function _GetError()
    {
        $type=$_FILES[$this->_field]['error'];
        $error_types=array(0=>'没有错误发生，文件上传成功。',
            1=>'上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值。',
            2=>'上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值。',
            3=>'文件只有部分被上传。',
            4=>'没有文件被上传。',
            6=>'找不到临时文件夹。',
            7=>'文件写入失败');
        if(false == isset($error_types[$type]))
        {
            $error_types[$type] = $val;
        }
        $this->_error = $error_types[$type];
        return true;
    }

    function _setError($type, $val = '')
    {

        $error_types = array(501 => '没有上载的文件',
            502 => '不允许的扩展名',
            503 => '上载的文件超过了服务器最大限制的值，上载失败！'.$val,
            504 => '目录不可写',
            505 => '移动文件时出错！'.$val,
            506 => '上载成功',
            507 => '上载的图片文件不是有效的图片文件',
            508 => '上载的文件不是有效的附件文件',
        );

        if(false == isset($error_types[$type]))
        {
            $error_types[$type] = $val;
        }
        $this->_error_no=$type;

        $this->_error = $error_types[$type];
        return true;
    }

    function getErrorNo()
    {
        return $this->_error_no;
    }
}

?>