<?php


namespace App\console;


use service\AppCenterService;

class ReportDataChanOrderConsole extends AbstractConsole
{

    /**
     * @var string 定义同步命令
     */
    public $name = 'report-data-chan-order';
    /**
     * @var string 定义命令描述
     */
    public $description = '联盟数据中心订单上报';

    /**
     * @var callable[]
     */
    private $works = [];

    /**
     *  php yaf report-data-chan-order 2020-07-01 2020-07-01
     *  php yaf report-data-chan-order "2020-07-01 10:00:00" "2020-07-01 23:00:00" //一定要用双引号否则解析失败
     *
     * @param $argc
     * @param $argv
     *
     */
    public function process($argc, $argv)
    {
        $from = $argv[1] ?? '';
        $to = $argv[2] ?? '';
        if (empty($from)) {
            echo "\r\n
            eg:php yaf report-data-chan-order 2020-07-01 2020-07-01 \r\n
            or \r\n
            eg:php yaf report-data-chan-order \"2020-07-01 10:00:00\" \"2020-07-01 23:00:00\" \r\n
            ###############################################################\r\n
            ";die;
        }
        if(preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/',$from)){
            $from_date = $from;
        }else{
            $from_date = $from . ' 00:00:00';
        }
        if(preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/',$to)){
            $to_date = $to;
        }else{
            $to_date = $from . ' 24:00:00';
        }
        echo "start php yaf report-data-chan-order {$from_date} {$to_date} \r\n";
        $from_time = strtotime($from_date);
        $to_time = strtotime($to_date);
        if ($to) {
            $to_date = $to . ' 24:00:00';
            $to_time = strtotime($to_date);
        }
        echo "query from: {$from_date} to: {$to_date} \r\n";
        $wherMin = [
            ['created_at', '>=', $from_time],
            //['build_id', '=', 'xb0182'],
        ]; $wherMax = [
            ['created_at', '<=', $to_time],
        //['build_id', '=', 'xb0182'],
        ];
        $minRow = \OrdersModel::where($wherMin)->select(['id'])->first();
        $maxRow = \OrdersModel::where($wherMax)->orderByDesc('id')->select(['id'])->first();
        $min_id = is_null($minRow) ? 0 : $minRow->id;
        $max_id = is_null($maxRow) ? 0 : $maxRow->id;
        echo "order:id  from: {$min_id} to: {$max_id} \r\n";
        $tips = "date [{$from_date}  -  {$to_date}] id:[{$min_id}  -  {$max_id}]";
        if ($min_id <= 0 || $max_id <= 0 || $min_id > $max_id) {
            echo "no query Data over \r\n";
            die;
        }
        $app = new AppCenterService();
        for ($i = $min_id; $i <= $max_id; $i++) {
            /** @var \OrdersModel $_order */
            $_order = \OrdersModel::where('id', $i)->first();
            if (is_null($_order)) {
                continue;
            }
            if($_order->order_type == \OrdersModel::TYPE_VIP){
                continue;
            }
            $_type = (\OrdersModel::TYPE_VIP == $_order->order_type) ? 0 : 1;
            if(!$_order->build_id){
                echo "not chann order id:{$i}  tips:{$tips} ".PHP_EOL;
                continue;
            }
            $flag = $app->addOrder($_order->order_id
                , $_order->uuid
                , (string)$_order->amount/100
                , (string)$_order->oauth_type
                , $_type
                , $_order->build_id
                , '0',
                0,
                $_order->created_at
            );
                echo "add queue addOrder {$_order->id} channel {$_order->build_id} result:{$flag} tips:{$tips}\r\n";
            if($_order->status == \OrdersModel::STATUS_SUCCESS){
                $is_update = $app->updateOrder($_order->order_id,(string)$_order->pay_amount/100,1,$_order->updated_at,$_order);
                echo "add queue updateOrder {$_order->id} channel {$_order->build_id} result:{$is_update} tips:{$tips}\r\n";
            }
            usleep(800);
        }

        echo "\r\n over \r\n";
    }


}