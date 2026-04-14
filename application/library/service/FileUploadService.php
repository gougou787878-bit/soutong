<?php

namespace service;

use tools\CurlService;
use Yaf\Exception;

class FileUploadService
{

    const XF_UUID = 'e2a144721e9e6cbcc4855217de1cb94f';


    static function uploadMP4File()
    {
        $fileList = self::getFileList();
        if (!$fileList) {
            return;
        }
        echo "File \r\n";
        print_r($fileList);
        array_map([self::class, 'doUploadAndQiePian'], $fileList);
    }

    static function getFileList()
    {
        $fileMatch = APP_PATH . '/public/static/videomp4/*.mp4';
        //echo $fileMatch;
        $files = glob($fileMatch, GLOB_MARK);
        return $files;
    }

    static function doUploadAndQiePian($file)
    {
        echo str_repeat('#', 10) . " uploading \r\n";
        echo $file . PHP_EOL;
        $fileInfo = pathinfo($file);
        $baseFileName = $fileInfo['basename'];
        $fileName = $fileInfo['filename'];
        $source_origin = '';
        $time = TIMESTAMP;

        //rname 防止中文上传失败

        $_tpm_name = '/'.uniqid("ergou",true).'.mp4';
        $oldfile = $file;

        if(rename($file,$fileInfo['dirname'].$_tpm_name)){
            $file = $fileInfo['dirname'].$_tpm_name;
        }
        try {
            $uuid = self::XF_UUID;
            $updateData = CurlService::uploadMp42Remote($uuid, $file, config('upload.mp4_upload'));
            echo "respon \r\n";
            print_r($updateData);
            if (isset($updateData['code']) && $updateData['code'] == '1') {
                //success
                $source_origin = $updateData['msg'];
            }

        } catch (Exception $e) {
            echo "上传限制 \r\n";
            var_dump($e->getMessage());
            $log = "update-error-" . date("Y-m-d") . '.log';
            $msg = "$oldfile \r\n {$e->getMessage()} ############ \r\n";
            file_put_contents(APP_PATH . '/storage/logs/' . $log, $msg, FILE_APPEND);
            return;
        }
        //die($source_origin);die;
        if ($source_origin) {
            echo "上传成功\r\n";
            echo $source_origin;
            $log = "update-success-" . date("Y-m-d") . '.log';
            $msg = json_encode([$source_origin, $file]) . PHP_EOL;
            file_put_contents(APP_PATH . '/storage/logs/' . $log, $msg, FILE_APPEND);

            $uid = '99';
            $data = [
                'uid'          => $uid,
                'title'        => '[xblue]' . $fileName,
                'm3u8'         => $source_origin,
                'cover_thumb'  => '/new/xiao/20201120/2020112018294744986.jpeg',
                'thumb_height' => 0,
                'thumb_width'  => 0,
                'tags'         => '性感',
                'via'          => \MvModel::VIA_OFFICAL,
                'coins'        => 0,
                'created_at'   => time(),
                'music_id'     => 0,
                'is_feature'     => 1,
            ];
            $m = \MvSubmitModel::create($data);
            if ($m) {
                print_r($m->toArray());
            }
            @unlink($file);
            @unlink($oldfile);
        }

    }
}