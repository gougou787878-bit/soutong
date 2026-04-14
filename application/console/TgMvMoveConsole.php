<?php


namespace App\console;


use Carbon\Carbon;
use MvModel;
use MvTgModel;

class TgMvMoveConsole extends AbstractConsole
{


    public $name = 'tg-mv-move';

    public $description = 'TG视频移动';


    public function process($argc, $argv) {
        set_time_limit(0);
       echo "start 开始\r\n";

        $ct =  MvTgModel::query()->where('status', 3)->count('id');
        if ($ct <= 0){
            echo PHP_EOL, "没有需要移动的视频", PHP_EOL;
            return;
        }

        echo PHP_EOL, "移动视频开始", PHP_EOL;

        $uuids = \MemberMakerModel::pluck('uuid')->toArray();
        $uids = \MemberModel::whereIn('uuid', $uuids)->pluck('uid')->toArray();

        /** 查出5条切片 */
        MvTgModel::query()
            ->where('status', 3)
            ->chunkById(500, function ($items) use ($uids) {
                collect($items)->each(function (MvTgModel $model) use ($uids){
                    $pid = $model->id;
                    $via = 'gtt0105';
                    $data = MvModel::where('music_id', $pid)->where('via', $via)->first();
                    if ($data) {
                        return true;
                    }

                    $index = array_rand($uids);
                    $uid = $uids[$index];
                    //标签
                    preg_match_all('/#(\S+)/u', $model->title, $matches);
                    $tags = $matches[1];
                    if ($tags){
                        $tags = implode(',', $tags);
                    }else{
                        $tags = '';
                    }

                    $title = str_replace('👉👉  @boygv  👈👈', '', $model->title);
                    $title = str_replace('👉👉  @boygv  👈👈', '', $title);
                    $title = trim($title);
                    $rand_num = rand(1, 10);
                    $newPrice = 0;
                    //一半金币 一半VIP
                    if ($rand_num > 5){
                        $newPrice = rand(1, 3);  // 随机选择 1 或 3
                    }
                    // 插入数据
                    $data = [
                        'uid'              => $uid,
                        'music_id'         => $pid,
                        'coins'            => $newPrice,
                        'vip_coins'        => -1,
                        'title'            => $title,
                        'm3u8'             => $model->m3u8,
                        'full_m3u8'        => '',
                        'v_ext'            => 'm3u8',
                        'duration'         => $model->duration,
                        'cover_thumb'      => $model->cover,//封面
                        'thumb_width'      => 0,
                        'thumb_height'     => 0,
                        'gif_thumb'        => $model->cover,//封面 竖
                        'gif_width'        => 0,
                        'gif_height'       => 0,
                        'directors'        => 0,
                        'actors'           => '',
                        'category'         => '',
                        'tags'             => $tags,
                        'via'              => $via,
                        'onshelf_tm'       => 0,
                        'rating'           => rand(6666, 9999),
                        'refresh_at'       => time(),
                        'is_free'          => 1,//收费
                        'like'             => rand(666, 9999),
                        'comment'          => 0,
                        'status'           => MvModel::STAT_CALLBACK_DONE,
                        'thumb_start_time' => 40,
                        'thumb_duration'   => 30,
                        'is_hide'          => MvModel::IS_HIDE_NO,
                        'created_at'       => TIMESTAMP,
                        'is_recommend'     => 1,
                        'is_feature'       => 0,
                        'is_top'           => 0,
                        'count_pay'        => 0,
                        'type'             => MvModel::TYPE_SHORT,
                    ];
                    $isOk = MvModel::create($data);
                    if ($isOk){
                        $model->status = 4;
                        $model->updated_at = Carbon::now();
                        $model->save();
                        echo "TG视频移动成功.标题:" . $model->title, PHP_EOL;
                    }

                    echo "已同步视频:" . $model->id . PHP_EOL;
                });
            });

      echo PHP_EOL, "移动视频结束", PHP_EOL;
    }
}