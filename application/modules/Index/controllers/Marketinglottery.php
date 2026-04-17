<?php

use helper\Util;
use service\MarketingLotteryRedemptionService;
use service\MarketingLotteryService;

class MarketinglotteryController extends H5BaseController
{
    public function remainingAction()
    {
        try {
            $token = (string) ($_GET['token'] ?? '');
            $activityId = (int) ($_GET['activity_id'] ?? 0);
            test_assert($activityId > 0, '请到APP打开活动页');

            $uid = getCode2ID($token);
            test_assert($uid, '请到APP打开活动页');

            $member = MemberModel::find($uid);
            test_assert($member, '请到APP打开活动页');

            $service = new MarketingLotteryService();
            $remaining = $service->GetUserRemainingPlays((int) $uid, $activityId);

            return $this->showJson([
                'activity_id' => $activityId,
                'uid' => (int) $uid,
                'remaining' => $remaining,
            ]);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function prizesAction()
    {
        try {
            $activityId = (int) ($_GET['activity_id'] ?? 0);
            test_assert($activityId > 0, 'activity_id 必填');

            $service = new MarketingLotteryService();

            return $this->showJson([
                'activity_id' => $activityId,
                'list' => $service->ListPrizes($activityId),
            ]);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function addPlayAction()
    {
        try {
            $uid = (int) ($_GET['uid'] ?? 0);
            $activityId = (int) ($_GET['activity_id'] ?? 0);
            $count = (int) ($_GET['count'] ?? 1);
            $count = max(1, min($count, 100));

            test_assert($uid > 0 && $activityId > 0, 'uid 和 activity_id 必填');

            $activity = MarketingLotteryActivityModel::query()
                ->where('id', $activityId)
                ->first();
            test_assert($activity, '活动不存在');

            $member = MemberModel::query()
                ->where('uid', $uid)
                ->first(['uid']);
            test_assert($member, '用户不存在');

            $now = date('Y-m-d H:i:s');
            $ids = [];
            for ($i = 0; $i < $count; $i++) {
                $row = MarketingLotteryPlayModel::create([
                    'activity_id' => $activityId,
                    'uid' => $uid,
                    'status' => MarketingLotteryPlayModel::STATUS_PENDING,
                    'extra' => [
                        'source' => 'marketinglottery_add_play_api',
                        'created_by' => 'MarketinglotteryController::addPlayAction',
                    ],
                    'expire_at' => null,
                    'remark' => '接口补充抽奖机会',
                    'idempotency_key' => 'ML_ADD_' . $uid . '_' . $activityId . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '_' . $i,
                    'source_order_id' => null,
                    'created_day' => date('Y-m-d'),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $ids[] = (int) $row->id;
            }

            $pending = (int) MarketingLotteryPlayModel::query()
                ->where('activity_id', $activityId)
                ->where('uid', $uid)
                ->where('status', MarketingLotteryPlayModel::STATUS_PENDING)
                ->count();

            return $this->showJson([
                'activity_id' => $activityId,
                'uid' => $uid,
                'added' => $count,
                'ids' => $ids,
                'pending' => $pending,
            ]);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function detailAction()
    {
        try {
            $activityId = (int) ($_GET['activity_id'] ?? 0);
            test_assert($activityId > 0, 'activity_id 必填');

            $service = new MarketingLotteryService();
            $detail = $service->GetActivityDetail($activityId);
            $products = $service->GetActivityProductList($activityId);

            return $this->showJson(array_merge($detail, $products));
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function drawAction()
    {
        try {
            $token = (string) ($_GET['token'] ?? '');
            $activityId = (int) ($_GET['activity_id'] ?? 0);
            test_assert($activityId > 0, '请到APP打开活动页');

            $uid = getCode2ID($token);
            test_assert($uid, '请到APP打开活动页');

            $key = sprintf('marketing_lottery:draw:%d:%d', $uid, $activityId);
            Util::PanicFrequency($key, 1, 3, '操作太频繁');

            $member = MemberModel::find($uid);
            test_assert($member, '请到APP打开活动页');

            $service = new MarketingLotteryService();
            return $this->showJson($service->Draw($member, $activityId));
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function redemptionsAction()
    {
        try {
            $activityId = (int) ($_GET['activity_id'] ?? 0);
            test_assert($activityId > 0, 'activity_id 必填');

            $service = new MarketingLotteryRedemptionService();
            $list = $service->ListLatestWinners($activityId, 10);

            $output = array_map(function (array $row) {
                $nickname = trim((string) ($row['nickname'] ?? ''));
                $username = trim((string) ($row['username'] ?? ''));

                return [
                    'username' => $nickname !== '' ? $nickname : $username,
                    'prize_name' => (string) ($row['prize_name'] ?? ''),
                    'prize_type' => (string) ($row['prize_type'] ?? ''),
                    'prize_type_str' => (string) ($row['prize_type_str'] ?? ''),
                ];
            }, $list);

            return $this->showJson([
                'activity_id' => $activityId,
                'list' => $output,
            ]);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function productsAction()
    {
        try {
            $rows = ProductModel::query()
                ->where('status', ProductModel::STAT_ON)
                ->where('type', ProductModel::TYPE_VIP)
                ->orderBy('sort_order', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            $list = $rows->map(function (ProductModel $row) {
                $price = (int) $row->promo_price;

                return [
                    'id' => (int) $row->id,
                    'pname' => (string) $row->pname,
                    'price' => $price,
                    'price_yuan' => $price / 100,
                    'corner_mark' => (string) ($row->corner_mark ?? ''),
                ];
            })->values()->toArray();

            return $this->showJson([
                'list' => $list,
            ]);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }
}
