<?php

namespace repositories;

use tools\RedisService;

trait PayRepository
{
    // 获取单个产品
    public function getProductById($product_id){

        $productFirst = \ProductModel::query()->where('id',$product_id)->first();

        $productFirst && $productFirst = $productFirst->toArray();

        return is_array($productFirst) ? $productFirst : [];
    }

    // 获取单个订单
    public function getOrdertFirst($uuid = '',$order_id = ''){

        $orderModel = \OrdersModel::query();

        $uuid && $orderModel = $orderModel->where('uuid',$uuid);

        $order_id && $orderModel = $orderModel->where('order_id',$order_id);

        $result = $orderModel->orderBy('id','desc')->limit(1)->first();

        return $result ? $result->toArray() : [];
    }

    // 获取产品列表
   public function getProductList($type = ''){

       $redisKey = \ProductModel::MONEY_PRODUCT_LIST."_{$type}";
       $list = RedisService::get($redisKey);
       if (!$list) {
               $result = \ProductModel::query()
                   ->where('status', 1)
                   ->where('type', $type)
                   ->orderBy('sort_order','asc')
                   ->get()
                   ->toArray();
               $list = [];
               foreach ($result as $key => $row) {
                   $payway = $value = [];
                   $value['img'] = $row['img'] ? $this->config->img->img_ads_url . $row['img'] : '';
                   $value['op'] = (int)$row['price'] / 100;                 // 原价
                   $value['p'] = (int)$row['promo_price'] / 100;     // 现价
                   $value['coins'] = (int)$row['coins'];     // 多少金币
                   $value['free_coins'] = (int)$row['free_coins'];     //赠送金币
                   $value['id'] = $row['id'];     //赠送金币
                   $value['pname'] = $row['pname'];
                   $value['pt'] = $row['pay_type'];
                   $value['description'] = $row['description']??'';
                   $row['payway_alipay'] && $payway[] = 'pa';
                   $row['payway_bank'] && $payway[] = 'pb';
                   $row['payway_visa'] && $payway[] =  'pv';
                   $row['payway_huabei'] && $payway[] = 'ph';
                   $row['payway_wechat'] && $payway[] = 'pw';
                   $value['pw'] = $payway;
                   if ($row['pay_type'] == 'online'){
                       $list['online'][] = $value;
                   }else if ($row['pay_type'] == 'agent'){
                       $list['agent'][] = $value;
                   }


               }
           $list && $this->setCacheWithSql($redisKey, $list, '产品列表', 86400);
       }
       $data['desc']        = '1.如遇多次充值失败，长时间未到账且消费金额未返还情况，请在【个人中心】-【意见反馈】中联系客服，发送支付截图凭证为您处理。## 2.请尽量在生成订单的两分钟内支付，若不能支付可以尝试重新发起订单请求。';
       $data['user']        =  [
           'coins'      => $this->member['coins'],
           'isVV'       => $this->member['expired_at'] > TIMESTAMP,
           'expiredStr' => $this->member['vip_level'] > 0 ? date('Y/m/d', $this->member['expired_at']) . '到期' : '还不是vip',
           'thumb'      => $this->fetchUserThumb($this->member['thumb']),
           'nickname'   => $this->member['nickname']
       ];
       if ($type == 1){
           $data['privilege']   = [
               ['name'=>'无限观看','coins_url'=>$this->config->img->img_ads_url.'91_ads_20200113ADnxYh1578898195594.png'],
               ['name'=>'金币福利','coins_url'=>$this->config->img->img_ads_url.'91_ads_20200113UXJkXN1578898257550.png'],
               ['name'=>'专属铭牌','coins_url'=>$this->config->img->img_ads_url.'91_ads_20200113uIsUpt1578898337529.png'],
               ['name'=>'昵称变色','coins_url'=>$this->config->img->img_ads_url.'91_ads_20200113tOU70x1578898350499.png'],
           ];
       }
       $data['list']  = is_array($list) ? $list : [];
       return $data;
   }


   // 获取订单
    public function getOrder($type){
        $uuid = $this->member['uuid'];
        $redisKey = \OrdersModel::ORDER_LIST."{$type}_{$uuid}_{$this->offset}_{$this->limit}";
        $return = RedisService::get($redisKey);
        if (!$return){
            $result = \OrdersModel::query()
                ->select('orders.id','orders.status','orders.amount','orders.updated_at','orders.payway','product.pname','product.description')
                ->leftJoin('product', 'orders.product_id','=','product.id')
                ->when(in_array($type,[1,2]), function ($query) use ($type) {
                    $query->where('product.type', $type);
                })
                ->where('orders.uuid', $uuid)
                ->orderBy('orders.id', 'desc')
                ->get()
                ->toArray();

            $return = [];
            foreach ($result as $key => $val){
                $row = array();
                $val['status'] == 3 ? $row['status'] = '已支付' : $row['status'] = '未支付';
                $row['amount'] = ceil(($val['amount']) / 100);
                $row['updated_at'] = date('Y-m-d h:i:s', $val['updated_at']);
                $row['pw'] = \OrdersModel::PAY_WAY_MAP[$val['payway']];
                $row['pname'] = $val['pname'];
                $row['description'] = $val['description'];
                $row['id'] = $val['id'];
                $return[] = $row;
            }

            $return && $this->setCacheWithSql($redisKey,$return,900);
        }

        return is_array($return) ? $return : [];
    }



    //添加账号
    public function addAccount($account){
        return \UserCashAccountModel::create($account);
    }

    //删除账号
    public function delAccount($id,$uid){
        return \UserCashAccountModel::query()->where('id',$id)->where('uid',$uid)->delete();
    }

    //获取账号列表
    public function getAccount($uid){
        return \UserCashAccountModel::query()->where('uid',$uid)->get()->toArray();
    }
}
