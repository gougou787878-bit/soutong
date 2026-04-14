<?php

namespace service;

/**
 * Class VideoScoreService
 * @package service
 */
class VideoScoreService
{

    const VIDEO_SCORE_KEY = 'video:score:gv';
    const VIDEO_INIT_KEY = 'video:score:init';
    const VIDEO_SCORE_COIN_KEY = 'video-coin:score:gv';


    public function calcScore($vid, $uid, $duration, $video_time)
    {
        if ($video_time <= 0) {
            return;
        }
        if ($duration > $video_time + 2) {
            return;
        }
        if ($duration > $video_time) {
            $duration = $video_time;
        }
        //检查视频有没有要求不在计算
        //检查视频有没有被用户上报过
        //计算完播率
        $skip_key = "rate-skip:" . ($vid % 20); // 使用 %分桶，放在不同的桶下面，避免过多的key， 因为只会打个标示，并不会存在性能问题,
        $skip_vu = "rate-uid-ary:" . $vid;
        if (redis()->hExists($skip_key, $vid) || redis()->sIsMember($skip_vu, $uid)) {
            return;
        }

        redis()->sAdd($skip_vu, $uid);//给视频的观看者打标示


        list($avgEndRate, $avgEndTime, $watch_count) = $this->finish_rate($vid, $duration, $video_time);
        $poolAry = [10, 50, 100, 500, 1000, 2000, 5000, 10000];

        if (!in_array($watch_count, $poolAry)) {
            //减少数据库影响
            return;
        }

        if ($watch_count >= max($poolAry)) {
            //打标示，本次执行后，不会在进入里面执行了
            redis()->hSet($skip_key, $vid, 1);
        }

        $mvModel = \MvModel::find($vid);

        if (false){
            //搜同片没有这些字段 字段
            $comment_rate = $mvModel->count_comment / $mvModel->count_play;
            $like_rate = $mvModel->count_like / $mvModel->count_play;
            $rate = $comment_rate + $like_rate;
            if ($watch_count < 50 || $rate >= 0.5 || ($rate / $avgEndRate) >= 0.5) {
                // 前50次不计入统计
                // 点赞 + 频率 超过了 0.5分
                // 点赞 + 频率 大于平均播放时长的0.5倍
                $rate = 0;
            }
            $rate_pre = $rate + $avgEndRate;
            $rate = $rate_pre > 1 ? $avgEndRate : $rate_pre;
        }else{
            $rate = $avgEndRate;
        }


        $minute = ceil((time() - $mvModel->created_at) / 60);

        $e = 0.0002;//系数，系数越大单位时间间隔影响越大
        $_rate = exp(-$e * $minute) * $rate;
        $rate_time = round($_rate, 9);

        $temp = [
            'vid'          => $vid,
            'vtype'        => 1,
            'needCoin'     => $mvModel->coins > 0 ? 1 : 0,
            'duration'     => $mvModel->duration,
            'avgEndTime'   => $avgEndTime,
            'avgEndRate'   => $avgEndRate,
            'replyRate'    => 0 , //$comment_rate,
            'likesRate'    => 0 , //$like_rate,
            'created_at'   => TIMESTAMP,
            'test_times'   => $watch_count,
            'published_at' => $mvModel->created_at,
            'rate'         => $rate, // 综合品分，包含 播放时长，点赞，评论
            'rate_time'    => $rate_time,
        ];

        $where = ['vid' => $vid];
        \VideoScoreModel::updateOrCreate($where, $temp);

        if ($watch_count >= 50) {
            if (redis()->sIsMember(self::VIDEO_INIT_KEY, $vid)) {
                redis()->sRem(self::VIDEO_INIT_KEY, $vid);
            }
            if ($mvModel->coins > 0) {
                redis()->zAdd(self::VIDEO_SCORE_COIN_KEY, $rate, $vid); //使用综合品分
            } else {
                redis()->zAdd(self::VIDEO_SCORE_KEY, $rate, $vid); //使用综合品分
            }
        }


        //redis()->zAdd('z:video:score:featured', $rate_time, $vid); //使用时间系数
    }


    /**
     * 计算完播放率
     * @param int $vid 视频id
     * @param int $watchTime 观看时长，秒
     * @param int $videoTime 视频时长，秒
     * @return float[] [完播率，平均播放时间, 观看次数, 视频时长,]
     */
    private function finish_rate(int $vid, int $watchTime, int $videoTime)
    {
        $key = "rate:vid-" . $vid;
        $watchTime = max(0, min($watchTime, $videoTime));//防止观看时间大于视频时间，或者小于0
        $data = redis()->hMGet($key, ['c', 'wt', 'vt']); // c=次数,wt=观看总时长,vt=视频时长
        if (empty($data) || empty($data['vt'])) {
            $data = ['c' => 1, 'wt' => $watchTime, 'vt' => $videoTime];
            redis()->hMSet($key, $data);
        } else {
            if ($data['vt'] != $videoTime) {
                $data['vt'] = $videoTime;
            }
            $data['c'] += 1;
            $data['wt'] += $watchTime;
            redis()->hMSet($key, $data);
        }

        $rate = (float)number_format($data['wt'] / ($data['vt'] * $data['c']), 2, '.', '');
        $time = intval($data['wt'] / $data['c']);
        return [$rate, $time, $data['c'], $data['vt']];
    }


}