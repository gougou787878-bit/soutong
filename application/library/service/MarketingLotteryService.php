<?php

namespace service;

use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * 营销抽奖：App/H5 接口服务
 */
class MarketingLotteryService
{
    private static function prizeProbabilityColumn(): string
    {
        try {
            return \DB::schema()->hasColumn('marketing_lottery_prize', 'win_probability') ? 'win_probability' : 'weight';
        } catch (\Throwable $e) {
            return 'weight';
        }
    }
    /**
     * 当前用户在活动下可抽奖次数（未抽 + 未过期）
     */
    public function GetUserRemainingPlays(int $uid, int $activityId): int
    {
        $now = date('Y-m-d H:i:s');
        return (int) \MarketingLotteryPlayModel::query()
            ->where('activity_id', $activityId)
            ->where('uid', $uid)
            ->where('status', \MarketingLotteryPlayModel::STATUS_PENDING)
            ->where(function ($q) use ($now) {
                $q->whereNull('expire_at')->orWhere('expire_at', '>', $now);
            })
            ->count();
    }

    /**
     * 奖项列表（仅上架）
     *
     * @return array<int, array<string, mixed>>
     */
    public function ListPrizes(int $activityId): array
    {
        $probCol = self::prizeProbabilityColumn();
        $rows = \MarketingLotteryPrizeModel::query()
            ->where('activity_id', $activityId)
            ->where('status', \MarketingLotteryPrizeModel::STATUS_ON)
            ->orderByDesc($probCol)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return $rows->map(function (\MarketingLotteryPrizeModel $p) {
            return [
                'id' => (int) $p->id,
                'activity_id' => (int) $p->activity_id,
                'name' => (string) $p->name,
                'prize_desc' => (string) ($p->prize_desc ?? ''),
                'prize_image' => (string) ($p->prize_image ?? ''),
                'prize_image_full' => url_ads($p->prize_image ?? ''),
                'prize_icon' => (string) ($p->prize_icon ?? ''),
                'prize_icon_full' => url_ads($p->prize_icon ?? ''),
                'total_stock' => (int) ($p->total_stock ?? -1),
                'per_user_cap' => (int) ($p->per_user_cap ?? 0),
            ];
        })->toArray();
    }

    /**
     * 活动详情（上架 + 时间窗校验可由上层决定是否强制）
     *
     * @return array<string, mixed>
     */
    public function GetActivityDetail(int $activityId): array
    {
        /** @var \MarketingLotteryActivityModel|null $act */
        $act = \MarketingLotteryActivityModel::query()->find($activityId);
        test_assert($act, '活动不存在');

        $cfg = $act->config;
        $extra = $act->extra_config;
        return [
            'id' => (int) $act->id,
            'name' => (string) $act->name,
            'status' => (int) $act->status,
            'status_str' => \MarketingLotteryActivityModel::STATUS_TIPS[$act->status] ?? '',
            'start_at' => $act->start_at,
            'end_at' => $act->end_at,
            'activity_image' => (string) ($act->activity_image ?? ''),
            'activity_image_full' => url_ads($act->activity_image ?? ''),
            'icon' => (string) ($act->icon ?? ''),
            'icon_full' => url_ads($act->icon ?? ''),
            'intro' => (string) ($act->intro ?? ''),
            'rule_text' => (string) ($act->rule_text ?? ''),
        ];
    }

    /**
     * 活动 other-config 里的产品列表（extra_config.product_list）
     *
     * @return array{product_ids:int[], products:array<int, array<string,mixed>>}
     */
    public function GetActivityProductList(int $activityId): array
    {
        /** @var \MarketingLotteryActivityModel|null $act */
        $act = \MarketingLotteryActivityModel::query()->find($activityId);
        test_assert($act, '活动不存在');
        $extra = is_array($act->extra_config) ? $act->extra_config : [];
        
        $ids = $extra['product_list'] ?? [];
        if (is_string($ids)) {
            $decoded = json_decode($ids, true);
            $ids = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($ids)) {
            $ids = [];
        }
        $productIds = [];
        foreach ($ids as $v) {
            $id = (int) $v;
            if ($id > 0) {
                $productIds[$id] = 1;
            }
        }
        $productIds = array_map('intval', array_keys($productIds));

        $products = [];
        if (!empty($productIds)) {
            $rows = \ProductModel::query()
                ->whereIn('id', $productIds)
                ->where('status', \ProductModel::STAT_ON)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
            foreach ($rows as $p) {
                /** @var \ProductModel $p */
                $products[] = [
                    'id' => (int) $p->id,
                    'type' => (int) $p->type,
                    'type_str' => \ProductModel::TYPE[$p->type] ?? (string) $p->type,
                    'pname' => (string) $p->pname,
                    'price' => (int) $p->price,
                    'promo_price' => (int) $p->promo_price,
                    'valid_date' => (int) ($p->valid_date ?? 0),
                    'vip_level' => (int) ($p->vip_level ?? 0),
                    'coins' => (int) ($p->coins ?? 0),
                    'free_coins' => (int) ($p->free_coins ?? 0),
                    'img' => (string) ($p->img ?? ''),
                    'img_url' => $p->img ? url_ads($p->img) : '',
                ];
            }
        }

        return [
            'product_ids' => $productIds,
            'products' => $products,
        ];
    }

    /**
     * 抽奖（扣减一次机会 + 生成 redemption + 立即发放可发奖品）
     *
     * 返回内容用于前端展示。
     *
     * @return array<string, mixed>
     */
    public function Draw(\MemberModel $member, int $activityId): array
    {
        /** @var \MarketingLotteryActivityModel|null $act */
        $act = \MarketingLotteryActivityModel::query()->find($activityId);
        test_assert($act, '活动不存在');
        test_assert((int) $act->status === \MarketingLotteryActivityModel::STATUS_ON, '活动未上架');

        $now = date('Y-m-d H:i:s');
        if ($act->start_at) {
            test_assert($act->start_at <= $now, '活动未开始');
        }
        if ($act->end_at) {
            test_assert($act->end_at >= $now, '活动已结束');
        }

        $uid = (int) $member->uid;

        return transaction(function () use ($uid, $member, $activityId, $act, $now) {
            /** @var \MarketingLotteryPlayModel|null $play */
            $play = \MarketingLotteryPlayModel::query()
                ->where('activity_id', $activityId)
                ->where('uid', $uid)
                ->where('status', \MarketingLotteryPlayModel::STATUS_PENDING)
                ->where(function ($q) use ($now) {
                    $q->whereNull('expire_at')->orWhere('expire_at', '>', $now);
                })
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            test_assert($play, '抽奖次数不足');

            $prize = $this->pickPrizeWithStock((int) $act->id,(int) $uid);
            test_assert($prize, '奖池为空');

            // 锁住奖项行，校验库存并占用
            /** @var \MarketingLotteryPrizeModel $lockedPrize */
            $lockedPrize = \MarketingLotteryPrizeModel::query()->where('id', $prize->id)->lockForUpdate()->first();
            test_assert($lockedPrize, '奖项不存在');
            test_assert((int) $lockedPrize->status === \MarketingLotteryPrizeModel::STATUS_ON, '奖项已下架');
            if ((int) $lockedPrize->total_stock !== -1) {
                test_assert((int) $lockedPrize->issued_count < (int) $lockedPrize->total_stock, '奖项库存不足');
            }


            // 消耗抽奖机会
            $play->status = \MarketingLotteryPlayModel::STATUS_USED;
            $play->save();

            // 发奖参数快照
            $grant = [
                'activity_id' => (int) $activityId,
                'play_id' => (int) $play->id,
                'uid' => $uid,
                'prize_id' => (int) $lockedPrize->id,
                'prize_type' => (string) $lockedPrize->prize_type,
            ];
            $prizeSnap = $this->buildPrizeSnapshot($lockedPrize);

            // 默认：立即成功（金币/VIP/谢谢参与），实物走待处理
            $redeemStatus = \MarketingLotteryRedemptionModel::STATUS_SUCCESS;
            $remark = '';

            if ($lockedPrize->prize_type === \MarketingLotteryPrizeModel::PRIZE_COINS) {
                $coins = (int) $lockedPrize->coins_amount;
                if ($coins === -1) {
                    $mn = (int) ($lockedPrize->coins_random_min ?? 0);
                    $mx = (int) ($lockedPrize->coins_random_max ?? 0);
                    $coins = $mx > 0 ? rand($mn, $mx) : 0;
                }
                test_assert($coins > 0, '奖项配置异常');
                $ok = \MemberModel::useWritePdo()->where('uid', $uid)->update([
                    'coins' => DB::raw("coins+{$coins}"),
                    'coins_total' => DB::raw("coins_total+{$coins}"),
                ]);
                test_assert($ok, '发放失败');
                \UsersCoinrecordModel::addIncome('lottery', $uid, null, $coins, (int) $lockedPrize->id, 0, "营销抽奖中奖，奖励{$coins}金币");
                $remark = "发放金币{$coins}";
                $grant['coins'] = $coins;
                $prizeSnap['coins'] = $coins;
            } elseif ($lockedPrize->prize_type === \MarketingLotteryPrizeModel::PRIZE_VIP) {
                // 固定/随机：最终选一个上架 VIP 产品
                $product = $this->resolveVipProductForPrize($lockedPrize);
                test_assert($product, 'VIP产品不存在或未上架');
                $nowTs = time();
                $currentVipLevel = (int) $member->expired_at > $nowTs ? (int) $member->vip_level : 0;
                $periodAt = ((int) $product->valid_date) * 86400 + max((int) $member->expired_at, $nowTs);
                $ok = \MemberModel::useWritePdo()->where('uid', $uid)->update([
                    'expired_at' => $periodAt,
                    'vip_level' => max((int) $product->vip_level, $currentVipLevel),
                    'order_at' => $nowTs,
                ]);
                test_assert($ok, '发放失败');
                if ((int) ($product->free_day ?? 0) > 0) {
                    \FreeMemberModel::createInit($uid, (int) $product->free_day, (int) $product->free_day_type);
                }
                $remark = "发放VIP产品#{$product->id}";
                $grant['vip_product_id'] = (int) $product->id;
                $prizeSnap['vip_product_id'] = (int) $product->id;
                $prizeSnap['vip_product_name'] = (string) $product->pname;
            } elseif ($lockedPrize->prize_type === \MarketingLotteryPrizeModel::PRIZE_PHYSICAL) {
                $redeemStatus = \MarketingLotteryRedemptionModel::STATUS_PENDING;
                $remark = '实物奖品待发放';
            } elseif ($lockedPrize->prize_type === \MarketingLotteryPrizeModel::PRIZE_THANKS) {
                $remark = '谢谢参与';
            }

            // 库存占用：立即成功/待处理都先计入 issued_count（避免超发）
            $lockedPrize->issued_count = (int) $lockedPrize->issued_count + 1;
            $lockedPrize->save();

            /** @var \MarketingLotteryRedemptionModel $red */
            $red = \MarketingLotteryRedemptionModel::create([
                'play_id' => (int) $play->id,
                'uid' => $uid,
                'activity_id' => (int) $activityId,
                'activity_name' => (string) ($act->name ?? ''),
                'prize_id' => (int) $lockedPrize->id,
                'prize_name' => (string) ($lockedPrize->name ?? ''),
                'is_win' => (int) ($lockedPrize->is_win ?? 1),
                'status' => $redeemStatus,
                'remark' => $remark,
                'grant_snapshot' => $grant,
                'prize_snapshot' => $prizeSnap,
            ]);

            return [
                'activity_id' => (int) $activityId,
                'play_id' => (int) $play->id,
                'remaining' => $this->GetUserRemainingPlays($uid, $activityId),
                'prize' => [
                    'id' => (int) $lockedPrize->id,
                    'name' => (string) $lockedPrize->name,
                    'prize_type' => (string) $lockedPrize->prize_type,
                    'prize_type_str' => \MarketingLotteryPrizeModel::PRIZE_TYPE_TIPS[$lockedPrize->prize_type] ?? $lockedPrize->prize_type,
                    'is_win' => (int) ($lockedPrize->is_win ?? 1),
                    'prize_image_full' => url_ads($lockedPrize->prize_image ?? ''),
                ],
                'redemption' => [
                    'id' => (int) $red->id,
                    'status' => (int) $red->status,
                    'status_str' => \MarketingLotteryRedemptionModel::STATUS_TIPS[(int) $red->status] ?? '',
                    'remark' => (string) ($red->remark ?? ''),
                ],
            ];
        });
    }

    private function buildPrizeSnapshot(\MarketingLotteryPrizeModel $p): array
    {
        return [
            'id' => (int) $p->id,
            'name' => (string) $p->name,
            'prize_type' => (string) $p->prize_type,
            'is_win' => (int) ($p->is_win ?? 1),
            'win_probability' => (int) ($p->win_probability ?? 0),
        ];
    }

    /**
     * 基于 win_probability 选奖；没命中则选非中奖（is_win=0）或谢谢参与。
     *
     * @return \MarketingLotteryPrizeModel|null
     */
    private function pickPrizeWithStock(int $activityId,int $uid)
    {
        $prizes = \MarketingLotteryPrizeModel::query()
            ->where('activity_id', $activityId)
            ->where('status', \MarketingLotteryPrizeModel::STATUS_ON)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $prizes = $prizes->filter(function (\MarketingLotteryPrizeModel $p) use ($uid, $activityId) {
            $cap = (int) ($p->per_user_cap ?? 0);

            if ($cap <= 0) {
                return true;
            }
            if ((int) ($p->is_win ?? 1) !== \MarketingLotteryPrizeModel::IS_WIN_YES) {
                return true;
            }
            if ((int) $p->total_stock !== -1 && (int) $p->issued_count >= (int) $p->total_stock) {
                return false;
            }
            $wonCount = \MarketingLotteryRedemptionModel::query()
                ->where('uid', $uid)
                ->where('activity_id', $activityId)
                ->where('prize_id', (int) $p->id)
                ->whereIn('status', [
                    \MarketingLotteryRedemptionModel::STATUS_PENDING,
                    \MarketingLotteryRedemptionModel::STATUS_PROCESSING,
                    \MarketingLotteryRedemptionModel::STATUS_SUCCESS,
                ])
                ->count();

            return $wonCount < $cap;
        })->values();


        if ($prizes->isEmpty()) {
            return null;
        }

        $wins = $prizes->filter(function (\MarketingLotteryPrizeModel $p) {
            return (int) ($p->is_win ?? 1) === \MarketingLotteryPrizeModel::IS_WIN_YES
                && (int) ($p->win_probability ?? 0) > 0;
        })->values();

        $r = rand(1, 100);
        $acc = 0;
        foreach ($wins as $p) {
            $acc += (int) $p->win_probability;
            if ($r <= $acc) {
                return $p;
            }
        }

        // 未命中：优先返回非中奖奖项（如 thanks / 非中奖实物等），否则返回第一个
        $noWin = $prizes->first(function (\MarketingLotteryPrizeModel $p) {
            return (int) ($p->is_win ?? 1) === \MarketingLotteryPrizeModel::IS_WIN_NO;
        });
        return $noWin ?: $prizes->first();
    }

    /**
     * VIP 奖项选择上架 VIP 产品（固定/随机）
     */
    private function resolveVipProductForPrize(\MarketingLotteryPrizeModel $prize): ?\ProductModel
    {
        $qid = \ProductModel::query()
            ->where('type', \ProductModel::TYPE_VIP)
            ->where('status', \ProductModel::STAT_ON);

        if ((int) ($prize->vip_product_id ?? 0) > 0) {
            $q = clone $qid;
            return $q->where('id', (int) $prize->vip_product_id)->first();
        }

        $ids = $prize->vip_random_product_ids;
        if (is_array($ids) && !empty($ids)) {
            $q = clone $qid;
            $rows = $q->whereIn('id', array_map('intval', $ids))->get();
            if ($rows->isEmpty()) {
                return null;
            }
            return $rows->random();
        }

        // 默认全部上架 VIP 产品随机
        $q = clone $qid;
        $rows = $q->get();
        if ($rows->isEmpty()) {
            return null;
        }
        return $rows->random();
    }

}

