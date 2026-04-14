<?php

use service\AppFeedSystemService;
use helper\Validator;
use helper\QueryHelper;

class MessageController extends PcBaseController
{
    /**
     * 反馈列表
     * post 请求
     * page 页数
     */
    public function feedbackAction(): bool
    {
        try {
            test_assert($this->member, '您没有登录');

            $member = $this->member;
            list($page, $limit) = QueryHelper::pageLimit();

            $list = FeedbackModel::leftJoin('feedback_reply', 'feedback.id', '=', 'fid')
                ->where('feedback.uid', $member->uid)
                ->select('feedback.*', 'feedback_reply.content as reply_content', 'created_at')
                ->orderByDesc('id')
                ->forPage($page,$limit)
                ->get()->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'content' => $item->content,
                        'thumb_full' => $item->thumb ? url_ads($item->thumb) : '',
                        'addtime_str' => date('Y-m-d H:i', $item->addtime),
                        'status' => $item->status,
                        'uptime_str' => $item->uptime ? date('Y-m-d H:i', $item->uptime) : '',
                        'reply_content' => $item->reply_content,
                        'created_at_str' => $item->created_at ? date('Y-m-d H:i', $item->created_at) : ''
                    ];
                })
                ->reverse()
                ->values();

            return $this->showJson($list);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }


    /**
     * 用户反馈
     * post请求
     * content 反馈内容
     * message_type 消息类型
     *
     */
    public function feedingAction(): bool
    {
        try {
            $member = $this->member;
            test_assert($member,'您没有登录');

            $validator = Validator::make($this->data, [
                'content' => 'required'
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            if (!frequencyLimit(60, 2, $member)) {
                return $this->errorJson('發送太頻繁,稍后再次重试反饋');
            }

            $content = $this->data['content'] ?? '';
            $thumb = $this->data['thumb'] ?? '';
            $oauth_type = $this->data['oauth_type'];
            $version = $this->data['version'];
            if ((mb_strlen($content) < 3) && empty($thumb)) {
                return $this->errorJson('详细描述下问题，方便快速解决哦');
            }
            $title = 'feed';

            $model = FeedbackModel::create([
                'uid'     => $member->uid,
                'title'   => $title,
                'version' => $version,
                'model'   => $oauth_type,
                'content' => $content,
                'thumb'   => $thumb,
                'addtime' => TIMESTAMP,
                'status'  => FeedbackModel::STATUS_ING,
                'uptime'  => 0,
                'platform' => 'xlan'
            ]);
            test_assert($model,'数据异常');

            (new AppFeedSystemService())->addFeed([
                'uuid'      => $model->withMember->uuid,
                'app_type'  => $model->withMember->oauth_type,
                'aff'       => $model->withMember->aff,
                'product'   => $model->platform,
                'type'      => $model->content ? 1 : 2,
                'nickname'  => $model->withMember->nickname,
                'content'   => $model->content ? $model->content : $model->thumb,
                'version'   => $model->withMember->app_version,
                'ip'        => USER_IP,
                'vip_level' => MemberModel::USER_VIP_TYPE[$model->withMember->vip_level] ?? '普通人',
                'status'    => 0,
            ]);

            return $this->showJson(['success' => true, 'msg' => '反馈正在处理中，请耐心等待']);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function get_message_listAction(): bool
    {
        try {
            test_assert($this->member, '您没有登录');
            $member = $this->member;
            $type = $this->data['type'] ?? MessageModel::TYPE_SYSTEM;
            $uuid = $member->uuid;
            if ($type == MessageModel::TYPE_SYSTEM) {
                $data = MessageModel::getMessageList($uuid);
                return $this->showJson($data);
            }
            $typeArr = MessageModel::MSG_TYPE;
            if (isset($typeArr[$type])) {
                $data = $this->getMessageByType($member, $type);
                return $this->showJson($data);
            }
            return $this->showJson([]);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    protected function getMessageByType(MemberModel $member,$type)
    {
        list($page, $limit) = QueryHelper::pageLimit();
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
        $data = $query->orderByDesc('id')->forPage($page,$limit)->get();
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

        return $data;
    }


    public function get_system_notice_listAction(): bool
    {
        try {
            test_assert($this->member, '您没有登录');

            list($page, $limit) = QueryHelper::pageLimit();
            $list = SystemNoticeModel::where('uuid', $this->member->uuid)
                ->forPage($page, $limit)
                ->orderBy('id', 'desc')
                ->get();
            if ($list->count()) {
                $list = $list->toArray();
                SystemNoticeModel::where('uuid', $this->member->uuid)
                    ->where('status', SystemNoticeModel::STAT_NO)
                    ->update(['status' => SystemNoticeModel::STAT_OK]);
            }
            return $this->showJson($list);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }
}