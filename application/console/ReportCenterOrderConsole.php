<?php
namespace App\console;


use service\AppReportService;

class ReportCenterOrderConsole extends AbstractConsole
{

    /**
     * @var string 定义同步命令
     */
    public $name = 'report-center-order';
    /**
     * @var string 定义命令描述
     */
    public $description = '数据中心订单上报';

    /**
     * @var callable[]
     */
    private $works = [];

    /**
     *  php yaf report-center-order 2020-07-01 2020-07-01
     *
     * @param $argc
     * @param $argv
     *
     */
    public function process($argc, $argv)
    {

        echo "start daemonize report-center-order \r\n";

        $from = $argv[1] ?? '';
        $to = $argv[2] ?? '';
        if (empty($from)) {
            echo "eg:php yaf report-center-user 2020-07-01 2020-07-01 \r\n";die;
        }
        $from_date = $from . ' 00:00:00';
        $to_date = $from . ' 24:00:00';
        $from_time = strtotime($from_date);
        $to_time = strtotime($to_date);
        if ($to) {
            $to_date = $to . ' 24:00:00';
            $to_time = strtotime($to_date);
        }
        echo "query from: {$from_date} to: {$to_date} \r\n";
        $minRow = \OrdersModel::where('created_at', '>=', $from_time)->select(['id'])->first();
        $maxRow = \OrdersModel::where('created_at', '<=', $to_time)->orderByDesc('id')->select(['id'])->first();
        $min_id = is_null($minRow) ? 0 : $minRow->id;
        $max_id = is_null($maxRow) ? 0 : $maxRow->id;
        echo "order:id  from: {$min_id} to: {$max_id} \r\n";
        $tips = "date [{$from_date}  -  {$to_date}] id:[{$min_id}  -  {$max_id}]";
        if ($min_id <= 0 || $max_id <= 0 || $min_id > $max_id) {
            echo "no query Data over \r\n";
            die;
        }

        $app = new AppReportService();
        for ($i = $min_id; $i <= $max_id; $i++) {
            /** @var \OrdersModel $_order */
            $_order = \OrdersModel::where('id', $i)->first();
            if (is_null($_order)) {
                continue;
            }
            $flag = $app->addOrder([
                'order_id'   => $_order->order_id,//order_sn 全局唯一
                'uid'        => $_order->withMember->uid,
                'oauth_type' => $_order->oauth_type,
                'amount'     => $_order->amount / 100,//订单金额 （单位元）
                'product'    => $_order->order_type,
                'way'        => $_order->payway,
                'created_at' => $_order->created_at,
                'third_id'   => $_order->app_order,
                'pay_amount' => $_order->pay_amount / 100,//支付金额（单位元）
                'payed_at' => ($_order->status == \OrdersModel::STATUS_SUCCESS) ? $_order->updated_at : 0,
                'status'   => ($_order->status == \OrdersModel::STATUS_SUCCESS) ? 1 : 0,
            ]);
            echo "add queue addOrder {$_order->id} result:{$flag}  tips:{$tips}\r\n";
            usleep(1000);
        }


        echo "\r\n over \r\n";

    }


}