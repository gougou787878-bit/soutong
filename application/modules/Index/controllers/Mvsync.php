<?php

use service\TopCreatorService;

/**
 *
 * 资源中心数据
 *
 */

class MvsyncController extends SiteController
{

    public $post = null;

    public function init()
    {
//https://aff.gvlan.club/index.php?&m=mvsync&a=data
    }

    /*
    sync_data--
    Array
    (
    [title] => 「果冻传媒」家教老师的肉体奖励 / GDCM-004 / 云朵
    [_id] => GDCM-004
    [mod] => 0  1横屏视频； 2 竖屏视频;
    [type] => 1   1长视频； 2短视频； 3gay视频
    [category] => pili-3d
    [category_id] => 106
    [p_id] => 3428803
    [duration] => 0
    [source] => /watch8/590631034acb087fef9482705e7b0af8/590631034acb087fef9482705e7b0af8.m3u8
    [cover_thumb] => /new/upload/20220416/2022041615180154367.png
    [cover_full] => /new/upload/20220416/2022041615180257901.png
    [actors] => 云朵
    [tags] => 中文字幕,国产,女神,美臀
    [directors] =>
    [coins] => 0
    [uuid] =>
    [release_at] =>
    [created_at] => 1650093476
    [sign] => 33243cf0c31f89a10dc77dab47db35ee
    )*/
    public function dataAction()
    {
        if (!$this->getRequest()->isPost()) {
            exit('fail');
        }
        $input = file_get_contents('php://input');
        $post = [];
        if (!empty($input)) {
            parse_str($input, $post);
        }
        if (!$post['source']) {
            exit('fail');
        }
        trigger_log("sync_data--\n" . print_r($post, true));
        //return;
        $sign = $this->getSign($post);
        if ($sign != $post['sign']) {
            exit('fail');
        }
        $data = MvModel::where('music_id', $post['p_id'])->first();
        if ($data) {
            exit('fail');
        }
        $fan_id = $post['_id'];
        $p_id = $post['p_id'];
        $uidData = [10065,100039,100047,100037,100114];
        shuffle($uidData);

        $insertData = [
            'uid'              => $uidData[0],
            'music_id'         => $p_id,
            'coins'            => $post['coins'],
            'vip_coins'        => -1,
            'title'            => $post['title'],
            'm3u8'             => $post['source'],
            'full_m3u8'        => '',
            'v_ext'            => 'm3u8',
            'duration'         => $post['duration'],
            'cover_thumb'      => $post['cover_thumb'],//封面
            'thumb_width'      => 0,
            'thumb_height'     => 0,
            'gif_thumb'        => $post['cover_full'],//封面 竖
            'gif_width'        => 0,
            'gif_height'       => 0,
            'directors'        => $fan_id,
            'actors'           => $post['actors'],
            'category'         => $post['tags'],
            'tags'             => $post['tags'],
            'via'              => 'zx',
            'onshelf_tm'       => $post['created_at'],
            'rating'           => rand(6666, 9999),
            'refresh_at'       => time(),
            'is_free'          => 0,//收费
            'like'             => rand(666, 9999),
            'comment'          => 0,
            'status'           => MvModel::STAT_CALLBACK_DONE,
            'thumb_start_time' => 40,
            'thumb_duration'   => 30,
            'is_hide'          => 1,
            'created_at'       => TIMESTAMP,
            'is_recommend'     => 1,
            'is_feature'       => 0,
            'is_top'           => 0,
            'count_pay'        => 0,
            'is_18'            => MvModel::IS_18_NO
        ];
        try {
            MvModel::create($insertData);
            (new TopCreatorService())->incrUp($insertData['uid']);//视频上传创作排行统计
        } catch (Exception $e) {
            errLog("zycx-error:" . $e->getMessage());
            exit('fail');
        }
        exit('success');
    }

    private function getSign($data)
    {
        unset($data['sign']);
        $signKey = config('app.data_sync_key');

        ksort($data);
        $string = '';
        foreach ($data as $key => $datum) {
            if ($datum === '') {
                continue;
            }
            $string .= "{$key}={$datum}&";
        }
        $string .= 'key=' . $signKey;
        return md5($string);
    }
}