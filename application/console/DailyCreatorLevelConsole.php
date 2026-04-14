<?php


namespace App\console;


use service\AppReportService;

class DailyCreatorLevelConsole extends AbstractConsole
{
    public $name = "daily-creator-level";

    public $description = '每日计划任务-创作者等级统计';


    /**
     * php yafdaily-creator-level
     * @param $argc
     * @param $argv
     */
    public function process($argc, $argv)
    {
        set_time_limit(0);
        $date = date('Y-m-d H:i:s', TIMESTAMP);

        echo "start {$date} \r\n";
        echo '——————————————————————————————————————————————' . PHP_EOL;
        $maxRow = \MemberMakerModel::orderByDesc('id')->first();
        if (is_null($maxRow)) {
            exit('查无记录');
        }

        $upLevelExp = \MemberMakerModel::getMakerRule();
        array_shift($upLevelExp);

        $maxId = $maxRow->id;
        //print_r([$upLevelExp,$maxId]);die;
        for ($i = 1; $i <= $maxId; $i++) {

            /** @var \MemberMakerModel $_creator */
            $_creator = \MemberMakerModel::where('id', $i)->where('status',
                \MemberMakerModel::CREATOR_STAT_YES)->first();
            if (is_null($_creator)) {
                echo "查无记录 或非认证创作者：ID:{$i}  maxId:{$maxId}\r\n";
                continue;
            }
            /** @var \MemberModel $_members */
            $_members = \MemberModel::where('uuid', $_creator->uuid)->first();
            if (is_null($_members)
                //|| $_members->vip_level == \MemberModel::VIP_LEVEL_TEMP
                || $_members->role_id == 20
                || $_members->role_id == \MemberModel::USER_ROLE_BLACK
                // || $_members->expired_at <= TIMESTAMP
            ) {
                //凡是 用户为非会员 或者 违规的都不统计
                echo "用户为非会员 或者 违规的都不统计：ID:{$i}  UUID: {$_creator->uuid} maxId:{$maxId} \r\n";
                continue;
            }
            $_member_coins = $_members->score_total;
            $_msg = "创作者 ID:{$i} user:{$_members->uuid} vipLevel:{$_members->vip_level} maxId:{$maxId} \r\n";
            echo $_msg;
            $isOk = false;
            $ct = count($upLevelExp) - 1;
            for ($k = $ct; $k >= 0; $k--) {
                list($level, $name, $vip, $vip_level, $mv_coins, $rate_str, $pay_rate) = array_values($upLevelExp[$k]);
                $_checkVip = ($_members->expired_at > time() && $_members->vip_level >= $vip_level);
                $_total_coins = max($_member_coins,$_creator->total_coins);
                $_checkCoin = $_total_coins >= $mv_coins;
                if ($_checkVip && $_checkCoin) {
                    $isOk = true;
                    //同时满足 并且需要更新
                    if ($level != $_creator->level_num) {
                        $_f = \MemberMakerModel::where('id', $_creator->id)->update(
                            [
                                'level_num' => $level,
                                'pay_rate'  => $pay_rate,
                                'total_coins'=>$_total_coins,
                            ]
                        );
                        echo "创作者 ID:{$i}  UUID:{$_creator->uuid} level_num:{$level} pay_rate:{$pay_rate} isOK:{$_f} maxId:{$maxId} \r\n";
                    } else {
                        echo "创作者 ID:{$i}  UUID:{$_creator->uuid} level_num:{$level} pay_rate:{$pay_rate} 一致未变动 maxId:{$maxId} \r\n";
                    }
                    break;
                }
            }
            if ($isOk == false && $_creator->level_num > 1) {
                echo "创作者 ID:{$i}  UUID:{$_creator->uuid}  修复默认值 maxId:{$maxId} \r\n";
                \MemberMakerModel::where('id', $_creator->id)->update(['level_num' => 1, 'pay_rate' => 0.3]);
            }

        }
        echo "\r\n over  {$date}  maxId:{$maxId} \r\n";
        echo '——————————————————————————————————————————————' . PHP_EOL;

    }

    protected function help()
    {

    }


}