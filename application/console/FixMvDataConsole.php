<?php


namespace App\console;


use App\console\Queue\QueueOption;

class FixMvDataConsole extends AbstractConsole
{

    public $name = 'fix-mv-data';

    public $description = 'mv数据打散及统计修复';


    public function process($argc, $argv)
    {

        $data = \DB::connection('spider_sync')->table('comics')->limit(2000)->get();
        print_r($data->toArray());
        foreach ($data as $_comics) {
            $insert = [
                'origin_id'     => $_comics->id,
                'title'         => $_comics->title,
                'description'   => $_comics->description,
                'author'        => "蓝蓝",
                'category_id'   => $_comics->id,
                'uid'           => 0,
                'bg_thumb'      => $_comics->bg_thumb,
                'thumb'         => $_comics->thumb,
                'favorites'     => $_comics->favorites,
                'tags'          => $_comics->tags,
                'is_finish'     => $_comics->status,
                'update_time'   => $_comics->update_time,
                'status'        => $_comics->obtained,
                'is_free'       => $_comics->is_type,
                'refresh_at'    => date("Y-m-d H:i:s"),
                'rating'        => $_comics->view_count,
                'from'          => $_comics->from,
                'coins'         => $_comics->coins,
                'newest_series' => 1,
                'type'          => 'short',
            ];
            if (!\MhModel::where('origin_id', $insert['origin_id'])->exists()) {
               $flag =  \MhModel::insert($insert);
               echo "MhModel:{$insert['origin_id']} {$flag}".PHP_EOL;
            }
            $comics_series = \DB::connection('spider_sync')->table('comics_series')->where(['pid' => $_comics->id])->get();
            if ($comics_series) {
                foreach ($comics_series as $_k => $_series) {
                    $ser = \MhSeriesModel::insert([
                        'pid'     => $_series->pid,
                        'episode' => $_series->episode,
                        'thumb'   => '',
                        'from'    => 0
                    ]);
                    echo "MhSeriesModel:{$_series->pid} {$_series->episode} {$ser}".PHP_EOL;
                    if ($_k > 2) {
                        \MhModel::where('origin_id',
                            $insert['origin_id'])->update(['newest_series' => $_series->episode, 'type' => 'long']);
                    }
                    $comics_series_src = \DB::connection('spider_sync')
                        ->table('comics_series_src')
                        ->where(['m_id' => $_comics->id, 's_id' => $_series->episode])
                        ->get();
                    if ($comics_series_src) {
                        foreach ($comics_series_src as $v) {
                            $src = \MhSrcModel::insert([
                                'm_id'       => $v->m_id,
                                's_id'       => $v->s_id,
                                'img_url'    => $v->img_url,
                                'img_width'  => $v->img_width,
                                'img_height' => $v->img_height,
                                'from'       => $v->from,
                            ]);
                            echo "MhSeriesModel:{$v->m_id} {$v->s_id} {$src}".PHP_EOL;
                        }
                    }

                }
            }


        }

        echo "end".PHP_EOL;


    }


}