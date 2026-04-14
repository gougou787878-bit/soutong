<?php


namespace App\console;


use AdminLogModel;
use ConfigModel;
use MvModel;
use MvSubmitModel;

class ExemptCheckConsole extends AbstractConsole
{


    public $name = 'exempt-check';

    public $description = '免审视频';


    public function process($argc, $argv)
    {
        \MemberCreatorModel::chunkById(100, function ($items) {
            $levelAry = [
                \MemberModel::VIP_LEVEL_JIKA,
                \MemberModel::VIP_LEVEL_YEAR,
                \MemberModel::VIP_LEVEL_LONG,
                \MemberModel::VIP_LEVEL_BN,
            ];
            $items = collect($items);
            $data = \MemberModel::whereIn('uid', $items->pluck('uid'))
                ->whereIn('vip_level', $levelAry) //权限检查
                ->where('auth_status', \MemberModel::AUTH_STATUS_YES)
                ->pluck('uid');

            $creator = $items->keyBy('uid');
            foreach ($data as $uid){
                $item = $creator[$uid] ?? null;
                if (!$item){
                    continue;
                }
                $this->_handler($item);
            }
        }, 'id');

    }

    protected function processOld($argc, $argv)
    {
        $levelAry = [
            \MemberModel::VIP_LEVEL_JIKA,
            \MemberModel::VIP_LEVEL_YEAR,
            \MemberModel::VIP_LEVEL_LONG,
            \MemberModel::VIP_LEVEL_BN,
        ];
        $items = \MemberCreatorModel::from('member_creator as mc')
            ->leftJoin('members as m', 'mc.uid', 'm.uid')
            ->select(['mc.*'])
            ->whereIn('m.vip_level', $levelAry) //权限检查
            ->where('m.auth_status' , \MemberModel::AUTH_STATUS_YES)
            ->get();

        /** @var \MemberCreatorModel $item */
        foreach ($items as $item){
            $this->_handler($item);
        }
    }


    protected function _handler(\MemberCreatorModel $item)
    {
        $video_count = $item->mv_check - $item->mv_refuse;
        if (empty($item->mv_check)) {
            $refuse_rate = 1;
        }else{
            $refuse_rate = $item->mv_refuse / $item->mv_check;
        }
        $this->logSuccess("\$uid = $item->uid, mv_submit=$item->mv_submit, mv_refuse=$item->mv_refuse, mv_check=$item->mv_check, \$video_count=$video_count, \$refuse_rate = $refuse_rate");
        if ($video_count >=3 && $refuse_rate <= 0.4 ){
            \MvSubmitModel::where(['status' => \MvSubmitModel::STAT_UNREVIEWED])
                ->where('id', '>', 9392)//
                ->where('uid' , '=' , $item->uid)
                ->get()->map(function (\MvSubmitModel $item){
                    $this->logSuccess(sprintf('审核{%d}', $item->id));
                    $this->pass($item);
                });
        }
    }

    /**
     * @param MvModel|MvSubmitModel|object $model
     * @return bool|string
     * @author xiongba
     * @date 2020-03-03 19:53:48
     */
    protected function approvedMv($model)
    {
        $data = [
            'uuid'    => 'fasdfddfasdfdjfajkodfs09ds0r23089df',
            'm_id'    => $model->id,
            'needMp3' => $model->music_id == 0 ? 1 : 0,
            'needImg' => empty($model->cover_thumb) ? 1 : 0,
            'playUrl' => $model->m3u8,
        ];
        if ($model->cover_thumb == '/new/xiao/20201120/2020112018294744986.jpeg') {//test img-covery
            $data['needImg'] = 1;
        }
        $crypt = new \tools\CryptService();
        $sign = $crypt->make_sign($data);
        $data['sign'] = $sign;
        $data['notifyUrl'] = SYSTEM_NOTIFY_SLICE_URL;
        $curl = new \tools\CurlService();
        $return = $curl->request(config('mp4.accept'), $data);
        //errLog("reslice req:" . var_export([$data, $return], true));
        return $return;
    }

    public function pass($model)
    {
        $model->status = MvSubmitModel::STAT_CALLBACK_ING;
        AdminLogModel::addReviewMv('system', sprintf('审视频[%d]#%s', $model->id, $model->title));
        if ($model->save()) {
            $re = $this->approvedMv($model);
            if ($re == setting('approvedUserUpload', 'success')) {
                \ExemptCheckModel::create(['uid' => $model->uid, 'vid' => $model->id, 'created_at' => time(), 'is_check' => 0]);
                return true;
            } else {
                return false;
            }
        }
        return false;
    }

}