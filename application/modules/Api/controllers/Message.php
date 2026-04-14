<?php

use helper\QueryHelper;
use service\EventTrackerService;

/**
 * Class MessageController
 */
class MessageController extends BaseController
{

    /**
     * 我的消息 -消息中心
     *
     * @return Json
     */
    public function listAction()
    {
        $type = $this->post['type'] ?? MessageModel::TYPE_SYSTEM;
        $member = request()->getMember();
        $uuid = $member->uuid;
        if ($type == MessageModel::TYPE_SYSTEM) {
            $data = MessageModel::getMessageList($uuid);
            return $this->showJson($data);
        }
        $typeArr = MessageModel::MSG_TYPE;
        if (isset($typeArr[$type])) {
            $data = $this->getMessageByType($type);
            return $this->showJson($data);
        }
        return $this->showJson([]);
    }

    public function productAction()
    {
        /** @var MemberModel $member */
        $member = request()->getMember();
        $data = [
            'message_total'   => (int)$member->exp,
            'message_product' => MessageModel::MSG_PRODUCT,
        ];
        return $this->showJson($data);
    }

    public function mineAction()
    {
        /** @var MemberModel $member */
        $member = request()->getMember();
        $fans = [
            'icon'  => url_ads('/new/ads/20210311/2021031112504064515.png'),
            'title' => '新粉',
            'count' => MessageModel::getMessageCount($member->uuid, MessageModel::TYPE_ATTENTION),
            'type'  => MessageModel::TYPE_ATTENTION,
        ];
        $fans = array_merge($fans,
            MessageModel::converMessageRow(MessageModel::getLeastMessage($member->uuid, MessageModel::TYPE_ATTENTION)));
        $comment = [
            'icon'  => url_ads('/new/ads/20210311/2021031112494643357.png'),
            'title' => '评论',
            'count' => MessageModel::getMessageCount($member->uuid, MessageModel::TYPE_MV),
            'type'  => MessageModel::TYPE_MV,
        ];
        $comment = array_merge($comment,
            MessageModel::converMessageRow(MessageModel::getLeastMessage($member->uuid, MessageModel::TYPE_MV)));
        $like = [
            'icon'  => url_ads('/new/ads/20210311/2021031112501434813.png'),
            'title' => '喜欢',
            'count' => MessageModel::getMessageCount($member->uuid, MessageModel::TYPE_MV_LIKE),
            'type'  => MessageModel::TYPE_MV_LIKE,
        ];
        $like = array_merge($like,
            MessageModel::converMessageRow(MessageModel::getLeastMessage($member->uuid, MessageModel::TYPE_MV_LIKE)));
        $system = [
            'icon'  => url_ads('/new/ads/20210311/2021031112474560819.png'),
            'title' => '系统通知',
            'count' => MessageModel::getMessageCount($member->uuid, MessageModel::TYPE_SYSTEM),
            'type'  => MessageModel::TYPE_SYSTEM,
        ];
        $system = array_merge($system,
            MessageModel::converMessageRow(MessageModel::getLeastMessage($member->uuid, MessageModel::TYPE_SYSTEM)));
        $data = [
            'message_total'   => (int)$member->exp,
            'message_product' => MessageModel::MSG_PRODUCT,
            'items'           => [
                $fans,
                $comment,
                $like,
                $system
            ],
        ];
        $this->showJson($data);
    }

    public function buyAction()
    {
        /** @var MemberModel $member */
        $member = request()->getMember();
        $price = $this->post['value'] ?? 0;

        // 获取产品列表，直接使用关联数组提高查找效率
        $productMap = [];
        foreach (MessageModel::MSG_PRODUCT as $item) {
            $productMap[$item['value']] = $item;
        }

        // 验证参数
        if (!isset($productMap[$price])) {
            return $this->errorJson('参数错误');
        }

        $product = $productMap[$price];
        $needCoin = (int)$product['value']; // 需要金币
        $subTitle = (int)filter_var($product['sub_title'], FILTER_SANITIZE_NUMBER_INT); // 提取条数

        $hasCoin = (int)$member->coins; // 用户金币数

        if ($needCoin > $hasCoin) {
            return $this->errorJson('请确认您的金币是否足够', 1008);
        }

        // 扣除金币并增加经验值
        $updated = MemberModel::where('uid', $member->uid)
            ->where('coins', '>=', $needCoin)
            ->decrement('coins', $needCoin);

        if ($updated) {
            MemberModel::where('uid', $member->uid)->increment('exp', $subTitle);
            $rs3 = UsersCoinrecordModel::createForExpend('buyMessage', $member->uid, 0, $needCoin, 0, 0, 0, 0);
            \MemberModel::clearFor($this->member);

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
                'product_id'            => '0',
                'product_name'          => "购买私信:" . $subTitle,
                'coin_consume_amount'   => (int)$needCoin,
                'coin_balance_before'   => (int)($member->coins),
                'coin_balance_after'    => (int)$member->coins - $needCoin,
                'consume_reason_key'    => 'im_buy',
                'consume_reason_name'   => '私信购买',
                'order_id'              => (string)$rs3->id,
                'create_time'           => to_timestamp($rs3->addtime),
            ]);

            return $this->showJson([
                'tips' => "您购买的 {$subTitle} 条私信已到账~",
            ]);
        }

        return $this->errorJson('扣款失败，请确认您的金币是否足够~', 1008);
    }


    public function systemAction()
    {
        return $this->listAction();
    }

    protected function getMessageByType($type)
    {
        list($limit, $offset) = QueryHelper::restLimitOffset();
        /** @var MemberModel $member */
        $member = request()->getMember();
        $where = [
            ['type', '=', $type],
            ['status', '=', MessageModel::STAT_ENABLE],
            ['to_uuid', '=', $member->uuid],
        ];
        $query = MessageModel::where($where)
            ->with('user:uuid,uid,nickname,thumb,aff');
        if ($type == MessageModel::TYPE_MV || $type == MessageModel::TYPE_MV_LIKE) {
            $query = $query->with('mv:id,title,cover_thumb');
        }
        $data = $query->orderByDesc('id')->limit($limit)->offset($offset)->get();
        if (!$data) {
            return [];
        }
        $data = collect($data)->map(function ($item) use ($member) {
            $item->created_at = date('m-d H:i', $item->created_at);
            if ($item->type == MessageModel::TYPE_ATTENTION) {
                if ($item->user) {
                    $item->user->watchByUser($member);
                }
                $item->mv = [];
            }
            return $item;
        })->filter()->toArray();
        MessageModel::where($where)->update(['is_read' => 1]);
        //errLog(var_export($data,true));

        return $data;

    }

    /** ########################################### 聊天相关   ------------------------------------- **/

    public function friendsAction()
    {
        $member = request()->getMember();

        // 清空计数器
        $data = ChatFriendsModel::where(['uuid' => $member->uuid])->orWhere(['to_uuid' => $member->uuid])
            ->with('user:uuid,uid,nickname,thumb')
            ->with('touser:uuid,uid,nickname,thumb')
            ->orderByDesc('id')
            ->limit(1000)
            ->get();
        if (is_null($data)) {
            return $this->showJson([]);
        }
        $data = collect($data)->map(function ($friend) use ($member) {
            $window_id = ChatLogModel::createWindowID($friend->uuid, $friend->to_uuid);
            $chatLog = ChatLogModel::where('window_id', '=', $window_id)->orderByDesc('id')->first();
            $friend->chat_log = '';
            $friend->chat_log_date = '';
            $friend->msg_count = 0;
            $friend->friend = [];
            if ($member->uuid == $friend->uuid) {
                $friend->friend = $friend->touser;
                $friend->msg_count = $friend->count;
                $friend->addHidden('user', 'touser');
            } elseif ($member->uuid == $friend->to_uuid) {
                $friend->friend = $friend->user;
                $friend->msg_count = $friend->t_count;
                $friend->addHidden('user', 'touser');
            }
            if (!is_null($chatLog)) {
                $friend->chat_log = $chatLog->content;
                $friend->chat_log_date = date("H:i", strtotime($chatLog->created_at));;
            }
            return $friend;
        });
        return $this->showJson($data);
    }

    public function friendMessageAction()
    {
        $uid = $this->post['uid'] ?? '';
        $member = request()->getMember();
        /** @var MemberModel $toMember */
        $toMember = MemberModel::where('uid', '=', $uid)->first();
        if (is_null($toMember)) {
            return $this->showJson([]);
        }
        $window_id = ChatLogModel::createWindowID($member->uuid, $toMember->uuid);
        $data = ChatLogModel::where('window_id', '=', $window_id)->limit(800)->get();
        if (is_null($data)) {
            return $this->showJson([]);
        }
        // 清空计数器
        $hasFriend = ChatFriendsModel::where('window_id', '=', $window_id)->first();
        if ($hasFriend) {
            if ($hasFriend->uuid == $member->uuid) {
                ChatFriendsModel::where('window_id', '=', $window_id)->update(['count' => 0]);
            } else {
                ChatFriendsModel::where('window_id', '=', $window_id)->update(['t_count' => 0]);
            }
        }
        $data = collect($data)->map(function ($item) use ($member) {
            $item->is_self = (int)($member->uuid == $item->from_uuid);
            $item->from_avater_full = url_avatar($item->from_avater);
            $item->to_avater_full = url_avatar($item->to_avater);
            $item->formate_date = date("m-d H:i", strtotime($item->created_at));
            $item->addHidden('from_avater', 'to_avater', 'created_at', 'window_id');
            return $item;
        })->filter()->toArray();
        return $this->showJson($data);
    }

    public function removeFriendAction()
    {
        $uid = $this->post['uid'] ?? '';
        $member = request()->getMember();
        /** @var MemberModel $toMember */
        $toMember = MemberModel::where('uid', '=', $uid)->first();
        if (is_null($toMember)) {
            return $this->errorJson('无效用户~');
        }
        $window_id = ChatLogModel::createWindowID($member->uuid, $toMember->uuid);
        ChatFriendsModel::where('window_id', '=', $window_id)->delete();
        ChatLogModel::where('window_id', '=', $window_id)->delete();
        return $this->showJson([
            'message' => "{$toMember->nickname}已移除~",
        ]);
    }

    public function chatAction()
    {
        $member = request()->getMember();
        $content = $this->post['content'] ?? '';
        $image = $this->post['image'] ?? '';//未启用
        $uid = $this->post['uid'] ?? '';
        $token = $this->post['chat_token'] ?? '';
        $myChatToken = getChatToken($member->uuid);
        if ($token != $myChatToken) {
            return $this->errorJson('私信聊天不允许，无效token~');
        }
        if ($this->member['role_id'] == MemberModel::USER_ROLE_LEVEL_BANED) {
            return $this->errorJson('您已经被禁言');
        }
        if ($member->exp < 0) {
            return $this->errorJson('请确认您的私信条数是否足够', 1009);
        }
        if (mb_strlen($content) > 600) {
            return $this->errorJson('私信内容超长~');
        }
        /** @var MemberModel $toMember */
        $toMember = MemberModel::where('uid', '=', $uid)->first();
        if (is_null($toMember)) {
            return $this->errorJson('无效用户~');
        }
        $window_id = ChatLogModel::createWindowID($member->uuid, $toMember->uuid);
        $hasFriends = ChatFriendsModel::where('window_id', '=', $window_id)->first();
        if (!$hasFriends) {
            $hasFriends = ChatFriendsModel::create([
                'uuid'      => $member->uuid,
                'to_uuid'   => $toMember->uuid,
                'window_id' => $window_id,
                'count'     => 0,
                't_count'   => 0,
                'update'    => date('Y-m-d H:i:s'),
            ]);
        }
        if (!MemberModel::where([
            ['uid', '=', $member->uid],
            ['exp', '>', 0]
        ])->decrement('exp', 1)) {
            return $this->errorJson('请确认您的私信条数是否足够', 1009);
        }
        MemberModel::clearFor($this->member);
        $flag = ChatLogModel::insert([
            'window_id'     => $window_id,
            'from_uuid'     => $member->uuid,
            'from_avater'   => $member->thumb,
            'from_nickname' => $member->nickname,
            'to_uuid'       => $toMember->uuid,
            'to_avater'     => $toMember->thumb,
            'to_nickname'   => $toMember->nickname,
            'content'       => $content,
            'images'        => '',
            'ext'           => '',
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
        if ($flag) {
            $col = 'count';
            if ($member->uuid == $hasFriends->uuid) {//a->b
                $col = 't_count';
            }
            ChatFriendsModel::where('window_id', '=', $window_id)->increment($col, 1);
        }

        return $this->showJson([
            'message' => "私信成功，待用户【{$toMember->nickname}】查看~",
        ]);

    }


}