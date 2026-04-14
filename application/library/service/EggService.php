<?php

namespace service;

use EggItemModel;
use EggLogModel;
use EggModel;
use EggUserModel;
use FreeMemberModel;
use MemberModel;
use ProductModel;
use UserDownloadModel;
use UsersCoinrecordModel;

class EggService
{
    public function init(MemberModel $member, $lottery_id){
        $egg_user = EggUserModel::getInfoByAff($member->aff);
        if (empty($egg_user)){
            $egg_num = 0;
        }else{
            $egg_num = $egg_user->val;
        }
        return [
            'id'        => $lottery_id,
            'token'     => getID2Code($member->uid),
            'egg_num'   => $egg_num,
            'top_list'  => $this->topList()
        ];
    }

    public function topList()
    {
        $list = redis()->lRange('lottery_top_list_eleven', 0, 10);
        $topList = Collect([]);
        if ($list){
            foreach ($list as $item) {
                $member = json_decode($item);
                $topList->push($member);
            }
        }else{
            $record = json_encode([
                'username' => '英俊学姐',
                'result' => '100金币',
            ]);
            redis()->lPush('lottery_top_list_eleven', $record);
            $record = json_encode([
                'username' => '爱笑蛋挞',
                'result' => '王者永久卡',
            ]);
            redis()->lPush('lottery_top_list_eleven', $record);
            $record = json_encode([
                'username' => '感动等于小海豚',
                'result' => 'iphone16 Pro Max',
            ]);
            redis()->lPush('lottery_top_list_eleven', $record);
            $record = json_encode([
                'username' => '烂漫飞鸟',
                'result' => '知名男模激情一夜(任选)',
            ]);
            redis()->lPush('lottery_top_list_eleven', $record);
            $record = json_encode([
                'username' => '知性大象',
                'result' => '心意男神共度良宵(任选)',
            ]);
            redis()->lPush('lottery_top_list_eleven', $record);
            $record = json_encode([
                'username' => '幸福麦片',
                'result' => '脱单现金奖1000元',
            ]);
            redis()->lPush('lottery_top_list_eleven', $record);
        }
        return $topList;
    }

    public function draw(MemberModel $member, EggModel $lottery, $num){
        //判断抽奖次数是否足够
        $userLottery = EggUserModel::getInfoByAff($member->aff);
        if (!$userLottery || $userLottery->val < $num){
            throw new \Exception('抽奖次数不足，前往充值金币或者VIP获取抽奖次数');
        }
        $jp_titles = transaction(function () use ($member, $lottery, $userLottery, $num){
            //扣除次数
            $userLottery->val = $userLottery->val - $num;
            $userLottery->updated_at = \Carbon\Carbon::now();
            $isOk = $userLottery->save();
            test_assert($isOk, '抽奖异常，请重试');

            //抽奖
            $item = EggModel::draw($lottery->id, $num);
            $jp_titles = [];
            foreach ($item as $value){
                $jp_titles[] = [
                    'stay' => $value->giveaway_id
                ];
                //中奖日志
                $isOk = EggLogModel::createBy($member, $value);
                test_assert($isOk, '记录添加失败');
                //中奖送东西
                $itOk = $this->giveaway($member, $value);
                test_assert($itOk, '操作失败');

                //进入轮播
                if ($value->is_show == EggItemModel::SHOW_OK) {
                    $record = json_encode([
                        'username' => $member->nickname,
                        'result'   => $value->item_name
                    ]);
                    redis()->lPush('lottery_top_list_eleven', $record);
                    redis()->lTrim('lottery_top_list_eleven',0,10);
                }
            }
            $itOk = $lottery->increment('lottery_num');
            test_assert($itOk, '操作失败');

            MemberModel::clearFor($member);
            return $jp_titles;
        });
        return [
            'egg_num' => $userLottery->val,
            'lottery_title' => $jp_titles,
        ];
    }

    /**
     * 赠送礼物
     */
    public function giveaway(MemberModel $member, EggItemModel $item)
    {
        $result = true;

        switch ($item->giveaway_type) {
            case EggItemModel::GIVEAWAY_TYPE_COIN://金币
                return $this->giveaway_coin($member, $item);
            case EggItemModel::GIVEAWAY_TYPE_VIP_COINS_EVER://金币永久卡
                return $this->giveaway_vip($member, MemberModel::VIP_LEVEL_LONG, 3650, 3650, ProductModel::FREE_DAY_MV);
            case EggItemModel::GIVEAWAY_TYPE_VIP_AW_EVER://暗网永久卡
                return $this->giveaway_vip($member, MemberModel::VIP_LEVEL_AW_YEAR, 3650, 0, ProductModel::FREE_DAY_COMMON);
            case EggItemModel::GIVEAWAY_TYPE_VIP_EVER://永久会员卡
                return $this->giveaway_vip($member, MemberModel::VIP_LEVEL_LONG, 3650, 0, ProductModel::FREE_DAY_COMMON);
            case EggItemModel::GIVEAWAY_TYPE_VIP_SEVEN_COINS://7天金币卡
                return $this->giveaway_vip($member, MemberModel::VIP_LEVEL_MOON, 7, 7, ProductModel::FREE_DAY_MV);
            case EggItemModel::GIVEAWAY_TYPE_VIP_THIRTY://30天VIP
                return $this->giveaway_vip($member, MemberModel::VIP_LEVEL_MOON, 30, 0, ProductModel::FREE_DAY_MV);
            case EggItemModel::GIVEAWAY_TYPE_VIP_FIFTEEN://15天VIP
                return $this->giveaway_vip($member, MemberModel::VIP_LEVEL_MOON, 15, 0, ProductModel::FREE_DAY_MV);
            case EggItemModel::GIVEAWAY_TYPE_VIP_SEVEN://7天VIP
                return $this->giveaway_vip($member, MemberModel::VIP_LEVEL_MOON, 7, 0, ProductModel::FREE_DAY_MV);
            case EggItemModel::GIVEAWAY_TYPE_VIP_WZ_EVER://王者永久卡
                return $this->giveaway_vip($member, MemberModel::VIP_LEVEL_SUPREME, 3650, 3650, ProductModel::FREE_DAY_MV_ADD_COMMUNITY);
            case EggItemModel::GIVEAWAY_TYPE_MANUAL:
                return true;
        }
        return $result;
    }


    /**
     * @desc 赠送金币
     */
    protected function giveaway_coin(MemberModel $member, EggItemModel $item): bool
    {
        $coin = $item->giveaway_num;
        $isOk = MemberModel::where('uid', $member->uid)->increment('coins', $coin, ['coins_total' => \DB::raw('coins_total + ' . $coin)]);
        if (!$isOk){
            throw new \Exception('更新用户金币失败');
        }

        $isOk = UsersCoinrecordModel::addIncome(
            'lottery', $member->uid, null, $coin, $item->item_id, 0, "抽奖中奖，奖励{$coin}金币。"
        );
        test_assert($isOk, "领取奖励异常");

        return true;
    }

    /**
     * @desc 送VIP
     */
    protected function giveaway_vip(MemberModel $member, $vip_level, $vip_time, $free_day, $free_day_type){
        $period_at = $vip_time * 86400 + max($member->expired_at, TIMESTAMP);
        $member->expired_at = $period_at;
        $member->vip_level = max($vip_level, $member->vip_level);
        $member->birthday = time();
        $isOk = $member->save();
        test_assert($isOk, "领取奖励异常");
        // 收费视频免费看
        if ($free_day > 0 && $free_day_type > 0) {
            FreeMemberModel::createInit($member->uid, $free_day, $free_day_type);
        }
        if ($vip_time == MemberModel::VIP_LEVEL_SUPREME){
            //VIP 赠送视频下载次数
            $aff = $member->aff;
            $download_num = 1000;
            jobs([UserDownloadModel::class, 'addDownloadNum'], [$aff, $download_num, 0]);
        }
        return true;
    }
}