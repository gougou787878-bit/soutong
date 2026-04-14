<?php

namespace service;

use MemberModel;

class PcAccountService
{
    public function login($username, $password): array
    {
        $password = md5($password);
        /** @var MemberModel $member */
        $member = MemberModel::findByUsername($username);
        test_assert($member, '用户名或者密码错误');
        test_assert($member->password == $password, '用户名或者密码错误');
        $crypt = new \LibCryptPwa();
        $token = $crypt->encryptToken($member->aff, $member->oauth_id, $member->oauth_type);
        //个人信息
        $user_key = MemberModel::USER_REIDS_PREFIX . $member->aff;
        redis()->del($user_key);
        return ['token' => $token];
    }

    public function register($oauthType, $oauthId, $version, $username, $password, $invitedAff): array
    {
        $rs = preg_match('/^([a-zA-Z0-9]{5,19})+$/', $username);
        test_assert($rs, '用户名不合法');

        $rs = in_array(strtolower($username), ['channel', 'windows', 'window', 'self', 'admin', 'android', 'ios', 'pwa', 'web', 'pc', 'proxy']);
        test_assert(!$rs, '系统保留账号。注册失败');

        $password = md5($password);
        return MemberModel::createAccountByPc($oauthType, $oauthId, $version, $username, $password, $invitedAff);
    }
}