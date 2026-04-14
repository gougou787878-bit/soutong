<?php


namespace service;


use MemberModel;
use Throwable;

class TalkServer
{

    /**
     * 修改聊天用户信息
     * @param MemberModel $member
     * @param $data
     * @return mixed
     * @throws Throwable
     */
    public function updateInfo(MemberModel $member, $data)
    {

        return transaction(function () use ($member, $data) {
            $talk = $member->talk;
            if (empty($talk)) {
                $talk = \MemberTalkModel::createInit($member->uid, $member->uuid);
            } else {
                $area = \AreaModel::getPosByIp();
                if (!empty($area)) {
                    $talk->province = $area->adcode;
                    $talk->province_str = $area->name;
                    unset($data['province'], $data['province_str']);
                }
            }
            $data['updated_at'] = time();
            $talk->update($data);
            $itOk = $member->update($data);
            if (empty($itOk)) {
                throw new \Exception('修改错误');
            }
            return true;
        });
    }


    /**
     * 充值时长
     * @param MemberModel $member
     * @param int $id
     * @return \MemberTalkModel
     * @throws Throwable
     */
    public function buyTime(MemberModel $member, int $id): \MemberTalkModel
    {
        $member->refresh();
        /** @var \MemberTalkTimeModel $product */
        $product = \MemberTalkTimeModel::queryBase(['id' => $id])->first();
        if (empty($product)) {
            throw new \Exception('产品不存在');
        }
        $price = $product->promo_price ?: $product->price;
        if ($price <= 0) {
            throw new \Exception('数据错误，请联系客服');
        }
        if ($member->coins < $price) {
            throw new \Exception('余额不足', 427);
        }

        return transaction(function () use ($member, $product, $price) {
            $talk = $member->talk;
            if (empty($talk)) {
                $talk = \MemberTalkModel::createInit($member->uid, $member->uuid);
            }
            $tmp = abs($product->duration) + abs($product->free_duration);
            $expired_at = max($talk->expired_at, time()) + $tmp * 3600;
            $talk->expired_at = $expired_at;
            $itOk = $talk->save();
            if (empty($itOk)) {
                throw new \Exception('时长修改失败');
            }
            $itOk = $member->incrMustGE_raw(['coins' => -$price, 'consumption' => $price]);
            if (empty($itOk)) {
                throw new \Exception('扣款失败,请确认您的金币是否足够', 1008);
            }
            $desc = sprintf("购买【%s】", $product->name);
            $rs3 = \UsersCoinrecordModel::createForExpend('buyTalkTime', $member->uid, 0, $price, 0, 0, 0, 0, null, $desc);
            if (empty($rs3)) {
                throw new \Exception('记录日志错误', 1008);
            }
            $extra = [
                'sale_income' => \DB::raw('sale_income+' . $price)
            ];
            $itOk = $product->increment('sale_count', 1, $extra);
            if (empty($itOk)) {
                throw new \Exception('记录日志错误2', 1008);
            }
            \MemberModel::clearFor($member);

            //金币消耗上报
            (new EventTrackerService(
                $member->oauth_type,
                $member->invited_by,
                $member->uid,
                $member->oauth_id,
                $_POST['device_brand'] ?? '',
                $_POST['device_model'] ?? ''
            ))->addTask([
                'event'                 => EventTrackerService::EVENT_COIN_CONSUME,
                'product_id'            => (string)$product->id,
                'product_name'          => "购买时长:" . $product->name,
                'coin_consume_amount'   => (int)$price,
                'coin_balance_before'   => (int)($member->coins),
                'coin_balance_after'    => (int)$member->coins - $price,
                'consume_reason_key'    => 'talk_purchase',
                'consume_reason_name'   => '聊天购买',
                'order_id'              => (string)$rs3->id,
                'create_time'           => to_timestamp($rs3->addtime),
            ]);

            return $talk;
        });

    }


    public function incrTime(MemberModel $member, $ttl): bool
    {
        $talk = \MemberTalkModel::where('uid', $member->uid)->first();
        if (empty($talk)) {
            $talk = \MemberTalkModel::createInit($member->uid, $member->uuid, time() + $ttl, false);
            if (empty($talk)) {
                return false;
            }
        } else {
            $expired_at = max($talk->expired_at, time()) + $ttl;
            $talk->expired_at = $expired_at;
            $itOk = $talk->save();
            if (empty($itOk)) {
                return false;
            }
        }
        return true;
    }


    public function ImConfig(MemberModel $member, \MemberTalkModel $talk)
    {
        $talkJson = json_encode($talk->getAttributes());
        trigger_log($talkJson);

        $server = [
            'ws://im2.hitikapi.info:8080',
            'ws://im3.hitikapi.info:8080',
            'ws://im4.hitikapi.info:8080',
            'ws://im5.hitikapi.info:8080',
            'ws://im6.hitikapi.info:8080',
            'ws://im7.hitikapi.info:8080',
            'ws://im8.hitikapi.info:8080'
        ];
//        $server = [
//            'ws://139.162.86.162:8282'
//        ];
        $imgBase = config('img.us_base_url');
        if (false === strpos($imgBase, 'http')) {
            $imgBase = 'https://' . $imgBase . '/';
        }
        return [
            'token'      => $member->imToken(),
            'talk'       => \LibCrypt::encrypt($talkJson, 'f5AKCkbAEowIBqxet6fcWtzZyny2QEab'),
            'phone'      => $member->phone,
            'nickname'   => $member->nickname,
            'avatar_url' => $member->avatar_url,
            'uuid'       => $member->uuid,
            'uid'        => $member->uid,
            'left_time'  => $talk->left_time, // 有效期
            'img_base'   => $imgBase,
            'via'        => 'xlp',
            'sign_key'   => 'f5AKCkbAEowIBqxet6fcWtzZyny2QEab',
            'server'     => collect($server)->shuffle(),
        ];
    }


}