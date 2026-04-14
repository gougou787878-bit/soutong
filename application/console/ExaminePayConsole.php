<?php


namespace App\console;


use tools\HttpCurl;


class ExaminePayConsole extends AbstractConsole
{

    public $name = 'examine-pay';

    public $description = '检查支付';


    public function process($argc, $argv)
    {
        $list = \ProductModel::where('status', \ProductModel::STAT_ON)->get();
        $priceAry = $list->pluck('promo_price');
        $requestAry = [];
        foreach ($priceAry as $price) {
            $requestAry[] = intval($price / 100);
        }
        try {
            $params = $this->genParams(SYSTEM_ID, $requestAry);
            $response_data = $this->post($params);
            $data = json_decode($response_data, true);
            if (json_last_error() != JSON_ERROR_NONE) {
                throw new \Exception('json错误');
            }
            $this->clearRedis();

            \ProductModel::where('status', \ProductModel::STAT_ON)
                ->where('type' ,'!=' , \ProductModel::TYPE_GAME)
                ->update([
                'payway_wechat' => 0,
                'payway_bank' => 0,
                'payway_alipay' => 0,
                'payway_visa' => 0,
                'payway_huabei' => 0,
            ]);

            $data = $data['data'] ?? [];

            if ($amounts = data_get($data, 'alipay')) {
                $amounts = array_map($this->map_fn(), $amounts);
                \ProductModel::where('status', \ProductModel::STAT_ON)
                    ->where('type' ,'!=' , \ProductModel::TYPE_GAME)
                    ->whereIn('promo_price', $amounts)
                    ->update([
                        'payway_alipay' => 1
                    ]);
            }

            if ($amounts = data_get($data, 'wechat')) {
                $amounts = array_map($this->map_fn(), $amounts);
                \ProductModel::where('status', \ProductModel::STAT_ON)
                    ->where('type' ,'!=' , \ProductModel::TYPE_GAME)
                    ->whereIn('promo_price', $amounts)
                    ->update([
                        'payway_wechat' => 1
                    ]);
            }

            if ($amounts = data_get($data, 'visa')) {
                $amounts = array_map($this->map_fn(), $amounts);
                \ProductModel::where('status', \ProductModel::STAT_ON)
                    ->where('type' ,'!=' , \ProductModel::TYPE_GAME)
                    ->whereIn('promo_price', $amounts)
                    ->update([
                        'payway_visa' => 1
                    ]);
            }

            if ($amounts = data_get($data, 'bankcard')) {
                $amounts = array_map($this->map_fn(), $amounts);
                \ProductModel::where('status', \ProductModel::STAT_ON)
                    ->where('type' ,'!=' , \ProductModel::TYPE_GAME)
                    ->whereIn('promo_price', $amounts)
                    ->update([
                        'payway_bank' => 1
                    ]);
            }

            if ($amounts = data_get($data, 'huabei')) {
                $amounts = array_map($this->map_fn(), $amounts);
                \ProductModel::where('status', \ProductModel::STAT_ON)
                    ->where('type' ,'!=' , \ProductModel::TYPE_GAME)
                    ->whereIn('promo_price', $amounts)
                    ->update([
                        'payway_huabei' => 1
                    ]);
            }
            print_r($data);
        } catch (\Throwable $e) {
            trigger_log($e);
        }

    }


    protected function clearRedis()
    {
        foreach ([1,2,3,4,5] as $type) {
            $key = \ProductModel::MONEY_PRODUCT_LIST . "_v2_{$type}";
            redis()->del($key);
            $key = \ProductModel::COINS_PRODUCT_LIST ."_{$type}";
            redis()->del($key);
        }
    }



    protected function genParams($app_name, $amounts)
    {
        $data = [
            'timestamps' => time(),
            'app_name' => $app_name,
        ];
        $data['amounts'] = $amounts;
        $data['sign'] = md5(sprintf("%s%d%s", $app_name, $data['timestamps'], config('pay.pay_signkey')));
        return $data;
    }

    protected function map_fn()
    {
        return function ($v) {
            return $v * 100;
        };
    }


    protected function post(array $post)
    {
        $curl = new HttpCurl;
        $result = $curl->post(config('pay.pay_channel'),$post);
        return trim($result);

    }


}