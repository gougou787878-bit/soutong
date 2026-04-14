<?php


namespace App\console;

use service\AppCenterService;

class ReportDataChanUserConsole extends AbstractConsole
{

    /**
     * @var string 定义同步命令
     */
    public $name = 'report-data-chan-user';
    /**
     * @var string 定义命令描述
     */
    public $description = '联盟用户上报';

    /**
     * @var callable[]
     */
    private $works = [];

    /**
     *  php yaf report-data-chan-user 2020-07-01 2020-07-01
     *
     * @param $argc
     * @param $argv
     *
     */
    public function process($argc, $argv)
    {
        /*$minRow = \MemberModel::query()->orderByDesc('uid')->first();
        print_r($minRow->regdate);
        return ;*/

        echo "start daemonize report-data-chan-user \r\n";

        $from = $argv[1] ?? '';
        $to = $argv[2] ?? '';
        //1471179
        if (empty($from)) {
            echo "eg:php yaf report-data-chan-user 2020-07-01 2020-07-01 \r\n";
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

        $minRow = \MemberModel::where('regdate', '>=', $from_time)->select(['uid'])->first();
        $maxRow = \MemberModel::where('regdate', '<=', $to_time)->orderByDesc('uid')->select(['uid'])->first();
        $min_uid = is_null($minRow) ? 0 : $minRow->uid;
        $max_uid = is_null($maxRow) ? 0 : $maxRow->uid;
        echo "uid  from: {$min_uid} to: {$max_uid} \r\n";
        $tips = "date [{$from_date}  -  {$to_date}] id:[{$min_uid}  -  {$max_uid}]";
        if ($min_uid <= 0 || $max_uid <= 0 || $min_uid > $max_uid) {
            echo "no query Data over \r\n";
            die;
        }
        $app = new AppCenterService();
        for ($i = $min_uid; $i <= $max_uid; $i++) {
            /** @var \MemberModel $_member */
            $_member = \MemberModel::where('uid', $i)->first();
            if (is_null($_member)) {
                continue;
            }
            if (!$_member->build_id) {
                continue;
            }
            $flag = $app->addUser(
                $_member->uid,
                $_member->uuid,
                $_member->oauth_type,
                $_member->build_id,
                $_member->invited_by,
                $_member->regdate
            );
            echo "add queue {$_member->uid} result:{$flag} tips:{$tips}\r\n";
            usleep(1000);
        }

        echo "\r\n over \r\n";
    }


}