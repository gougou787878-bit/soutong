<?php

use helper\Validator;
use service\PcAccountService;

class AccountController extends PcBaseController
{
    // 注册
    public function registerAction(): bool
    {
        try {
            test_assert(!$this->member, '您已经登录了');

            $validator = Validator::make($this->data, [
                'username' => 'required',
                'password' => 'required',
                'oauth_type' => 'required',
                'oauth_id' => 'required',
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $service = new PcAccountService();
            $username = $this->data['username'];
            $password = $this->data['password'];
            $inviteAff = $this->data['invite_aff'] ?? '';
            $oauthType = $this->data['oauth_type'];
            $oauthId = $this->data['oauth_id'];
            $version = $this->data['version'];
            $data = $service->register($oauthType, $oauthId, $version, $username, $password, $inviteAff);
            // 注册统计
            SysTotalModel::incrBy('pc:register');
            return $this->showJson($data);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    // 登录
    public function loginAction(): bool
    {
        try {
            test_assert(!$this->member, '您已经登录了');

            $validator = Validator::make($this->data, [
                'username' => 'required',
                'password' => 'required',
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $service = new PcAccountService();
            $username = $this->data['username'];
            $password = $this->data['password'];
            $data = $service->login($username, $password);
            return $this->showJson($data);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }
}