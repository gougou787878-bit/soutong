<?php


namespace App\console;

use App\console\Queue\QueueOption;

class GrabPicUcmhConsole extends AbstractConsole
{

    public $name = 'grab-ucmh';
    public $description = '抓取图片入库';
    const SELF_YES = 1;
    const SELF_NO = 0;

    ////nohup php yaf grab-ucmh 3 1000 > grab-ucmh.log 2>&1 &

    //eg test http://172.104.35.32:8888/index.php?id=1
    public function process($argc, $argv)
    {
        if ($argc >= 2) {
            $min = $max = $argv[1];
            if (isset($argv[2])) {
                $max = $argv[2];
            }
            echo "FROM:{$min}-{$max}".PHP_EOL;
            $this->grabByUcmh($min, $max);
            //print_r([$argv,$argv]);
            return;
        }
        $lastMin = setting('uc.number', 0);
        $max = $lastMin + 20;
        echo "FROM:{$lastMin}-{$max}".PHP_EOL;
        $this->grabByUcmh($lastMin + 1, $max);
    }

    static function getData($id)
    {
        $url = config('grab.ucmh.url') . "?id={$id}";
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
                \SettingModel::set('uc.number',$i);
                continue;
            }
            $data = $response['data'];
            $existComic = \MhModel::where(['origin_id' => $data['id'] . '_uc'])->first();
            if (is_null($existComic)) {
                $comic = new \stdClass();
                $comic->cate_id = $data['id'];
                $comic->title = $data['title'];
                $comic->desc = $data['desc'];
                $comic->author = $data['author'];
                $comic->upload = $data['upload'];
                $comic->update_status = $data['update_status'];
                $comic->update_time = $data['update_time'];
                $existComic = $this->insertComic($comic);

                //统计
                \SysTotalModel::incrBy('now:manhua');
                //echo "insert-cmic-{$existComic->id}：{$existComic->title}" . PHP_EOL;
            }
            foreach ($data['episodes'] as $key => $value) {
                $existSeries = \MhSeriesModel::where(['pid' => $existComic->id, 'episode' => $value['id']])->first();
                if (is_null($existSeries)) {
                    $series = new \stdClass();
                    $series->pid = $existComic->id;
                    $series->episode = $value['id'];
                    $existSeries = $this->insertSeries($series);
                    $existComic->update(['newest_series' => $existSeries->episode]);
                    //echo "insert-series-{$existSeries->id}：{$value['id']} {$existComic->title}" . PHP_EOL;
                }
                if (isset($value['pics']) && $value['pics']) {
                    foreach ($value['pics'] as $k => $v) {
                        $item = (object)$v;
                        $item->m_id = $existComic->id;
                        $item->s_id = $existSeries->episode;
                        $this->insertSrc($item);
                        echo "insert-src-{$existSeries->id}-{$item->cate_sub_id}：{$item->upload} {$existComic->title}" . PHP_EOL;
                    }
                }
            }
            \SettingModel::set('uc.number',$i);
        }
        return true;
    }


    protected function insertComic($item)
    {
        if (trim($item->update_status) == '连载中') {
            $is_finish = 0;
        } else {
            $is_finish = 1;
        }
        $insertData = [
            'origin_id'     => $item->cate_id . '_uc', //资源id
            'title'         => $item->title, //标题
            'description'   => $item->desc, //描述
            'author'        => $item->author, //作者
            'category_id'   => 0, //漫画分类标识
            'uid'           => 1, //用户id
            'bg_thumb'      => '', //详情背景图
            'thumb'         => $item->upload, //封面图
            'favorites'     => 0, //收藏人数
            'tags'          => '', //标签
            'is_finish'     => $is_finish, //状态 0 未完结， 1已完结
            'update_time'   => $item->update_status, //更新时间 周一 - 周日
            'recommend'     => self::SELF_NO, //是否推荐
            'status'        => self::SELF_YES, //1上架0下架
            'is_free'       => 1, //0 免费 1 vip 2  钻石（金币）
            'refresh_at'    => $item->update_time, //刷新时间
            'rating'        => 0, //浏览数
            'coins'         => 0, //定价
            'from'          => 1, //来源
            'newest_series' => '', //最近更新到的章节
            'type'          => '', // 类型  long  short  single
        ];
        return \MhModel::create($insertData);
    }

    protected function insertSeries($item)
    {
        $insertData = [
            'pid'     => $item->pid, //漫画编号 id
            'episode' => $item->episode, //章节编号
            'thumb'   => '', //封面url
            'from'    => 1,
        ];
        return \MhSeriesModel::create($insertData);
    }

    protected function insertSrc($item)
    {
        $insertData = [
            'm_id'       => $item->m_id, //漫画ID，剧集ID
            's_id'       => $item->s_id, //章节ID，单本默认1
            'img_url'    => $item->upload, //图片地址
            'img_width'  => $item->width,
            'img_height' => $item->height,
            'from'       => 1,
        ];
        return \MhSrcModel::create($insertData);
    }

}
