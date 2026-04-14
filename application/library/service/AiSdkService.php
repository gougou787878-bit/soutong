<?php

namespace service;

use CURLFile;
use LibCrypt;
use LibUpload;
use MemberDrawModel;
use MemberMagicModel;
use MemberNovelModel;
use Throwable;
use MemberFaceModel;
use MemberStripModel;
use tools\HttpCurl;

class AiSdkService
{

    const STRIP_BACK_API = NOTIFY_BACK_URL . '/index.php?m=mv&a=ai_ty';
    const STRIP_LOG_FILE = '/storage/logs/strip.log';

    const VIDEO_FACE_BACK_API = NOTIFY_BACK_URL . '/index.php?m=mv&a=ai_hl';
    const VIDEO_FACE_LOG_FILE = '/storage/logs/video_face.log';

    const IMAGE_FACE_BACK_API = NOTIFY_BACK_URL . '/index.php?m=mv&a=ai_ht';
    const IMAGE_FACE_LOG_FILE = '/storage/logs/img_face.log';

    const STRIP_NEW_API = '/api/public/generate/undress/images/male';
    const STRIP_NEW_BACK_URI = '/index.php?m=ai&a=on_strip';

    const IMAGE_NEW_FACE_API = '/api/public/generate/face-swap';
    const IMAGE_NEW_FACE_BACK_API = '/index.php?m=ai&a=on_image_face';

    const AI_TASK_URI = '/api/public/task/list';

    public static function js($data)
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    public static function wr_strip_log($tip, $data)
    {
        wf($tip, $data, false, self::STRIP_LOG_FILE);
    }

    public static function wr_video_face_log($tip, $data)
    {
        wf($tip, $data, false, self::VIDEO_FACE_LOG_FILE);
    }

    public static function wr_image_face_log($tip, $data)
    {
        wf($tip, $data, false, self::IMAGE_FACE_LOG_FILE);
    }


    /**
     * @throws \Exception
     */
    public static function upload_img($fr, $type = 1)
    {
        self::wr_image_face_log('开始处理图片', $fr);
        $image = file_get_contents($fr);
        //list($image, $content_type, $code, $err) = self::fetch_image($fr);
        //self::wr_image_face_log('info:', [$image, $content_type, $code, $err]);
        test_assert($image, '请求远程异常' . $fr);
        $md5 = substr(md5($fr), 0, 16);
        $to = APP_PATH . '/storage/data/images/' . $md5 . '_to';
        $dirname = dirname($to);
        if (!is_dir($dirname) || !file_exists($dirname)) {
            mkdir($dirname, 0777, true);
        }
        self::wr_image_face_log('写入路径:', $to);
        $rs = file_put_contents($to, $image);
        self::wr_image_face_log('写入文件结果:', $rs);
        test_assert($rs, '无法写入文件:' . $to);

        $flag = false;
        for ($i = 1; $i <= 3; $i++) {
            $return = LibUpload::upload2Remote(uniqid(), $to, 'upload');
            self::wr_image_face_log('上传返回', $return);
            if ($return && $return['code'] == 1) {
                $flag = true;
                break;
            }
        }
        test_assert($flag, '上传图片异常');
        unlink($to);
        self::wr_image_face_log('处理完成', $return['msg']);
        return $return['msg'];
    }

    public static function fetch_image($url, $headers = [], $forceIPv4 = false) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,   // 返回二进制到变量
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_USERAGENT => 'Mozilla/5.0',
            CURLOPT_ACCEPT_ENCODING => '',    // 自动解压
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        if ($forceIPv4) curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        if ($headers)   curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $data = curl_exec($ch);
        $info = curl_getinfo($ch);
        $err  = curl_error($ch);
        curl_close($ch);

        // 保护：200 且非空才认为成功
        if ($data !== false && $info['http_code'] === 200 && $data !== '') {
            return [$data, $info['content_type'] ?? 'application/octet-stream', null, $info];
        }
        return [null, null, $err ?: 'http_status_'.$info['http_code'], $info];
    }

    public static function image_face_api($id, $fr, $fr2)
    {
        self::wr_image_face_log('开始处理', $fr);
        $image = file_get_contents($fr);
        test_assert($image, '请求远程异常:' . $fr);
        $md5 = substr(md5($fr), 0, 16);
        $from = APP_PATH . '/storage/data/images/' . $md5 . '_fr';
        $dirname = dirname($from);
        if (!is_dir($dirname) || !file_exists($dirname)) {
            mkdir($dirname, 0755, true);
        }
        self::wr_image_face_log('写入文件', $from);
        $rs = file_put_contents($from, $image);
        test_assert($rs, '无法写入文件:' . $from);

        self::wr_image_face_log('开始处理', $fr2);
        $image = file_get_contents($fr2);
        test_assert($image, '请求远程异常:' . $fr2);
        $md5 = substr(md5($fr2), 0, 16);
        $from2 = APP_PATH . '/storage/data/images/' . $md5 . '_fr2';
        $dirname = dirname($from2);
        if (!is_dir($dirname) || !file_exists($dirname)) {
            mkdir($dirname, 0755, true);
        }
        self::wr_image_face_log('写入文件', $from2);
        $rs = file_put_contents($from2, $image);
        test_assert($rs, '无法写入文件:' . $from2);

        $cover = new CURLFile(realpath($from), mime_content_type($from));
        $cover2 = new CURLFile(realpath($from2), mime_content_type($from2));
        $data = [
            'source'   => $cover2,
            'target'   => $cover,
            'id'       => $id,
            'callback' => self::IMAGE_FACE_BACK_API,
            'project'  => SYSTEM_ID
        ];
        self::wr_image_face_log('请求参数', $data);
        $rs = LibUpload::execCurl(config('ai2.url'), $data);
        $url = $rs['imageUrl'] ?? '';
        self::wr_image_face_log('返回响应:', $rs);
        test_assert($url, '请求远程AI换头异常');
        unlink($from);
    }

    public static function image_face_back()
    {
        try {
            $id = (int)($_POST['id'] ?? 0);
            $img = trim($_POST['image'] ?? '');
            $code = (int)($_POST['code'] ?? 0);

            self::wr_image_face_log('收到回调', $_POST);
            test_assert($id, '回调异常');

            /** @var MemberFaceModel $item */
            $item = MemberFaceModel::where('id', $id)
                ->where('status', MemberFaceModel::STATUS_DOING)
                ->first();
            if (!$item) {
                exit('success');
            }

            if ($code == 0) {
                $item->status = MemberFaceModel::STATUS_FAIL;
                $item->reason = '换脸失败';
                $isOk = $item->save();
                test_assert($isOk, '系统异常');
                exit('success');
            }

            // 上传远程图片
            test_assert($img, '回调成功,图片地址异常');
            list($w, $h) = getimagesize($img);
            $url = self::upload_img($img, 1);
            $item->status = MemberFaceModel::STATUS_SUCCESS;
            $item->face_thumb = $url;
            $item->face_thumb_w = $w;
            $item->face_thumb_h = $h;
            $item->updated_at = \Carbon\Carbon::now();
            $isOk = $item->save();
            test_assert($isOk, '系统异常');
            exit('success');
        } catch (Throwable $e) {
            self::wr_image_face_log('出现异常', $e->getMessage());
            exit('fail');
        }
    }

    public static function image_face($task_id)
    {
        try {
            $item = MemberFaceModel::useWritePdo()
                ->where('id', $task_id)
                ->where('status', MemberFaceModel::STATUS_WAIT)
                ->first();
            test_assert($item, '任务不存在');
            self::image_face_api($item->id, TB_IMG_ADM_US . parse_url($item->ground, PHP_URL_PATH), TB_IMG_ADM_US . parse_url($item->thumb, PHP_URL_PATH));
            $item->status = MemberFaceModel::STATUS_DOING;
            $isOk = $item->save();
            test_assert($isOk, '系统异常');
        } catch (Throwable $e) {
            self::wr_image_face_log('出现异常', $e->getMessage());
        }
    }
    /*****************************************AI脱衣*********************************************/

    public static function start_task_strip(){
        $http = new HttpCurl();
        MemberStripModel::where('status', MemberStripModel::STATUS_WAIT)
            ->chunkById(100, function ($items) use ($http) {
                collect($items)->map(function (MemberStripModel $item) use ($http) {
                    try {
                        $header = [
                            'apikey:' . config('ai_strip.key'),
                            'Content-Type:application/x-www-form-urlencoded'
                        ];

                        $thumb = TB_IMG_ADM_US . '/' . ltrim(parse_url($item->thumb, PHP_URL_PATH), '/');
                        $bid = strtoupper(sprintf('%s_%s_%s', SYSTEM_ID, 'strip', $item->id));
                        $data = [
                            'source_path'     => $thumb,
                            'bid'             => $bid,
                            'fee'             => 10,
                            'notify_url'      => NOTIFY_BACK_URL . self::STRIP_NEW_BACK_URI,
                            'app_id'          => SYSTEM_ID
                        ];

                        self::wr_strip_log('调用参数', $data);
                        $url = config('ai_strip.url') . self::STRIP_NEW_API;
                        $rs = $http->post($url, $data, $header, true, 60);
                        self::wr_strip_log('返回数据', $rs);
                        test_assert($rs, '调用远程出现异常-001');
                        $rs = json_decode($rs, true);
                        test_assert($rs, '调用远程出现异常-002');
                        test_assert(!isset($rs['request_id']), '调用远程出现异常-003');
                        $item->task_id = $rs['task_id'];
                        $item->status = MemberStripModel::STATUS_DOING;
                        $is_ok = $item->save();
                        test_assert($is_ok, '出现异常');
                        self::wr_strip_log('任务ID', $rs['task_id']);
                    } catch (Throwable $e) {
                        wf('出现异常了', $e->getMessage(), false, self::STRIP_LOG_FILE);
                        $item->reason = $e->getMessage();
                        $is_ok = $item->save();
                        test_assert($is_ok, '出现异常');
                    }
                });
            });
        sleep(5);
    }

    /**
     * @throws \Exception
     */
    public static function on_strip($data){
        /**
         * @var $model MemberStripModel
         */
        $model = MemberStripModel::where('task_id', $data['task_id'])
            ->where('status', MemberStripModel::STATUS_DOING)
            ->first();
        test_assert($model, '记录不存在');

        if ($data['status'] != 2) {
            $model->status = MemberStripModel::STATUS_FAIL;
            $model->reason = $data['error'] ?? '';
            $is_ok = $model->save();
            test_assert($is_ok, '维护数据出现异常');
            return;
        }

        if (!count($data['out_data']) || !$data['out_data'][0]) {
            $model->status = MemberStripModel::STATUS_FAIL;
            $model->reason = $data['error'] ?? '';
            $is_ok = $model->save();
            test_assert($is_ok, '维护数据出现异常');
            return;
        }

        // 开始上传到远程
        $dir = APP_PATH . '/storage/data/images';
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        $new_url = $data['out_data'][0];
        // 上传远程图片
        test_assert($new_url, '回调成功,图片地址异常');
        list($w, $h) = getimagesize($new_url);
        $url = self::upload_img($new_url);
        $model->status = MemberStripModel::STATUS_SUCCESS;
        $model->strip_thumb = $url;
        $model->strip_thumb_w = $w;
        $model->strip_thumb_h = $h;
        $isOk = $model->save();
        test_assert($isOk, '维护数据出现异常');
    }

    /*****************************************AI图片换脸*********************************************/

    public static function start_task_face_img(){
        $http = new HttpCurl();
        MemberFaceModel::query()
            ->where('status', MemberFaceModel::STATUS_WAIT)
            ->chunkById(100, function ($items) use ($http){
                collect($items)->map(function (MemberFaceModel $item) use ($http) {
                    try {
                        $header = [
                            'apikey:' . config('ai_image_face.key'),
                            'Content-Type:application/x-www-form-urlencoded'
                        ];

                        $target_path = TB_IMG_ADM_US . '/' . ltrim(parse_url($item->ground, PHP_URL_PATH), '/');
                        $source_path = TB_IMG_ADM_US . '/' . ltrim(parse_url($item->thumb, PHP_URL_PATH), '/');
                        $bid = strtoupper(sprintf('%s_%s_%s', SYSTEM_ID, 'face_img', $item->id));
                        $data = [
                            'source_path' => $source_path,
                            'target_path' => $target_path,
                            'bid'         => $bid,
                            'fee'         => 10,
                            'title'       => '',
                            'notify_url'  => NOTIFY_BACK_URL . self::IMAGE_NEW_FACE_BACK_API,
                            'app_id'      => SYSTEM_ID
                        ];
                        self::wr_image_face_log('调用参数', $data);
                        $url = config('ai_image_face.url') . self::IMAGE_NEW_FACE_API;
                        self::wr_image_face_log('请求地址', $url);
                        $rs = $http->post($url, $data, $header, true,60);
                        self::wr_image_face_log('返回数据', $rs);
                        test_assert($rs, '调用远程出现异常-001');
                        $rs = json_decode($rs, true);
                        test_assert($rs, '调用远程出现异常-002');
                        test_assert(!isset($rs['request_id']), '调用远程出现异常-003');
                        self::wr_image_face_log('任务ID', $rs['task_id']);

                        $item->task_id = $rs['task_id'];
                        $item->status = MemberFaceModel::STATUS_DOING;
                        $isOk = $item->save();
                        test_assert($isOk, '结果保存失败');
                    } catch (Throwable $e) {
                        $item->reason = $e->getMessage();
                        $is_ok = $item->save();
                        test_assert($is_ok, '出现异常');
                    }
                });
            });
        sleep(5);
    }

    /**
     * @throws \Exception
     */
    public static function on_image_face($data){
        self::wr_image_face_log('回调来了:', $data);
        /**
         * @var $model MemberFaceModel
         */
        $model = MemberFaceModel::where('task_id', $data['task_id'])
            ->where('status', MemberFaceModel::STATUS_DOING)
            ->first();
        test_assert($model, '记录不存在');

        if ($data['status'] != 2) {
            $model->status = MemberFaceModel::STATUS_FAIL;
            $model->reason = $data['error'] ?? '';
            $is_ok = $model->save();
            test_assert($is_ok, '维护数据出现异常');
            return;
        }
        if (!count($data['out_data']) || !$data['out_data'][0]) {
            $model->status = MemberFaceModel::STATUS_FAIL;
            $model->reason = $data['error'] ?? '';
            $is_ok = $model->save();
            test_assert($is_ok, '维护数据出现异常');
            return;
        }

        // 开始上传到远程
        $dir = APP_PATH . '/storage/data/images';
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        $new_url = $data['out_data'][0];
        // 上传远程图片
        test_assert($new_url, '回调成功,图片地址异常');
        list($w, $h) = getimagesize($new_url);
        $url = self::upload_img($new_url);
        $model->status = MemberFaceModel::STATUS_SUCCESS;
        $model->face_thumb = $url;
        $model->face_thumb_w = $w;
        $model->face_thumb_h = $h;
        $is_ok = $model->save();
        test_assert($is_ok, '维护数据出现异常');
    }

    // ======================手动回调===========================
    public static function manual_callback($index)
    {
        $map = [
            // 图片换脸 0
            [
                'class'        => MemberFaceModel::class,
                'name'         => '图片换脸',
                'callback_url' => NOTIFY_BACK_URL . self::IMAGE_NEW_FACE_BACK_API,
                'api_url'      => config('ai_image_face.url') . self::AI_TASK_URI,
                'api_key'      => config('ai_image_face.key')
            ],
            // 脱衣 1
            [
                'class'        => MemberStripModel::class,
                'name'         => 'AI脱衣',
                'callback_url' => NOTIFY_BACK_URL . self::STRIP_NEW_BACK_URI,
                'api_url'      => config('ai_strip.url') . self::AI_TASK_URI,
                'api_key'      => config('ai_strip.key')
            ],
            // 魔法 2
            [
                'class'        => MemberMagicModel::class,
                'name'         => 'AI魔法',
                'callback_url' => NOTIFY_BACK_URL . '/index.php?m=ai&a=on_magic',
                'api_url'      => config('ai_magic.url') . self::AI_TASK_URI,
                'api_key'      => config('ai_magic.key')
            ]
        ];
        $item = $map[$index];
        pf('处理任务', $item['name']);
        self::process_item($item['class'], $item['callback_url'], $item['api_url'], $item['api_key']);
    }

    private static function process_item($class, $callback_url, $api_url, $api_key)
    {
        $class::select(['id', 'aff', 'task_id'])
            ->where('status', $class::STATUS_DOING)
            ->where('task_id', '!=', '')
            ->chunkById(20, function ($items) use ($callback_url, $api_url, $api_key) {
                try {
                    $ids = $items->pluck('task_id')->toArray();
                    $ids_str = implode(',', $ids);

                    $header = [
                        'apikey:' . $api_key
                    ];
                    $url = $api_url . '?task_list_str=' . $ids_str;
                    $rs = (new HttpCurl())->get($url, [], $header, 60);
                    test_assert($rs, '出现异常,地址:' . $url);
                    $rs = json_decode($rs, true);

                    test_assert($rs, '调用远程出现异常-002');
                    test_assert(!isset($rs['request_id']), '调用远程出现异常-003');

                    foreach ($rs as $item) {
                        if ($item['status'] == 0) {
                            continue;
                        }
                        $rs = self::call_api($callback_url, $item);
                        pf('处理结果', $rs == 'success' ? '成功' . $item['bid'] : '失败' . $item['bid']);
                    }
                } catch (Throwable $e) {
                    pf('异常', $e->getMessage());
                }
            });
    }

    private static function call_api($url, $data)
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);

        $options = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\n",
                'content' => $json,
                'timeout' => 15
            ]
        ];

        $context = stream_context_create($options);
        return file_get_contents($url, false, $context);
    }
}