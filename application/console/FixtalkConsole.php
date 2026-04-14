<?php


namespace App\console;


use DB;

class FixtalkConsole extends AbstractConsole
{

    public $name = 'fix-talk';

    public $description = '修复talk时间';


    public function process($argc, $argv)
    {

        \OrdersModel::where('status', \OrdersModel::STATUS_SUCCESS)
            ->whereIn('product_id', [71, 72, 73])
            ->where('created_at', '>', strtotime('2021-09-30 14:00:00'))
            ->get()
            ->each(function (\OrdersModel $item) {
                if ($item->order_type == \OrdersModel::TYPE_VIP) {
                    if ($item->product_id == 71) {
                        $day = 3;
                    } elseif ($item->product_id == 72) {
                        $day = 7;
                    } elseif ($item->product_id == 73) {
                        $day = 20;
                    }

                    $this->setTime($item->withMember, $day * 86400);


                }
            });


        echo "#################  over ############## \r\n ";
    }


    protected function setTime($member, $ttl)
    {
        $talk = \MemberTalkModel::where('uid', $member->uid)->first();
        if (empty($talk)) {
            $talk = \MemberTalkModel::createInit($member->uid, $member->uuid, time() + $ttl, false);
            if (empty($talk)) {
                return false;
            }
        } else {
            if ($talk->expired_at > 1682578501) {
                $expired_at = time() + $ttl;
            } else {
                $expired_at = max($talk->expired_at, time()) + $ttl;
            }
            $talk->expired_at = $expired_at;
            $itOk = $talk->save();
            if (empty($itOk)) {
                return false;
            }
        }
    }

}