<?php

use service\AiSdkService;
use service\ApiAiDrawService;
use service\ApiAiFaceSwapVideoService;
use service\ApiAiMagicService;
use service\ApiAiNovelService;

class AiController extends SiteController
{
    protected function parser_post()
    {
        $raw = file_get_contents('php://input');
        test_assert($raw, '接受数据异常');

        $rs = json_decode($raw, true);
        test_assert($rs, '解析数据异常');

        // TODO验证签名
        return $rs;
    }

    //AI换脸
    public function on_image_faceAction()
    {
        try {
            $post = $this->parser_post();
            wf('AI图片换脸结果回调', $post);
            AiSdkService::on_image_face($post);
            exit('success');
        } catch (Throwable $e) {
            wf('AI图片换脸出现异常', $e->getMessage());
            exit('fail');
        }
    }

    //AI脱衣
    public function on_stripAction()
    {
        try {
            $post = $this->parser_post();
            wf('AI脱衣结果回调', $post);
            AiSdkService::on_strip($post);
            exit('success');
        } catch (Throwable $e) {
            wf('AI脱衣出现异常', $e->getMessage());
            exit('fail');
        }
    }

    // AI魔法
    public function on_magicAction()
    {
        try {
            $post = $this->parser_post();
            wf('魔法结果回调', $post);
            ApiAiMagicService::on_magic($post);
            exit('success');
        } catch (Throwable $e) {
            wf('魔法出现异常', $e->getMessage());
            exit('fail');
        }
    }

    // AI魔法视频资源回调
    public function on_magic_sliceAction()
    {
        try {
            wf('魔法视频切片回调', '');
            ApiAiMagicService::magic_slice();
            exit('success');
        } catch (Throwable $e) {
            wf('魔法切片出现异常', $e->getMessage());
            exit('fail');
        }
    }
}