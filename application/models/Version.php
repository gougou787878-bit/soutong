<?php

use tools\IpLocation;
use tools\RedisService;

/**
 * class VersionModel
 *
 * @property string $apk 下载连接
 * @property int $apptype 框架类型 1 java 2 rn
 * @property string $bundle_id ios企业安装包id
 * @property int $created_at 创建时间
 * @property int $from_id 更新起点
 * @property int $id
 * @property string $message 系统维护公告
 * @property int $mstatus 系统公告状态 0 没有 1通知 2禁用
 * @property int $must 0 不强制更新 1强制
 * @property int $status 1 启用  2 停用
 * @property string $tips 更新说明
 * @property int $to_id 更新终点
 * @property string $type 型号
 * @property string $version 版本号
 * @property string $via 来源 'agent' 企业签  'single' 个人签
 * @property int $custom 域名跟随
 * @property string $sha256 sha256
 *
 * @author xiongba
 * @date 2020-03-18 10:34:40
 *
 * @mixin \Eloquent
 */
class VersionModel extends EloquentModel
{
    protected $table = 'version';

    const STATUS_SUCCESS = 1;
    const STATUS_FAIL = 2;

    const CHAN_TF = 'testflight';// tf 包
    const CHAN_PG = 'normal';// 企業簽  包
    const CHAN_PWA = 'pwa';// 企業簽  包


    const STATUS = [
        self::STATUS_SUCCESS => '启用',
        self::STATUS_FAIL    => '停用',
    ];

    const MUST_UPDATE = 1;
    const MUST_UPDATE_NOT = 0;
    const MUST = [
        self::MUST_UPDATE_NOT => '软更',
        self::MUST_UPDATE     => '强更',
    ];

    const TYPE_ANDROID = 'android';
    const TYPE_IOS = 'ios';
    const TYPE = [
        self::TYPE_ANDROID => '安卓',
        self::TYPE_IOS     => '苹果'
    ];
    const REDIS_VERSION_KEY = [
        'ios'     => 'version:ios',
        'android' => 'version:android',
    ];

    const CUSTOM_NO = 0;
    const CUSTOM_OK = 1;
    const CUSTOM_TIPS = [
        self::CUSTOM_NO     => '否',
        self::CUSTOM_OK     => '是'
    ];

    protected $fillable = [
        'version',
        'apptype',
        'type',
        'apk',
        'tips',
        'bundle_id',
        'must',
        'created_at',
        'status',
        'via',
        'mstatus',
        'from_id',
        'to_id',
        'custom',
        'sha256'
    ];

    public function getApkAttribute()
    {
        $val = $this->attributes['apk'];
        $custom = $this->attributes['custom'];
        if (str_ends_with($val, '.apk')) {
            if ($custom == self::CUSTOM_OK) {
                return $val;
            }
            $val = parse_url($val, PHP_URL_PATH);
            $val = TB_APP_DOWN_URL . $val;
        }
        return $val;
    }

    /**
     * @param $type
     * @param int $status
     * @param string $channel
     * @return self
     */
    static function getleastVersion($type, $status = self::STATUS_SUCCESS, $channel = '')
    {
        $key = 'ver:' . $type;
        $where['type'] = $type;
        $where['status'] = $status;
        $where['custom'] = self::CUSTOM_NO;
        if ($channel) {
            $key .= $channel;
            $where['via'] = $channel;
        }else{
            $where['via'] = '';
        }
        //echo $key;
        $version = cached($key)
            ->serializerPHP()
            ->expired(88400)
            ->fetch(function () use ($where,$key) {
                $d = self::query()
                    ->select(['id','version','type','apk','tips','must','via','sha256'])
                    ->where($where)
                    ->orderByDesc('id')
                    ->first();
                CacheKeysModel::createOrEdit($key,'版本管理');
                return is_null($d) ? null : $d->toArray();
        });
        if ($version && VersionModel::TYPE_IOS == $type) {
            //0 east 1 south 2 west 3 north 4 foreign
            $text = $version['apk'];
            $position = IpLocation::getLocation(USER_IP);
            //var_dump($position);die;
            $province = $position['province'] ?? '';
            if(empty($province)){
                $province = '北京';
            }
            $flag_via = '[=]';//后台分割标识
            if (strpos($text, "\n") !== false) {
                $ary = explode("\n", $text);
                /**
                 * print_r($ary);die;
                 * Array
                 * (
                 * [0] => 上海,江苏,浙江,安徽,福建,江西,山东,台湾=https://testflight.apple.com/join/mtgUxVaN
                 * [1] => 山西,河南,湖北,湖南,广东,广西,海南=https://testflight.apple.com/join/bTcGNnho
                 * [2] => 重庆,四川,贵州,云南,西藏,陕西,甘肃,青海,宁夏,新疆=https://testflight.apple.com/join/wiLPC1BY
                 * [3] => 北京,内蒙古,天津,河北,辽宁,吉林,黑龙江=https://tf.chuangm23.cn/tf/api/GjLsckXi6
                 * [4] => https://tf.chuangm23.cn/tf/api/GjLsckXi6
                 * )*/
                foreach ($ary as $item) {
                    $item = trim($item);
                    if (empty($item)) {
                        continue;
                    }
                    if (strpos($item, $flag_via) === false) {
                        $version['apk'] = trim($item);
                        break;
                    }
                    list($areaStr, $url) = explode($flag_via, $item);
                    $area = explode(',', $areaStr);
                    foreach ($area as $v) {
                        $v = trim($v);
                        if ($province && strpos($v, $province) !== false) {
                            $version['apk'] = trim($url);
                            break 2;
                        }
                    }
                }
            }
            if (stripos($version['apk'], '.plist') != false) {
                $version['apk'] = "itms-services://?action=download-manifest&url=" . $version['apk'];
            }

        }
        if (false !== strpos($version['apk'] ,'.apk')){
            $old_host = parse_url($version['apk'] , PHP_URL_HOST);
            $new_host = parse_url(TB_APP_DOWN_URL , PHP_URL_HOST);
            $version['apk'] = str_replace($old_host, $new_host , $version['apk']);
            if ($version['must']) {
                $set = (int)setting('upgrade_apk_jump', 0);
                if ($set && isset($version['must']) && $version['must']) {
                    $version['apk'] = self::get_upgrade_apk_url();
                }
            }
        }
        return $version;
    }

    private static function get_upgrade_apk_url(): string
    {
        $domain = trim(getShareURL(), '/');
        $token = getID2Code(request()->getMember()->uid);
        return $domain . "/index.php?m=index&a=upgrade_apk&token={$token}";
    }

    /**
     *  后台版本管理 缓存清除
     * @param $type
     * @param string $channel
     */
    static function clearVersionCache($type, $channel = '')
    {
        $key = 'ver:' . $type;
        if($type == 'ios'){
            $key_chan = 'ver:' . $type . 'normal';
            redis()->del($key_chan);
            $key_chan = 'ver:' . $type . 'testflight';
            redis()->del($key_chan);
            $key_chan = 'ver:' . $type . 'pwa';
            redis()->del($key_chan);
        }
        cached('')->clearGroup('version');
        redis()->del($key);
    }



    const VERSION_BOUND = 'bound';
    static function addBound($data)
    {
        if (is_array($data) && $data) {
            //redis()->del(self::VERSION_BOUND);
            collect($data)->map(function ($item){
                if(stripos($item,'=')!==false){
                    list($_pkg_name,$_pkg_hash) = explode('=',$item);
                    if($_pkg_name && $_pkg_hash){
                        //redis()->hSet(self::VERSION_BOUND,$_pkg_name,$_pkg_hash);
                        ApkHashModel::addPackage($_pkg_name,$_pkg_hash);
                    }
                }
            });

        }
        return false;
    }

    static function checkBound($bound_id)
    {
        //$_pkg_name
        if (empty($bound_id)) {
            return false;
        }
        //return redis()->hGet(self::VERSION_BOUND, $bound_id);
        /** @var ApkHashModel $has */
        $has = ApkHashModel::hasPackage($bound_id);
        if ($has) {
            return $has->package_hash;
        }

        return false;
    }


    public static function get_android_version($code)
    {
        $channel_android = self::getLeastVersionNew(VersionModel::TYPE_ANDROID, VersionModel::STATUS_SUCCESS, $code);
        if ($code && $channel_android && $channel_android->via == $code) {
            // 渠道包 直接下载渠道包地址
            $is_download = 1;
            $version_and = $channel_android->apk;
            $special_and = $channel_android->apk;
            return [$is_download, $version_and, $special_and];
        }

        // 安卓防毒包
        $antivirus_android = self::get_main_android_least_version_v2(VersionModel::CUSTOM_OK);
        // 主包
        $main_android = self::get_main_android_least_version_v2(VersionModel::CUSTOM_NO);

        // 主包 防毒包有则为防毒包+相对主包
        if ($antivirus_android) {
            $is_download = 0;
            $version_and = $antivirus_android->apk;
            $main_url = $main_android ? $main_android->apk : "";
            $special_and = parse_url($main_url, PHP_URL_PATH);
            return [$is_download, $special_and, $version_and];
        }

        // 只有主包 则显示主包地址与主包相对地址
        $is_download = 0;
        $version_and = $main_android ? $main_android->apk : "";
        $main_url = $main_android ? $main_android->apk : "";
        $special_and = parse_url($main_url, PHP_URL_PATH) . '?v=1';
        return [$is_download, $version_and, $special_and];
    }

    /**
     *  版本获取
     * @param $type
     * @param int $status
     * @param string $channel 渠道| 默认空
     * @return mixed
     */
    public static function getLeastVersionNew($type, $status = self::STATUS_SUCCESS, $channel = '')
    {
        return cached('version:' . $type . '-' . $channel)
            ->group('version')
            ->chinese('版本管理')
            ->fetchPhp(function () use ($type, $status, $channel) {
                $where = [
                    ['type', '=', $type],
                    ['status', '=', $status],
                    ['via', '=', $channel],
                ];
                return self::query()->where($where)->orderByDesc('id')->first();
            }, 86400);
    }

    public static function get_main_android_least_version_v2($custom)
    {
        return cached('version:android:v3' . $custom)
            ->group('version')
            ->chinese('版本管理')
            ->fetchPhp(function () use ($custom) {
                $where = [
                    ['via', '=', ""],
                    ['type', '=', VersionModel::TYPE_ANDROID],
                    ['status', '=', VersionModel::STATUS_SUCCESS],
                    ['custom', '=', $custom],
                ];
                return VersionModel::query()->where($where)->orderByDesc('id')->first();
            }, 86400);
    }

    // 下载到其他的目录下面
    public static function defend_apk($apk, $is_update = 0)
    {
        try {
            $filename = ltrim(parse_url($apk, PHP_URL_PATH), '/');
            //$dirname = rtrim(APP_PATH, '/') . '/../www_html/apk';
            $dirname = rtrim(APP_PATH, '/') . '/public/apk';
            $file_path = $dirname . '/' . $filename;
            $tmp_path = $dirname . '/' . $filename . '_bk';
            wf("获取信息", [$dirname, $file_path], false, '/storage/logs/apk.log');
            if (file_exists($file_path) && $is_update == 0){
                wf("跳过存在", $file_path, false, '/storage/logs/apk.log');
                return;
            }
            $dirname = dirname($file_path);
            if (!file_exists($dirname)) {
                wf("创建目录", $dirname, false, '/storage/logs/apk.log');
                $rs = mkdir($dirname, 0777, true);
                test_assert($rs, '无法创建目录:' . $dirname);
            }
            wf("获取文件", $apk, false, '/storage/logs/apk.log');
            download_apk($apk, $tmp_path, $file_path);
            wf('下载成功', [$tmp_path, $file_path], false, '/storage/logs/apk.log');

            $cmd = sprintf('chown www:www -R %s', $dirname);
            wf('给予权限', $cmd, false, '/storage/logs/apk.log');
            exec($cmd, $log, $status);
            test_assert(!$status, '给予权限异常');
//            $txt = file_get_contents($apk);
//            test_assert($txt, '无法获取文件:' . $apk);
//            wf("写入文件", $file_path, false, '/storage/logs/apk.log');
//            $rs = file_put_contents($file_path, $txt);
//            test_assert($rs, '无法写入文件:' . $file_path);
            //清除缓存
            self::clearVersionCache(self::TYPE_ANDROID);
        } catch (Throwable $e) {
            wf("出现异常", $e->getMessage(), false, '/storage/logs/apk.log');
        }
    }

    public static function report_apk($address)
    {
        try {
            $address = parse_url($address , PHP_URL_PATH);
            $data = [
                'app_id'        => config('click.report.app_id'),
                'share_name'    => '{share.soutong_app}',
                'app_url'       => replace_share('https://{share.soutong_app}'),
                'app_apk'       => TB_APP_DOWN_URL . $address,
            ];
            $http = new \tools\HttpCurl();
            $rs = $http->post(config('channel.report.apk'), $data);
            wf('上报apk链接结果:', $rs, false, '/storage/logs/apk.log');
        } catch (Throwable $e) {
            wf("出现异常", $e->getMessage(), false, '/storage/logs/apk.log');
        }
    }
}