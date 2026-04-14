<?php


namespace App\console;


class UpRunnerConsole extends AbstractConsole
{
    public $name = "up-runner";

    public $description = 'up主-分析';

    /**
     * php yaf up-runner
     * 通跑创作者
     * 日志查询：
     *   1年内所有up 主 提现记录，审核通过的帐号，最近 更新的时间，帐号状态，账户余额，申请提现的ip
     * 业务上：
     *   1 ，规范明确创作者，提现审核标准
     *   2， 规范帐号充值密码的 日志记录，和审核流程
     * @param $argc
     * @param $argv
     */
    public function process($argc, $argv)
    {
        set_time_limit(60);

        echo "start \r\n";
        echo '——————————————————————————————————————————————' . PHP_EOL;



        $creatorData = \MemberMakerModel::query()->where(['status' => \MemberMakerModel::CREATOR_STAT_YES])->with('member')
            ->get()->map(function (\MemberMakerModel $creator,$_index) {
                $userStat=['用户','昵称','余额','状态','最近相对提现上传日期','相对天','提现日期','金币申请','提现金额','类型','描述','账户名','账号','ip','地址'];
                if($_index==0){
                    file_put_contents(SYSTEM_ID . date("Y-m-d") . '_uper.csv', implode(',',$userStat).PHP_EOL, FILE_APPEND);
                }
                $userStatData=[];

                if (is_null($creator) || is_null($creator->member)) {
                    return;
                }
                //creator
                $member_uuid = $creator->uuid;
                $uid = $creator->member->uid;
                //mv
                $withDraw = \UserWithdrawModel::query()->where([
                    'status' => \UserWithdrawModel::STATUS_POST,
                    'uuid'   => $member_uuid
                ])->orderByDesc('id')->get();
                $role_id = $creator->member->role_id;
                $role_text = $role_id==\MemberModel::USER_ROLE_LEVEL_MEMBER?'普通':$role_id;
                $text = '';
                if ($withDraw) {
                    foreach ($withDraw as $_withDraw) {
                        $_date = date('Y-m-d',$_withDraw->created_at);
                        $type = stripos($_withDraw->cash_id,'usdt')!==false;
                        $mv =  \MvModel::query()->where('uid', $uid)->where('created_at','<=',$_withDraw->created_at)->orderByDesc('id')->first();
                        $_mv_date = '';
                        $_gab = 0;
                        if($mv){
                            $_mv_date = date('Y-m-d',strtotime($mv->created_str));
                            $_gab = ceil((strtotime($_date)-strtotime($_mv_date))/86400);
                        }
                        $userStatData = [
                            $member_uuid,
                            $creator->member->nickname,
                            $creator->member->score,
                            $role_text,
                            $_mv_date,
                            $_gab,
                            $_date,
                            $_withDraw->coins,
                            $_withDraw->amount,
                            $type ? 'U' : '卡',
                            $_withDraw->descp,
                            $_withDraw->name,
                            $_withDraw->account,
                            $_withDraw->ip,
                            $_withDraw->address
                        ];
                        echo var_export($userStatData,true).PHP_EOL;
                        file_put_contents(SYSTEM_ID . date("Y-m-d") . '_uper.csv', implode(',',$userStatData).PHP_EOL, FILE_APPEND);
                    }

                }
                echo $text;




            });
        echo PHP_EOL . '——————————————————————————————————————————————' . PHP_EOL;
        echo PHP_EOL . 'game over' . PHP_EOL;
    }

    protected function help()
    {

    }

}