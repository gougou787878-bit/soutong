<?php

namespace App\console;

use DB;
use service\AppCenterService;

class KeepDataReportConsole extends AbstractConsole
{

    public $name = 'keep-data-report';//每日凌晨之后执行即可 计划任务

    public $description = '每日留存安装统计上报';
    //注意更新 member表 lastvisit 字段
    //注意理清楚 联盟绑定的渠道数据 agent_id


    public function process($argc, $argv)
    {
        $this->tongjiData();

        echo "#################  over ############## \r\n ";
    }

    public function tongjiData()
    {

        /**
         * If you are updating database records while chunking results,
         * your chunk results could change in unexpected ways.
         * If you plan to update the retrieved records while chunking,
         * it is always best to use the chunkById method instead.
         * This method will automatically paginate the results based on the record's primary key
         */

        $chunk = 500;//根据项目渠道多少来设置 500 1000 都行

        \AgentsUserModel::query()->chunkById($chunk, function ($items) use ($chunk) {
            collect($items)->each(function (\AgentsUserModel $agentUser) use ($chunk) {
                if(is_null($agentUser)){
                    return ;
                }
                $agent_id = $agentUser->root_id;//和联盟的代理 agent_id 一致
                $channel = $agentUser->channel;//一般和用户的build_id 一致
                $agent_level = $agentUser->agent_level;//默认1 主渠道 大于1子渠道（只统计直推）
                $aff = $agentUser->aff;//渠道关联的用户
                $username = $agentUser->username;//联盟登陆账号 一般
                $channel_name = $agentUser->channel_name;//联盟账号对应的中文别称 一般
                /**
                 * 每天 凌晨以后上报 ，以10-11为列子
                 * 算法
                 * eg：
                 * 10月11日凌晨上报数据为例，计算各渠道数据
                 * 上报10月10日安装，10月9日安装及在10月10日的活跃的数量
                 */
                //昨天时间
                $day_pre_1 = date('Y-m-d 00:00:00',strtotime('-1 days'));
                $day_pre_1_end = date('Y-m-d 23:59:59',strtotime('-1 days'));
                //前天时间
                $day_pre_2 = date('Y-m-d 00:00:00',strtotime('-2 days'));
                $day_pre_2_end = date('Y-m-d 23:59:59',strtotime('-2 days'));
                $where_pre1 = [
                    ['build_id','=',$channel],
                    ['regdate','>=',strtotime($day_pre_1)],
                    ['regdate','<=',strtotime($day_pre_1_end)],
                ];
                if ($agent_level > 1) {//子渠道 只统计直推 没有列表
                    $where_pre1[] = ['invited_by', '=', $aff];
                }
                //昨日安装
                $day_pre_1_install = \MemberModel::where($where_pre1)->count('uid');
                $where_pre2 = [
                    ['build_id','=',$channel],
                    ['regdate','>=',strtotime($day_pre_2)],
                    ['regdate','<=',strtotime($day_pre_2_end)],
                ];
                if ($agent_level > 1) {//子渠道 只统计直推 没有列表
                    $where_pre2[] = ['invited_by', '=', $aff];
                }
                //前日安装
                $day_pre_2_install = \MemberModel::where($where_pre2)->count('uid');
                $where_active = [
                    ['build_id','=',$channel],
                    ['regdate','>=',strtotime($day_pre_2)],
                    ['regdate','<=',strtotime($day_pre_2_end)],
                    ['lastvisit','>=',strtotime($day_pre_1)],
                    //['lastvisit','<=',strtotime($day_pre_1_end)]
                ];
                if ($agent_level > 1) {//子渠道 只统计直推 没有列表
                    $where_active[] = ['invited_by', '=', $aff];
                }
                //前日安装在昨日的活跃量
                $day_pre_2_serve = \MemberModel::where($where_active)->count('uid');
                //入队列上报
                (new AppCenterService())->keepData($channel,$aff,$day_pre_1_install,$day_pre_2_install,$day_pre_2_serve,['agent_id'=>$agent_id]);

                echo "======== chunk {$chunk}  id:{$agentUser->id}========" . PHP_EOL . PHP_EOL;

            });

        });

    }
}