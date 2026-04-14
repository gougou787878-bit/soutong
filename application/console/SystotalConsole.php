<?php

namespace App\console;


class SystotalConsole extends AbstractConsole
{
    public $name = "sys-total-create";

    public $description = 'sys_total定时入库';

    public function process($argc, $argv)
    {
        try {
            redis()->lock('sys-total-db', function () {
                $date = date('Y-m-d', strtotime('-1 days'));
                $key = sprintf(\SysTotalModel::RK_NAME, $date);
                $all = redis()->hGetAll($key);
                //删除5天前的
                $del_key = date('Y-m-d', strtotime('-5 days'));
                redis()->del($del_key);
                foreach ($all as $n1 => $v1) {
                    $query = \SysTotalModel::query();
                    $model = $query->where([
                        'date' => $date,
                        'name' => $n1,
                    ])->first();
                    if (empty($model)) {
                        try {
                            $data = [
                                'date' => $date,
                                'name' => $n1,
                                'value' => $v1
                            ];
                            \SysTotalModel::create($data);
                        } catch (\Exception $exception) {
                            trigger_log($exception);
                        }
                    } else {
                        $model->increment('value', $v1);
                    }
                }
            }, 30);
        } catch (\Throwable $e) {
            trigger_log($e);
        }
    }

}