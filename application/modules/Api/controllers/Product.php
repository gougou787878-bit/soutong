<?php


use service\ProductService;

class ProductController extends BaseController
{

    /**
     * 获取产品
     * @return array|bool
     * @author xiongba
     */
    public function listAction()
    {
        $type = $this->post['type'] ?? 1;
        if (!in_array($type, [1, 2])) {
            return $this->errorJson('参数错误');
        }
        $service = new ProductService();
        try {
            $member = request()->getMember();
            $desc = setting('product.list.!dest', '1.如遇多次充值失败，长时间未到账且消费金额未返还情况，请在【个人中心】-'
                . '【意见反馈】中联系客服，发送支付截图凭证为您处理。'
                . '## 2.请尽量在生成订单的两分钟内支付，若不能支付可以尝试重新发起订单请求。');
            $pos = $type == 1 ? AdsModel::POSITION_MEMBER_RECHARGE : AdsModel::POSITION_DIAMOND_PLAZA;
            $data = [
                //'ads'  => \service\AdService::getADsByPosition($pos),
                'ads'  => [],
                'list' => $service->getProductListType($type,request()->getMember()),
                'desc' => $desc,
                'user' => [
                    'uid'         => $member->uid,
                    'uuid'        => $member->uuid,
                    'coins'       => $member->coins,
                    'nickname'    => $member->nickname,
                    'is_vip'      => $member->is_vip,
                    'expired_str' => $member->expired_str,
                    'avatar_url'  => $member->avatar_url
                ]
            ];
            if (version_compare($_POST['version'], '3.8.1', '<')) {//vip 干掉代理
               $listGoods = $data['list']['online'];
               if($listGoods){
                   foreach ($listGoods as &$_goods){
                       if($_goods && $_goods['pw']){
                           $_key = array_search('ps',$_goods['pw']);//低版本不要了
                           if(is_numeric($_key)){
                               unset($_goods['pw'][$_key]);
                               unset($_goods['pw_new'][$_key]);
                           }
                       }
                   }
                   $data['list']['online'] = $listGoods;
               }
            }
            if ($type == 1) {
                $data['privilege'] = [
                    [
                        'name'      => '无限观看',
                        'coins_url' => url_ads('/new/ads/20200418/2020041820012996282.png')
                    ],
                    [
                        'name'      => '金币福利',
                        'coins_url' => url_ads('/new/ads/20200418/2020041820021498093.png')
                    ],
                    [
                        'name'      => '专属铭牌',
                        'coins_url' => url_ads('/new/ads/20200418/2020041820024490793.png')
                    ],
                    [
                        'name'      => '金币视频折扣',
                        'coins_url' => url_ads('/new/ads/20200425/2020042520162044070.png')
                    ],
                ];
            }
            $data['run_light'] = WordNoticeModel::getNoticeByPosition(WordNoticeModel::POSITION_PAY_VIP);
            // var_dump($data);die;
            $this->showJson($data);
        } catch (\Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

}