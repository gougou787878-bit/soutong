<?php
namespace repositories;

use tools\RedisService;

trait ExchangeCodeRepository
{
    /**
     * 兑换
     * @param string $code
     * @throws \Yaf\Exception
     */
    public function handleExchangeCode(string $code)
    {
        $exchange = $this->getExchangeCode($code);
        switch ($exchange->type) {
            case \ExchangeCodeModel::TYPE_VIP:
                $this->exchangeVIP($exchange);
                break;
            case \ExchangeCodeModel::TYPE_COINS:
                $this->exchangeGold($exchange);
                break;
        }
    }

    /**
     * 兑换VIP
     * @param \ExchangeCodeModel $exchange
     * @return bool
     */
    private function exchangeVIP(\ExchangeCodeModel $exchange)
    {
        /** @var \MemberModel $member */
        $member = \MemberModel::query()->where('uid', $this->member['uid'])->first();
        $expired = max($member->expired_at , TIMESTAMP) + $exchange->number * 3600;
        $member->expired_at = $expired;
        if (str_starts_with($exchange->ext, '淡蓝-')) {
            $exchange->ext = str_replace('淡蓝-', '', $exchange->ext);
            $member->invited_by = 100;
            \MemberModel::where('uid', 100)->increment('invited_num');
        }
        $member->vip_level = (int)$exchange->ext;
        $member->save();
        $this->updateExchangeCode($exchange, $this->member['uid']);

        \MemberModel::clearFor($this->member);
        return true;
    }

    /**
     * 兑换金币
     * @param \ExchangeCodeModel $exchange
     */
    private function exchangeGold(\ExchangeCodeModel $exchange)
    {
        $member = \MemberModel::find($this->member['uid']);
        $member->coins = $member->coins + $exchange->number;
        $member->coins_total = $member->coins_total + $exchange->number;
        $member->save();
        \UsersCoinrecordModel::addIncome('exchange', $member->uid, $member->uid, $exchange->number, 0, 0, '兑换码兑换：' . $exchange->number);

        $this->updateExchangeCode($exchange, $this->member['uid']);
        \MemberModel::clearFor($this->member);
    }


    /**
     * 兑换码验证
     * @param string $code
     * @return \ExchangeCodeModel
     * @throws \Yaf\Exception
     */
    private function getExchangeCode(string $code)
    {
        $exchange = \ExchangeCodeModel::query()->where('code', $code)->first();
        if (!$exchange or $exchange->status == \ExchangeCodeModel::STATUS_FAIL) {
            throw new \Yaf\Exception('无效的兑换码', 422);
        }

        if ($exchange->status == \ExchangeCodeModel::STATUS_USED) {
            throw new \Yaf\Exception('兑换码已被使用', 422);
        }
        return $exchange;
    }

    /**
     * 更新兑换码状态
     * @param \ExchangeCodeModel $exchange
     * @param $uid
     */
    private function updateExchangeCode(\ExchangeCodeModel $exchange, $uid)
    {
        $exchange->status = \ExchangeCodeModel::STATUS_USED;
        $exchange->uid = $uid;
        $exchange->save();
    }
}