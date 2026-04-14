<?php

use helper\QueryHelper;
use service\CreatorService;
use service\ProxyService;
use service\UserVideoService;

class CreatorController extends BaseController
{

    /**
     * 创作者申请-验证配置接口
     */
    public function preCheckAction()
    {
        $member = request()->getMember();
        //公共配置
        $confData = [
            [
                'title'   => '累计审核通过视频200部及以上',
                'reached' => 0,
            ],
            [
                'title'   => '审核通过率超过60%',
                'reached' => 0,
            ],
            [
                'title'   => 'VIP等级大等于季卡',
                'reached' => 0,
            ],
            [
                'title'   => '累计粉丝达到100',
                'reached' => 0,
            ],

        ];
        //check 视频
        $count = MvModel::queryBase()->where('uid', $member->uid)->count('id');
        if ($count >= 200) {
            $confData[0]['reached'] = 1;
        }
        //check pass
        $row = MemberCreatorModel::where('uid', $member->uid)->first();
        if ($row) {
            $rate = $row['mv_check'] > 0 ? round($row['mv_submit'] / $row['mv_check'], 2) : 0;
            if ($rate >= 0.6) {
                $confData[1]['reached'] = 1;
            }
        }
        //check vip
        if ($member->expired_at > TIMESTAMP && $member->vip_level >= 2) {
            $confData[2]['reached'] = 1;
        }
        //check fans
        if ($member->fans_count >= 100) {
            $confData[3]['reached'] = 1;
        }
        return $this->showJson($confData);
    }


    /**
     * 创作者申请
     */
    public function applyAction()
    {
        $data = $this->post;
        $member = request()->getMember();
        $phone = $member->phone;
        $tag = $data['tag'] ?? '';
        $type = $data['type'] ?? \MemberMakerModel::TYPE_PERSONAL;
        $contact = $data['contact'] ?? '';
        $description = $data['description'] ?? '';
        if (empty($phone)) {
            return $this->errorJson('请绑定手机');
        }
        //初步筛选
        if ($member->expired_at < TIMESTAMP || $member->vip_level<=1) {
            return $this->errorJson('季卡充值会员用户才能提交创作者申请哟~');
        }
        /**
         * @var $memberCreator MemberCreatorModel
         */
        $memberCreator = MemberCreatorModel::where('uid', $member->uid)->first();
        if (empty($memberCreator)) {
            return $this->errorJson('您没有上传过视频~');
        }
        if ($memberCreator->mv_pass <= 5) {
            return $this->errorJson('您被审核通过的视频太少了~');
        }
        if ($memberCreator->refuse_rate >= 0.4 && $memberCreator->refuse_rate<1) {
            return $this->errorJson('您的视频被拒绝的太多了~');
        }

        /** @var MemberMakerModel $maker */
        $maker = CreatorService::applyCreator($member, $tag, $description, $type, $contact);
        $data = [];
        $data['status'] = $maker->status;
        $data['msg'] = '申请成功，已进入审核队列，请稍后查看';

        if (\MemberMakerModel::CREATOR_STAT_BAN == $maker->status) {
            $data['msg'] = '您的创作者身份已经禁用';
        } elseif (\MemberMakerModel::CREATOR_STAT_YES == $maker->status) {
            $data['msg'] = '您已经是创作者，不需要重复申请';
        } elseif (\MemberMakerModel::CREATOR_STAT_NO == $maker->status) {
            $data['msg'] = "您的创作者申请不符合要求，拒绝原因：{$maker->refuse_reason}";
        }
        return $this->showJson($data);

    }

    /**
     *制片人信息
     */
    public function infoAction()
    {
        $member = request()->getMember();
        if (!$member->auth_status) {
            return $this->errorJson('你还不是制片人~');
        }
        //print_r($member->toArray());die;
        $data = [];
        $rate_rule = MemberMakerModel::getMakerRule();

        $data['avatar_url'] = $member->avatar_url;
        $data['nickname'] = $member->nickname;
        $data['auth_level'] = 0;
        $data['auth_status'] = 0;
        $data['auth_rate'] = '25%';
        if ($member['auth_status']) {
            $data['auth_status'] = 1;
            /** @var MemberMakerModel $makerInfo */
            $makerInfo = MemberMakerModel::getMakeInfo($this->member['uuid']);
            $data['auth_level'] = $makerInfo->level_num;
            $data['auth_rate'] = sprintf("%d%%", $makerInfo->pay_rate * 100);
            $next_rate = $data['auth_rate'];
            if ($data['auth_level'] != 5) {
                $next_rate = $rate_rule[$data['auth_level'] + 1]['rate'];
            }
            $data['next_rate'] = $next_rate;
        }
        $data['warn_tips'] = '制片人违规处罚公告';
        $tips = setting('warn_tips', '禁止上传未成年、真实强奸、吸毒、枪支、偷拍、侵害他人隐私等违规内容');
        $data['warn_tips_detail'] = $tips;
        $data['rate_rule'] = $rate_rule;

        return $this->showJson($data);

    }


}
