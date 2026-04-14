<?php


namespace App\console;


use DB;

class ImportPicConsole extends AbstractConsole
{

    public $name = 'import-pic';

    public $description = '导入图片数据';


    public function process($argc, $argv)
    {
        for ($i = 1; $i <= 1436; $i++) {

            $pic = \PictureModel::find($i);
            if (is_null($pic)) {
                continue;
            }
            $total = \PictureSrcModel::where('picture_id', '=', $pic->id)->count('id');
            $flag = $pic->update(['total' => $total]);

            echo "pic:{$pic->id}  total:{$total} Flag:{$flag}" . PHP_EOL;
        }

        echo "#################  over ############## \r\n ";
    }

    public function importData()
    {
        $pic_id = 1461;
        for ($i = 1; $i <= $pic_id; $i++) {
            $picData = DB::query()->from('sq_picture')->where('id', $i)->first();
            if (is_null($picData)) {
                echo "no data {$i}" . PHP_EOL;
                continue;
            }
            $picSrcData = DB::query()->from('sq_picture_value')->where('picture_id', $i)->get();

            if ($picSrcData) {

                $model = \PictureModel::create([
                    'p_id'        => 'yan' . $picData->id,
                    'title'       => $picData->title,
                    'desc'        => $picData->desc ?: $picData->title,
                    'thumb'       => $picData->thumb,
                    'category_id' => 0,
                    'tags'        => $picData->tags,
                    'is_free'     => 1,
                    'rating'      => rand(100000, 99999),
                    'favorites'   => rand(10000, 9999),
                    'refresh_at'  => date('Y-m-d H:i:s', strtotime('-1 month')),
                    'recommend'   => 1,
                    'coins'       => 0,
                    'status'      => 1,
                ]);
                if ($model) {
                    foreach ($picSrcData as $srcData) {
                        $flag = \PictureSrcModel::insert([
                            'picture_id' => $model->id,
                            'img_url'    => $srcData->original_url ?: $srcData->thumb_url,
                            'img_width'  => 0,
                            'img_height' => 0,
                        ]);
                        echo "pic:{$model->id}  src:{$srcData->id} insertFlag:{$flag}" . PHP_EOL;
                    }
                }
            }

        }
    }

}