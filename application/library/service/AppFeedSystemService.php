<?php
/**
 *
 * @copyright
 * @todo 工单系统远程处理 ，调用逻辑控制
 * @todo  https://showdoc.hyys.info/web/#/5?page_id=3761  doc
 *
 *
 */

namespace service;

use tools\CurlService;

/**
 * Class AppFeedSystemService
 * @package service
 */
class AppFeedSystemService
{

    /**
     * 定义要使用的加密类
     *
     * @return \LibCrypt
     */
    function crypt()
    {
        $crypt = new \LibCrypt();
        $crypt->setKey(config('ticket.sign_key'), config('ticket.encrypt_key'));
        return $crypt;
    }

    /**
     * 定义要使用的curl
     *
     * @return CurlService
     */
    protected function getCurlService()
    {
        return (new CurlService());
    }

    /**
     *  定义工单通讯远程请求 (暂只对工单通讯系统有用)
     *
     * @param $url
     * @param array $postData
     * @param int $timeout
     * @return array
     */
    public function sendRemoteRequest($url, $postData = [], $timeout = 30)
    {
        $returnData = false;
        try {
            $data = $this->crypt()->replyData($postData);
            $result = $this->getCurlService()->curlPost($url, json_decode($data, true), $timeout);
            //errLog('feed-remote:'.var_export([$result,$postData],1));
            if ($result && preg_match('/success/', $result)) {
                $returnData = true;
            }
        } catch (\Throwable $exception) {
            errLog("sendfeedRemoteRequestError: \r\n " . var_export([
                    $url,
                    $postData,
                    $returnData,
                    $exception->getMessage()
                ],
                    true));
        }
        return $returnData;
    }


    /**
     * 添加 回复 工单
     * @param array $data
     * @return array
     * @example
     * app    是    string    app名称，同支付
     * uuid    是    string    用户uuid
     * app_type    是    string    设备类型
     * aff    是    string    用户邀请码
     * product    是    string    app工单：0， 游戏工单：1
     * type    是    string    消息类型：1 文字 2 图片
     * nickname    否    string    昵称，可为空
     * content    是    string    消息内容，图片为链接相对路径
     * version    是    string    app版本
     * ip    是    string    用户IP地址
     * vip_level    是    string    用户会员会员等级：普通用户，月卡会员，季卡会员。。。
     * status    是    string    用户留言 0， 管理员回复 1
     */
    public function addFeed($data = [])
    {
        $data['app'] = SYSTEM_ID;
        return $this->sendRemoteRequest(config('ticket.url'), $data);
    }


    /**
     * @param string $action 请求数据头
     * @param array $data 请求数据结构体
     * @return bool
     */
    function processData(string $action, array $data)
    {
        return call_user_func_array([$this, $action], [$data]);
    }


    const SHOW_SUCCESS = 'success';
    const SHOW_FAIL = 'fail';

    /**
     * 工单回复
     * @param $data
     * @return array
     * @desc
     * @example $data
     * uuid    是    string    用户uuid
     * content    是    string    回复内容
     */
    function reply($data)
    {
        $result = [];
        $uuid = $data['uuid'] ?? 'xyz';
        $w[] = ['uuid', '=', $uuid];
        //uid    日
        /** @var \MemberModel $member */
        $member = \MemberModel::where($w)->first();
        if (is_null($member)) {
            return self::SHOW_FAIL;
        }

        $leastRow = \FeedbackModel::where('uid', $member->uid)->orderByDesc('id')->first();
        if (is_null($leastRow)) {
            return self::SHOW_FAIL;
        }
        $flag = \FeedbackReplyModel::insert([
            'fid'        => $leastRow->id,
            'content'    => $data['content'] ?? '-',
            'mid'        => 0,
            'created_at' => time(),
        ]);
        \FeedbackModel::where('uid', $member->uid)
            ->where('status', \FeedbackModel::STATUS_ING)
            ->update(['status' => \FeedbackModel::STATUS_DONE]);
        return $flag ? self::SHOW_SUCCESS : self::SHOW_FAIL;

    }


    /**
     * 获取用户资料
     * @param $data
     * @return array
     * @desc
     * @example $data
     * actionn    是    string    getUser
     * uuid    是    string    用户uuid
     */
    function getUser($data)
    {
        $result = [];
        $uuid = $data['uuid'] ?? '';
        $aff = $data['aff'] ?? '0';
        $phone = $data['phone'] ?? '';
        $w = [];
        $uuid && $w[] = ['uuid', '=', $uuid];
        $aff && $w[] = ['aff', '=', $aff];
        $phone && $w[] = ['phone', '=', $phone];
        if (!$w) {
            $w[] = ['uuid', '=', 'xyz'];
        }
        /** @var \MemberModel $member */
        $member = \MemberModel::where($w)->first();
        if (!is_null($member)) {
            $result = [
                'uuid'        => $member->uuid,
                'oauth_id'    => $member->oauth_id,
                'oauth_type'  => $member->oauth_type,
                'aff'         => $member->aff,
                'version'     => $member->app_version,
                'reg_at'      => $member->regdate,
                'coins'       => $member->coins,
                'vip'         => $member->expired_at,
                'vip_level'   => \MemberModel::USER_VIP_TYPE[$member->vip_level] ?? '普通人',
                'phone'       => $member->phone,
                'channel'     => $member->build_id,
                'invited_by'  => $member->invited_by,
                'invited_num' => $member->invited_num,
            ];
        }
        return [
            'status' => 1,
            'data'   => $result
        ];
    }


    /**
     * 更新用户资料
     * @param $data
     * @return array
     * @desc
     * @example $data
     * actionn    是    string    updateUser
     * uuid    是    string    用户uuid
     * vip    是    string    会员过期时间
     * coins    是    string    金币数量
     */
    function updateUser($data)
    {
        $uuid = $data['uuid'] ?? 'xyz';
        $phone = $data['phone'] ?? '';
        $w[] = ['uuid', '=', $uuid];
        $update = [];
        if (isset($data['vip'])) {
            $update['expired_at'] = $data['vip'];
        }
        if (isset($data['coins']) && $data['coins'] >= 0) {
            $update['coins'] = $data['coins'];
        }
        $flag = false;
        if ($update) {
            /** @var \MemberModel $member */
            $member = \MemberModel::where($w)->first();
            if (!is_null($member)) {
                if ($phone && $member->phone != $phone) {
                    $update['phone'] = $phone;
                }
                $flag = $member->update($update);
                \MemberModel::clearFor($member->toArray());
            }
        }
        return $flag ? self::SHOW_SUCCESS : self::SHOW_FAIL;
    }


    /**
     * 获取订单列表
     * @param $data
     * @return array
     * @desc
     * @example $data
     * actionn    是    string    getOrders
     * uuid    是    string    用户uuid
     * page    是    string    默认1， 分页
     * limit    是    string    每页数量，默认10
     */
    function getOrders($data)
    {
        $uuid = $data['uuid'] ?? 'xyz';
        $page = $data['page'] ?? '1';
        $limit = $data['limit'] ?? '10';
        $query = \OrdersModel::where('uuid', $uuid);
        $count = (clone $query)->count('id');
        $result = [];
        if ($count) {
            $result = $query->orderByDesc('id')->forPage($page, $limit)->get()->map(function ($item) {
                if (is_null($item)) {
                    return [];
                }
                /** @var \OrdersModel $item */
                $product = 'other';
                if ($item->order_type == \OrdersModel::TYPE_VIP) {
                    $product = 'vip';
                } elseif ($item->order_type == \OrdersModel::TYPE_GLOD) {
                    $product = 'coins';
                }elseif ($item->order_type == \OrdersModel::TYPE_GLOD) {
                    $product = 'game';
                }
                return [
                    'order_id'   => $item->order_id,
                    'third_id'   => $item->app_order,
                    'channel'    => $item->channel,
                    'product'    => $product,
                    'amount'     => number_format($item->amount / 100, 2, '.', ''),
                    'pay_amount' => number_format($item->pay_amount / 100, 2, '.', ''),
                    'status'     => (\OrdersModel::STATUS_SUCCESS == $item->status) ? 1 : 0,
                    'desc'       => $item->descp,
                    'created_at' => $item->created_at,
                    'payed_at'   => $item->updated_at,
                ];

            })->filter()->toArray();
        }
        return ['status' => 1, 'count' => $count, 'items' => $result];
    }

    /**
     * 获取提现列表
     * @param $data
     * @return array
     * @desc
     * @example $data
     * actionn    是    string    getExchanges
     * uuid    是    string    用户uuid
     * page    是    string    默认1， 分页
     * limit    是    string    每页数量，默认10
     */
    function getExchanges($data)
    {
        $uuid = $data['uuid'] ?? 'xyz';
        $page = $data['page'] ?? '1';
        $limit = $data['limit'] ?? '10';
        $query = \UserWithdrawModel::where('uuid', $uuid);
        $count = (clone $query)->count('id');
        $result = [];
        if ($count) {
            $result = $query->orderByDesc('id')->forPage($page, $limit)->get()->map(function ($item) {
                if (is_null($item)) {
                    return [];
                }
                /** @var \UserWithdrawModel $item */
                $product = 'other';
                if ($item->order_type == \UserWithdrawModel::DRAW_TYPE_PROXY) {
                    $product = 'vip';
                } elseif ($item->order_type == \UserWithdrawModel::DRAW_TYPE_MV) {
                    $product = 'coins';
                }elseif ($item->order_type == \UserWithdrawModel::DRAW_TYPE_GAME) {
                    $product = 'game';
                }
                return [
                    'order_id'   => $item->cash_id,
                    'third_id'   => $item->third_id,
                    'channel'    => $item->channel,
                    'product'    => $product,
                    'amount'     => number_format($item->amount, 2, '.', ''),
                    'pay_amount' => number_format($item->trueto_amount, 2, '.', ''),
                    'status'     => (\UserWithdrawModel::STATUS_POST == $item->status) ? 1 : 0,
                    'desc'       => '',
                    'created_at' => $item->created_at,
                    'payed_at'   => $item->payed_at,
                ];

            })->filter()->toArray();
        }
        return ['status' => 1, 'count' => $count, 'items' => $result];
    }

    /**
     * 获取短信列表
     * @param $data
     * @return array
     * @desc
     * @example $data
     * actionn    是    string    getSms
     * uuid    是    string    用户uuid
     * page    是    string    默认1， 分页
     * limit    是    string    每页数量，默认10
     */
    function getSms($data)
    {
        $uuid = $data['uuid'] ?? 'xyz';
        $page = $data['page'] ?? '1';
        $limit = $data['limit'] ?? '10';
        $query = \SmsLogModel::where('uuid', $uuid);
        $count = (clone $query)->count('id');
        $result = [];
        if ($count) {
            $result = $query->orderByDesc('id')->forPage($page, $limit)->get()->map(function ($item) {
                if (is_null($item)) {
                    return [];
                }
                /** @var \SmsLogModel $item */

                return [
                    'phone'      => $item->mobile,
                    'prefix'     => $item->prefix,
                    'code'       => $item->code,
                    'status'     => $item->status,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                ];

            })->filter()->toArray();
        }
        return ['status' => 1, 'count' => $count, 'items' => $result];
    }

    /**
     * 获取交换日志列表
     * @param $data
     * @return array
     * @desc
     * @example $data
     * actionn    是    string    getChanges
     * uuid    是    string    用户uuid
     * page    是    string    默认1， 分页
     * limit    是    string    每页数量，默认10
     */
    function getChanges($data)
    {
        $uuid = $data['uuid'] ?? 'xyz';
        $page = $data['page'] ?? '1';
        $limit = $data['limit'] ?? '10';
        $query = \UuidLogModel::where('new_uuid', $uuid);
        $count = (clone $query)->count('id');
        $result = [];
        if ($count) {
            $result = $query->orderByDesc('id')->forPage($page, $limit)->get()->map(function ($item) {
                if (is_null($item)) {
                    return [];
                }
                /** @var \UuidLogModel $item */
                return [
                    'uuid'       => $item->old_uuid,
                    'new_uuid'   => $item->new_uuid,
                    'created_at' => $item->created_at,
                ];

            })->filter()->toArray();
        }
        return ['status' => 1, 'count' => $count, 'items' => $result];
    }


}