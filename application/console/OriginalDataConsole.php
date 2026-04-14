<?php


namespace App\console;

use tools\HttpCurl;

class OriginalDataConsole extends AbstractConsole
{


    public $name = 'original-data';

    public $description = '同步原创数据';


    public function process($argc, $argv) {
        for ($i=1;$i<100;$i++){
            $params = ['page'=>$i,'limit'=>50];
            $url ="http://192.46.228.177:6529/gol_list";
            $resjson  =(new HttpCurl())->remoteGet($url,$params);
            if($resjson){
                $res = json_decode($resjson,true);
                $this->dealData($res);
            }else{
                var_dump('没有数据');
                break;
            }
        }
       echo "\r\n end ############ \r\n";
    }

    public function dealData($res){
        if(!empty($res['data'])){
            foreach ($res['data'] as $item){
                /** @var \OriginalModel $original */
                $original = \OriginalModel::query()->where('source_id',$item['id'])->first();
                if($original){
                    if($original->is_series == 0 && $original->video_num != 0){
                        continue;
                    }
                }else{
                    $original =   $this->saveOriginal($item);
                }
                if($original &&  $mum = count($item['video'])){
                    foreach ($item['video'] as $videoData){
                        $video = \OriginalVideoModel::query()->where('source_video_id',$videoData['id'])->first();
                        if(!$video){
                            $this->saveVideo($videoData,$original->id);
                        }
                    }
                    $original->update(['video_num'=>$mum]);
                }
            }
        }
    }

    public function saveOriginal($item){
        $insertData['title'] = $item['title'];
        $insertData['desc'] = $item['description'];
        $insertData['actors'] = $item['actor'];
        $insertData['category'] = $item['cate_name'];
        $insertData['country'] = $item['country'];
        $insertData['directors'] = $item['director'];
        $insertData['is_series'] = $item['is_series'];
        $insertData['cover'] = $item['cover'];
        $insertData['tags'] = $item['tags'];
        $insertData['langs'] = $item['langs'];
        $insertData['year_released'] = $item['year_released'];
        $insertData['source_id'] = $item['id'];
        $insertData['status'] = 0;
        return \OriginalModel::create($insertData);
    }
    public function saveVideo($videoData,$pid){
        $insert['pid'] = $pid;
        $insert['cover'] = $videoData['cover'];
        $insert['duration'] = $videoData['duration'];
        $insert['height'] = $videoData['height'];
        $insert['source_video_id'] = $videoData['id'];
        $insert['sort'] = $videoData['sort'];
        $insert['type'] = $videoData['type'];
        $insert['source'] = $videoData['video_path'];
        $insert['width'] = $videoData['width'];
        $insert['status'] = 1;
        \OriginalVideoModel::create($insert);
    }




}