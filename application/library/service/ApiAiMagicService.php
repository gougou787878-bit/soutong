<?php

namespace service;

use Constant;
use Illuminate\Support\Collection;
use LibCrypt;
use MemberCoinrecordModel;
use MemberMagicModel;
use MagicModel;
use MemberModel;
use PrivilegeModel;
use tools\HttpCurl;
use Throwable;
use UsersProductPrivilegeModel;

class ApiAiMagicService
{
    const MAGIC_URI = '/api/public/generate/videos/scenes';
    const TASK_URI = '/api/public/task/list';
    const LOG_PATH = '/storage/logs/magic.log';

    public function list_material($page, $limit)
    {
        return MagicModel::list_material($page, $limit);
    }

    public function getMagicPreData(MemberModel $member)
    {
        $free_num = (int)UsersProductPrivilegeModel::hasPrivilege(
            USER_PRIVILEGE,
            PrivilegeModel::RESOURCE_TYPE_AI_MAGIC,
            PrivilegeModel::PRIVILEGE_TYPE_UNLOCK
        );
        return [
            'max_size' => '2M',
            'free_num' => (string)$free_num,
            'coins' => $member->coins,
            'ai_magic_coins' => setting('ai_magic_coins', 19),
            'ai_magic_tips' => setting('ai_magic_tips', '
1、素材仅供AI使用，绝无外泄风险，请放心使用，
2、素材需清晰，不超过2MB，上传间隔大于60秒。
3、本功能不支持多人图片，脸部无遮挡物（眼镜、刘海等）
4、近距离大头照会生成失败，禁止使用未成年人照片！'),
            'exp_correct_img' => url_cover('/upload_01/ads/20260116/2026011611215111256.png'),
            'exp_error1_img' => url_cover('/upload_01/ads/20260116/2026011611222816986.jpeg'),
            'exp_error2_img' => url_cover('/upload_01/ads/20260116/2026011611221298854.jpeg'),
            'exp_error3_img' => url_cover('/upload_01/ads/20260116/2026011611220341326.png'),
        ];
    }

    public function generate_video(MemberModel $member, $type, $material_id, $thumb, $thumb_w, $thumb_h)
    {
        $this->check_type($thumb);
        /** @var MagicModel $material */
        $material = MagicModel::detail($material_id);
        test_assert($material, '素材已被下架');
        transaction(function () use ($member, $material, $material_id, $type, $thumb, $thumb_w, $thumb_h) {
            $need_coins = 0;
            if ($type == MagicModel::TYPE_COINS) {
                $need_coins = $material->coins;
                if (!$need_coins) {
                    //金币数未配置 走默认的
                    $need_coins = setting('ai_magic_coins', 190);
                }
                $discount = UsersProductPrivilegeModel::hasPrivilege(
                    USER_PRIVILEGE,
                    PrivilegeModel::RESOURCE_TYPE_AI_MAGIC,
                    PrivilegeModel::PRIVILEGE_TYPE_DISCOUNT
                );
                //折扣
                if ($discount) {
                    $need_coins = ceil($discount / 100 * $need_coins);
                }

                if ($member->coins < $need_coins) {
                    throw new \Exception('余额不足', \Constant::COINS_INSUFFICIENT);
                }

                $isOk = MemberModel::where('aff', $member->aff)
                    ->where('coins', '>=', $need_coins)
                    ->decrement('coins', $need_coins);
                test_assert($isOk, '扣款失败,请确认您的余额是否足够', \Constant::COINS_INSUFFICIENT);

                $rs = MemberMagicModel::create_record($member->aff, $material, $thumb, $thumb_w, $thumb_h, MemberMagicModel::PAY_TYPE_COINS, $need_coins);
                test_assert($rs, '系统异常，请稍后再试');
                //记录日志

                $tips = "[AI魔法]扣款]#金币： $need_coins";
                \UsersCoinrecordModel::createForExpend("aiMagic", $member->uid, 0,
                    $need_coins,
                    $material_id,
                    0,
                    0,
                    0,
                    null,
                    $tips);

                MemberModel::clearFor($member);
            } else {
                if ($material->type == MagicModel::TYPE_FIX) {
                    $value = UsersProductPrivilegeModel::hasPrivilege(
                        USER_PRIVILEGE,
                        PrivilegeModel::RESOURCE_TYPE_AI_MAGIC,
                        PrivilegeModel::PRIVILEGE_TYPE_UNLOCK
                    );
                    test_assert($value, '免费解锁次数不足');
                } else {
                    test_assert(false, '次素材不能用次数解锁');
                }

                $rs = MemberMagicModel::create_record($member->aff, $material, $thumb, $thumb_w, $thumb_h, MemberMagicModel::PAY_TYPE_FREE, 0);
                test_assert($rs, '系统异常，请稍后再试');

                //使用VIP权限的次数
                UsersProductPrivilegeModel::hasPrivilegeAndSubTimePrivilege(
                    USER_PRIVILEGE,
                    PrivilegeModel::RESOURCE_TYPE_AI_MAGIC,
                    PrivilegeModel::PRIVILEGE_TYPE_UNLOCK,
                    $member->aff
                );
            }
            jobs([MagicModel::class, 'defend_ct'], [$material_id, $need_coins]);
        });
    }

    protected function check_type($file)
    {
        $uri = TB_IMG_ADM_US . $file;
        $data = getimagesize($uri);
        test_assert($data, '仅支持JPEG|JPG|PNG图片格式,其他格式请自行转码');
        test_assert(in_array($data['mime'] ?? '', ['image/jpeg', 'image/jpg', 'image/png']), '仅支持JPEG|JPG|PNG图片格式,其他格式请自行转码');
    }

    public function list_my_generate_video(MemberModel $member, $status, $page, $limit): Collection
    {
        return MemberMagicModel::list_my_generate_video($member->aff, $status, $page, $limit);
    }

    public function del_generate_video(MemberModel $member, $ids)
    {
        $ids = explode(",", $ids);
        $ids = array_unique($ids);
        $ids = array_filter($ids);
        test_assert(count($ids), '请选择需要删除的记录');
        MemberMagicModel::del_generate_video($member->aff, $ids);
    }

    public static function start_task()
    {
        MemberMagicModel::where('status', MemberMagicModel::STATUS_WAIT)
            ->where('re_ct', '<', 3)
            ->chunkById(100, function ($items) {
                collect($items)->map(function (MemberMagicModel $item) {
                    try {
                        $header = [
                            'apikey:' . config('ai_magic.key'),
                            'Content-Type:application/x-www-form-urlencoded'
                        ];
                        $thumb = TB_IMG_ADM_US . '/' . ltrim(parse_url($item->thumb, PHP_URL_PATH), '/');
                        $bid = strtoupper(sprintf('%s_%s_%s', SYSTEM_ID, 'magic', $item->id));
                        $data = [
                            'source_path' => $thumb,
                            'scene_name' => $item->magic_param,
                            'bid' => $bid,
                            'notify_url' => NOTIFY_BACK_URL . '/index.php?m=ai&a=on_magic',
                            'app_id' => SYSTEM_ID
                        ];
                        wf('调用参数', $data, false, self::LOG_PATH);
                        $url = config('ai_magic.url') . self::MAGIC_URI;
                        wf('请求地址', $url, false, self::LOG_PATH);
                        $rs = (new HttpCurl())->post($url, $data, $header, 60);
                        wf('返回数据', $rs, false, self::LOG_PATH);
                        test_assert($rs, '调用远程出现异常-001');
                        $rs = json_decode($rs, true);

                        test_assert($rs, '调用远程出现异常-002');
                        test_assert(!isset($rs['request_id']), '调用远程出现异常-003');
                        $item->task_id = $rs['task_id'];
                        $item->status = MemberMagicModel::STATUS_DOING;
                        $item->re_ct = 0;
                        $is_ok = $item->save();
                        test_assert($is_ok, '出现异常');
                        wf('执行成功', $item->id, false, self::LOG_PATH);
                    } catch (Throwable $e) {
                        wf('出现异常了', $e->getMessage(), false, self::LOG_PATH);
                        $item->re_ct += 1;
                        $item->reason = $e->getMessage();
                        $is_ok = $item->save();
                        test_assert($is_ok, '出现异常');
                    }
                });
            });
        sleep(5);
    }

    public static function slice_mp4($aff, $id, $media_url): bool
    {
        $data = [
            'uuid' => $aff,
            'm_id' => $id,
            'needImg' => 1,
            'needMp3' => 0,
            'playUrl' => $media_url
        ];
        wf('发起切片请求:', $data, false, self::LOG_PATH);
        return MemberMagicModel::approvedMv($data);
    }

    public static function on_magic($data)
    {
        /**
         * @var $model MemberMagicModel
         */
        $model = MemberMagicModel::where('status', MemberMagicModel::STATUS_DOING)
            ->where('task_id', $data['task_id'])
            ->first();

        if ($data['status'] != 2) {
            $model->status = MemberMagicModel::STATUS_FAIL;
            $model->re_ct = 0;
            $model->reason = $data['error'] ?? '';
            $is_ok = $model->save();
            test_assert($is_ok, '维护数据出现异常');
            return;
        }

        if (!count($data['out_data']) || !$data['out_data'][0]) {
            $model->status = MemberMagicModel::STATUS_FAIL;
            $model->re_ct = 0;
            $model->reason = $data['error'] ?? '';
            $is_ok = $model->save();
            test_assert($is_ok, '维护数据出现异常');
            return;
        }

        // 开始切片
        $media_url = $data['out_data'][0];
        $rs = self::slice_mp4($model->aff, $model->id, $media_url);
        test_assert($rs, '切片请求异常');

        $model->status = MemberMagicModel::STATUS_SLICE;
        $model->remote_video = $media_url;
        $model->re_ct = 0;
        $is_ok = $model->save();
        test_assert($is_ok, '维护数据出现异常');
    }

    // AI魔法视频资源回调
    public static function magic_slice()
    {
        try {
            trigger_log('AI魔法视频切片回调' . json_encode($_POST));
            $data = jaddslashes($_POST);
            unset($data['mod']);
            unset($data['code']);
            $rs = LibCrypt::check_sign($data, '132f1537f85scxpcm59f7e318b9epa51');
            wf('视频回调', $data);
            test_assert($rs, '验签失败');
            transaction(function () use ($data) {
                /**
                 * @var $record MemberMagicModel
                 */
                $record = MemberMagicModel::where('id', $data['mv_id'])
                    ->where('status', MemberMagicModel::STATUS_SLICE)
                    ->first();
                test_assert($record, '视频记录不存在');

                $record->video = $data['source'];
                $record->cover = $data['cover_thumb'];
                $record->cover_width = $data['thumb_width'];
                $record->cover_height = $data['thumb_height'];
                $record->duration = $data['duration'];
                $record->status = MemberMagicModel::STATUS_SUCCESS;
                $isOk = $record->save();
                test_assert($isOk, '更新视频记录失败');
            });
            exit('success');
        } catch (Throwable $e) {
            trigger_error($e->getMessage());
            exit('fail');
        }
    }
}