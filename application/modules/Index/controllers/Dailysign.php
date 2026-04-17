<?php

use service\MarketingDailySignService;

class DailysignController extends H5BaseController
{
    public function infoAction()
    {
        try {
            $uid = $this->uidFromToken(false);
            $service = new MarketingDailySignService();
            return $this->showJson($service->info($uid));
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function signAction()
    {
        try {
            $uid = $this->uidFromToken(true);
            $member = MemberModel::find($uid);
            test_assert($member, '请到APP打开活动页');

            $service = new MarketingDailySignService();
            return $this->showJson($service->sign($member));
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    private function uidFromToken(bool $required): ?int
    {
        $token = (string) ($_GET['token'] ?? '');
        if ($token === '') {
            test_assert(!$required, '请到APP打开活动页');
            return null;
        }

        $uid = getCode2ID($token);
        test_assert($uid || !$required, '请到APP打开活动页');
        return $uid ? (int) $uid : null;
    }
}
