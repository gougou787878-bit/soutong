<?php

/**
 * Class TaskController
 * @author xiongba
 * @date 2021-06-26 15:46:57
 */
class TalkController extends BaseController
{

    /**
     * 修改匹配设置
     * @return bool
     */
    public function update_infoAction()
    {

        $data = [];
        $member = request()->getMember();

        $value = $this->post['province'] ?? null;
        if ($value) {
            $data['province'] = intval($value);
        } elseif ($value !== null) {
            $data['province'] = 0;
        }
        $value = $this->post['province_str'] ?? null;
        if ($value) {
            $data['province_str'] = trim($value);
        } elseif ($value !== null) {
            $data['province_str'] = 0;
        }
        $value = $this->post['pwd'] ?? null;
        if ($value) {
            $data['pwd'] = trim($value);
        } elseif ($value !== null) {
            $data['pwd'] = '';
        }

        $value = $this->post['hide_province'] ?? null;
        if ($value) {
            $data['hide_province'] = $value;
        } elseif ($value !== null) {
            $data['hide_province'] = 0;
        }
        $value = $this->post['age_range'] ?? null;
        if ($value) {
            $data['age_range'] = trim($value);
        } elseif ($value !== null) {
            $data['age_range'] = '';
        }
        $value = $this->post['tag'] ?? null;
        if ($value) {
            $data['tag'] = $value;
        } elseif ($value !== null) {
            $data['tag'] = '';
        }
        $value = $this->post['hope_province'] ?? null;
        if ($value) {
            $data['hope_province'] = intval($value);
        } elseif ($value !== null) {
            $data['hope_province'] = 0;
        }
        $value = $this->post['hope_province_str'] ?? null;
        if ($value) {
            $data['hope_province_str'] = trim($value);
        } elseif ($value !== null) {
            $data['hope_province_str'] = '';
        }
        $value = $this->post['hope_age_range'] ?? null;
        if ($value) {
            $data['hope_age_range'] = trim($value);
        } elseif ($value !== null) {
            $data['hope_age_range'] = '';
        }

        $value = $this->post['match_status'] ?? null;
        if ($value) {
            $data['match_status'] = $value;
        } elseif ($value !== null) {
            $data['match_status'] = 0;
        }


        try {
            if ($member->isBan()) {
                throw new \Exception('你没有权限使用');
            }
            $service = new \service\TalkServer;
            $service->updateInfo($member, $data);
            return $this->showJson('更新成功');
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }

    }

    /**
     * 购买时长
     * @return bool
     */
    public function buy_timeAction()
    {
        $id = $this->post['id'] ?? null;
        if (empty($id)) {
            return $this->errorJson('参数错误');
        }
        $member = request()->getMember();
        try {
            $service = new \service\TalkServer;
            $talk = $service->buyTime($member, (int)$id);
            if (empty($member->phone)) {
                $uid = '' . $member->uid;
                $member->phone = str_repeat('9', 12 - strlen($uid)) . $uid;
            }
            if ($member->isBan()) {
                throw new \Exception('你没有权限使用');
            }
            $s = redis()->incr("talk:number-" . date('Y-m-d'));
            if ($s <= 10) {
                redis()->expire("talk:number-" . date('Y-m-d'), 87400);
            }
            return $this->showJson((new \service\TalkServer())->ImConfig($member, $talk));
        } catch (Throwable $e) {
            $status = $e->getCode();
            if (empty($status)) {
                $status = 0;
            }
            return $this->errorJson($e->getMessage(), $status);
        }
    }


    public function chat_tokenAction()
    {
        $member = request()->getMember();
        $member->load('talk');
        $talk = $member->talk;
        if (empty($talk)) {
            $talk = MemberTalkModel::createInit($member->uid, $member->uuid);
        }
        if ($talk->left_time <= 3) {
            return $this->errorJson('您的时长已不足', 1018); // status=2008 ， 时间不住
        }
        try {
//           return $this->errorJson('假面舞会火爆开场！
//由于场面过于火爆，舞会临时执行排队入场，请您耐心排队等待。');
            if ($member->isBan()) {
                throw new \Exception('你没有权限使用');
            }
            if (empty($member->phone)) {
                $uid = '' . $member->uid;
                $member->phone = str_repeat('9', 12 - strlen($uid)) . $uid;
            }
            $s = redis()->incr("talk:number-" . date('Y-m-d'));
            if ($s <= 10) {
                redis()->expire("talk:number-" . date('Y-m-d'), 87400);
            }
            return $this->showJson((new \service\TalkServer())->ImConfig($member, $talk));
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }

    }


    public function talk_infoAction()
    {
        $member = request()->getMember();
        $member->load('talk');
        $talk = $member->talk;
        if (empty($talk)) {
            $talk = MemberTalkModel::createInit($member->uid, $member->uuid);
        }
        $talk->addHidden('uuid', 'match_status', 'created_at');
        return $this->showJson($talk);
    }


    public function product_listAction()
    {
        $list = MemberTalkTimeModel::queryBase()->get();
        return $this->showJson($list);
    }


    public function area_listAction()
    {
        $list = AreaModel::where('parent', '')->get();
        $list = $list->groupBy('first_py')->sortKeys();
        return $this->showJson($list->values());
    }


    public function report_listAction()
    {
        $list = setting('talk:report_list');
        if (empty($list)) {
            $list = ["广告/机器人", '让人不适的发言', '包含幼痛等违规内容（核实将永久封禁）'];
        }
        $this->showJson($list);
    }

    public function report_createAction()
    {
        $value = $this->post['value'] ?? null;
        $uuid = $this->post['uuid'] ?? null;
        try {
            if (empty($value) || empty($uuid)) {
                return $this->errorJson('参数错误');
            }
            $member = request()->getMember();
            TalkReportModel::insert([
                'uuid'       => $member->uuid,
                'to_uuid'    => $uuid,
                'value'      => $value,
                'msg_list'   => '[]',
                'status'     => 0,
                'created_at' => time(),
                'updated_at' => time()
            ]);
            return $this->showJson(['success' => true, 'msg' => '投诉成功']);
        } catch (\Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function configAction()
    {
        // 0   所有用户可以发送
        // 1   vip_level>=1 最低月卡可以发送
        // 2   vip_level>=2 最低季卡可有发送
        // 5   vip_level>=3 最低半年卡可有发送
        // 3   vip_level>=3 && vip_level!=5 最低年卡使用
        // 4   vip_level==4  永久卡才可以发送
        // 100  所有用户都不可以发送
        $member = request()->getMember();
        $img_role = 0;
        if ($member->vip_level == MemberModel::VIP_LEVEL_LONG) {
            $img_role = 1;
        }
        $filter_role = 0;
        if ($member->vip_level != MemberModel::VIP_LEVEL_NO) {
            $filter_role = 1;
        }
        $fn_on = 1;
//        if ($member->uid % 2 == 0){
//            $fn_on = 1;
//        }
//        if (in_array($member->aff, [35612, 973, 5664394, 139, 35612])) {
//            $fn_on = 1;
//        }


        $data = [
            'fn_on'       => $fn_on,
            'fn_text'     => '假面舞会火爆开场！
由于场面过于火爆，舞会临时执行排队入场，请您耐心排队等待。',
            'img_role'    => $img_role,
            'filter_role' => $filter_role,
            'msg_notice'  => '注意：请注意保护个人隐私，请勿泄漏QQ，微信，手机等个人联系方式，切勿相信对方发送的广告。如遇到违规用户，请退出聊天并进行投诉。',
            'msg_1'       => '您的聊天时长不足30秒，请尽快补充时间',
            'msg_2'       => '发送图片功能仅供永久会员使用',
            'msg_3'       => "您的个人身份资料不足\r\n请返回完善",
            'msg_4'       => "假面速配成功\r\n立即开始聊天",
            'pwd_list'    => ['熊熊', '健身', '二次元'],
            'tags'        => [
                '王者荣耀', '游戏党', '绝地吃鸡', '贴吧玩家', '知乎玩家', '理科生',
                '文科生', 'B站玩家', '和平精英', '刺激战场', '二次元', '旅游',
                'Gay', '健身', '学中文', '小可爱', '双性恋',
                '纯零', '纯1', '0.5', '超大'
            ],
            'age_range'   => [
                '18-23岁',
                '23-30岁',
                '30岁'
            ],
        ];
        $readme = [
            [
                'qt'       => 'Q1',
                'question' => '假面速配怎么玩？',
                'answer'   => '假面速配为隐藏身份的聊骚玩法，您可以找个想要聊的人
尽情的聊开心。开启匹配后由系统按照您设置的匹配条件
进行匹配，找到符合条件的人后开始聊天，聊天中不会展
示准确的个人信息，仅展示您的身份标签与所在地区等大
致信息。',
            ],
            [
                'qt'       => 'Q2',
                'question' => '假面速配怎么计时？',
                'answer'   => '假面速配按时间计费，在开启匹配后即开始计时。您无法
中断或取消计时，还请您合理安排时间。如果您正在聊天，
时间结束前3分钟会给您提醒，您可以根据实际情况选择
需要补充的时长。',
            ],
            [
                'qt'       => 'Q3',
                'question' => '如何匹配到我想要的人？',
                'answer'   => '开通年卡VIP可对城市和对方年龄进行筛选，更加精准。
注：精确条件匹配时，匹配时间可能会变长',
            ],
            [
                'qt'       => 'Q4',
                'question' => '暗号匹配是什么？',
                'answer'   => '您可以预设一个想聊的话题作为暗号，使用相同暗号的用
户将随机匹配在一起聊天。使用暗号匹配时，匹配设置将
不会生效，请您注意。',
            ],
            [
                'qt'       => 'Q5',
                'question' => '我想发送图片？',
                'answer'   => '发送图片作为一个测试性功能，目前仅开放给永久卡用户
使用，充值永久卡即可体验斗图的快乐。',
            ],
        ];

        $data['readme'] = $readme;
        $this->showJson($data);
    }


}