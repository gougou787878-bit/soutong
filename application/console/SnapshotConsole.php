<?php


namespace App\console;


use DB;

class SnapshotConsole extends AbstractConsole
{

    public $name = 'snapshot-user';

    public $description = '快照用户';


    public function process($argc, $argv)
    {
        $this->importData();

        echo "#################  over ############## \r\n ";
    }

    public function importData()
    {

        /**
         * If you are updating database records while chunking results,
         * your chunk results could change in unexpected ways.
         * If you plan to update the retrieved records while chunking,
         * it is always best to use the chunkById method instead.
         * This method will automatically paginate the results based on the record's primary key
         */

        //充值时间3个月前，会员有效期大于30天的
        $expired_at = TIMESTAMP + 30 * 86400;
        $where = [
            //3个月前注册的用户
            ['regdate', '<=', strtotime("2024-08-05")],
            ['role_id', '=', 8],//普通用户
            ['expired_at', '>', $expired_at],//大于30天
        ];

        \MemberModel::where('vip_level', '>=',\MemberModel::VIP_LEVEL_JIKA)
            ->where($where)->chunkById(1000, function ($items) {
                collect($items)->each(function (\MemberModel $user) {
                    if (is_null($user) || $user->auth_status) {
                        //创作者不处理
                        return;
                    }
                    $hasSnapshot = \MemberSnapshotModel::where('uid', $user->uid)->where('created_at','>','2024-05-10')->exists();
                    if ($hasSnapshot) {
                        return;
                    }
                    /** @var \OrdersModel $order */
                    $order = \OrdersModel::where([
                        'uuid'       => $user->uuid,
                        'status'     => \OrdersModel::STATUS_SUCCESS,
                        'order_type' => \OrdersModel::TYPE_VIP
                    ])->orderByDesc('id')->first();
                    if (!is_null($order) && $order->created_at > strtotime('2024-08-05')) {
                        //3个月内 不处理
                        return;
                    }

                    /** @var \FreeMemberModel $hasFreeMember */
                    $hasFreeMember = \FreeMemberModel::where('uid', $user->uid)->first();
                    $content = $user->getAttributes();
                    $freeMember_content = null;
                    if ($hasFreeMember) {
                        $freeMember_content = $hasFreeMember->getAttributes();
                        //$content['expired_at'] = max($hasFreeMember->expired_at, $content['expired_at']);
                    }
                    $flag = \MemberModel::where('uid', $user->uid)->update(['vip_level' => 0, 'expired_at' => 0, 'build_id' => '']);
                    echo "{$user->uid} flag:{$flag}" . PHP_EOL;
                    if ($flag) {
                        if (!is_null($hasFreeMember)) {
                            $hasFreeMember->delete();
                        }
                        $arr = [
                            'user' => $content,
                            'free_member' => $freeMember_content,
                        ];
                        \MemberSnapshotModel::create(
                            [
                                'uid'        => $user->uid,
                                'data'       => json_encode($arr),
                                'status'     => 0,
                                'created_at' => date("Y-m-d H:i:s"),
                                'updated_at' => date("Y-m-d H:i:s")
                            ]
                        );
                    }
                });

                echo "======== chunk 1000 ========" . PHP_EOL . PHP_EOL;

            });

    }

}