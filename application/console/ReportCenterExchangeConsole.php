<?php

namespace App\console;


use service\AppReportService;

class ReportCenterExchangeConsole extends AbstractConsole
{

    /**
     * @var string 定义同步命令
     */
    public $name = 'report-center-exchange';
    /**
     * @var string 定义命令描述
     */
    public $description = '数据中心提现上报';

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

        echo "start daemonize report-center-exchange \r\n";

        $from = $argv[1] ?? '';
        $to = $argv[2] ?? '';
        if (empty($from)) {
            echo "eg:php yaf report-center-user 2020-07-01 2020-07-01 \r\n";
            die;
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
        $minRow = \UserWithdrawModel::where('created_at', '>=', $from_time)
            ->where('status', '=', \UserWithdrawModel::STATUS_POST)
            ->select(['id'])->first();
        $maxRow = \UserWithdrawModel::where('created_at', '<=', $to_time)
            ->where('status', '=', \UserWithdrawModel::STATUS_POST)
            ->orderByDesc('id')->select(['id'])->first();
        $min_id = is_null($minRow) ? 0 : $minRow->id;
        $max_id = is_null($maxRow) ? 0 : $maxRow->id;
        echo "withdraw:id  from: {$min_id} to: {$max_id} \r\n";
        $tips = "date [{$from_date}  -  {$to_date}] id:[{$min_id}  -  {$max_id}]";

        if ($min_id <= 0 || $max_id <= 0 || $min_id > $max_id) {
            echo "no query Data over \r\n";
            die;
        }
        $app = new AppReportService();
        for ($i = $min_id; $i <= $max_id; $i++) {
            /** @var \UserWithdrawModel $_model */
            $_model = \UserWithdrawModel::where('id', $i)->first();
            if (is_null($_model) || $_model->status != \UserWithdrawModel::STATUS_POST) {
                continue;
            }
            /** @var \MemberModel $user */
            $user = \MemberModel::where('uuid', $_model->uuid)->first();

            $flag = $app->exchangeReport([
                [
                    'order_id'    => $_model->cash_id,
                    'third_id'    => $_model->third_id,
                    'uid'         => $user->uid,
                    'oauth_type'  => $user->oauth_type,
                    'name'        => $_model->name,
                    'card_number' => $_model->account,
                    'amount'      => $_model->amount,
                    'pay_amount'  => $_model->trueto_amount,
                    'product'     => ($_model->withdraw_from == 2) ? 1 : 2,
                    'way'         => $_model->type == 1 ? 'bankcard' : 'alipay',
                    'created_at'  => $_model->created_at,
                    'payed_at'    => $_model->updated_at,
                    'status'      => 1,
                ]
            ]);
            echo "add queue exchangeReport {$_model->id} result:{$flag} tips:{$tips}\r\n";
            usleep(1000);
        }

        echo "\r\n over \r\n";
    }


}