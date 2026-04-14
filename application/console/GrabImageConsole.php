<?php


namespace App\console;

use App\console\Queue\QueueOption;

class GrabImageConsole extends AbstractConsole
{

    public $name = 'grab-image';
    public $description = '同步男同图片入库';
    const SELF_YES = 1;
    const SELF_NO = 0;

    //nohup php yaf grab-image 3 1000 > grab-image.log 2>&1 &
    //eg   http://172.104.35.32:8888/index.php?m=picture&a=gay&id=850

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
        $lastMin = setting('image.number', 0);
        $max = $lastMin + 20;
        echo "FROM:{$lastMin}-{$max}" . PHP_EOL;
        $this->grabByUcmh($lastMin + 1, $max);
    }

    static function getData($id)
    {
        $url = config('grab.img_url') . "?m=picture&a=gay&id={$id}";
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
            if (!$response || !$response['data'] || !$response['data']['episodes']) {
                \SettingModel::set('image.number', $i);
                continue;
            }
            $data = $response['data'];
            $existComic = \PictureModel::where(['p_id' => $data['id'] . '_sy'])->first();
            if (is_null($existComic)) {
                $tags = $data['cate_name'];

                $insertData = [
                    'p_id'        => $data['id'] . '_sy', //资源id
                    'title'       => $data['title'], //标题
                    'desc'        => $data['desc'], //描述
                    'author'      => $data['author'] ?? '蓝奢', //作者
                    'category_id' => 0, //漫画分类标识
                    'uid'         => 1, //用户id
                    'thumb'       => $data['upload'] ?? '', //封面图
                    'favorites'   => 0, //收藏人数
                    'tags'        => $tags, //标签
                    'recommend'   => self::SELF_NO, //是否推荐
                    'status'      => self::SELF_YES, //1上架0下架
                    'is_free'     => 1, //0 免费 1 vip 2  钻石（金币）
                    'refresh_at'  => date('Y-m-d H:i:s'), //刷新时间
                    'rating'      => rand(1111, 99999), //浏览数
                    'coins'       => 0, //定价
                    'total'       => $data['count_episode'], //最近更新到的章节
                ];
                $existComic = \PictureModel::create($insertData);
                //统计
                \SysTotalModel::incrBy('now:pic');
                echo "insert-image-{$existComic->id}：{$existComic->title}" . PHP_EOL;
            }
            if ($list = $data['episodes']) {
                foreach ($list as $key => $value) {
                        $serData = [
                            'picture_id' => $existComic->id,
                            'img_url'    => $value['upload'],
                            'img_width'  => $value['width'],
                            'img_height' => $value['height'],

                        ];
                        $existSeries = \PictureSrcModel::create($serData);
                        echo "story-src-{$existSeries->id}-{$existSeries->series}：{$existSeries->url} {$existComic->title}" . PHP_EOL;

                }
            }

            \SettingModel::set('image.number', $i);
        }
        return true;
    }

}
