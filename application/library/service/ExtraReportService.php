<?php

namespace service;

use tools\HttpCurl;
use Throwable;

class ExtraReportService
{
    protected static function decrypt($token)
    {
        return openssl_decrypt($token, config('extra.report.method'), config('extra.report.key'), 0, config('extra.report.iv'));
    }

    protected static function encrypt($enc_data)
    {
        return openssl_encrypt($enc_data, config('extra.report.method'), config('extra.report.key'), 0, config('extra.report.iv'));
    }

    protected static function make_sign($array, $signKey): string
    {
        if (empty($array)) {
            return '';
        }
        ksort($array);
        $arr_temp = array();
        foreach ($array as $key => $val) {
            if ($key == 'data') {
                $valTemp = str_replace(' ', '+', $val);
                $arr_temp[] = $key . '=' . $valTemp;
            } else {
                $arr_temp[] = $key . '=' . $val;
            }
        }
        $string = implode('&', $arr_temp);
        $string = $string . $signKey;
        $sign_str = md5(hash('sha256', $string));
        return $sign_str;
    }

    /**
     * @param $app_id string pay.app_name
     * @param $date string 日期2025-01-01
     * @param $ai_draw string AI绘图金额(人民币)
     * @param $ai_draw_ct int AI绘图次数
     * @param $ai_image_to_video string 图生视频/AI魔法金额(人民币)
     * @param $ai_image_to_video_ct int 图生视频/AI魔法次数
     * @param $ai_strip string 脱衣金额(人民币)
     * @param $ai_strip_ct int 脱衣次数
     * @param $ai_novel string 小说金额(人民币)
     * @param $ai_novel_ct int 小说次数
     * @param $ai_image_change_face string 图片换脸金额(人民币)
     * @param $ai_image_change_face_ct int 图片换脸次数
     * @param $ai_video_change_face string 视频换脸金额(人民币)
     * @param $ai_video_change_face_ct int 视频换脸次数
     * @param $ai_girlfriend string 女友金额(人民币)
     * @param $ai_girlfriend_ct int 女友次数
     * @param $ai_voice string 语音金额(人民币)
     * @param $ai_voice_ct int 语音次数
     * @return void
     */
    public static function ai_report(
        $app_id,
        $date,
        $ai_draw = '0.00',
        $ai_draw_ct = 0,
        $ai_image_to_video = '0.00',
        $ai_image_to_video_ct = 0,
        $ai_strip = '0.00',
        $ai_strip_ct = 0,
        $ai_novel = '0.00',
        $ai_novel_ct = 0,
        $ai_image_change_face = '0.00',
        $ai_image_change_face_ct = 0,
        $ai_video_change_face = '0.00',
        $ai_video_change_face_ct = 0,
        $ai_girlfriend = '0.00',
        $ai_girlfriend_ct = 0,
        $ai_voice = '0.00',
        $ai_voice_ct = 0
    )
    {
        try {
            $data = [
                'app_id'                  => $app_id,
                'date'                    => $date,
                'ai_draw'                 => $ai_draw,
                'ai_draw_ct'              => $ai_draw_ct,
                'ai_image_to_video'       => $ai_image_to_video,
                'ai_image_to_video_ct'    => $ai_image_to_video_ct,
                'ai_strip'                => $ai_strip,
                'ai_strip_ct'             => $ai_strip_ct,
                'ai_novel'                => $ai_novel,
                'ai_novel_ct'             => $ai_novel_ct,
                'ai_image_change_face'    => $ai_image_change_face,
                'ai_image_change_face_ct' => $ai_image_change_face_ct,
                'ai_video_change_face'    => $ai_video_change_face,
                'ai_video_change_face_ct' => $ai_video_change_face_ct,
                'ai_girlfriend'           => $ai_girlfriend,
                'ai_girlfriend_ct'        => $ai_girlfriend_ct,
                'ai_voice'                => $ai_voice,
                'ai_voice_ct'             => $ai_voice_ct,
            ];
            $data = [
                'time' => time(),
                'data' => self::encrypt(json_encode($data)),
            ];
            $data['sign'] = self::make_sign($data, config('extra.report.sign'));
            $rs = (new HttpCurl())->post(config('extra.report.ai.url'), $data);
            test_assert($rs, '上报异常-001');
            $rs = json_decode($rs, true);
            pf('解密', self::decrypt($rs['data']));
            test_assert(!($rs['errcode'] ?? 1), '上报异常-002');
        } catch (Throwable $e) {
            wf('出现异常', $e->getMessage());
        }
    }

    /**
     * @param $app_id int 在后台开账户
     * @param $date string 日期2025-01-01
     * @param $charge_and int 安卓充值 单位元
     * @param $charge_ios int ios充值 单位元
     * @param $charge_pwa int pwa充值 单位元
     * @param $charge_self int 自充值 单位元
     * @param $charge_channel int 渠道充值
     * @param $charge_total int 总充值
     * @param $charge_ct int 总充值次数
     * @param $reg_and int 安卓注册
     * @param $reg_ios int ios注册
     * @param $reg_pwa int pwa注册
     * @param $reg_total int 总新增
     * @param $activity int 活跃
     * @param $user_ct string 到站用户数 进站的用户数量（无需活跃，进站就算）
     * @param $old_user_ct string 老用户到站 进站的老用户数量（过去曾经访问过的用户）
     * @param $new_user_ct string 新用户到站 进站的首次访问用户
     * @param $old_active_ct string 老用户日活 站内活跃的用户数量
     * @param $add_promotion_ct string 推广新增 来自自身推广渠道的首次访问用户数量
     * @param $add_nature_ct string 自然新增 来自自然流量的首次访问用户数量
     * @param $add_internal_ct string 内部导流新增 来自内部导流的首次访问用户数量
     * @param $add_charge string 新增充值 本日首次访问用户的充值总金额
     * @param $internal_charge string 内部导量新增充值 来自内部导流的首次访问用户充值总金额
     * @param $external_charge string 外部新增充值 来自外部（推广+自然）的首次访问用户充值总金额
     * @param $old_charge string 老用户充值 来自老用户的充值总金额
     * @param $charge_user_ct string 充值人数
     * @param $new_charge_user_ct string 新充人数 新用户充值人数
     * @param $internal_new_charge_ct string 内部导量新充人数 来自内部导流的首次访问用户充值人数
     * @param $external_new_charge_ct string 外部新充人数 来自外部（推广+自然）的首次访问用户充值人数
     * @param $old_charge_user_ct string 老充人数 来自老用户的充值人数
     * @param $charge_order_ct string 充值单数 总计充值单数
     * @param $new_charge_order_ct string 新充单数 新用户充值单数
     * @param $internal_charge_order_ct string 内部导量新充单数 来自内部导流的首次访问用户充值单数
     * @param $external_charge_order_ct string 外部新充单数 来自外部（推广+自然）的首次访问用户充值单数
     * @param $old_charge_order_ct string 老充单数 来自老用户的充值单数
     * @param $charge_success_rate string 订单付款成功率 用户充值订单的付款成功率
     * @param $retain_1 string 次留 1天前的新用户在今日回访的人数/100 (今日为2025-12-17，则1天前代表2025-12-16)
     * @param $retain_3 string 3留 3天前的新用户在今日回访的人数/100 (今日为2025-12-17，则3天前代表2025-12-14)
     * @param $retain_7 string 7留 7天前的新用户在今日回访的人数/100 (今日为2025-12-17，则7天前代表2025-12-10)
     * @param $visit_ct string 下载页访问数 下载页面的访问次数
     * @param $down_ct string 下载页点击数 下载页面的点击次数 (下载次数)
     * @param $internal_ct string 向其他内部产品导量 向其他内部产品传导的点击量
     * @param $external_ct string 向外部导量 向外部广告传导的点击量
     * @return void
     */
    public static function daily_report($app_id, $date, $charge_and, $charge_ios, $charge_pwa, $charge_self, $charge_channel, $charge_total, $charge_ct, $reg_and, $reg_ios, $reg_pwa, $reg_total, $activity,
        $user_ct, $old_user_ct, $new_user_ct, $old_active_ct, $add_promotion_ct, $add_nature_ct, $add_internal_ct, $add_charge, $internal_charge, $external_charge, $old_charge, $charge_user_ct, $new_charge_user_ct, $internal_new_charge_ct, $external_new_charge_ct, $old_charge_user_ct, $charge_order_ct, $new_charge_order_ct, $internal_charge_order_ct, $external_charge_order_ct, $old_charge_order_ct, $charge_success_rate, $retain_1, $retain_3, $retain_7, $visit_ct, $down_ct, $internal_ct, $external_ct
    ){
        try {
            $tmp = $data = [
                'app_id'         => $app_id,
                'date'           => $date,
                'charge_and'     => $charge_and,
                'charge_ios'     => $charge_ios,
                'charge_pwa'     => $charge_pwa,
                'charge_self'    => $charge_self,
                'charge_channel' => $charge_channel,
                'charge_total'   => $charge_total,
                'charge_ct'      => $charge_ct,
                'reg_and'        => $reg_and,
                'reg_ios'        => $reg_ios,
                'reg_pwa'        => $reg_pwa,
                'reg_total'      => $reg_total,
                'activity'       => $activity,
                // ====================================新增字段====================================
                'version'                  => 'v2',
                "user_ct"                  => $user_ct,
                "old_user_ct"              => $old_user_ct,
                "new_user_ct"              => $new_user_ct,
                "active_ct"                => (string)$activity,
                "old_active_ct"            => $old_active_ct,
                "add_ct"                   => (string)$reg_total,
                "add_promotion_ct"         => $add_promotion_ct,
                "add_nature_ct"            => $add_nature_ct,
                "add_internal_ct"          => $add_internal_ct,
                "charge_rmb"               => (string)$charge_total,
                "add_charge"               => $add_charge,
                "internal_charge"          => $internal_charge,
                "external_charge"          => $external_charge,
                "old_charge"               => $old_charge,
                "charge_user_ct"           => $charge_user_ct,
                "new_charge_user_ct"       => $new_charge_user_ct,
                "internal_new_charge_ct"   => $internal_new_charge_ct,
                "external_new_charge_ct"   => $external_new_charge_ct,
                "old_charge_user_ct"       => $old_charge_user_ct,
                "charge_order_ct"          => $charge_order_ct,
                "new_charge_order_ct"      => $new_charge_order_ct,
                "internal_charge_order_ct" => $internal_charge_order_ct,
                "external_charge_order_ct" => $external_charge_order_ct,
                "old_charge_order_ct"      => $old_charge_order_ct,
                "charge_success_rate"      => $charge_success_rate,
                "retain_1"                 => $retain_1,
                "retain_3"                 => $retain_3,
                "retain_7"                 => $retain_7,
                "visit_ct"                 => $visit_ct,
                "down_ct"                  => $down_ct,
                "internal_ct"              => $internal_ct,
                "external_ct"              => $external_ct,
            ];
            $data = [
                'time' => time(),
                'data' => self::encrypt(json_encode($data)),
            ];
            $data['sign'] = self::make_sign($data, config('extra.report.sign'));
            $rs = (new HttpCurl())->post(config('extra.report.daily.url'), $data);
            pf('参数', [config('extra.report.daily.url'), $tmp]);
            test_assert($rs, '上报异常-001');
            pf('响应', $rs);
            $rs = json_decode($rs, true);
            test_assert(!($rs['errcode'] ?? 1), '上报异常-002');
            pf('解密', self::decrypt($rs['data']));
            $rs = self::decrypt($rs['data']);
            $rs = json_decode($rs, true);
            test_assert($rs['status'] ?? 0, $rs['msg'] ?? '');
        } catch (Throwable $e) {
            pf('出现异常', $e->getMessage());
        }
    }
    /**
     * @param $app_id int 在后台开账户
     * @param $date string 日期2025-01-01
     * @param $today_active int 登录日活跃
     * @param $upmonth_today_active int 上月登录日活跃
     * @param $today_active_rate int 登录日活跃比
     * @param $charge_self int 充值金额
     * @param $upmonth_charge_self int 上月充值金额
     * @param $charge_self_rate int 充值金额环比
     * @param $channel_users int 导量安装
     * @param $upmonth_channel_users string 上月导量安装
     * @param $channel_users_rate string 导量安装环比
     * @param $charge_channel string 导量充值(ios渠道+android渠道)
     * @param $upmonth_charge_channel string 上月同一天导量充值(ios渠道+android渠道)
     * @param $charge_channel_rate string 导量充值环比
     * @return void
     */
    public static function robot_daily_report(
        $app_id,
        $date,
        $today_active,
        $upmonth_today_active,
        $charge_self,
        $upmonth_charge_self,
        $channel_users,
        $upmonth_channel_users,
        $charge_channel,
        $upmonth_charge_channel
    ) {

        try {
            $tmp = $data = [
                'date'                      => $date,
                'appName'                   => $app_id,
                'loginDailyActive'          => $today_active,
                'lastMonthLoginDailyActive' => $upmonth_today_active,
                //'loginDailyActiveRate'      => $today_active_rate,
                'rechargeAmount'            => $charge_self,
                "lastMonthRechargeAmount"   => $upmonth_charge_self,
                //'rechargeAmountRate'        => $charge_self_rate,
                'referralInstall'           => $channel_users,
                'lastMonthReferralInstall'  => $upmonth_channel_users,
                //'referralInstallRate'       => $channel_users_rate,
                'referralRecharge'          => $charge_channel,
                'lastMonthReferralRecharge' => $upmonth_charge_channel,
                //'referralRechargeRate'      => $charge_channel_rate,
                'type'                      => 1 //0-web 1-付费 2-免费

            ];

            $header[] = 'Content-Type: application/json';
            $url = 'http://54.180.124.199:9632/web/system/data';
            $rs = (new HttpCurl())->post($url, json_encode($data),$header);
            pf('参数', [$url, $tmp]);
            test_assert($rs, '上报异常-001');
            pf('响应', $rs);
//            $rs = json_decode($rs, true);
//            test_assert(!($rs['errcode'] ?? 1), '上报异常-002');
//            pf('解密', self::decrypt($rs['data']));
//            $rs = self::decrypt($rs['data']);
//            $rs = json_decode($rs, true);
//            test_assert($rs['status'] ?? 0, $rs['msg'] ?? '');
        } catch (Throwable $e) {
            pf('出现异常', $e->getMessage());
        }
    }

}

