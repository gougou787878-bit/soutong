<?php


namespace App\console;


use MvTgModel;

class TgMvSliceConsole extends AbstractConsole
{


    public $name = 'tg-mv-slice';

    public $description = 'TG视频切片';


    public function process($argc, $argv) {
        set_time_limit(0);
       echo "start 开始\r\n";

        $ct =  MvTgModel::query()->where('status', 2)->count('id');
        if ($ct >= 2){
            echo PHP_EOL, "删除视频开始", PHP_EOL;
            self::tg_mv_del();
            echo PHP_EOL, "删除视频结束", PHP_EOL;
            return;
        }
        /** 查出5条切片 */
        MvTgModel::query()
            ->where('status', 1)
            ->orderBy('id')
            ->limit(1)
            ->get()
            ->map(function (MvTgModel $model){
                $path = $model->local_path;
                $path = str_replace('gtt0105/file', 'tg_mv', $path);
                $path = "https://notify.stgay.pro/" . $path;
                $data = [
                    'uuid'    => 0,
                    'm_id'    => $model->id,
                    'needImg' => 1,
                    'needMp3' => 0,
                    'playUrl' => $path
                ];
                $crypt = new \tools\CryptService();
                $sign = $crypt->make_sign($data);
                $data['sign'] = $sign;
                $data['notifyUrl'] = 'https://notify.stgay.pro/index.php?m=mv&a=tgMvVideo';

                $curl = new \tools\CurlService();
                $return = $curl->request('http://examine-new.xmyy8.co/queue.php', $data);
                errLog("post reslice req:" . var_export([$data, $return], true));
                if ($return != 'success') {
                    trigger_error('审核失败-----' . print_r($return, true));
                    return false;
                }
                $model->status = 2;
                $model->save();
                return true;
            });

       echo "\r\n end 结束 \r\n";

       echo PHP_EOL, "删除视频开始", PHP_EOL;
        self::tg_mv_del();
      echo PHP_EOL, "删除视频结束", PHP_EOL;
    }

    public static function tg_mv_del(){
        MvTgModel::query()
            ->whereIn('status', [3, 5, 2])
            ->where('type', MvTgModel::TYPE_8)
            ->orderBy('id')
            ->chunkById(20 , function ($items){
                foreach ($items as $item){
                    if ($item->status == 2){
                        if ($item->updated_at < date('Y-m-d H:i:s', strtotime('-1 hour'))){
                            $item->status = 5;
                            $item->save();
                        }
                    }else{
                        if ($item->status == 5 && $item->updated_at > date('Y-m-d H:i:s', strtotime('-1 day'))){
                            continue;
                        }
                        if ($item->status == 3 && $item->updated_at < date('Y-m-d H:i:s', strtotime('-1 hour'))){
                            continue;
                        }
                        $file = '/home/python_tg/gtt0105/file/' . basename($item->local_path);
                        //删除文件
                        if (file_exists($file)) {
                            if (unlink($file)) {
                                echo "文件已删除: $file", PHP_EOL;
                            } else {
                                echo "删除文件失败: $file", PHP_EOL;
                            }
                        } else {
                            echo "文件不存在: $file", PHP_EOL;
                        }
                    }
                }
            });
    }
}