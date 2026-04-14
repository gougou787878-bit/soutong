<?php


namespace service;


class ActivityInviteService
{
    private $uid;

    private $reward_items = [

        ['id' => 1, 'rate' => 0.00000, 'name' => '666元现金红包', 'amount' => 666, 'icon' => './assets/lottery/bonus_666.png'],
        ['id' => 2, 'rate' => 0.22500, 'name' => '1元现金红包', 'amount' => 1.0, 'icon' => './assets/lottery/bonus_1.png'],
        ['id' => 3, 'rate' => 0.25000, 'name' => '0.5元现金红包', 'amount' => 0.5, 'icon' => './assets/lottery/bonus_05.png'],
        ['id' => 4, 'rate' => 0.04000, 'name' => '2元现金红包', 'amount' => 2.0, 'icon' => './assets/lottery/bonus_2.png'],
        ['id' => 5, 'rate' => 0.00001, 'name' => '10元现金红包', 'amount' => 10., 'icon' => './assets/lottery/bonus_10.png'],
        ['id' => 6, 'rate' => 0.22500, 'name' => '1元现金红包', 'amount' => 1.0, 'icon' => './assets/lottery/bonus_1.png'],
        ['id' => 7, 'rate' => 0.25000, 'name' => '0.5元现金红包', 'amount' => 0.5, 'icon' => './assets/lottery/bonus_05.png'],
        ['id' => 8, 'rate' => 0.00989, 'name' => '5元现金红包', 'amount' => 5.0, 'icon' => './assets/lottery/bonus_5.png'],
    ];


    /**
     * ActivityInviteService constructor.
     * @param $uid
     */
    public function __construct($uid)
    {
        $this->uid = $uid;
    }


    public function incrRewardRemainder(int $value = 1)
    {
        $model = $this->getModel();
        if (!$model) {
            $model = \ActivityInviteLotteryModel::make();
            $model->uid = $this->uid;
            $model->reward_remainder = 0;
            $model->reward_amount = 0;
            $model->reward_amount_total = 0;
        }

        $model->reward_remainder += $value;
        return $model->save();
    }

    /**
     * @return \ActivityInviteLotteryModel|object|null
     */
    protected function getModel()
    {
        static $model = null;
        if ($model === null) {
            $model = \ActivityInviteLotteryModel::where('uid', $this->uid)->first();
        }
        return $model;
    }

    /**
     * @return array[]
     */
    public function getRewardItems(): array
    {
        $reward_items = $this->reward_items;
        foreach ($reward_items as &$item) {
            unset($item['rate']);
        }
        return $reward_items;
    }


    public function lottery()
    {
        if ($this->getRewardRemainder() === 0) {
            throw new \Exception('您没有抽奖次数');
        }
        $items = [];
        $b = 100000;
        foreach ($this->reward_items as $item) {
            $count = $item['rate'] * $b;
            for ($i = 0; $i < $count; $i++) {
                $items[] = $item['id'];
            }
        }
        $index = array_rand($items);
        $reward_index = $items[$index];
        $reward = collect($this->reward_items)->where('id' , $reward_index)->first();
        unset($reward['rate']);

        $model = $this->getModel();
        $where = [
            'uid'              => $model->uid,
            'reward_remainder' => $model->reward_remainder,
            'reward_amount'    => $model->reward_amount,
        ];

        $update = [
            'reward_remainder'    => $model->reward_remainder - 1,
            'reward_amount'       => $model->reward_amount + $reward['amount'],
            'reward_amount_total' => $model->reward_amount_total + $reward['amount'],
        ];

        try {
            \DB::beginTransaction();
            $itOk = \ActivityInviteLotteryModel::where($where)->update($update);
            if (empty($itOk)) {
                throw new \Exception('操作失败');
            }
            $itOk = \ActivityInviteLotteryLogModel::create(
                [
                    'uid'           => $model->uid,
                    'item'          => json_encode($reward),
                    'item_id'       => $reward['id'],
                    'item_icon'     => $reward['icon'],
                    'reward_amount' => $reward['amount'],
                    'log'           => '抽中:' . $reward['amount'].'元现金红包',
                    'created_at'    => time()
                ]
            );
            if (empty($itOk)) {
                throw new \Exception('操作失败');
            }
            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
            return $e;
        }
        return $reward;
    }


    /**
     * 剩余抽奖次数
     * @return int
     */
    public function getRewardRemainder(): int
    {
        if (!$this->getModel()) {
            return 0;
        }
        return $this->getModel()->reward_remainder;
    }

    /**
     * 用户可用奖金
     * @return int
     */
    public function getRewardAmount(): float
    {
        if (!$this->getModel()) {
            return 0;
        }
        return $this->getModel()->reward_amount;
    }

    /**
     * 排行榜
     * @return array
     */
    public function getLeaderBoard()
    {
        $items = \ActivityInviteLotteryModel::with('member:uid,thumb,nickname')
            ->orderByDesc('reward_amount_total')
            ->limit(10)
            ->get()
            ->map(function (\ActivityInviteLotteryModel $item) {
                if (empty($item->member)) {
                    return null;
                }
                return [
                    'uid'        => $item->member->uid,
                    'nickname'   => $item->member->nickname,
                    'avatar_url' => $item->member->avatar_url,
                    'score'      => $item->reward_amount_total,
                ];
            })
            ->filter();
        return $items->toArray();
    }


    public $exchange_vip = [
        ['id' => 1, 'name' => '季度会员', 'url' => './assets/vip_1.png', 'score' => 90, 'need_amount' => 50, 'vip_level' => \MemberModel::VIP_LEVEL_JIKA],
        ['id' => 2, 'name' => '年度会员', 'url' => './assets/vip_2.png', 'score' => 365, 'need_amount' => 100, 'vip_level' => \MemberModel::VIP_LEVEL_YEAR],
        ['id' => 3, 'name' => '永久会员', 'url' => './assets/vip_3.png', 'score' => 999, 'need_amount' => 200, 'vip_level' => \MemberModel::VIP_LEVEL_LONG],
    ];

    /**
     * 兑换vip
     * @param $type
     * @return bool
     * @throws \Exception
     */
    public function exchangeVip($type)
    {
        $item = collect($this->exchange_vip)->where('id', $type);
        if ($item->isEmpty()) {
            throw new \Exception('兑换类型不存在');
        }
        $item = $item->pop();
        $model = $this->getModel();
        if (empty($model) || $model->reward_amount < $item['need_amount']) {
            throw new \Exception('您的兑换金额不足');
        }

        $member = \MemberModel::find($this->uid);
        if (empty($member)) {
            throw new \Exception('用户不存在');
        }
        $where = [
            'uid'           => $model->uid,
            'reward_amount' => $model->reward_amount,
        ];
        $update = [
            'reward_amount' => $model->reward_amount - $item['need_amount'],
        ];
        $score = $item['score'];
        $vip_level = $item['vip_level'];
        try {
            \DB::beginTransaction();
            $itOk = \ActivityInviteLotteryModel::where($where)->update($update);
            if (empty($itOk)) {
                throw new \Exception('操作失败1');
            }
            $expired_at = max(TIMESTAMP, $member->expired_at) + $score * 86400;
            $updateMemberData = ['expired_at' => $expired_at, 'vip_level' => $vip_level];
            $itOk = $member->fill($updateMemberData)->save();
            if (empty($itOk)) {
                throw new \Exception('操作失败2');
            }
            $itOk = \ActivityInviteLotteryLogModel::create(
                [
                    'uid'           => $model->uid,
                    'item'          => '{}',
                    'item_id'       => 0,
                    'item_icon'     => $item['icon'] ?? '',
                    'reward_amount' => -$item['need_amount'],
                    'log'           => '使用:' . $item['need_amount'] . ",兑换:" . $item['name'],
                    'created_at'    => time()
                ]
            );
            if (empty($itOk)) {
                throw new \Exception('操作失败3');
            }
            \DB::commit();
            \MemberModel::clearFor($member);
            return true;
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }

    }


    public $exchange_gold = [
        ['id' => 1, 'name' => '50金币', 'url' => './assets/gold_1.png', 'score' => 50, 'need_amount' => 50],
        ['id' => 2, 'name' => '100金币', 'url' => './assets/gold_2.png', 'score' => 100, 'need_amount' => 100],
        ['id' => 3, 'name' => '200金币', 'url' => './assets/gold_3.png', 'score' => 200, 'need_amount' => 200],
    ];

    /**
     * 兑换金币
     * @param $type
     * @return bool
     * @throws \Exception
     */
    public function exchangeGold($type)
    {
        $item = collect($this->exchange_gold)->where('id', $type);
        if ($item->isEmpty()) {
            throw new \Exception('兑换类型不存在');
        }
        $item = $item->pop();
        $model = $this->getModel();
        if (empty($model) || $model->reward_amount < $item['need_amount']) {
            throw new \Exception('您的兑换金额不足');
        }

        $member = \MemberModel::find($this->uid);
        if (empty($member)) {
            throw new \Exception('用户不存在');
        }

        $where = [
            'uid'           => $model->uid,
            'reward_amount' => $model->reward_amount,
        ];
        $update = [
            'reward_amount' => $model->reward_amount - $item['need_amount'],
        ];
        $score = $item['score'];
        try {
            \DB::beginTransaction();
            $itOk = \ActivityInviteLotteryModel::where($where)->update($update);
            if (empty($itOk)) {
                throw new \Exception('操作失败');
            }
            $itOk = \UsersCoinrecordModel::addIncome('activity', $this->uid, $this->uid, $score, 0, 0, '红包活动兑换：' . $score);
            if (empty($itOk)) {
                throw new \Exception('操作失败');
            }
            $member->coins = $member->coins + $score;
            $member->coins_total = $member->coins_total + $score;
            $itOk = $member->save();
            if (empty($itOk)) {
                throw new \Exception('操作失败');
            }
            $itOk = \ActivityInviteLotteryLogModel::create(
                [
                    'uid'           => $model->uid,
                    'item'          => '{}',
                    'item_id'       => 0,
                    'item_icon'     => $item['icon'] ?? '',
                    'reward_amount' => -$item['need_amount'],
                    'log'           => '使用:' . $item['need_amount'] . ",兑换金币:" . $score,
                    'created_at'    => time()
                ]
            );
            if (empty($itOk)) {
                throw new \Exception('操作失败');
            }

            \DB::commit();
            \MemberModel::clearFor($member);
            return true;
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }

    }

    public function getLotteryLog()
    {
        $where = [
            ['uid', '=', $this->uid],
            ['reward_amount', '>', 0],
        ];
        $query = \ActivityInviteLotteryLogModel::where($where)->orderByDesc('id');
        return $query->limit(30)->get()->toArray();
    }


}