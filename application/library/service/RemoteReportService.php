<?php

namespace service;

use AgentsUserModel;
use Carbon\Carbon;
use ProductModel;
use MemberModel;
use OrdersModel;

class RemoteReportService
{
    public function get_user_order_aggregate($start_time, $end_time, $time_zone, $currency): array
    {
        list($start_time, $end_time) = $this->get_start_end_times_in_time_zone($start_time, $end_time, $time_zone);

        $start_time = strtotime($start_time);
        $end_time = strtotime($end_time);

        $register_user_count = (int)MemberModel::select(['aff'])
            ->whereBetween("regdate", [$start_time, $end_time])
            ->count();

        $recharge_user_count = (int)OrdersModel::whereBetween("created_at", [$start_time, $end_time])
            ->select('uuid')
            ->distinct()
            ->count();

        $order_count = (int)OrdersModel::select(['id'])
            ->whereBetween("created_at", [$start_time, $end_time])
            ->count();

        $recharge_amount_cent = (int)OrdersModel::whereBetween("created_at", [$start_time, $end_time])
            ->where("status", OrdersModel::STATUS_SUCCESS)
            ->sum("pay_amount");


        return [
            'register_user_count'  => $register_user_count,
            'recharge_user_count'  => $recharge_user_count,
            'order_count'          => $order_count,
            'recharge_amount_cent' => $recharge_amount_cent,
        ];
    }

    public function get_user_list($start_time, $end_time, $time_zone, $page_size, $page): array
    {
        list($start_time, $end_time) = $this->get_start_end_times_in_time_zone($start_time, $end_time, $time_zone);

        $start_time = strtotime($start_time);
        $end_time = strtotime($end_time);

        $total = MemberModel::whereBetween("regdate", [$start_time, $end_time])->count();
        $total_pages = (int)ceil($total / $page_size);


        $ids = MemberModel::select(['aff'])
            ->whereBetween("regdate", [$start_time, $end_time])
            ->forPage($page, $page_size)
            ->pluck("aff")
            ->toArray();

        $list = MemberModel::select(['aff', 'invited_by', 'regdate', 'regip', 'trace_id'])
            ->whereIn("aff", $ids)
            ->get()
            ->map(function (MemberModel $item) use ($time_zone) {
                $reg_date = Carbon::parse(date('Y-m-d H:i:s', $item->regdate), 'UTC');
                $reg_date = $reg_date->copy()->setTimezone($time_zone)->timestamp;
                $channel = '';
                if ($item->invited_by){
                    $channel = AgentsUserModel::getUsernameByAff($item->invited_by);
                }
                return [
                    "uid"        => $item->aff,
                    "channel"    => $channel,
                    "event_time" => $reg_date,
                    "ip"         => $item->regip,
                    "trace_id"   => $item->trace_id
                ];
            });

        return [
            'page_size'   => $page_size,
            'page'        => $page,
            'total_pages' => $total_pages,
            'total_count' => $total,
            'items'       => $list
        ];
    }


    public function get_order_list($start_time, $end_time, $time_zone, $page_size, $page): array
    {
        list($start_time, $end_time) = $this->get_start_end_times_in_time_zone($start_time, $end_time, $time_zone);
        $start_time = strtotime($start_time);
        $end_time = strtotime($end_time);

        $total = OrdersModel::where("status", OrdersModel::STATUS_SUCCESS)
            ->whereBetween("created_at", [$start_time, $end_time])
            ->count();

        $total_pages = (int)ceil($total / $page_size);

        $ids = OrdersModel::select('id')
            ->where("status", OrdersModel::STATUS_SUCCESS)
            ->whereBetween("created_at", [$start_time, $end_time])
            ->orderBy("id")
            ->forPage($page, $page_size)
            ->pluck('id')
            ->toArray();

        $list = OrdersModel::with(['withMember'])
            ->whereIn("id", $ids)
            ->get()
            ->map(function (OrdersModel $item) use ($time_zone) {
                $created_at = Carbon::parse(date('Y-m-d H:i:s',$item->created_at), 'UTC');
                $created_at = $created_at->copy()->setTimezone($time_zone)->timestamp;

                $product = ProductModel::find($item->product_id);
                if (empty($product)){
                    return null;
                }

                $expired = 0;
                if ($item->order_type == ProductModel::TYPE_VIP) {
                    $expired = Carbon::now()->addDays($product->valid_date)->setTimezone($time_zone)->timestamp;
                }

                $coin_quantity = 0;
                if ($item->order_type == ProductModel::TYPE_DIAMOND) {
                    $coin_quantity = $product->coins + $product->free_coins;
                }

                $channel = '';
                $uid = $item->withMember->uid ?? 0;
                $invited_by = $item->withMember->invited_by ?? 0;
                if ($invited_by){
                    $channel = AgentsUserModel::getUsernameByAff($invited_by);
                }
                return [
                    "order_id"            => $item->order_id,
                    "order_type"          => $item->order_type == ProductModel::TYPE_VIP ? "vip_subscription" : "coin_purchase",
                    "product_id"          => $item->product_id,
                    "uid"                 => $uid,
                    "channel"             => $channel,
                    "amount"              => $item->amount,
                    "currency"            => "CNY",
                    "coin_quantity"       => $coin_quantity,
                    "vip_expiration_time" => $expired,
                    "event_time"          => $created_at,
                    "pay_type"            => $item->payway,
                    "pay_channel"         => $item->channel,
                    "transaction_id"      => $item->app_order
                ];
            });


        return [
            'page_size'   => $page_size,
            'page'        => $page,
            'total_pages' => $total_pages,
            'total_count' => $total,
            'items'       => $list
        ];
    }

    private function get_start_end_times_in_time_zone($start_time, $end_time, $time_zone): array
    {
        $start_time = Carbon::parse($start_time, $time_zone);
        $start_time = $start_time->copy()->setTimezone(date_default_timezone_get())->toDateTimeString();

        $end_time = Carbon::parse($end_time, $time_zone);
        $end_time = $end_time->copy()->setTimezone(date_default_timezone_get())->toDateTimeString();

        return [$start_time, $end_time];
    }
}
