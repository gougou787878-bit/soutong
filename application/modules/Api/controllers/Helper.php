<?php

use helper\QueryHelper;
use service\AppFeedSystemService;
use service\UserHelperService;

/**
 * Class Helper
 */
class HelperController extends BaseController
{

    /**
     * 迭代版本，金币商城
     */
    public function listAction()
    {
        $data = UserHelperService::getList();
        return $this->showJson($data);
    }

    function feedSaveAction()
    {
        $member = $this->member;

        if (!frequencyLimit(60, 2, $member)) {
            return $this->errorJson('發送太頻繁,稍后再次重试反饋');
        }

        $content = $this->post['content'] ?? '';
        $thumb = $this->post['thumb'] ?? '';
        $oauth_type = $this->post['oauth_type'];
        $version = $this->post['version'];
        $platform = $this->post['platform']??0;
        if ((mb_strlen($content) < 3) && empty($thumb)) {
            return $this->errorJson('详细描述下问题，方便快速解决哦');
        }
        $title = 'feed';

        $model = FeedbackModel::create([
            'uid'     => $member['uid'],
            'title'   => $title,
            'version' => $version,
            'model'   => $oauth_type,
            'content' => $content,
            'thumb'   => $thumb,
            'addtime' => TIMESTAMP,
            'status'  => FeedbackModel::STATUS_ING,
            'uptime'  => 0,
            'platform' => $platform
        ]);
        if (!is_null($model)) {
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
        }
        return $this->errorJson('反馈未收到，请稍后再试~');


    }

    /**
     * 反馈列表
     */
    public function feedListAction()
    {
        $member = $this->member;
        list($limit, $offset, $page) = QueryHelper::restLimitOffset();


        $list = FeedbackModel::leftJoin('feedback_reply', 'feedback.id', '=', 'fid')
            ->where('feedback.uid', $member['uid'])
            ->select('feedback.*', 'feedback_reply.content as reply_content', 'created_at')
            ->limit($limit)
            ->offset($offset)
            ->orderByDesc('id')
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

    }

}