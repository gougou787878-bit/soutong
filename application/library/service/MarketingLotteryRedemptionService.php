<?php

namespace service;

/**
 * 营销抽奖兑奖：状态流转、用户查询与收货信息
 */
class MarketingLotteryRedemptionService
{
    /**
     * 用户侧：某活动下的兑奖记录（分页）
     *
     * @return array<int, array<string, mixed>>
     */
    public function ListForUser(int $uid, int $activityId, int $limit, int $offset): array
    {
        test_assert($activityId > 0, 'activity_id 必填');
        $q = \MarketingLotteryRedemptionModel::query()
            ->where('uid', $uid)
            ->where('activity_id', $activityId)
            ->orderByDesc('id');

        $rows = (clone $q)->offset($offset)->limit($limit)->get();

        return $rows->map(function (\MarketingLotteryRedemptionModel $r) {
            return $this->formatUserRow($r);
        })->values()->all();
    }

    /**
     * 活动侧：最新中奖记录（用于跑马灯/展示墙）
     *
     * @return array<int, array{uid:int, username:string, nickname:string, prize_name:string, created_at:string}>
     */
    public function ListLatestWinners(int $activityId, int $limit = 10): array
    {
        test_assert($activityId > 0, 'activity_id 必填');
        if ($limit <= 0) {
            $limit = 10;
        }
        if ($limit > 50) {
            $limit = 50;
        }

        $q = \MarketingLotteryRedemptionModel::query()
            ->where('activity_id', $activityId);


        $rows = $q->orderByDesc('id')->limit($limit * 5)->get()->filter(function (\MarketingLotteryRedemptionModel $r) {
            $prize = is_array($r->prize_snapshot) ? $r->prize_snapshot : [];
            if (array_key_exists('is_win', $prize)) {
                return (int) $prize['is_win'] === 1;
            }
            return (string) ($prize['prize_type'] ?? '') !== \MarketingLotteryPrizeModel::PRIZE_THANKS;
        })->take($limit)->values();




        $uids = $rows->pluck('uid')->map(function ($uid) {
            return (int) $uid;
        })->unique()->values()->all();

        $userMap = [];
        if (!empty($uids)) {
            $users = \MemberModel::query()
                ->whereIn('uid', $uids)
                ->get(['uid', 'username', 'nickname']);
            foreach ($users as $u) {
                /** @var \MemberModel $u */
                $userMap[(int) $u->uid] = [
                    'username' => (string) ($u->username ?? ''),
                    'nickname' => (string) ($u->nickname ?? ''),
                ];
            }
        }

        return $rows->map(function (\MarketingLotteryRedemptionModel $r) use ($userMap) {
            $uid = (int) $r->uid;
            $u = $userMap[$uid] ?? ['username' => '', 'nickname' => ''];
            $prize = is_array($r->prize_snapshot) ? $r->prize_snapshot : [];
            $prizeType = (string) ($prize['prize_type'] ?? '');
            if ($prizeType === '' && $r->prize_id) {
                $prizeType = (string) (\MarketingLotteryPrizeModel::query()
                    ->where('id', (int) $r->prize_id)
                    ->value('prize_type') ?? '');
            }
            return [
                'uid' => $uid,
                'username' => (string) $u['username'],
                'nickname' => (string) $u['nickname'],
                'prize_name' => (string) ($r->prize_name ?? ''),
                'prize_type' => $prizeType,
                'prize_type_str' => \MarketingLotteryPrizeModel::PRIZE_TYPE_TIPS[$prizeType] ?? $prizeType,
                'created_at' => (string) ($r->created_at ?? ''),
            ];
        })->values()->all();
    }

    /**
     * 用户补充实物收货信息（仅待处理 + 实物奖）
     *
     * @param array<string, string> $data receiver_name, receiver_phone, receiver_address, receiver_remark（可选）
     */
    public function SubmitReceiverInfo(int $uid, int $redemptionId, array $data): array
    {
        $name = trim((string) ($data['receiver_name'] ?? ''));
        $phone = trim((string) ($data['receiver_phone'] ?? ''));
        $address = trim((string) ($data['receiver_address'] ?? ''));
        $extraRemark = trim((string) ($data['receiver_remark'] ?? ''));

        test_assert($name !== '', '请填写收货人');
        test_assert($phone !== '', '请填写手机号');
        test_assert($address !== '', '请填写详细地址');

        return transaction(function () use ($uid, $redemptionId, $name, $phone, $address, $extraRemark) {
            /** @var \MarketingLotteryRedemptionModel|null $row */
            $row = \MarketingLotteryRedemptionModel::query()
                ->where('id', $redemptionId)
                ->where('uid', $uid)
                ->lockForUpdate()
                ->first();
            test_assert($row, '兑奖记录不存在');
            test_assert((int) $row->status === \MarketingLotteryRedemptionModel::STATUS_PENDING, '当前状态不可提交收货信息');

            $prize = is_array($row->prize_snapshot) ? $row->prize_snapshot : [];
            test_assert(
                (string) ($prize['prize_type'] ?? '') === \MarketingLotteryPrizeModel::PRIZE_PHYSICAL,
                '非实物奖品无需填写地址'
            );


            $snap = is_array($row->grant_snapshot) ? $row->grant_snapshot : [];
            $snap['receiver_name'] = $name;
            $snap['receiver_phone'] = $phone;
            $snap['receiver_address'] = $address;
            if ($extraRemark !== '') {
                $snap['receiver_remark'] = $extraRemark;
            }
            $snap['receiver_submitted_at'] = date('Y-m-d H:i:s');

            $row->grant_snapshot = $snap;
            $prevRemark = trim((string) ($row->remark ?? ''));
            if ($prevRemark === '' || $prevRemark === '实物奖品待发放') {
                $row->remark = '用户已提交收货信息';
            } else {
                $row->remark = $prevRemark . '；用户已提交收货信息';
            }
            $row->save();

            return $this->formatUserRow($row);
        });
    }

    /**
     * 后台保存：校验核心字段与状态机，写入管理员与快照
     *
     * @param array<string, mixed> $post 表单 POST（含 _pk、status、remark、grant_snapshot、prize_snapshot 等）
     */
    public function AdminSave(int $redemptionId, int $adminUid, array $post): \MarketingLotteryRedemptionModel
    {
        test_assert($adminUid > 0, '未登录');

        return transaction(function () use ($redemptionId, $adminUid, $post) {
            /** @var \MarketingLotteryRedemptionModel|null $row */
            $row = \MarketingLotteryRedemptionModel::query()
                ->where('id', $redemptionId)
                ->lockForUpdate()
                ->first();
            test_assert($row, '记录不存在');

            foreach (['play_id', 'uid', 'activity_id', 'prize_id'] as $f) {
                if (!array_key_exists($f, $post)) {
                    continue;
                }
                test_assert((int) $post[$f] === (int) $row->{$f}, '不允许修改核心字段');
            }

            $oldStatus = (int) $row->status;
            $newStatus = array_key_exists('status', $post) ? (int) $post['status'] : $oldStatus;

            if ($newStatus !== $oldStatus) {
                $this->AssertStatusTransition($oldStatus, $newStatus);
            }

            if (array_key_exists('remark', $post)) {
                $row->remark = (string) $post['remark'];
            }

            if (array_key_exists('grant_snapshot', $post) && $post['grant_snapshot'] !== '' && $post['grant_snapshot'] !== null) {
                $g = $post['grant_snapshot'];
                if (is_string($g)) {
                    $decoded = json_decode($g, true);
                    test_assert(is_array($decoded), 'grant_snapshot JSON 无效');
                    $g = $decoded;
                }
                test_assert(is_array($g), 'grant_snapshot 格式错误');
                $base = is_array($row->grant_snapshot) ? $row->grant_snapshot : [];
                $row->grant_snapshot = array_merge($base, $g);
            }

            if (array_key_exists('prize_snapshot', $post) && $post['prize_snapshot'] !== '' && $post['prize_snapshot'] !== null) {
                $p = $post['prize_snapshot'];
                if (is_string($p)) {
                    $decoded = json_decode($p, true);
                    test_assert(is_array($decoded), 'prize_snapshot JSON 无效');
                    $p = $decoded;
                }
                test_assert(is_array($p), 'prize_snapshot 格式错误');
                $base = is_array($row->prize_snapshot) ? $row->prize_snapshot : [];
                $row->prize_snapshot = array_merge($base, $p);
            }

            $row->status = $newStatus;
            $row->admin_uid = $adminUid;
            $row->save();

            return $row;
        });
    }

    private function AssertStatusTransition(int $from, int $to): void
    {
        if ($from === $to) {
            return;
        }
        test_assert(
            !in_array($from, [\MarketingLotteryRedemptionModel::STATUS_SUCCESS, \MarketingLotteryRedemptionModel::STATUS_FAIL], true),
            '终态不可变更状态'
        );

        $allowed = [
            \MarketingLotteryRedemptionModel::STATUS_PENDING => [
                \MarketingLotteryRedemptionModel::STATUS_PROCESSING,
                \MarketingLotteryRedemptionModel::STATUS_SUCCESS,
                \MarketingLotteryRedemptionModel::STATUS_FAIL,
            ],
            \MarketingLotteryRedemptionModel::STATUS_PROCESSING => [
                \MarketingLotteryRedemptionModel::STATUS_SUCCESS,
                \MarketingLotteryRedemptionModel::STATUS_FAIL,
            ],
        ];

        test_assert(
            isset($allowed[$from]) && in_array($to, $allowed[$from], true),
            '非法的状态流转'
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function formatUserRow(\MarketingLotteryRedemptionModel $r): array
    {
        $grant = is_array($r->grant_snapshot) ? $r->grant_snapshot : [];
        $prize = is_array($r->prize_snapshot) ? $r->prize_snapshot : [];
        $prizeType = (string) ($prize['prize_type'] ?? '');
        $isWin = (int) ($prize['is_win'] ?? 0);
        $prizeImage = (string) ($prize['prize_image'] ?? '');
        $status = (int) $r->status;


        return [
            'id' => (int) $r->id,
            'activity_id' => (int) $r->activity_id,
            'activity_name' => (string) ($r->activity_name ?? ''),
            'play_id' => (int) $r->play_id,
            'prize_id' => $r->prize_id !== null ? (int) $r->prize_id : null,
            'prize_name' => (string) ($r->prize_name ?? ''),

            'prize_type' => $prizeType,
            'prize_type_str' => \MarketingLotteryPrizeModel::PRIZE_TYPE_TIPS[$prizeType] ?? $prizeType,
            'is_win' => $isWin,
            'prize_image_full' => url_ads($prizeImage),
            'grant_coins' => (int) ($grant['coins'] ?? 0),
            'grant_vip_product_id' => isset($grant['vip_product_id']) ? (int) $grant['vip_product_id'] : null,
            'status' => $status,
            'status_str' => \MarketingLotteryRedemptionModel::STATUS_TIPS[$status] ?? (string) $status,
            'remark' => (string) ($r->remark ?? ''),
            'created_at' => (string) ($r->created_at ?? ''),
            'updated_at' => (string) ($r->updated_at ?? ''),
            'receiver' => [
                'name' => (string) ($grant['receiver_name'] ?? ''),
                'phone' => (string) ($grant['receiver_phone'] ?? ''),
                'address' => (string) ($grant['receiver_address'] ?? ''),
                'remark' => (string) ($grant['receiver_remark'] ?? ''),
                'submitted_at' => (string) ($grant['receiver_submitted_at'] ?? ''),
            ],
            'fulfillment' => [
                'express_company' => (string) ($grant['express_company'] ?? ''),
                'tracking_no' => (string) ($grant['tracking_no'] ?? ''),
                'shipped_at' => (string) ($grant['shipped_at'] ?? ''),
            ],
        ];
    }
}
