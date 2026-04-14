<?php
namespace tools;
/**
 * IP 地理位置查询类
 * https://github.com/itbdw/ip-database
 * @author    马秉尧，赵彬言<itbudaoweng@gmail.com>
 * @version   2.0
 * @copyright 2005 CoolCode.CN，2012-2017 itbdw.com
 *Array
(
[ip] => 112.85.xxx.xxx
[country] => 中国
[province] => 江苏
[city] => 徐州市
[county] =>
[isp] => 联通
[area] => 中国江苏徐州市联通
)
 * Array
(
[error] => ip invalid
)
 */
/**
 *
 * 输出编码为 UTF-8
 *
 * Class IpLocation
 * print_r($ipLocation->getLocation($ip));
 * @package itbdw\IpLocation
 */
class IpLocation
{
    private static $instance;

    /**
     * qqwry.dat文件指针
     *
     * @var resource
     */
    private $fp;

    /**
     * 第一条IP记录的偏移地址
     *
     * @var int
     */
    private $firstip;

    /**
     * 最后一条IP记录的偏移地址
     *
     * @var int
     */
    private $lastip;

    /**
     * IP记录的总条数（不包含版本信息记录）
     *
     * @var int
     */
    private $totalip;

    /**
     * 运营商词典
     *
     * @var array
     */
    private $dict_isp = [
        '联通',
        '移动',
        '铁通',
        '电信',
        '长城',
        '聚友',
    ];

    /**
     * 中国直辖市
     *
     * @var array
     */
    private $dict_city_directly = [
        '北京',
        '天津',
        '重庆',
        '上海',
        '香港',
        '澳门',
        '台湾'
    ];

    /**
     * 中国省份
     *
     * @var array
     */
    private $dict_province = [
        '北京',
        '天津',
        '重庆',
        '上海',
        '河北',
        '山西',
        '辽宁',
        '吉林',
        '黑龙江',
        '江苏',
        '浙江',
        '安徽',
        '福建',
        '江西',
        '山东',
        '河南',
        '湖北',
        '湖南',
        '广东',
        '海南',
        '四川',
        '贵州',
        '云南',
        '陕西',
        '甘肃',
        '青海',
        '台湾',
        '内蒙古',
        '广西',
        '宁夏',
        '新疆',
        '西藏',
        '香港',
        '澳门',
    ];

    /**
     * 构造函数，打开 qqwry.dat 文件并初始化类中的信息
     *
     * @return IpLocation
     */
    public  function __construct($filepath = null)
    {
        $this->init($filepath);
    }

    private function init($filepath)
    {
        $filename = __DIR__ . '/IpPosition/qqwry.dat';
        if ($filepath) {
            $filename = $filepath;
        }

        if (!file_exists($filename)) {
            trigger_error("Failed open ip database file!");
            return;
        }

        $this->fp = 0;
        if (($this->fp = fopen($filename, 'rb')) !== false) {
            $this->firstip = $this->getlong();
            $this->lastip = $this->getlong();
            $this->totalip = ($this->lastip - $this->firstip) / 7;
        }
    }

    /**
     * 返回读取的长整型数
     *
     * @access private
     * @return int
     */
    private function getlong()
    {
        //将读取的little-endian编码的4个字节转化为长整型数
        $result = unpack('Vlong', fread($this->fp, 4));

        return $result['long'];
    }

    /**
     * @param $ip
     * @return array
     */
    public static function getLocation($ip, $filepath = null)
    {
        if (self::$instance === null) {
            self::$instance = new self($filepath);
        }

        return self::$instance->getAddr($ip);
    }

    /**
     * 如果ip错误，返回 $result['error'] 信息
     * province city county isp 对中国以外的ip无法识别
     * <code>
     * $result 是返回的数组
     * $result['ip']            输入的ip
     * $result['country']       国家 如 中国
     * $result['province']      省份信息 如 河北省
     * $result['city']          市区 如 邢台市
     * $result['county']        郡县 如 威县
     * $result['isp']           运营商 如 联通
     * $result['area']          最完整的信息 如 中国河北省邢台市威县新科网吧(北外街)
     * </code>
     *
     * @param $ip
     * @return array
     */
    private function getAddr($ip)
    {
        $result = [];
        $is_china           = false;
        $seperator_sheng    = '省';
        $seperator_shi      = '市';
        $seperator_xian     = '县';
        $seperator_qu       = '区';

        if (!$this->isValidIpV4($ip)) {
            if( $this->validateIPv6($ip) ){
                $ipV6 = new ipdbv6();
                $jsonDecode = $ipV6->query($ip);
                //$jsonDecode = json_decode($str,true);
              /*  $result['addr'] = $location = $jsonDecode['addr'][0] ?? '';
                if(stripos($location,'中国')!==false ){
                    $country = '中国';
                    $location = str_replace('中国','',$location);
                }

                if(stripos($location,'省')!==false ){
                    $provinces = explode('省',$location);
                    $province = isset($provinces[0])?$provinces[0]:'';
                    $location = isset($provinces[1])?$provinces[1]:'';
                }


                if(stripos($location,'市')!==false ){
                    $citys = explode('市',$location);
                    $city = isset($citys[0])?$citys[0]:'';
                    $area = isset($citys[1])?$citys[1]:'';

                }

                $result['city']     = $city;
                $result['province'] = $province;
                $result['country']  = $country;
                $result['area']     = $area;*/


                $location['org_country'] = $location['country'] = $jsonDecode['addr'][0] ?? ''; //北京市朝阳区

                $location['org_area'] = $location['area'] = $jsonDecode['addr'][1]; // 金桥国际小区

                $location['ip'] = $ip; // 金桥国际小区

                $location['province'] = $location['city'] = $location['county'] = '';

                $_tmp_province = explode($seperator_sheng, $location['country']);
                //存在 省 标志 xxx省yyyy 中的yyyy
                if (isset($_tmp_province[1])) {
                    $is_china = true;
                    //省
                    $location['province'] = $_tmp_province[0]; //河北

                    if (strpos($_tmp_province[1], $seperator_shi) !== false) {
                        $_tmp_city = explode($seperator_shi, $_tmp_province[1]);
                        //市
                        $location['city'] = $_tmp_city[0];

                        //县
                        if (isset($_tmp_city[1])) {
                            if (strpos($_tmp_city[1], $seperator_xian) !== false) {
                                $_tmp_county = explode($seperator_xian, $_tmp_city[1]);
                                $location['county'] = $_tmp_county[0] . $seperator_xian;
                            }

                            //区
                            if (!$location['county'] && strpos($_tmp_city[1], $seperator_qu) !== false) {
                                $_tmp_qu = explode($seperator_qu, $_tmp_city[1]);
                                $location['county'] = $_tmp_qu[0] . $seperator_qu;
                            }
                        }
                    }

                } else {
                    //处理内蒙古不带省份类型的和直辖市
                    foreach ($this->dict_province as $key => $value) {

                        if (false !== strpos($location['country'], $value)) {
                            $is_china = true;
                            //直辖市
                            if (in_array($value, $this->dict_city_directly)) {
                                $_tmp_province = explode($seperator_shi, $location['country']);
                                //直辖市
                                $location['province'] = $_tmp_province[0];

                                //市辖区
                                if (isset($_tmp_province[1])) {
                                    if (strpos($_tmp_province[1], $seperator_qu) !== false) {
                                        $_tmp_qu = explode($seperator_qu, $_tmp_province[1]);
                                        $location['city'] = $_tmp_qu[0] . $seperator_qu;
                                    }
                                }
                            } else {
                                //省
                                $location['province'] = $value;

                                //没有省份标志 只能替换
                                $_tmp_city = str_replace($location['province'], '', $location['country']);

                                //防止直辖市捣乱 上海市xxx区 =》 市xx区
                                $_tmp_shi_pos = mb_stripos($_tmp_city, $seperator_shi);
                                if ($_tmp_shi_pos === 0) {
                                    $_tmp_city = mb_substr($_tmp_city, 1);
                                }

                                //内蒙古 类型的 获取市县信息
                                if (strpos($_tmp_city, $seperator_shi) !== false) {
                                    //市
                                    $_tmp_city = explode($seperator_shi, $_tmp_city);

                                    $location['city'] = $_tmp_city[0];

                                    //县
                                    if (isset($_tmp_city[1])) {
                                        if (strpos($_tmp_city[1], $seperator_xian) !== false) {
                                            $_tmp_county = explode($seperator_xian, $_tmp_city[1]);
                                            $location['county'] = $_tmp_county[0] . $seperator_xian;
                                        }

                                        //区
                                        if (!$location['county'] && strpos($_tmp_city[1], $seperator_qu) !== false) {
                                            $_tmp_qu = explode($seperator_qu, $_tmp_city[1]);
                                            $location['county'] = $_tmp_qu[0] . $seperator_qu;
                                        }
                                    }
                                }
                            }

                            break;
                        }
                    }

                }

                if ($is_china) {
                    $location['country'] = '中国';
                }

                $location['isp'] = $this->getIsp($location['area']);

                $result['ip'] = $location['ip'];
                $result['country'] = $location['country'];
                $result['province'] = $location['province'];
                $result['city'] = str_replace('中国区','',$location['city']);
                $result['county'] = $location['county'];
                $result['isp'] = $location['isp'];

                $result['area'] = $location['country'] . $location['province'] . $location['city'] . $location['county'] . $location['org_area'];

            }else{

            }
        } else {
            $location = $this->getlocationfromip($ip); // $location[country] [area]
            if (!$location) {
                $result['error'] = 'file open failed';
                return $result;
            }

            $location['org_country'] = $location['country']; //北京市朝阳区

            $location['org_area'] = $location['area']; // 金桥国际小区

            $location['province'] = $location['city'] = $location['county'] = '';

            $_tmp_province = explode($seperator_sheng, $location['country']);
            //存在 省 标志 xxx省yyyy 中的yyyy
            if (isset($_tmp_province[1])) {
                $is_china = true;
                //省
                $location['province'] = $_tmp_province[0]; //河北

                if (strpos($_tmp_province[1], $seperator_shi) !== false) {
                    $_tmp_city = explode($seperator_shi, $_tmp_province[1]);
                    //市
                    $location['city'] = $_tmp_city[0];

                    //县
                    if (isset($_tmp_city[1])) {
                        if (strpos($_tmp_city[1], $seperator_xian) !== false) {
                            $_tmp_county = explode($seperator_xian, $_tmp_city[1]);
                            $location['county'] = $_tmp_county[0] . $seperator_xian;
                        }

                        //区
                        if (!$location['county'] && strpos($_tmp_city[1], $seperator_qu) !== false) {
                            $_tmp_qu = explode($seperator_qu, $_tmp_city[1]);
                            $location['county'] = $_tmp_qu[0] . $seperator_qu;
                        }
                    }
                }

            } else {
                //处理内蒙古不带省份类型的和直辖市
                foreach ($this->dict_province as $key => $value) {

                    if (false !== strpos($location['country'], $value)) {
                        $is_china = true;
                        //直辖市
                        if (in_array($value, $this->dict_city_directly)) {
                            $_tmp_province = explode($seperator_shi, $location['country']);
                            //直辖市
                            $location['province'] = $_tmp_province[0];

                            //市辖区
                            if (isset($_tmp_province[1])) {
                                if (strpos($_tmp_province[1], $seperator_qu) !== false) {
                                    $_tmp_qu = explode($seperator_qu, $_tmp_province[1]);
                                    $location['city'] = $_tmp_qu[0] . $seperator_qu;
                                }
                            }
                        } else {
                            //省
                            $location['province'] = $value;

                            //没有省份标志 只能替换
                            $_tmp_city = str_replace($location['province'], '', $location['country']);

                            //防止直辖市捣乱 上海市xxx区 =》 市xx区
                            $_tmp_shi_pos = mb_stripos($_tmp_city, $seperator_shi);
                            if ($_tmp_shi_pos === 0) {
                                $_tmp_city = mb_substr($_tmp_city, 1);
                            }

                            //内蒙古 类型的 获取市县信息
                            if (strpos($_tmp_city, $seperator_shi) !== false) {
                                //市
                                $_tmp_city = explode($seperator_shi, $_tmp_city);

                                $location['city'] = $_tmp_city[0];

                                //县
                                if (isset($_tmp_city[1])) {
                                    if (strpos($_tmp_city[1], $seperator_xian) !== false) {
                                        $_tmp_county = explode($seperator_xian, $_tmp_city[1]);
                                        $location['county'] = $_tmp_county[0] . $seperator_xian;
                                    }

                                    //区
                                    if (!$location['county'] && strpos($_tmp_city[1], $seperator_qu) !== false) {
                                        $_tmp_qu = explode($seperator_qu, $_tmp_city[1]);
                                        $location['county'] = $_tmp_qu[0] . $seperator_qu;
                                    }
                                }
                            }
                        }

                        break;
                    }
                }

            }

            if ($is_china) {
                $location['country'] = '中国';
            }

            $location['isp'] = $this->getIsp($location['area']);

            $result['ip'] = $location['ip'];
            $result['country'] = $location['country'];
            $result['province'] = $location['province'];
            $result['city'] = str_replace('中国区','',$location['city']);
            $result['county'] = $location['county'];
            $result['isp'] = $location['isp'];

            $result['area'] = $location['country'] . $location['province'] . $location['city'] . $location['county'] . $location['org_area'];
        }
        foreach ($this->dict_city_directly as $row){
            if (false !== strpos($result['area'],$row)) {
                $result['city'] = $row;
            }
        }
        return $result; //array
    }

    /**
     * @param $ip
     * @return bool
     */
    private function isValidIpV4($ip)
    {
        $flag = false !== filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);

        return $flag;
    }

    /**
     * @param $value
     * @return bool
     */
    private function validateIPv4($value)
    {
        if (preg_match('/^([01]{8}\.){3}[01]{8}\z/i', $value)) {
            //二进制数  00000000.00000000.00000000.00000000
            $value = bindec(substr($value, 0, 8)) . '.' . bindec(substr($value, 9, 8)) . '.'
                . bindec(substr($value, 18, 8)) . '.' . bindec(substr($value, 27, 8));
        } elseif (preg_match('/^([0-9a-f]{2}\.){3}[0-9a-f]{2}\z/i', $value)) {
            //十六进制格式 ff.ff.ff.ff
            $value = hexdec(substr($value, 0, 2)) . '.' . hexdec(substr($value, 3, 2)) . '.'
                . hexdec(substr($value, 6, 2)) . '.' . hexdec(substr($value, 9, 2));
        }

        $ip2long = ip2long($value);
        if ($ip2long === false) {
            return false;
        }

        return ($value == long2ip($ip2long));
    }

    /**
     * @param $value
     * @return bool|int
     */
    private function validateIPv6($value)
    {
        if (strlen($value) < 3) {
            return $value == '::';
        }

        if (strpos($value, '.')) {
            $lastcolon = strrpos($value, ':');
            if (! ($lastcolon && $this->validateIPv4(substr($value, $lastcolon + 1)))) {
                return false;
            }

            $value = substr($value, 0, $lastcolon) . ':0:0';
        }

        if (strpos($value, '::') === false) {
            return preg_match('/\A(?:[a-f0-9]{1,4}:){7}[a-f0-9]{1,4}\z/i', $value);
        }

        $colonCount = substr_count($value, ':');
        if ($colonCount < 8) {
            return preg_match('/\A(?::|(?:[a-f0-9]{1,4}:)+):(?:(?:[a-f0-9]{1,4}:)*[a-f0-9]{1,4})?\z/i', $value);
        }

        //特殊情况双冒号结束或开始
        if ($colonCount == 8) {
            return preg_match('/\A(?:::)?(?:[a-f0-9]{1,4}:){6}[a-f0-9]{1,4}(?:::)?\z/i', $value);
        }

        return false;
    }

    /**
     * 根据所给 IP 地址或域名返回所在地区信息
     *
     * @access public
     * @param string $ip
     * @return array
     */
    private function getlocationfromip($ip)
    {
        if (!$this->fp) {
            return null;
        } // 如果数据文件没有被正确打开，则直接返回空

        $location['ip'] = $ip;

        $ip = $this->packip($location['ip']); // 将输入的IP地址转化为可比较的IP地址
        // 不合法的IP地址会被转化为255.255.255.255
        // 对分搜索
        $l = 0; // 搜索的下边界
        $u = $this->totalip; // 搜索的上边界
        $findip = $this->lastip; // 如果没有找到就返回最后一条IP记录（qqwry.dat的版本信息）
        while ($l <= $u) { // 当上边界小于下边界时，查找失败
            $i = floor(($l + $u) / 2); // 计算近似中间记录
            fseek($this->fp, $this->firstip + $i * 7);
            $beginip = strrev(fread($this->fp, 4)); // 获取中间记录的开始IP地址
            // strrev函数在这里的作用是将little-endian的压缩IP地址转化为big-endian的格式
            // 以便用于比较，后面相同。
            if ($ip < $beginip) { // 用户的IP小于中间记录的开始IP地址时
                $u = $i - 1; // 将搜索的上边界修改为中间记录减一
            } else {
                fseek($this->fp, $this->getlong3());
                $endip = strrev(fread($this->fp, 4)); // 获取中间记录的结束IP地址
                if ($ip > $endip) { // 用户的IP大于中间记录的结束IP地址时
                    $l = $i + 1; // 将搜索的下边界修改为中间记录加一
                } else { // 用户的IP在中间记录的IP范围内时
                    $findip = $this->firstip + $i * 7;
                    break; // 则表示找到结果，退出循环
                }
            }
        }

        //获取查找到的IP地理位置信息
        fseek($this->fp, $findip);
        $location['beginip'] = long2ip($this->getlong()); // 用户IP所在范围的开始地址
        $offset = $this->getlong3();
        fseek($this->fp, $offset);
        $location['endip'] = long2ip($this->getlong()); // 用户IP所在范围的结束地址
        $byte = fread($this->fp, 1); // 标志字节
        switch (ord($byte)) {
            case 1: // 标志字节为1，表示国家和区域信息都被同时重定向
                $countryOffset = $this->getlong3(); // 重定向地址
                fseek($this->fp, $countryOffset);
                $byte = fread($this->fp, 1); // 标志字节
                switch (ord($byte)) {
                    case 2: // 标志字节为2，表示国家信息被重定向
                        fseek($this->fp, $this->getlong3());
                        $location['country'] = $this->getstring();
                        fseek($this->fp, $countryOffset + 4);
                        $location['area'] = $this->getarea();
                        break;
                    default: // 否则，表示国家信息没有被重定向
                        $location['country'] = $this->getstring($byte);
                        $location['area'] = $this->getarea();
                        break;
                }
                break;
            case 2: // 标志字节为2，表示国家信息被重定向
                fseek($this->fp, $this->getlong3());
                $location['country'] = $this->getstring();
                fseek($this->fp, $offset + 8);
                $location['area'] = $this->getarea();
                break;
            default: // 否则，表示国家信息没有被重定向
                $location['country'] = $this->getstring($byte);
                $location['area'] = $this->getarea();
                break;
        }

        $location['country'] = iconv("GBK", "UTF-8", $location['country']);
        $location['area'] = iconv("GBK", "UTF-8", $location['area']);

        if ($location['country'] == " CZ88.NET" || $location['country'] == "纯真网络") { // CZ88.NET表示没有有效信息
            $location['country'] = "无数据";
        }
        if ($location['area'] == " CZ88.NET") {
            $location['area'] = "";
        }

        return $location;
    }

    /**
     * 返回压缩后可进行比较的IP地址
     *
     * @access private
     * @param string $ip
     * @return string
     */
    private function packip($ip)
    {
        // 将IP地址转化为长整型数，如果在PHP5中，IP地址错误，则返回False，
        // 这时intval将Flase转化为整数-1，之后压缩成big-endian编码的字符串
        return pack('N', intval($this->ip2long($ip)));
    }

    /**
     * Ip 地址转为数字地址
     * php 的 ip2long 这个函数有问题
     * 133.205.0.0 ==>> 2244804608
     *
     * @param string $ip 要转换的 ip 地址
     * @return int    转换完成的数字
     */
    private function ip2long($ip)
    {
        $ip_arr = explode('.', $ip);
        $iplong = (16777216 * intval($ip_arr[0])) + (65536 * intval($ip_arr[1])) + (256 * intval($ip_arr[2])) + intval($ip_arr[3]);

        return $iplong;
    }

    /**
     * 返回读取的3个字节的长整型数
     *
     * @access private
     * @return int
     */
    private function getlong3()
    {
        //将读取的little-endian编码的3个字节转化为长整型数
        $result = unpack('Vlong', fread($this->fp, 3) . chr(0));

        return $result['long'];
    }

    /**
     * 返回读取的字符串
     *
     * @access private
     * @param string $data
     * @return string
     */
    private function getstring($data = "")
    {
        $char = fread($this->fp, 1);
        while (ord($char) > 0) { // 字符串按照C格式保存，以\0结束
            $data .= $char; // 将读取的字符连接到给定字符串之后
            $char = fread($this->fp, 1);
        }

        return $data;
    }

    /**
     * 返回地区信息
     *
     * @access private
     * @return string
     */
    private function getarea()
    {
        $byte = fread($this->fp, 1); // 标志字节
        switch (ord($byte)) {
            case 0: // 没有区域信息
                $area = "";
                break;
            case 1:
            case 2: // 标志字节为1或2，表示区域信息被重定向
                fseek($this->fp, $this->getlong3());
                $area = $this->getstring();
                break;
            default: // 否则，表示区域信息没有被重定向
                $area = $this->getstring($byte);
                break;
        }

        return $area;
    }

    /**
     * @param $str
     * @return string
     */
    private function getIsp($str)
    {
        $ret = '';

        foreach ($this->dict_isp as $k => $v) {
            if (false !== strpos($str, $v)) {
                $ret = $v;
                break;
            }
        }

        return $ret;
    }

    /**
     * 析构函数，用于在页面执行结束后自动关闭打开的文件。
     */
    public function __destruct()
    {
        if ($this->fp) {
            fclose($this->fp);
        }
        $this->fp = 0;
    }
}

class ipdbv6
{
    public $file;
    public $fd;
    public $total;
    public $db4;
    // 索引区
    public $index_start_offset;
    public $index_end_offset;
    public $offlen;
    public $iplen;

    public function __construct($file = "/IpPosition/ipv6wry.db", $dbipv4 = null)
    {
        $file = __DIR__ . $file;
        if (PHP_INT_SIZE < 8) {
            die("本程序不支持PHP_INT_SIZE小于8的环境，请使用64位PHP。Windows系统请使用7.0.0以上版本。");
        }
        if (version_compare(PHP_VERSION, "5.6.3", "<")) {
            die("您的PHP版本过低，请使用5.6.3以上版本。");
        }
        if (!file_exists($file) or !is_readable($file)) {
            throw new Exception("{$file} does not exist, or is not readable");
        }
        $this->file = $file;
        $this->fd = fopen($file, "rb");
        $this->index_start_offset = $this->read8(16);
        $this->offlen = $this->read1(6);
        $this->iplen = $this->read1(7);
        $this->total = $this->read8(8);
        $this->index_end_offset = $this->index_start_offset + ($this->iplen + $this->offlen) * $this->total;
        $db4 = $dbipv4;
    }

    public function query($ip)
    {
        $ip_bin = inet_pton($ip);
        if ($ip_bin == false) {
            throw new Exception("错误的IP地址: $ip");
        }
        if (strlen($ip_bin) != 16) {
            throw new Exception("错误的IPv6地址: $ip");
        }
        $ip_num_arr = unpack("J2", $ip_bin);
        $ip_num1 = $ip_num_arr[1];
        $ip_num2 = $ip_num_arr[2];
        $ip_find = $this->find($ip_num1, $ip_num2, 0, $this->total);
        $ip_offset = $this->index_start_offset + $ip_find * ($this->iplen + $this->offlen);
        $ip_offset2 = $ip_offset + $this->iplen + $this->offlen;
        $ip_start = inet_ntop(pack("J2", $this->read8($ip_offset), 0));
        try {
            $ip_end = inet_ntop(pack("J2", $this->read8($ip_offset2) - 1, 0));
        } catch (Exception $e) {
            $ip_end = "FFFF:FFFF:FFFF:FFFF::";
        }
        $ip_record_offset = $this->read8($ip_offset + $this->iplen, $this->offlen);
        $ip_addr = $this->read_record($ip_record_offset);
        $ip_addr_disp = $ip_addr[0] . " " . $ip_addr[1];
        return array("start" => $ip_start, "end" => $ip_end, "addr" => $ip_addr, "disp" => $ip_addr_disp);
    }

    /**
     * 读取记录
     */
    public function read_record($offset)
    {
        $record = array(0 => "", 1 => "");
        $flag = $this->read1($offset);
        if ($flag == 1) {
            $location_offset = $this->read8($offset + 1, $this->offlen);
            return read_record($location_offset);
        } else {
            $record[0] = $this->read_location($offset);
            if ($flag == 2) {
                $record[1] = $this->read_location($offset + $this->offlen + 1);
            } else {
                $record[1] = $this->read_location($offset + strlen($record[0]) + 1);
            }
        }
        return $record;
    }

    /**
     * 读取地区
     */
    public function read_location($offset)
    {
        if ($offset == 0) {
            return "";
        }
        $flag = $this->read1($offset);
        // 出错
        if ($flag == 0) {
            return "";
        }
        // 仍然为重定向
        if ($flag == 2) {
            $offset = $this->read8($offset + 1, $this->offlen);
            return $this->read_location($offset);
        }
        $location = $this->readstr($offset);
        return $location;
    }

    /**
     * 查找 ip 所在的索引
     */
    public function find($ip_num1, $ip_num2, $l, $r)
    {
        if ($l + 1 >= $r) {
            return $l;
        }
        $m = intval(($l + $r) / 2);
        $m_ip1 = $this->read8($this->index_start_offset + $m * ($this->iplen + $this->offlen), $this->iplen);
        $m_ip2 = 0;
        if ($this->iplen <= 8) {
            $m_ip1 <<= 8 * (8 - $this->iplen);
        } else {
            $m_ip2 = $this->read8($this->index_start_offset + $m * ($this->iplen + $this->offlen) + 8, $this->iplen - 8);
            $m_ip2 <<= 8 * (16 - $this->iplen);
        }
        if ($this->uint64cmp($ip_num1, $m_ip1) < 0) {
            return $this->find($ip_num1, $ip_num2, $l, $m);
        } else if ($this->uint64cmp($ip_num1, $m_ip1) > 0) {
            return $this->find($ip_num1, $ip_num2, $m, $r);
        } else if ($this->uint64cmp($ip_num2, $m_ip2) < 0) {
            return $this->find($ip_num1, $ip_num2, $l, $m);
        } else {
            return $this->find($ip_num1, $ip_num2, $m, $r);
        }
    }

    public function readraw($offset = null, $size = 0)
    {
        if (!is_null($offset)) {
            fseek($this->fd, $offset);
        }
        return fread($this->fd, $size);
    }

    public function read1($offset = null)
    {
        if (!is_null($offset)) {
            fseek($this->fd, $offset);
        }
        $a = fread($this->fd, 1);
        return @unpack("C", $a)[1];
    }

    public function read8($offset = null, $size = 8)
    {
        if (!is_null($offset)) {
            fseek($this->fd, $offset);
        }
        $a = fread($this->fd, $size) . "\0\0\0\0\0\0\0\0";
        return @unpack("P", $a)[1];
    }

    public function readstr($offset = null)
    {
        if (!is_null($offset)) {
            fseek($this->fd, $offset);
        }
        $str = "";
        $chr = $this->read1($offset);
        while ($chr != 0) {
            $str .= chr($chr);
            $offset++;
            $chr = $this->read1($offset);
        }
        return $str;
    }

    public function ip2num($ip)
    {
        return unpack("N", inet_pton($ip))[1];
    }

    public function inet_ntoa($nip)
    {
        $ip = array();
        for ($i = 3; $i > 0; $i--) {
            $ip_seg = intval($nip / pow(256, $i));
            $ip[] = $ip_seg;
            $nip -= $ip_seg * pow(256, $i);
        }
        $ip[] = $nip;
        return join(".", $ip);
    }

    public function uint64cmp($a, $b)
    {
        if ($a >= 0 && $b >= 0 || $a < 0 && $b < 0) {
            return $a <=> $b;
        } else if ($a >= 0 && $b < 0) {
            return -1;
        } else if ($a < 0 && $b >= 0) {
            return 1;
        }
    }

    public function __destruct()
    {
        if ($this->fd) {
            fclose($this->fd);
        }
    }
}


class ipdbv4 {
    public $file;
    public $fd;
    public $total;
    // 索引区
    public $index_start_offset;
    public $index_end_offset;
    public $offlen;
    public $iplen;
    public function __construct($file="qqwry.db") {
        if (PHP_INT_SIZE < 8) {
            die("本程序不支持PHP_INT_SIZE小于8的环境。请使用适当版本的64位PHP。");
        }
        if (!file_exists($file) or !is_readable($file)) {
            throw new Exception("{$file} does not exist, or is not readable");
        }
        $this->file = $file;
        $this->fd = fopen($file, "rb");
        $this->index_start_offset = $this->read8(16);
        $this->offlen = $this->read1(6);
        $this->iplen = $this->read1(7);
        $this->total = $this->read8(8);
        $this->index_end_offset = $this->index_start_offset + ($this->iplen + $this->offlen) * $this->total;
    }
    public function query($ip) {
        $ip_bin = inet_pton($ip);
        if ($ip_bin == false) {
            throw new Exception("错误的IP地址: $ip");
        }
        if (strlen($ip_bin) != 4) {
            throw new Exception("错误的IPv4地址: $ip");
        }
        $ip_num = unpack("N", $ip_bin)[1];
        $ip_find = $this->find($ip_num, 0, $this->total);
        $ip_offset = $this->index_start_offset + $ip_find * ($this->iplen + $this->offlen);
        $ip_offset2 = $ip_offset + $this->iplen + $this->offlen;
        $ip_start = inet_ntop(pack("N", $this->read4($ip_offset)));
        try {
            $ip_end = inet_ntop(pack("N", $this->read4($ip_offset2) - 1));
        } catch (Exception $e) {
            $ip_end = "255.255.255.255";
        }
        $ip_record_offset = $this->read8($ip_offset + $this->iplen, $this->offlen);
        $ip_addr = $this->read_record($ip_record_offset);
        $ip_addr_disp = $ip_addr[0] . " " . $ip_addr[1];
        return array("start" => $ip_start, "end" => $ip_end, "addr" => $ip_addr, "disp" => $ip_addr_disp);
    }
    /**
     * 读取记录
     */
    public function read_record($offset) {
        $record = array(0 => "", 1 => "");
        $flag = $this->read1($offset);
        if ($flag == 1) {
            $location_offset = $this->read8($offset + 1, $this->offlen);
            return $this->read_record($location_offset);
        } else {
            $record[0] = $this->read_location($offset);
            if ($flag == 2) {
                $record[1] = $this->read_location($offset + $this->offlen + 1);
            } else {
                $record[1] = $this->read_location($offset + strlen($record[0]) + 1);
            }
        }
        return $record;
    }
    /**
     * 读取地区
     */
    public function read_location($offset) {
        if ($offset == 0) {
            return "";
        }
        $flag = $this->read1($offset);
        // 出错
        if ($flag == 0) {
            return "";
        }
        // 仍然为重定向
        if ($flag == 2) {
            $offset = $this->read8($offset + 1, $this->offlen);
            return $this->read_location($offset);
        }
        $location = $this->readstr($offset);
        return $location;
    }
    /**
     * 查找 ip 所在的索引
     */
    public function find($ip_num, $l, $r) {
        if ($l + 1 >= $r) {
            return $l;
        }
        $m = intval(($l + $r) / 2);
        $m_ip = $this->read8($this->index_start_offset + $m * ($this->iplen + $this->offlen), $this->iplen);
        if ($ip_num < $m_ip) {
            return $this->find($ip_num, $l, $m);
        } else {
            return $this->find($ip_num, $m, $r);
        }
    }
    public function readraw($offset=null, $size=0) {
        if (!is_null($offset)) {
            fseek($this->fd, $offset);
        }
        return fread($this->fd, $size);
    }
    public function read1($offset=null) {
        if (!is_null($offset)) {
            fseek($this->fd, $offset);
        }
        $a = fread($this->fd, 1);
        return @unpack("C", $a)[1];
    }
    public function read4($offset=null) {
        if (!is_null($offset)) {
            fseek($this->fd, $offset);
        }
        $a = fread($this->fd, 4);
        return @unpack("V", $a)[1];
    }
    public function read8($offset=null, $size=8) {
        if (!is_null($offset)) {
            fseek($this->fd, $offset);
        }
        $a = fread($this->fd, $size) . "\0\0\0\0\0\0\0\0";
        return @unpack("P", $a)[1];
    }
    public function readstr($offset=null) {
        if (!is_null($offset)) {
            fseek($this->fd, $offset);
        }
        $str = "";
        $chr = $this->read1($offset);
        while ($chr != 0) {
            $str .= chr($chr);
            $offset++;
            $chr = $this->read1($offset);
        }
        return $str;
    }
    public function ip2num($ip) {
        return unpack("N", inet_pton($ip))[1];
    }
    public function inet_ntoa($nip) {
        $ip = array();
        for ($i=3; $i > 0; $i--) {
            $ip_seg = intval($nip/pow(256, $i));
            $ip[] = $ip_seg;
            $nip -= $ip_seg * pow(256, $i);
        }
        $ip[] = $nip;
        return join(".", $ip);
    }
    public function __destruct() {
        if ($this->fd) {
            fclose($this->fd);
        }
    }
}


