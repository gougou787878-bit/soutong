<?php

namespace service;

use Carbon\Carbon;
use FreeMemberModel;
use LotteryBaseModel;
use LotteryFrqAwardModel;
use UserLotteryAwardModel;
use UserLotteryModel;

class LotteryService
{
    // 获取配置信息
    public function listConfig()
    {
        return LotteryBaseModel::info();
    }

    // 转盘
    /**
     * @throws \RedisException
     * @throws \Exception
     */
    public function draw(\MemberModel $member)
    {
        //时间判断
        $begin_time = setting('lottery_begin_time', '2023-12-22');
        $end_time =  setting('lottery_end_time', '2024-01-04');
        if(strtotime($begin_time) > TIMESTAMP || strtotime($end_time) < TIMESTAMP){
            //throw new \Exception('抽奖活动还没有开始');
        }
        /** @var UserLotteryModel $userLottery */
        $userLottery = UserLotteryModel::getInfoByAff($member->aff);
        if (!$userLottery || $userLottery->val < 1){
            throw new \Exception('抽奖次数不足，前往充值金币获取抽奖次数');
        }

        if (!redis()->exists(LotteryBaseModel::LOTTERY_SET)){
            throw new \Exception('奖池未生成');
        }
        $len = redis()->sCard(LotteryBaseModel::LOTTERY_SET);
        //奖池空了
        if ($len <= 0) {
            $reward = LotteryBaseModel::ptpAward();
        }else{
            //随机返回一个中奖奖品
            $rs = redis()->sRandMember(LotteryBaseModel::LOTTERY_SET);
            $reward = json_decode($rs);
        }

        transaction(function () use ($member, $userLottery, $reward){
            if ($reward->type == LotteryBaseModel::TYPE_COINS || $reward->type == LotteryBaseModel::TYPE_COINS_RAND){
                if ($reward->type == LotteryBaseModel::TYPE_COINS_RAND){
                    $reward->exp = rand($reward->exp, $reward->exp_end);
                }
                $member->coins += $reward->exp;
                $member->coins_total += $reward->exp;
                $isOk = $member->save();
                test_assert($isOk, "领取奖励异常");

                $isOk = \UsersCoinrecordModel::addIncome(
                    'lottery', $member->uid, null, $reward->exp, $reward->id, 0, "抽奖中奖，奖励{$reward->exp}金币。"
                );
                test_assert($isOk, "领取奖励异常");
            }elseif ($reward->type == LotteryBaseModel::TYPE_VIP){
                $product = \ProductModel::find($reward->exp);
                test_assert($product, '配置的产品不存在');
                $period_at = ($product->valid_date) * 86400 + max($member->expired_at, TIMESTAMP);
                $member->expired_at = $period_at;
                $member->vip_level = max($product->vip_level,$member->vip_level);
                $member->birthday = time();
                $isOk = $member->save();
                test_assert($isOk, "领取奖励异常");
                // 收费视频免费看
                if ($product->free_day > 0) {
                    FreeMemberModel::createInit($member->uid, $product->free_day, $product->free_day_type);
                }
            }else{
                throw new \Exception('奖品配置异常');
            }
            //扣除次数
            $userLottery->val = $userLottery->val - 1;
            $userLottery->updated_at = \Carbon\Carbon::now();
            $isOk = $userLottery->save();
            test_assert($isOk, '抽奖异常，请重试');

            //中奖记录
            $isOk = \UserLotteryLogModel::insert([
                'aff' => $member->aff,
                'lottery_id' => $reward->id,
                'type' => $reward->type,
                'val' => $reward->exp,
                'snapshot' => json_encode($reward),
                'created_at' => \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ]);
            test_assert($isOk, "领取奖励异常");
            //中奖展示
            if ($reward->is_show == LotteryBaseModel::SHOW_OK) {
                $record = json_encode([
                    'nick_name' => $member->nickname,
                    'hint'   => $reward->type == LotteryBaseModel::TYPE_COINS_RAND ? $reward->exp . '金币' : $reward->hint
                ]);
                redis()->lPush('lottery_top_list', $record);
                redis()->lTrim('lottery_top_list',0,10);
            }
            //中奖数加1
            $reward_id = $reward->id;
            bg_run(function () use ($reward_id){
                LotteryBaseModel::luckyIncrement($reward_id);
            });
        });
        \MemberModel::clearFor($member);

        return [
            'id' => $reward->id,
            'index' => $reward->index,
            'chance' => $userLottery->val,
            'hint' => $reward->type == LotteryBaseModel::TYPE_COINS_RAND ? $reward->exp . '金币' : $reward->hint,
            'record' => collect([
                'type' => '抽奖',
                'bi' => $reward->hint,
                'date' => date('Y-m-d H:i:s')
            ])
        ];
    }

    public function topList()
    {
        $list = redis()->lRange('lottery_top_list', 0, 10);
        $topList = Collect([]);
        if ($list){
            foreach ($list as $item) {
                $member = json_decode($item);
                $topList->push($member);
            }
        }else{
            $record = json_encode([
                'nick_name' => '英俊学姐',
                'hint' => '1088金币',
            ]);
            redis()->lPush('lottery_top_list', $record);
            $record = json_encode([
                'nick_name' => '爱笑蛋挞',
                'hint' => '王者通卡',
            ]);
            redis()->lPush('lottery_top_list', $record);
            $record = json_encode([
                'nick_name' => '感动等于小海豚',
                'hint' => '588金币',
            ]);
            redis()->lPush('lottery_top_list', $record);
            $record = json_encode([
                'nick_name' => '烂漫飞鸟',
                'hint' => '1088金币',
            ]);
            redis()->lPush('lottery_top_list', $record);
            $record = json_encode([
                'nick_name' => '知性大象',
                'hint' => '至尊通卡',
            ]);
            redis()->lPush('lottery_top_list', $record);
            $record = json_encode([
                'nick_name' => '幸福麦片',
                'hint' => '588金币',
            ]);
            redis()->lPush('lottery_top_list', $record);
        }
        return $topList;
    }

    public function awardList(\MemberModel $member)
    {
        $list = LotteryFrqAwardModel::info();
        //用户抽奖次数
        /** @var UserLotteryModel $userLottery */
        $userLottery = UserLotteryModel::getInfoByAff($member->aff);
        //领取记录
        $userLotteryAward = UserLotteryAwardModel::recordByAff($member->aff);
        return collect($list)->map(function (LotteryFrqAwardModel $lotteryFrqAward) use ($userLottery, $userLotteryAward){
            $lotteryFrqAward->times = $lotteryFrqAward->lottery_frq;
            $lotteryFrqAward->reward = $lotteryFrqAward->title;
            if (!$userLottery){
                $lotteryFrqAward->state = 0;
            }else{
                if (($userLottery->total - $userLottery->val) >= $lotteryFrqAward->lottery_frq){
                    if (in_array($lotteryFrqAward->id, $userLotteryAward)){
                        $lotteryFrqAward->state = 2;
                    }else{
                        $lotteryFrqAward->state = 1;
                    }
                }else{
                    $lotteryFrqAward->state = 0;
                }
            }

            return $lotteryFrqAward;
        });
    }

    /**
     * @throws \Exception
     */
    public function receive(\MemberModel $member, $id){
        /** @var LotteryFrqAwardModel $lotteryFrqAward */
        $lotteryFrqAward = LotteryFrqAwardModel::find($id);
        test_assert($lotteryFrqAward, '抽奖次数配置不存在');
        $record = UserLotteryAwardModel::record($member->aff, $id);
        if ($record){
            throw new \Exception('你已经领取了该奖励');
        }
        transaction(function () use ($member, $lotteryFrqAward){
            if ($lotteryFrqAward->type == LotteryFrqAwardModel::TYPE_COINS){
                $member->coins += $lotteryFrqAward->val;
                $member->coins_total += $lotteryFrqAward->val;
                $isOk = $member->save();
                test_assert($isOk, "领取奖励异常");

                $isOk = \UsersCoinrecordModel::addIncome(
                    'lotteryFrq', $member->uid, null, $lotteryFrqAward->val, $lotteryFrqAward->id, 0, "抽奖次数满{$lotteryFrqAward->lottery_frq}次奖励{$lotteryFrqAward->val}金币"
                );
                test_assert($isOk, "领取奖励异常");
            }elseif ($lotteryFrqAward->type == LotteryFrqAwardModel::TYPE_VIP){
                $product = \ProductModel::find($lotteryFrqAward->val);
                test_assert($product, '配置的产品不存在');
                $period_at = ($product->valid_date) * 86400 + max($member->expired_at, TIMESTAMP);
                $member->expired_at = $period_at;
                $member->vip_level = max($product->vip_level,$member->vip_level);
                $member->birthday = time();
                $isOk = $member->save();
                test_assert($isOk, "领取奖励异常");
                // 收费视频免费看
                if ($product->free_day > 0) {
                    FreeMemberModel::createInit($member->uid, $product->free_day, $product->free_day_type);
                }
            }else{
                throw new \Exception('抽奖次数奖励配置异常');
            }
            //领取记录
            $isOk = UserLotteryAwardModel::insert([
                'aff' => $member->aff,
                'type' => $lotteryFrqAward->type,
                'award_id' => $lotteryFrqAward->id,
                'title' => $lotteryFrqAward->title,
                'val' => $lotteryFrqAward->val,
                'created_at' => \Carbon\Carbon::now()
            ]);
            test_assert($isOk, "领取奖励异常");
        });

        return true;
    }

    public function user_award(\MemberModel $member){
        //中奖记录
        $userLotteryLog = \UserLotteryLogModel::list($member->aff);
        //抽奖次数领取记录
        //$userLotteryAward = UserLotteryAwardModel::listByAff($member->aff);
        //$result = array_merge($userLotteryLog, $userLotteryAward);
        $result = array_merge($userLotteryLog, []);
        array_multisort(array_column($result, 'date'), SORT_DESC, $result);
        $list = Collect([]);
        if ($result){
            foreach ($result as $item) {
                $list->push($item);
            }
        }
        return $list;
    }
}