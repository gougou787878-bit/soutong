<?php
/**
 * 文件缓存相关操作类
 * This is NOT a freeware, use is subject to license terms
 */
class LibCache {

    public $io = null;
    public $path = '';
    public $prefix;
    public $memory;

    function __construct() {
        $this->prefix = 'cache_file_';
        $root_path = APP_PATH;
        $this->path = $root_path . 'storage/cache/cache_file/';
        $this->io = new Libio();
    }

    function get($key) {
        static $datas = null;
        if(!isset($datas[$key])) {
            if($this->memory) {
                $cache = $this->memory->get($key, $this->prefix);
            } else {
                if( file_exists($this->_file($key)) )
                    require_once($this->_file($key));
            }
            if(!isset($cache)||!$cache) {
                return false;
            }
            $datas[$key] = $cache['val'];
            if($datas[$key]['life']>0 && ($cache['dateline'] + $datas[$key]['life'] < TIMESTAMP)) {
                $datas[$key]['data'] = false;
            }
        }
        return $datas[$key]['data'];
    }

    function set($key, $val, $life=0) {
        $life = max(0, (int) $life);
        if($life < 1 || $life > 2592000) {
            $life = 2592000;
        }
        $datas = array(
            'key' => $key,
            'dateline' => TIMESTAMP,
            'val' => array('life'=>$life, 'data'=>$val, ),
        );

        if($this->memory) {
            $ret = $this->memory->set($key, $datas, $life, $this->prefix);
        } else {
            $data = "<?php if(!defined('IN_APP')) { exit('invalid request'); } \n
			\$cache = " . var_export($datas, true) . ";\n?>";
            $file = $this->_file($key);
            if(!is_dir(($dir = dirname($file)))) {
                $this->io->MakeDir($dir);
            }
            $ret = $this->io->WriteFile($file, $data);
            if(false === $ret) {
                //exit("缓存文件 $file 写入失败，请检查相应目录的可写权限。");
                trigger_error("缓存文件 $file 写入失败，请检查相应目录的可写权限。", E_USER_ERROR);
            }
            @chmod($file, 0777);
        }
        return $ret;
    }

    function del($key, $more=0) {
        if($this->memory) {
            $this->memory->del($key, $this->prefix);
        } else {
            if($more && is_dir(($dir = $this->path . $key))) {
                $ret = $this->io->ClearDir($dir);
            } else {
                $ret = $this->io->DeleteFile($this->_file($key));
            }
        }
        return $ret;
    }
    function rm($key, $more=0) {
        return $this->del($key, $more);
    }

    function clean() {
        return $this->io->ClearDir($this->path);
    }
    function clear() {
        return $this->clean();
    }

    function _file($key) {
        return $this->path . $key . '.cache.php';
    }
}
