<?php
namespace repositories;


use service\VerifyService;
use tools\CurlService;
use tools\RedisService;
use Yaf\Exception;

trait SmsRepository
{
    /**
     * 发送验证码
     * @param string $phone
     * @param string $prefix
     * @param null|\MemberModel $member
     * @param string $verify_code
     * @return bool
     * @throws Exception
     */
    public function seed(string $phone, string $prefix = '86',$member=null,$verify_code=''):bool
    {
        $_prefix = trim($prefix , '+');
        //虚拟号段不发送短信
        if($prefix == '86' || $prefix == ''){
            if(preg_match("/1(?:7[01]|6[257])\d{8}/",$phone)){
                throw new Exception('短信发送失败，请重试.', 422);
            }
        }
        $this->validatorPhone($phone);
        $identify = $this->makeIdentify();
        if ($prefix == 1 && USER_COUNTRY == 'CN') {
            return true;
        }

        if (setting('sms.use',0)) {
            if (empty($verify_code)) {
                throw new Exception('短信发送失败,确认已更新到最新版本~', VerifyService::VERIFY_CODE);
            }
            //verifycode
            if (!(new VerifyService())->verifyCheck($member->uid, $verify_code)) {
                throw new Exception(VerifyService::VERIFY_CODE_TEXT, VerifyService::VERIFY_CODE);
            }
        }
        $data = [
            'content' => $identify,
            'mobile' => '+' . $prefix . $phone,
            'app_name' => SYSTEM_ID,
        ];

        if('product' == APP_ENVIRON){
            $result = CurlService::post(config('sms.url'), json_encode($data), ['Content-Type: application/json']);
            //errLog("sms:".var_export($result,1));
            if (!isset($result['success']) || !$result['success']) {
                throw new Exception('短信发送失败，请重试..', 422);
            }

        }
       
        $this->handleCreateSms($phone, $prefix, $identify);
        RedisService::set('sms:' . $phone, $identify, config('sms.timeout'));
        return true;
    }

    /**
     * 验证短信
     * @param string $phone
     * @param string $identify
     * @return bool
     * @throws Exception
     */
    public function validatorSMS(string $phone, string $identify):bool
    {
        $sms = \SmsLogModel::query()->where('mobile', $phone)->orderBy('id','desc')->first();
        if (empty($sms)) {
            throw new Exception('验证码已过期', 422);
        }

        if ($sms->code != $identify) {
            throw new Exception('验证码不正确', 422);
        }

        RedisService::redis()->del('sms:' . $phone);
        $sms->status = \SmsLogModel::STATUS_USED;
        $sms->save();

        return true;
    }

    /**
     * 保存发送记录
     * @param string $phone
     * @param string $prefix
     * @param string $identify
     */
    private function handleCreateSms(string $phone, string $prefix, string $identify)
    {
        $data = [
            'uuid' => $this->member['uuid'],
            'prefix' => $prefix,
            'mobile' => $phone,
            'code' => $identify,
            'ip' => USER_IP,
            'created_at'=>date('Y-m-d H:i:s')
        ];
        \SmsLogModel::create($data);
    }

    /**
     * validator
     * @param string $phone
     * @throws Exception
     */
    private function validatorPhone(string $phone)
    {
        if ($phone == '' or empty($phone)) {
            throw new Exception('手机号码不能为空', 422);
        }

        $has = RedisService::get('sms:' . $phone);
        if ($has) {
            throw new Exception('发送短信太频繁，请稍后重试', 422);
        }
    }

    /**
     * 生成验证码
     * @return int
     */
    private function makeIdentify()
    {
        return rand(1000, 9999);
    }
}