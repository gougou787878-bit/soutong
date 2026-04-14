<?php

use helper\Validator;
use service\ObjectR2Service;
use tools\RedisService;

class UserController extends PcBaseController
{
    public function infoAction(): bool
    {
        try {
            /** @var MemberModel $member */
            $member = $this->member;
            test_assert($member, '您没有登录');
            $data = [
                'nickname'   => $member->nickname,
                'thumb'      => $member->avatar_url,
                'sex'        => $member->sexType,
                'aff'        => $member->aff,
                'aff_code'   => generate_code($member->aff),
                'expired_at' => $member->expired_at,
                'is_vip'     => $member->is_vip,
                'vip_level'  => $member->vip_level,
            ];
            $member->updateSession();
            return $this->showJson($data);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    // 更新信息
    public function updateAction(): bool
    {
        try {
            test_assert($this->member, '您没有登录');
            test_assert($this->member->vip_level > 0, '非会员不允许修改,请下载app购买会员');

            // 存在未审核的记录直接提示
            $nickname = trim($this->data['nickname'] ?? '');
            $sex = trim($this->data['sex'] ?? '');
            $thumb = trim($this->data['thumb'] ?? '');
            test_assert(!(!$nickname && !$sex && !$thumb), '请正确提交需要修改的数据');
            $keys = array_keys(MemberModel::SEX_TYPE_TIPS);
            test_assert(!($sex && !in_array($sex, $keys)), '性别参数不合法');
            /** @var MemberModel $member */
            $member = $this->member;
            if ($nickname) {
                $member->nickname = $nickname;
            }
            if ($thumb) {
                $member->thumb = $thumb;
            }
            if ($sex) {
                $member->sexType = (int)$sex;
            }

            $isOk = $member->save();
            test_assert($isOk, '系统异常');

            return $this->successMsg('修改成功');
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function user_detailAction(): ?bool
    {
        try {
            $validator = Validator::make($this->data, [
                'aff' => 'required|numeric|min:1',
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $aff = (int)$this->data['aff'];

            $user = MemberModel::getUserInfo($aff);
            test_assert($user, '此用户不存在');
            $_SERVER['SCRIPT_PARAMS'] = [$aff];
            return $this->showJson($user);
        } catch (\Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * R2上传配置
     * @return bool
     */
    public function r2upload_infoAction()
    {
        try {
            test_assert($this->member, '您没有登录');
            $member = $this->member;
            if ($member->isBan()){
                return $this->errorJson('涉嫌违规，没有权限操作，请联系壮壮~');
            }
            $data = ObjectR2Service::r2UploadInfo();
            if (!$data) {
                return $this->errorJson('上传配置异常，关闭重试～');
            }
            $data['uploadName'] = $data['UploadName'];
            unset($data['UploadName']);
            return $this->showJson($data);
        }catch (Throwable $e){
            return $this->errorJson($e->getMessage());
        }
    }

    public function uploadMvAction(){
        try {
            $member = $this->member;
            test_assert($member, '您没有登录');

            $validator = Validator::make($this->data, [
                'title' => 'required',
                'url' => 'required',
                'tags' => 'required',
                'img_url' => 'required',
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $tags = $this->data['tags'];
            $tags = implode(',', explode(',', trim($tags)));
            $img_url = $this->data['img_url'];
            $url = $this->data['url'];
            $title = $this->data['title'];
            $thumb_height = $this->data['thumb_height'] ?? 0;
            $thumb_width = $this->data['thumb_width'] ?? 0;
            $music_id = $this->data['music_id'] ?? 0;

            if ($member->isBan()) {
                return $this->errorJson('你涉嫌违规，没有权限操作');
            }

            //check
            $res = MvModel::checkMemberToReleaseGoldMV($member->uid);
            $is_fee = $res['can_release_fee'] ?? 0;
            $is_can = $res['can_release'] ?? 0;
            $not_can_msg = $res['msg_tips'] ?? '该用户不允许上传视频';
            if (!$is_can) {
                return $this->errorJson($not_can_msg);
            }

            $coins = isset($this->data['coins']) ? (int)$this->data['coins'] : 0;
            if (!$is_fee && $coins) {
                return $this->errorJson('你的付费视频额度已超比例，请先上传免费视频');
            }

            transaction(function () use ($member,$tags,$title,$img_url,$url,$coins,$thumb_height,$thumb_width,$music_id){
                if (!\MemberCreatorModel::where('uid', $member->uid)->exists()) {
                    $itOK = \MemberCreatorModel::init($member);
                } else {
                    $itOK = \MemberCreatorModel::where('uid', $member->uid)->increment('mv_submit');
                }
                test_assert($itOK, '创建失败');

                $data = [
                    'uid'          => $member->uid,
                    'title'        => strip_tags($title),
                    'm3u8'         => $url,
                    'cover_thumb'  => $img_url,
                    'thumb_height' => (int)$thumb_height,
                    'thumb_width'  => (int)$thumb_width,
                    'tags'         => strip_tags($tags),
                    'via'          => 'user_upload',
                    'coins'        => $coins,
                    'is_free'      => $coins <= 0 ? \MvSubmitModel::IS_FREE_YES : \MvSubmitModel::IS_FREE_NO,
                    'created_at'   => TIMESTAMP,
                    'music_id'     => (int)$music_id,
                ];

                $itOK = \MvSubmitModel::query()->insert($data);
                test_assert($itOK, '创建失败');
            });

            RedisService::redis()->zIncrBy(\MvModel::STAT_UPLOAD_NUMBER, 1, date('Ymd'));
            //上传视频数统计
            \SysTotalModel::incrBy('now:mv:up');
            //ip 限制 统计
            MvUploadIpInfoModel::addData($member);

            return $this->successMsg('上传成功，请等待审核');
        }catch (Throwable $e){
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     *用户发布砖石视频 新增 配置 接口
     */
    public function preUploadAction()
    {
        try {
            $member = $this->member;
            test_assert($member, '您没有登录');

            $res = MvModel::checkMemberToReleaseGoldMV($member->uid);
            $return = [
                'tags'         => TagsModel::tagList(),
                'is_fee'       => $res['can_release_fee'] ?? 0,
                'price_max'    => abs(intval(setting('mv:coins:max', 100))),
                'rule_text'    => setting('upload.tips', '禁止上传未成年、真实强奸、吸毒、枪支、偷拍、侵害他人隐私等违规内容'),
                'price_text'   => '#txt#，后续可设置为付费。每日总付费视频数量不可超过免费视频数量。',
                'price_strong' => '每日前两部只可上传免费视频',
                'rule'         => [
                    'rule'   => '可以上传',
                    'status' => 1,
                    'msg'    => '上传更多视频,收益更多~',
                ],
                'is_maker'     => (int)$member->auth_status,
                'new_rule'     => $res
            ];
            return $this->showJson($return);
        }catch (Throwable $e){
            return $this->errorJson($e->getMessage());
        }
    }

}