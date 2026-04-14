<?php


namespace App\console;

use App\console\Queue\QueueOption;

class GrabStoryConsole extends AbstractConsole
{

    public $name = 'grab-story';
    public $description = '抓取小说入库';
    const SELF_YES = 1;
    const SELF_NO = 0;

    ////nohup php yaf grab-story 1 3000 > grab-story.log 2>&1 &

    //eg test http://172.104.35.32:8888/index.php?m=story&a=index&id=1000
    public function process($argc, $argv)
    {
        if ($argc >= 2) {
            $min = $max = $argv[1];
            if (isset($argv[2])) {
                $max = $argv[2];
            }
            echo "FROM:{$min}-{$max}" . PHP_EOL;
            $this->grabByUcmh($min, $max);
            //print_r([$argv,$argv]);
            return;
        }
        $lastMin = setting('story.number', 0);
        $max = $lastMin + 20;
        echo "FROM:{$lastMin}-{$max}" . PHP_EOL;
        $this->grabByUcmh($lastMin + 1, $max);
    }

    static function getData($id)
    {
        $url = "http://172.104.35.32:8888/index.php?m=story&a=index&id={$id}";
        $jsonString = getRemoteData($url);
        if ($jsonString) {
            return json_decode($jsonString, true);
        }
        return null;
    }

    public function grabByUcmh($from, $to)
    {
        for ($i = $from; $i <= $to; $i++) {
            $response = self::getData($i);
            errLog("respons:{$i}" . var_export($response, true));
            //print_r($response);die;
            if (!$response || !$response['data'] || !$response['data']['episodes'] || !$response['data']['episodes'][0]) {
                \SettingModel::set('story.number', $i);
                continue;
            }
            $data = $response['data'];
            $existComic = \StoryModel::where(['origin_id' => $data['id'] . '_sy'])->first();
            if (is_null($existComic)) {
                $tags = $data['cate_name'];
                if ($tags) {
                    $tags .= ',' . $data['tags'];
                }
                $insertData = [
                    'origin_id'     => $data['id'] . '_sy', //资源id
                    'title'         => $data['title'], //标题
                    'desc'          => $data['desc'], //描述
                    'author'        => $data['desc'], //作者
                    'category_id'   => 0, //漫画分类标识
                    'uid'           => 1, //用户id
                    'thumb'         => $data['upload']??'', //封面图
                    'favorites'     => 0, //收藏人数
                    'tags'          => $tags, //标签
                    'is_finish'     => $data['update_status'] != '已完结' ? 0 : 1, //状态 0 未完结， 1已完结
                    'update_time'   => $data['update_time'], //更新时间 周一 - 周日
                    'recommend'     => self::SELF_NO, //是否推荐
                    'status'        => self::SELF_YES, //1上架0下架
                    'is_free'       => 1, //0 免费 1 vip 2  钻石（金币）
                    'refresh_at'    => date('Y-m-d H:i:s'), //刷新时间
                    'rating'        => rand(1111, 99999), //浏览数
                    'coins'         => 0, //定价
                    'newest_series' => $data['lastid_episode'], //最近更新到的章节
                    'type'          => '1', // 类型  long  short  single
                ];
                $existComic = \StoryModel::create($insertData);

                echo "insert-story-{$existComic->id}：{$existComic->title}" . PHP_EOL;
            }
            if ($list = $data['episodes'][0]['list']) {
                foreach ($list as $key => $value) {
                    $existSeries = \StorySeriesModel::where([
                        'story_id' => $existComic->id,
                        'series'   => $value['sort']
                    ])->first();
                    if (is_null($existSeries)) {
                        $serData = [
                            'story_id'    => $existComic->id,
                            'series'      => $value['sort'],
                            'title'       => $value['title'],
                            'is_free'     => 1,
                            'views_count' => rand(1111, 99999),
                            'status'      => 1,
                            'created_at'  => date('Y-m-d H:i:s'), //刷新时间
                            'updated_at'  => date('Y-m-d H:i:s'), //刷新时间
                            'url'         => $value['upload'],

                        ];
                        $existSeries = \StorySeriesModel::create($serData);
                        echo "story-src-{$existSeries->id}-{$existSeries->series}：{$existSeries->url} {$existComic->title}" . PHP_EOL;
                    }
                }
            }

            \SettingModel::set('story.number', $i);
        }
        return true;
    }

}
