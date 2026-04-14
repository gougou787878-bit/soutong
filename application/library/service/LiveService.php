<?php

namespace service;

use LiveModel;
use LiveThemeModel;
use LiveRelatedModel;
use LibUpload;
use Throwable;
use tools\HttpCurl;

class LiveService
{
    const USER_AGENT = [
        'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.95 Safari/537.36 OPR/26.0.1656.60',
        'Opera/8.0 (Windows NT 5.1; U; en)',
        'Mozilla/5.0 (Windows NT 5.1; U; en; rv:1.8.1) Gecko/20061208 Firefox/2.0.0 Opera 9.50',
        'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; en) Opera 9.50',
        'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:34.0) Gecko/20100101 Firefox/34.0',
        'Mozilla/5.0 (X11; U; Linux x86_64; zh-CN; rv:1.9.2.10) Gecko/20100922 Ubuntu/10.10 (maverick) Firefox/3.6.10',
        'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/534.57.2 (KHTML, like Gecko) Version/5.1.7 Safari/534.57.2 ',
        'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.71 Safari/537.36',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.64 Safari/537.11',
        'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/534.16 (KHTML, like Gecko) Chrome/10.0.648.133 Safari/534.16',
        'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/30.0.1599.101 Safari/537.36',
        'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; rv:11.0) like Gecko',
        'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/536.11 (KHTML, like Gecko) Chrome/20.0.1132.11 TaoBrowser/2.0 Safari/536.11',
        'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.1 (KHTML, like Gecko) Chrome/21.0.1180.71 Safari/537.1 LBBROWSER',
        'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; QQDownload 732; .NET4.0C; .NET4.0E)',
        'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/535.11 (KHTML, like Gecko) Chrome/17.0.963.84 Safari/535.11 SE 2.X MetaSr 1.0',
        'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; Trident/4.0; SV1; QQDownload 732; .NET4.0C; .NET4.0E; SE 2.X MetaSr 1.0) ',
    ];
    const CATEGORIES = [
        ['autoTagInteractiveToy', 'stripRanking'],//互动玩具
        ['mobile', 'stripRanking'],//手机
        ['doPublicPlace', 'stripRanking'],//户外
        ['doAnal', 'stripRanking'],//肛交
        ['specificsBigAss', 'stripRanking'],//大屁股
        ['doBlowjob', 'stripRanking'],//口交
        ['doMasturbation', 'stripRanking'],//自慰
        ['specificHairyArmpits', 'stripRanking'],//多毛腋下
        ['doOffice', 'stripRanking'],//办公室
        ['doDeepThroat', 'stripRanking'],//深喉
        ['specificShaven', 'stripRanking'],//剃光
        ['doFootFetish', 'stripRanking'],//恋足
        ['doDildoOrVibrator', 'stripRanking'],//假阳具
        ['doShower', 'stripRanking'],//洗澡
        ['specificsBigCock', 'stripRanking'],//大吊
        ['doDoggyStyle', 'stripRanking'],//狗势
        ['jerkOffInstruction', 'stripRanking'],//手淫
    ];
    const SHELL = 'curl --max-time 600 --tls-max 1.3 %s "%s" -L > "%s"';
    const LOG = '/storage/logs/strip_chat.log';
    const LIVE_LOG = '/storage/logs/strip_chat_live.log';
    //const DOAMIN = 'https://go.rmhfrtnd.com/';
    const DOMAINS = [
        'https://go.rmhfrtnd.com', // -- 不用这个
        'https://go.mnaspm.com',
        'https://go.bbrdbr.com',
    ];
    const DOMAIN = 'https://go.mnaspm.com';
    const DEBUG = true;
    const ONLINE_URI = '/api/models/online';
    const PLAY_DOMAINS = [
        'sacdnssedge.com',
        'doppiocdn.com',
        'doppiocdn.com',
        'doppiocdn1.com',
        'doppiocdn.media',
        'doppiocdn.net',
        'doppiocdn.org',
        'doppiocdn.live'
    ];
    const PLAY_DOMAIN = 'doppiocdn.com';
    const SK_REPLENISH = 'sk:live:replenish';
    const HK_REPLENISH_COVER = 'hk:live:replenish:cover';

    public static function wf($tip, $data)
    {
        self::DEBUG && wf($tip, $data, false, '/storage/logs/live.log', 3, true, false);
    }

    private static function create_thumb($username): string
    {
        try {
            $size = 100;
            $w = 200;
            $h = 200;
            $im = imagecreate($w, $h);
            $background_color = imagecolorallocate($im, 125, 125, 126);
            imagefill($im, 0, 0, $background_color);
            $text_color = imagecolorallocate($im, 255, 255, 255);
            $font = rtrim(APP_PATH, '/') . '/script/wryh.ttf';
            $text = mb_substr(strtoupper($username), 0, 1);
            $f = imagettfbbox($size, 0, $font, $text);
            $font_w = (int)($f[2] - $f[0]);
            $font_h = (int)($f[1] - $f[7]);
            $x = $w / 2 - $font_w / 2;
            $y = $font_h + ($h / 2 - $font_h / 2);
            imagettftext($im, $size, 0, $x, $y, $text_color, $font, $text);

            $md5 = substr(md5($username), 0, 16);
            $to = rtrim(APP_PATH, '/') . '/storage/data/images/' . $md5 . '_to.jpeg';
            imagejpeg($im, $to, 100);
            return $to;
        } catch (Throwable $e) {
            self::wf($e->getMessage(), '');
            return '';
        }
    }

    public static function request_cover2($url, $file)
    {
        $data = [
            'url'     => $url,
            'code'    => '12a2394f1aff9bcfb742e545ee7bc585',
            'headers' => json_encode([
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                'Accept-encoding: gzip, deflate, br, zstd',
                'Accept-language: zh-CN,zh;q=0.9,en;q=0.8',
            ])
        ];
        self::wf('远程参数', $data);
        $http = new HttpCurl();
        $rs = $http->post(config('live.cf_get'), $data);
        //self::wf('远程返回', $rs);
        test_assert($rs, '请求远程异常');

        $res = json_decode($rs, true);
        test_assert($res['code'] == 0, $res['msg']);
        file_put_contents($file, base64_decode($res['data']));
        self::wf('写入文件', $file);

        $info = getimagesize($file);
        self::wf('获取文件', $info);
        $mime = $info['mime'] ?? '';
        $allow_mime = ['image/jpeg', 'image/png', 'image/gif', 'image/bmp', 'image/webp'];
        test_assert(in_array($mime, $allow_mime), '不支持的格式:' . $mime . ' 内容:' . file_get_contents($file));
    }

    private static function request_cover($url, $file)
    {
        $url_info = parse_url($url);
        $key = config('live.api_key');
        $proxy = config('live.proxy_url');
        $path = $url_info['path'];
        $host = $url_info['host'];
        $headers = [
            'x-cb-apikey: ' . $key,
            'x-cb-host: ' . $host,
            'x-cb-proxy: ' . $proxy
        ];
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => 'https://api.cloudbypass.com/' . $path,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers
        ]);
        $res = curl_exec($ch);
        test_assert(!curl_errno($ch), curl_error($ch));
        test_assert($res, '返回数据异常');
        $rs = file_put_contents($file, $res);
        test_assert($rs, '写入文件异常:' . $file);
        self::wf('请求穿云', '');
    }

    public static function request_cover3($url, $file)
    {
        $user_agent = self::USER_AGENT[array_rand(self::USER_AGENT)];
        $headers = [
            'User-Agent: ' . $user_agent,
        ];
        foreach ($headers as &$v) {
            $v = sprintf('-H "%s"', $v);
        }
        $headers = implode(" ", $headers);
        $cmd = sprintf(self::SHELL, $headers, $url, $file);
        self::wf('执行命令', $cmd);
        exec($cmd, $log, $status);
        test_assert(!$status, '执行异常:' . $cmd);
        test_assert(file_exists($file), '文件不存在:' . $file);

        $info = getimagesize($file);
        self::wf('获取文件', $info);
        $mime = $info['mime'] ?? '';
        $allow_mime = ['image/jpeg', 'image/png', 'image/gif', 'image/bmp', 'image/webp'];
        test_assert(in_array($mime, $allow_mime), '不支持的格式:' . $mime . ' 内容:' . file_get_contents($file));
    }

    private static function req_cover($fr, $to)
    {
        try {
            self::wf('执行了SHELL:CURL', '');
            self::request_cover3($fr, $to);
            return;
        } catch (Throwable $e) {
            self::wf('远程出现异常-00001', $e->getMessage());
        }

        try {
            self::wf('执行了远程获取COOKIE', '');
            self::request_cover2($fr, $to);
            return;
        } catch (Throwable $e) {
            self::wf('远程出现异常-00002', $e->getMessage());
        }

        try {
            self::wf('执行了穿云请求资源', '');
            self::request_cover($fr, $to);
        } catch (Throwable $e) {
            self::wf('远程出现异常-00003', $e->getMessage());
        }
        return;
    }

    private static function upload_img($fr)
    {
        try {
            $md5 = substr(md5($fr), 0, 16);
            $t1 = $to = rtrim(APP_PATH, '/') . '/storage/data/images/' . $md5 . '_to';
            $to2 = rtrim(APP_PATH, '/') . '/storage/data/images/' . $md5 . '_to2';
            $dirname = dirname($to);
            if (!is_dir($dirname) || !file_exists($dirname)) {
                mkdir($dirname, 0755, true);
            }
            if (strpos($fr, 'http') !== false) {
                self::req_cover($fr, $to);
            } else {
                file_put_contents($to, file_get_contents($fr));
            }
            $info = getimagesize($to);
            $mime = $info['mime'] ?? '';
            $allow_mime = ['image/jpeg', 'image/png', 'image/gif', 'image/bmp', 'image/webp'];
            test_assert(in_array($mime, $allow_mime), '不支持的格式:' . $mime . ' 内容:' . file_get_contents($to));
            if ($mime === 'image/webp') {
                $bin = rtrim(APP_PATH, '/') . '/script/dwebp';
                $cmd = sprintf('%s %s -o %s', $bin, $to, $to2);
                exec($cmd, $log, $status);
                test_assert(!$status, '执行命令异常:' . $cmd);
                $to = $to2;
            }
            $return = LibUpload::upload2Remote(uniqid(), $to, 'live');
            test_assert($return, '上传文件异常');
            test_assert($return['code'] == 1, '上传文件异常');
            file_exists($t1) && unlink($t1);
            file_exists($to2) && unlink($to2);
            return $return['msg'];
        } catch (Throwable $e) {
            self::wf($e->getMessage(), '');
            $t1 && file_exists($t1) && unlink($t1);
            $to2 && file_exists($to2) && unlink($to2);
            return '';
        }
    }

    private static function curl($url, $headers)
    {
        $name = substr(md5($url), 0, 16);
        $file = rtrim(APP_PATH, '/') . '/storage/data/' . $name . '.html';
        foreach ($headers as &$v) {
            $v = sprintf('-H "%s"', $v);
        }
        $headers = implode(" ", $headers);
        $cmd = sprintf(self::SHELL, $headers, $url, $file);
        self::wf('执行命令', $cmd);
        exec($cmd, $log, $status);
        test_assert(!$status, '执行异常:' . $cmd);
        test_assert(file_exists($file), '文件不存在:' . $file);
        $data = file_get_contents($file);
        test_assert($data, '内容异常');
        unlink($file);
        return $data;
    }

    private static function get_theme_ids($country, $language, $gender, $tags): array
    {
        $theme_ids = [];
        LiveThemeModel::where('status', LiveThemeModel::STATUS_OK)
            ->get()
            ->map(function ($item) use ($country, $language, $gender, $tags, &$theme_ids) {
                $values = explode(",", $item->value);
                if ($item->type == LiveThemeModel::TYPE_COUNTRY) {
                    if ($item->symbol == LiveThemeModel::SYMBOL_OK) {
                        if (in_array($country, $values)) {
                            $theme_ids[] = $item->id;
                        }
                    } else {
                        if (!in_array($country, $values)) {
                            $theme_ids[] = $item->id;
                        }
                    }
                }
                if ($item->type == LiveThemeModel::TYPE_LANGUAGE) {
                    if ($item->symbol == LiveThemeModel::SYMBOL_OK) {
                        if (in_array($language, $values)) {
                            $theme_ids[] = $item->id;
                        }
                    } else {
                        if (!in_array(!$language, $values)) {
                            $theme_ids[] = $item->id;
                        }
                    }
                }
                if ($item->type == LiveThemeModel::TYPE_GENDER) {
                    if ($item->symbol == LiveThemeModel::SYMBOL_OK) {
                        if (in_array($gender, $values)) {
                            $theme_ids[] = $item->id;
                        }
                    } else {
                        if (!in_array($gender, $values)) {
                            $theme_ids[] = $item->id;
                        }
                    }
                }
                if ($item->type == LiveThemeModel::TYPE_TAG) {
                    $has = false;
                    if ($item->symbol == LiveThemeModel::SYMBOL_OK) {
                        foreach ($tags as $tag) {
                            if (in_array($tag, $values)) {
                                $has = true;
                                break;
                            }
                        }
                        $has && $theme_ids[] = $item->id;
                    } else {
                        foreach ($values as $value) {
                            if (in_array($value, $tags)) {
                                $has = true;
                                break;
                            }
                        }
                        !$has && $theme_ids[] = $item->id;
                    }
                }
            });
        sort($theme_ids);
        return $theme_ids;
    }

    public static function process_model($v)
    {
        //不是男性数据 不要
        if (!in_array($v['gender'], ['males', 'male'])){
            self::wf('过滤的数据', [$v['gender'], $v['id'], $v['username']]);
            return;
        }

        $show = $v['status'];
        $model_id = $v['id'];

        $snapshot_url = $v['snapshotUrl'] ?? '';
        $widget_preview_url = $v['widgetPreviewUrl'] ?? '';
        $ml_preview_image = $v['mlPreviewImage'] ?? '';
        $popular_snapshot_url = $v['popularSnapshotUrl'] ?? '';
        $snapshot_urls = array_unique(array_filter([$snapshot_url, $widget_preview_url, $ml_preview_image, $popular_snapshot_url]));
        $snapshot_url = $snapshot_urls[0] ?? '';
        test_assert($snapshot_url, '预览封面未找到');

        $avatar_url = $v['avatarUrl'] ?? '';
        $preview_url = $v['previewUrl'] ?? '';
        $preview_url_thumb_big = $v['previewUrlThumbBig'] ?? '';
        $preview_url_thumb_small = $v['previewUrlThumbSmall'] ?? '';
        $avatar_urls = array_unique(array_filter([$avatar_url, $preview_url, $preview_url_thumb_big, $preview_url_thumb_small]));
        $avatar_url = $avatar_urls[0] ?? '';
        //test_assert($avatar_url, '用户头像未找到');

        $tags = $v['tags'] ?? [];
        $tag = implode(",", $tags);
        $language = implode(",", $v['languages'] ?? []);
        $gender = $v['gender'] ?? '';
        $country = $v['modelsCountry'] ?? '';
        $theme_ids = self::get_theme_ids($country, $language, $gender, $tags);

        $m3u8 = $v['stream']['url'] ?? '';
        $f_cover = $snapshot_url;
        redis()->hSet(self::HK_REPLENISH_COVER, $model_id, $f_cover);

        $fields = ['id', 'tag', 'hls', 'show', 'favorite_oct', 'view_oct', 'cover'];
        $record = LiveModel::select($fields)->where('model_id', $model_id)->first();
        if ($record) {
            if ($show == 'off') {
                self::off_live($record);
                return;
            }

            $url = self::process_url($m3u8);
            if (!$url) {
                self::off_live($record);
                return;
            }
            list($code, $rsp) = self::curl_live($url);
            if ($code !== 200) {
                self::off_live($record);
                return;
            }
            $urls = self::process_rsp($rsp);
            if (!count($urls)) {
                self::off_live($record);
                return;
            }
            $urls = self::process_hls_domain($urls);
            $hls = json_encode($urls);

            $ori_cover = parse_url($record->cover, PHP_URL_PATH);
            if ($ori_cover == '/upload_01/upload/20240715/2024071518043988394.png') {
                if ($f_cover) {
                    self::wf('上传图片', $f_cover);
                    $cover2 = self::upload_img($f_cover);
                    $cover = $cover2 ? $cover2 : $ori_cover;
                    $record->f_cover = $f_cover;
                    $record->cover = $cover;
                }
            }

            $record->tag = $tag;
            $record->hls = $hls;
            $record->show = $show;
            $record->favorite_oct = $v['favoritedCount'] ?? 0;
            $record->view_oct = $v['viewersCount'] ?? 0;
            $isOk = $record->save();
            test_assert($isOk, '出现异常');
            self::wf('维护数据', [$model_id, $hls, $show, $record->cover]);

            LiveRelatedModel::where('live_id', $record->id)->delete();
            foreach ($theme_ids as $theme_id) {
                $data = [
                    'theme_id' => $theme_id,
                    'live_id'  => $record->id,
                ];
                $isOk = LiveRelatedModel::create($data);
                test_assert($isOk, '维护主题关联异常');
            }
            return;
        }

        if (!$avatar_url) {
            $avatar_url = self::create_thumb($v['username']);
        }
        if ($avatar_url) {
            $avatar_url = self::upload_img($avatar_url);
        }
        if (!$avatar_url) {
            return;
        }

        $url = self::process_url($m3u8);
        if (!$url) {
            return;
        }
        list($code, $rsp) = self::curl_live($url);
        if ($code !== 200) {
            return;
        }
        $urls = self::process_rsp($rsp);
        if (!count($urls)) {
            return;
        }
        $urls = self::process_hls_domain($urls);
        $hls = json_encode($urls);

        $rand_view = 500;
        $rand_favorite = 300;

        $cover = '/upload_01/upload/20240715/2024071518043988394.png';
        if ($f_cover) {
            $cover2 = self::upload_img($f_cover);
            $cover = $cover2 ? $cover2 : $cover;
        }

        $info = [
            'username'     => $v['username'],
            'thumb'        => $avatar_url,
            'gender'       => $gender,
            'country'      => $country,
            'cover'        => $cover,
            'f_cover'      => $f_cover,
            'hls'          => $hls,
            'model_id'     => $v['id'],
            'tag'          => $tag,
            'language'     => $language,
            'show'         => $show,
            'status'       => LiveModel::STATUS_ON,
            'favorite_oct' => $v['favoritedCount'] ?? 0,
            'view_oct'     => $v['viewersCount'] ?? 0,
            'view_ct'      => 0,
            'view_fct'     => $rand_view,
            'favorite_ct'  => 0,
            'favorite_fct' => $rand_favorite,
            'comment_ct'   => 0,
            'type'         => LiveModel::TYPE_VIP,
            'coins'        => 0,
            'intro'        => '',
            'sort'         => 0,
            'fr_width'     => $v['stream']['width'] ?? 0,
            'fr_height'    => $v['stream']['height'] ?? 0,
        ];
        self::wf('新增数据', $info);
        $record = LiveModel::create($info);
        test_assert($record, '出现异常');
        if ($record->id % 2 == 0){
            $record->type = LiveModel::TYPE_COINS;
            $record->coins = rand(10, 30);
            $record->save();
        }

        foreach ($theme_ids as $theme_id) {
            $data = [
                'theme_id' => $theme_id,
                'live_id'  => $record->id,
            ];
            $isOk = LiveRelatedModel::create($data);
            test_assert($isOk, '维护主题关联异常');
        }
    }

    public static function replenish_models()
    {
        $usernames = redis()->sMembers(self::SK_REPLENISH);
        redis()->del(self::SK_REPLENISH);
        $len = count($usernames);
        $usernames = implode(",", $usernames);
        $url = self::DOMAIN . '/api/models?fields=tags&modelsList=' . $usernames . '&limit=' . $len . '&offset=0';
        $user_agent = self::USER_AGENT[array_rand(self::USER_AGENT)];
        $headers = [
            'Accept: */*',
            'Accept-Encoding: deflate',
            'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
            'Origin: https://creative.mnaspm.com',
            'Priority: u=1, i',
            'Referer: https://creative.mnaspm.com/',
            'User-Agent: ' . $user_agent,
        ];
        $data = self::curl($url, $headers);
        $data = json_decode($data, true);
        foreach ($data['models'] as $v) {
            self::process_model($v);
        }
    }

    public static function list_models()
    {
        $p = 0;
        $limit = 100;
        while (true) {
            $p++;
            $offset = ($p - 1) * $limit;
            $url = self::DOMAIN . '/api/models?fields=tags&limit=' . $limit . '&offset=' . $offset . '&status=public';
            $user_agent = self::USER_AGENT[array_rand(self::USER_AGENT)];
            $headers = [
                'Accept: */*',
                'Accept-Encoding: deflate',
                'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
                'Origin: https://creative.mnaspm.com',
                'Priority: u=1, i',
                'Referer: https://creative.mnaspm.com/',
                'User-Agent: ' . $user_agent,
            ];
            $data = self::curl($url, $headers);
            $data = json_decode($data, true);
            if (count($data['models']) == 0) {
                break;
            }
            foreach ($data['models'] as $v) {
                self::process_model($v);
            }
            sleep(1);
        }
    }

    public static function off_live($item)
    {
        $item->hls = '';
        $item->cover = '/upload_01/upload/20240715/2024071518043988394.png';
        $item->f_cover = '';
        $item->favorite_oct = 0;
        $item->view_oct = 0;
        $item->fr_height = 0;
        $item->fr_width = 0;
        $item->show = LiveModel::SHOW_OFF;
        $isOk = $item->save();
        test_assert($isOk, '出现异常');
        //删除当场直播购买门票的用户集合
        //redis()->del(sprintf(LiveModel::LIVE_PAY_SET, $item->id));
    }

    public static function live_online()
    {
        LiveModel::select(['id'])
            ->where('show', LiveModel::SHOW_PUBLIC)
            ->where('status', LiveModel::STATUS_ON)
            ->orderBy('id', 'desc')
            ->chunkById(500,function (\Illuminate\Support\Collection $items){
                collect($items)->each(function (LiveModel $item){
                    jobs2([self::class, 'online'], [$item->id], 'jobs:work:queue:live');
                });
            });
    }

    public static function curl_live($url): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_NOBODY, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        $rsp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        return [$code, $rsp];
    }

    public static function process_url($url)
    {
        if (strpos($url, '_') !== false) {
            $url = str_replace("_160p.m3u8", "_auto.m3u8", $url);
            $url = str_replace("_240p.m3u8", "_auto.m3u8", $url);
            $url = str_replace("_480p.m3u8", "_auto.m3u8", $url);
            $url = str_replace("_720p.m3u8", "_auto.m3u8", $url);
            $url = str_replace("_1080p.m3u8", "_auto.m3u8", $url);
        } else {
            $url = str_replace(".m3u8", "_auto.m3u8", $url);
        }
        return $url;
    }

    public static function process_rsp($rsp): array
    {
        $preg = '#NAME="(.+)"\n([^\n]+)#';
        preg_match_all($preg, $rsp, $matches);
        $hls = [];
        foreach ($matches[1] as $k => $v) {
            $hls[] = ['label' => $v, 'url' => $matches[2][$k]];
        }
        return $hls;
    }

    public static function online($id)
    {
        $fields = ['id', 'tag', 'hls', 'show', 'favorite_oct', 'view_oct'];
        $item = LiveModel::select($fields)
            ->where('id', $id)
            ->where('show', LiveModel::SHOW_PUBLIC)
            ->where('status', LiveModel::STATUS_ON)
            ->first();
        if (!$item) {
            return;
        }

        $hls = LiveModel::process_hls($item->hls);
        $urls = array_column($hls, 'url');
        if (!count($urls)) {
            self::off_live($item);
            return;
        }
        $url = $urls[0];
        list($code, $rsp) = self::curl_live($url);
        self::wf('当前', '处理:' . $item->id . ' CODE:' . $code . ' URL:' . $url);
        if ($code == 200) {
            return;
        }
        self::off_live($item);
    }

    protected static function process_hls_domain($hls): array
    {
        foreach ($hls as $k => $v) {
            $hls[$k]['url'] = str_replace(self::PLAY_DOMAINS, self::PLAY_DOMAIN, $v['url']);
        }
        return $hls;
    }

    public static function list_models2($tag, $sort)
    {
        $p = 0;
        $limit = 60;
        $ori_sort = 10000;
        while (true) {
            $p++;
            $offset = ($p - 1) * $limit;
            $params = [
                'limit'           => $limit,
                'offset'          => $offset,
                'primaryTag'      => 'men',
                'filterGroupTags' => urlencode('[["' . $tag . '"]]'),
                'sortBy'          => $sort,
                'parentTag'       => $tag,
                'userRole'        => 'guest',
                'groupId'         => 6,
                'uniq'            => substr(md5(uniqid() . time() . uniqid()), 0, 16),
            ];
            self::wf('MODEL请求参数', $params);
            $url = 'https://zh.stripchat.com/api/front/models?';
            $kv = [];
            foreach ($params as $k => $v) {
                $kv[] = $k . '=' . $v;
            }
            $url = $url . implode("&", $kv);
            $user_agent = self::USER_AGENT[array_rand(self::USER_AGENT)];
            $headers = [
                'Front-Version: 10.86.21',
                'Priority: u=1, i',
                'Accept: */*',
                'Accept-Encoding: deflate',
                'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
                'Content-Type: application/json',
                'Priority: u=0, i',
                'Referer: https://zh.stripchat.com/girls/chinese',
                'User-Agent: ' . $user_agent,
            ];
            $data = self::curl($url, $headers);
            //print_r($data);
            $data = json_decode($data, true);
            if (count($data['models']) == 0) {
                break;
            }
            foreach ($data['models'] as $v) {
                $show = $v['status'];
                $model_id = $v['id'];
                $username = $v['username'];
                $height = (int)($v['broadcastSettings']['height'] ?? 0);
                $width = (int)($v['broadcastSettings']['width'] ?? 0);
                $snapshot = (int)($v['popularSnapshotTimestamp'] ?? '');
                $snapshot2 = (int)($v['snapshotTimestamp'] ?? '');
                $snapshot = $snapshot ? $snapshot : $snapshot2;
                $f_cover = $snapshot ? "https://img.doppiocdn.org/thumbs/" . $snapshot . "/" . $model_id : '';
                $hls = $v['hlsPlaylist'] ?? null;
                if (empty($hls)) {
                    self::wf("MODELS2跳过：缺少 hlsPlaylist", ['model_id' => $model_id, 'username' => $username]);
                    continue;
                }

                $view_oct = $v['viewersCount'] ?? 0;
                self::wf('MODELS2获取到参数', [$show, $model_id, $height, $width, $f_cover, $view_oct, $hls]);
                redis()->hSet(self::HK_REPLENISH_COVER, $model_id, $f_cover);
                if ($show !== LiveModel::SHOW_PUBLIC) {
                    self::wf('MODELS2本MODEL非PUBLIC', '');
                    continue;
                }
                $fields = ['id', 'tag', 'hls', 'show', 'favorite_oct', 'view_oct', 'cover'];
                $live = LiveModel::select($fields)->where('model_id', $model_id)->first();
                if (!$live) {
                    redis()->sAdd(self::SK_REPLENISH, $username);
                    self::wf('MODELS2不存在' . $model_id . '跳过', '');
                    continue;
                }
                if ($live->show == LiveModel::SHOW_PUBLIC) {
                    $live->sort = $ori_sort;
                    $isOk = $live->save();
                    test_assert($isOk, '出现异常');
                    $ori_sort--;
                    self::wf('MODELS2秀类型PUBLIC跳过', '');
                    continue;
                }
                //更新播放链接
                $url = self::process_url($hls);
                list($code, $rsp) = self::curl_live($url);
                if ($code !== 200) {
                    self::wf('MODELS2获取播放地址状态异常', '');
                    continue;
                }
                $urls = self::process_rsp($rsp);
                if (!count($urls)) {
                    self::wf('MODELS2获取播放链接异常', '');
                    continue;
                }
                $urls = self::process_hls_domain($urls);
                $hls = json_encode($urls);


                $ori_cover = parse_url($live->cover, PHP_URL_PATH);
                if ($ori_cover == '/upload_01/upload/20240715/2024071518043988394.png' || empty($ori_cover)) {
                    if ($f_cover) {
                        self::wf('上传图片', $f_cover);
                        $cover2 = self::upload_img($f_cover);
                        $cover = $cover2 ?: $ori_cover;
                    }
                }

                $data = [
                    'hls'          => $hls,
                    'show'         => LiveModel::SHOW_PUBLIC,
                    'favorite_oct' => 0,
                    'view_oct'     => $view_oct,
                    'fr_width'     => $width,
                    'fr_height'    => $height,
                    'sort'         => $ori_sort,
                ];
                if (!empty($cover)){
                    $data['cover'] = $cover;
                }
                self::wf('MODELS2更新参数', $data);
                $live->fill($data);
                $isOk = $live->save();
                test_assert($isOk, '无法更新数据');
                $ori_sort--;
            }
            sleep(2);
        }
    }

    public static function live_cover()
    {
        LiveModel::select(['sort', 'id', 'model_id'])
            ->where('cover', '/upload_01/upload/20240715/2024071518043988394.png')
            ->where('status', LiveModel::STATUS_ON)
            ->where('show', LiveModel::SHOW_PUBLIC)
            ->orderByDesc('sort')
            ->chunk(1000, function ($items){
                collect($items)->each(function ($item){
                    $msg = sprintf('ID:%s MODEL_ID:%s SORT:%s', $item->id, $item->model_id, $item->sort);
                    self::wf('更新封面开始', $msg);
                    $cover = redis()->hGet(self::HK_REPLENISH_COVER, $item->model_id);
                    if (!$cover) {
                        return;
                    }
                    $cover = self::upload_img($cover);
                    if (!$cover) {
                        redis()->hDel(self::HK_REPLENISH_COVER, $item->model_id);
                        return;
                    }
                    $item->cover = $cover;
                    $isOk = $item->save();
                    $msg = sprintf('ID:%s MODEL_ID:%s SORT:%s COVER-->%s', $item->id, $item->model_id, $item->sort, $cover);
                    self::wf('更新封面', $msg);
                    test_assert($isOk, '无法更新直播封面');
                    redis()->hDel(self::HK_REPLENISH_COVER, $item->model_id);
                });
            });
        redis()->del(self::HK_REPLENISH_COVER);
        sleep(60);
    }
}