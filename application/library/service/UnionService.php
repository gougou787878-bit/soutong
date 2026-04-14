<?php


namespace service;


use LibCrypt;

class UnionService
{
    const SIGN_KEY = 's745dkslfh4kihfks3kjdhfksjh3kdjf';
    const ENCRYPT_KEY = 'c9077f8c6268ccdfadf6ffddcgbsd2dg';
    const PRODUCT_ID = 2;
    const AD_API = "https://un.hao123apps.info/api.php/api/ads/appCenter";
    const APP_CENTER_API = "https://un.hao123apps.info/api.php/api/ads/appCenter";


    protected function crypt()
    {
        $crypt = new LibCrypt();
        $crypt->setKey(self::SIGN_KEY, self::ENCRYPT_KEY);
        return $crypt;
    }


    protected function postWithResult($url, $data)
    {
        $curl = new \tools\CurlService();
        $crypt = $this->crypt();
        $data = $crypt->replyData($data);
        $result = $curl->curlPost($url, json_decode($data, true));
        if (!empty($result)) {
            $result = $this->crypt()->checkInputData(json_decode($result, true), false);
            if (!empty($result) && $result['status'] == 1) {
                $result = $result['data'] ?? [];
            } else {
                $result = [];
            }
        } else {
            $result = [];
        }
        return $result;
    }


    public function getAds(\MemberModel $memberModel)
    {
        $data = [
            'channel'    => $memberModel->build_id,
            'product_id' => self::PRODUCT_ID,
        ];
        $result = $this->postWithResult(self::AD_API, $data);
    }

    public function getAppCenter(\MemberModel $memberModel)
    {
        $data = [
            'channel'    => $memberModel->build_id,
            'product_id' => self::PRODUCT_ID,
        ];
        return $this->postWithResult(self::APP_CENTER_API, $data);
    }


}