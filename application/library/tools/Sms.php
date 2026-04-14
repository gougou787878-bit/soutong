<?php

namespace tools;

use Illuminate\Support\Facades\DB;

class Sms
{
    private $url;
    private $appID = 'C67034629';
    private $appKey = '092f7ea179598c225ef865e4a4a936a5';
    private $time;
    private $Db;
    private $userInfo;

    private $smsTable = TABLE_PREFIX . 'sms_log';

    public function __construct()
    {
        $this->time = TIMESTAMP;
    }

    public function sendSms(string $phone, string $prefix = '86', int $type = 1): array
    {
        $validator = $this->validatorPhone($phone);
        if ($validator['code'] != 2) {
            return $validator;
        }
        $this->setAppID($prefix);
        $code = $this->makeCode();
        $params = [
            'account' => $this->appID,
            'password' => $this->appKey,
            'mobile' => $this->getSendPhone($phone, $prefix),
            'content' => $this->getContent($code, $type),
            'time' => $this->time,
            'format' => 'json'
        ];

        $params['password'] = $this->sign($params);
        $this->insertLog($phone, $prefix, (string)$code,$type);
        $postdata['content'] = $code;
        $postdata['mobile'] = '+' . $prefix . $phone;
        // appname 需要更改
        $postdata['app_name'] = 'wyw';
        $result = singleton(HttpCurl::class)->post(config('sms.url'), $postdata);
        $returndata = [];
        if (!$result) {
            $returndata['msg'] = '短信服务器错误,请稍后再试';
            $returndata['code'] = 0;
        } else {
            $result = json_decode($result, true);
            if ($result['success'] == true) {
                $returndata['msg'] = '发送成功';
                $returndata['code'] = 2;
            } else {
                $returndata['msg'] = '发送失败';
                $returndata['code'] = 0;
            }
        }
        return $returndata;
    }

    private function getSendPhone(string $phone, string $prefix = '86'): string
    {
        $sendPhone = $prefix == '86' ? $phone : "{$prefix} {$phone}";
        return $sendPhone;
    }

    public function setAppID(string $prefix = '86'): bool
    {
        if ($prefix == '86') {
            return true;
        }
        $this->url = config('sms.url');
        $this->appID = 'I27580178';
        $this->appKey = '1da16421b17c1a8cb730dd26a30957f0';
        return true;
    }

    public function validatorCode(string $phone, $code)
    {
        if (empty($phone)) {
            return $this->status('手机号码不能为空');
        }

        $data = $this->getSmsCode($phone);
        if (empty($data)) {
            return $this->status('未找到验证码记录,请重试');
        }

        if (!array_key_exists($code, $data)) {
            return $this->status('验证码不正确');
        }

        //使用完毕就将改手机号的验证码都设置成已使用
        \SmsLogModel::where('mobile', '=', $phone)
            ->update([
                'status' => 1
            ]);
        return $this->status('验证成功', true);
    }

    public function validatorPhone(string $phone)
    {

        if (empty($phone)) {
            return $this->status('手机号码不能为空');
        }
        /*$preg = "/^1[123456789]\d{9}$/";
        if (!preg_match($preg, $phone)) {
            return $this->status('手机号码不正确');
        }*/

        $data = $this->getSmsCode($phone);
        /*if (!empty($data)) {
            $step = $this->time - end($data) > 60;
            if (!$step) {
                return $this->status('发送短信太频繁，请稍后再试');
            }
        }*/
        return $this->status('', true);
    }

    private function sign(array $params): string
    {
        $string = $params['account'] . $params['password'] . $params['mobile'] . $params['content'] . $this->time;
        return md5($string);
    }

    public function setUserInfo($arr)
    {
        $this->userInfo = $arr;
        return $this;
    }

    private function insertLog(string $phone, string $prefix, string $code, int $type = 1)
    {
        $data = [
            'uuid' => $this->userInfo['MEMBER_UUID'],
            'prefix' => $prefix,
            'mobile' => $phone,
            'code' => $code,
            'ip' => $this->userInfo['USER_IP'],
            'status' => 0,
            'type' => $type,
            'created_at' => $this->time,
        ];
        \SmsLogModel::insert($data);
        return $data;
    }

    /**
     * 鉴于短信送达率,将短信修改成只要是没用过的,都可以使用,不限于最后一条
     * @param string $phone
     * @return array
     */
    public function getSmsCode(string $phone)
    {
        $codesQuery = \SmsLogModel::where([
            'mobile' => $phone,
            'status' => 0
        ])->orderBy('id', 'desc')
            ->get(['*'], false)->toArray();
        $codes = [];
        foreach ($codesQuery as $code) {
            $codes[$code['code']] = $code['created_at'];
        }

        return $codes;
    }

    private function status(string $msg = '', bool $status = false): array
    {
        return ['code' => $status ? 2 : 0, 'msg' => $msg];
    }

    private function makeCode(): int
    {
        return rand(1000, 9999);
    }

    private function getContent(int $code, int $type = 1)
    {
        $content = [
            1 => '您的验证码是：%s 。请不要把验证码泄露给其他人。',
            2 => '您的验证码是：%s 。请不要把验证码泄露给其他人。',
            3 => '您的验证码是：%s 。请不要把验证码泄露给其他人。',
            4 => '您的验证码是：%s 。请不要把验证码泄露给其他人。',
        ];

        return sprintf($content[$type], $code);
    }
}