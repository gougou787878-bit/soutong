<?php

namespace service;

use Carbon\Carbon;
use DailyStatModel;
use MemberFaceModel;
use OrdersModel;
use SysTotalModel;
use MemberModel;


class AiStatService
{
    public static function data($start1, $end1){
        $ai_draw_consumption = 0;
        $ai_draw_ct = 0;
        $ai_image_to_video_consumption = 0;
        $ai_image_to_video_ct = 0;
        $ai_strip_consumption = 0;
        $ai_strip_ct = 0;

        $ai_image_change_face_consumption = MemberFaceModel::query()
            ->whereBetween('created_at', [$start1, $end1])
            ->where('type', MemberFaceModel::TYPE_COINS)
            ->sum('coins');
        $ai_image_change_face_ct = MemberFaceModel::query()
            ->whereBetween('created_at', [$start1, $end1])
            ->count();

        $ai_video_change_face_consumption = 0;
        $ai_video_change_face_ct = 0;

        $ai_novel = 0;
        $ai_novel_ct = 0;

        $ai_voice = 0;
        $ai_voice_ct = 0;

        $ai_girlfriend_consumption = 0;
        $ai_girlfriend_ct = 0;

        return [
            'ai_draw'                 => self::coins_to_rmb_format($ai_draw_consumption),
            'ai_draw_ct'              => $ai_draw_ct,
            'ai_image_to_video'       => self::coins_to_rmb_format($ai_image_to_video_consumption),
            'ai_image_to_video_ct'    => $ai_image_to_video_ct,
            'ai_strip'                => self::coins_to_rmb_format($ai_strip_consumption),
            'ai_strip_ct'             => $ai_strip_ct,
            'ai_image_change_face'    => self::coins_to_rmb_format($ai_image_change_face_consumption),
            'ai_image_change_face_ct' => $ai_image_change_face_ct,
            'ai_video_change_face'    => self::coins_to_rmb_format($ai_video_change_face_consumption),
            'ai_video_change_face_ct' => $ai_video_change_face_ct,
            'ai_novel'                => self::coins_to_rmb_format($ai_novel),
            'ai_novel_ct'             => $ai_novel_ct,
            'ai_voice'                => self::coins_to_rmb_format($ai_voice),
            'ai_voice_ct'             => $ai_voice_ct,
            'ai_girlfriend'           => self::coins_to_rmb_format($ai_girlfriend_consumption),
            'ai_girlfriend_ct'        => $ai_girlfriend_ct,
        ];
    }

    private static function coins_to_rmb_format($coins): string
    {
        return number_format($coins / 10, 2, '.', '');
    }

    public static function dailyData($date){
        $start_day = "{$date} 00:00:00";
        $end_day = "{$date} 23:59:59";
        //充值
        $today_charge_data = OrdersModel::query()
            ->where('updated_at', '>=', strtotime($start_day))
            ->where('updated_at', '<=', strtotime($end_day))
            ->where('status', OrdersModel::STATUS_SUCCESS);
        //总充值
        $today_charge_model = clone $today_charge_data;
        $charge_total = $today_charge_model->sum('pay_amount');

        //今日总成功订单数
        $today_charge_number_success = clone $today_charge_data;
        $charge_ct = $today_charge_number_success->count('id');

        //今日Android总成功订单额
        $today_charge_and = clone $today_charge_data;
        $charge_and = $today_charge_and->where('oauth_type', '=', 'android')->sum('pay_amount');
        $charge_pwa = $charge_total - $charge_and;

        //今日渠道总成功订单额
        $today_charge_channel = clone $today_charge_data;
        $charge_channel = $today_charge_channel->where('build_id', '!=', '')->sum('pay_amount');
        //官网用户充值金额
        $charge_self = $charge_total - $charge_channel;

        //安卓注册
        $reg_and = SysTotalModel::getValueBy('member:create:and', $date);
        //总注册
        $reg_total = SysTotalModel::getValueBy('member:create', $date);
        $reg_pwa = $reg_total - $reg_and;
        //总活跃
        $activity = SysTotalModel::getValueBy('member:active',$date);

        //新增
        $old_active_ct = $activity - $reg_total;

        $start = strtotime($date . ' 00:00:00');
        $end = strtotime($date . ' 23:59:59');
        $add_charge = MemberModel::Join('orders', 'orders.uuid', '=', 'members.uuid')
            ->whereBetween('members.regdate', [$start, $end])
            ->whereBetween('orders.updated_at', [$start, $end])
            ->where('status', OrdersModel::STATUS_SUCCESS)
            ->sum('orders.pay_amount');

        $uuids = OrdersModel::where('status', OrdersModel::STATUS_SUCCESS)
            ->whereBetween('updated_at', [$start, $end])
            ->get()
            ->pluck('uuid', 'uuid');

        $charge_user_ct = OrdersModel::where('status', OrdersModel::STATUS_SUCCESS)
            ->whereBetween('updated_at', [$start, $end])
            ->get()
            ->pluck('uuid', 'uuid')
            ->count();

        $old_charge_user_ct = OrdersModel::where('updated_at', '<', $start)
            ->whereIn('uuid', $uuids)
            ->where('status', OrdersModel::STATUS_SUCCESS)
            ->pluck('uuid', 'uuid')
            ->count();

        $old_user_uuids = OrdersModel::where('updated_at', '<', $start)
            ->whereIn('uuid', $uuids)
            ->where('status', OrdersModel::STATUS_SUCCESS)
            ->pluck('uuid', 'uuid')
            ->toArray();

        $old_charge_order_ct = OrdersModel::where('status', OrdersModel::STATUS_SUCCESS)
            ->whereBetween('updated_at', [$start, $end])
            ->whereIn('uuid', $old_user_uuids)
            ->count();

        $allOrder = OrdersModel::whereBetween('updated_at', [$start, $end])->count();
        $dayOrder = OrdersModel::where('status', OrdersModel::STATUS_SUCCESS)->whereBetween('updated_at', [$start, $end])->count();
        $charge_success_rate = $allOrder ? (int)(($dayOrder / $allOrder) * 100000) : 0;

        $keep1 = (int)SysTotalModel::getValueBy('keep:1day', $date);
        $keep3 = (int)SysTotalModel::getValueBy('keep:3day', $date);
        $keep7 = (int)SysTotalModel::getValueBy('keep:7day', $date);

        $down_and_ct = SysTotalModel::getValueBy('and:download', $date);
        $down_ios_ct = SysTotalModel::getValueBy('ios:download', $date);
        $down_pwa_ct = SysTotalModel::getValueBy('pwa:download', $date);
        $down_ct = $down_and_ct + $down_ios_ct + $down_pwa_ct;

        $visit_ct = SysTotalModel::getValueBy('welcome', $date);


        return [
            'reg_and'       => $reg_and,
            'reg_ios'       => 0,
            'reg_pwa'       => $reg_pwa,
            'reg_total'     => $reg_total,
            'charge_and'    => intval($charge_and / 100),
            'charge_ios'    => 0,
            'charge_pwa'    => intval($charge_pwa / 100),
            'charge_self'   => intval($charge_self / 100),
            'charge_channel'=> intval($charge_channel / 100),
            'charge_total'  => intval($charge_total / 100),
            'charge_ct'     => $charge_ct,
            'activity'      => $activity,
            // 新增数据
            "user_ct"                  => '',
            "old_user_ct"              => '',
            "new_user_ct"              => '',
            "old_active_ct"            => (string)$old_active_ct,
            "add_promotion_ct"         => '',
            "add_nature_ct"            => '',
            "add_internal_ct"          => '',
            "add_charge"               => (string)intval($add_charge / 100),
            "internal_charge"          => '',
            "external_charge"          => '',
            "old_charge"               => (string)intval(($charge_total - $add_charge) / 100),
            "new_charge_user_ct"       => (string)($charge_user_ct - $old_charge_user_ct),
            "internal_new_charge_ct"   => '',
            "external_new_charge_ct"   => '',
            "charge_user_ct"           => (string)$charge_user_ct,
            "old_charge_user_ct"       => (string)$old_charge_user_ct,
            "charge_order_ct"          => (string)$charge_ct,
            "new_charge_order_ct"      => (string)($charge_ct - $old_charge_order_ct),
            "internal_charge_order_ct" => '',
            "external_charge_order_ct" => '',
            "old_charge_order_ct"      => (string)$old_charge_order_ct,
            "charge_success_rate"      => (string)$charge_success_rate,
            "retain_1"                 => (string)$keep1,
            "retain_3"                 => (string)$keep3,
            "retain_7"                 => (string)$keep7,
            "visit_ct"                 => (string)$visit_ct,
            "down_ct"                  => (string)$down_ct,
            "internal_ct"              => '',
            "external_ct"              => '',
        ];
    }

    public static function dailyDataHistory($date){
        $start_day = "{$date} 00:00:00";
        $end_day = "{$date} 23:59:59";
        //充值
        $charge_number = OrdersModel::query()
            ->where('updated_at', '>=', strtotime($start_day))
            ->where('updated_at', '<=', strtotime($end_day))
            ->where('status', OrdersModel::STATUS_SUCCESS)
            ->count('id');

        /** @var DailyStatModel $data */
        $data = DailyStatModel::query()->where('day', $date)->first();
        if (!empty($data)){
            return [
                'reg_and'       => $data->and_user,
                'reg_ios'       => 0,
                'reg_pwa'       => $data->user - $data->and_user,
                'reg_total'     => $data->user,
                'charge_and'    => intval($data->and_charge),
                'charge_ios'    => 0,
                'charge_pwa'    => intval($data->charge - $data->and_charge),
                'charge_self'   => intval($data->charge - $data->build_charge),
                'charge_channel'=> intval($data->build_charge),
                'charge_total'  => intval($data->charge),
                'charge_ct'     => $charge_number,
                'activity'      => $data->activity,
            ];
        }
        return [];
    }

    public static function dailyDataReportRobot($date)
    {
        $start_day = "{$date} 00:00:00";
        $end_day = "{$date} 23:59:59";
        //充值
        $today_charge_data = OrdersModel::query()
            ->where('updated_at', '>=', strtotime($start_day))
            ->where('updated_at', '<=', strtotime($end_day))
            ->where('status', OrdersModel::STATUS_SUCCESS);

        //上个月充值
        $upmonth_charge_data = OrdersModel::query()
            ->whereBetween('updated_at',
                [
                    Carbon::parse($start_day)->subDays(30)->timestamp,
                    Carbon::parse($end_day)->subDays(30)->timestamp
                ])
            ->where('status', OrdersModel::STATUS_SUCCESS);

        //总充值
        $today_charge_model = clone $today_charge_data;
        $charge_total = $today_charge_model->sum('pay_amount');

        //上月总充值
        $upmonth_today_charge_model = clone $upmonth_charge_data;
        $upmonth_charge_total = $upmonth_today_charge_model->sum('pay_amount');

        //渠道总充值金币
        $today_charge_channel = clone $today_charge_data;
        $charge_channel = $today_charge_channel->where('build_id', '!=', '')->sum('pay_amount');

        //上月渠道总充值金币
        $upmonth_today_charge_channel = clone $upmonth_charge_data;
        $upmonth_charge_channel = $upmonth_today_charge_channel->where('build_id', '!=', '')->sum('pay_amount');

        //官网用户充值金额
        $charge_self = $charge_total - $charge_channel;
        //上月官网用户充值金额
        $upmonth_charge_self = $upmonth_charge_total - $upmonth_charge_channel;

        //官网用户充值环比
//        if ($upmonth_charge_self == 0) {
//            $charge_self_rate = $charge_self > 0 ? '100' : '0';
//        }else{
//            $charge_self_rate = round(($charge_self - $upmonth_charge_self) / $upmonth_charge_self * 100);
//        }

        //渠道用户充值环比
//        if ($upmonth_charge_channel == 0) {
//            $charge_channel_rate = $charge_channel > 0 ? '100' : '0';
//        }else{
//            $charge_channel_rate = round(($charge_channel - $upmonth_charge_channel) / $upmonth_charge_channel * 100);
//        }

        //日活跃用户
        $today_active = SysTotalModel::getValueBy('member:active', $date);
        $upmonth_today_active = SysTotalModel::getValueBy('member:active',
            Carbon::parse($date)->subDays(30)->toDateString());
        //日活用户环比
//        if ($upmonth_today_active == 0) {
//            $today_active_rate = $today_active > 0 ? '100' : '0';
//        }else{
//            $today_active_rate = round(($today_active - $upmonth_today_active) / $upmonth_today_active * 100);
//        }

        //渠道安装数
        $channel_users = SysTotalModel::getValueBy('member:create:invite', $date);
        $upmonth_channel_users = SysTotalModel::getValueBy('member:create:invite',
            Carbon::parse($date)->subDays(30)->toDateString());
        //渠道环比
//        if ($upmonth_channel_users == 0) {
//            $channel_users_rate = $channel_users > 0 ? '100' : '0';
//        }else{
//            $channel_users_rate = round(($channel_users - $upmonth_channel_users) / $upmonth_channel_users * 100);
//        }

        return [
            'today_active'           => $today_active,//登录日活跃
            'upmonth_today_active'   => $upmonth_today_active,//上月登录日活跃
            //'today_active_rate'      => $today_active_rate,//登录日活跃比
            'charge_self'            => $charge_self/100,//充值金额
            'upmonth_charge_self'    => $upmonth_charge_self/100,//上月充值金额
            //'charge_self_rate'       => $charge_self_rate,//充值金额环比
            'channel_users'          => $channel_users,//导量安装
            'upmonth_channel_users'  => $upmonth_channel_users,//上月导量安装
            //'channel_users_rate'     => $channel_users_rate,//导量安装环比
            'charge_channel'         => $charge_channel/100,//导量充值(ios渠道+android渠道)
            'upmonth_charge_channel' => $upmonth_charge_channel/100,//上月同一天导量充值(ios渠道+android渠道)
            //'charge_channel_rate'    => $charge_channel_rate,//导量充值环比

        ];
    }

}

