<?php

namespace repositories;


use Carbon\Traits\Timestamp;
use DB;
use tools\RedisService;
use Yaf\Exception;

trait ActivityRepository
{
    /**
/*
* 通过uid获取用户信息
* @param $uid
* @return bool
*/
    function getTotalData($egg_type)
    {

        $defaultdata['avatar'] = $this->config->img->default_jd_thumb;
        $defaultdata['user_nicename'] = '虚位以待';
        $defaultdata['coin'] = 0;

        $start_time = strtotime(date("Y-m-d", time()) . " 0:0:0");
        $end_time = strtotime(date("Y-m-d", time()) . " 24:00:00");

        $uid = RedisService::redis()->zRevRange("active_join_num_coins_".date("Y-m-d").$egg_type,0,1);

        $data = \MemberModel::query()
            ->select(
                'uid',
                'nickname',
                'thumb'
            )
            ->whereIn('uid',$uid)
            ->first();
        $data && $data->toArray();
        if (!empty($data)) {
            $data['avatar'] = $this->fetchUserThumb($data['thumb']);
            $data['user_nicename'] = strlen($data['nickname'])>12 ? '***'.substr($data['nickname'],-6) : $data['nickname'];
            $data['coin'] = RedisService::redis()->zScore("active_join_num_coins_".date("Y-m-d").$egg_type,$data['uid']);
        }
        return $data ? $data : $defaultdata;
    }

    function get_rand($proArr)
    {
        $result = array();
        foreach ($proArr as $key => $val) {
            $arr[$key] = $val['v'];
        }
        // 概率数组的总概率
        $proSum = array_sum($arr);
        asort($arr);
        // 概率数组循环
        foreach ($arr as $k => $v) {
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $v) {
                $result = $proArr[$k];
                break;
            } else {
                $proSum -= $v;
            }
        }
        return $result;
    }

    function get_award($type){
        switch ($type){
            case 1:
                return [
                    ['id' => 1, 'coin' => 10000, 'v' => 1, 'prize_id' => 1],
                    ['id' => 2, 'coin' => 5000, 'v' => 10, 'prize_id' => 2],
                    ['id' => 3, 'coin' => 2000, 'v' => 50, 'prize_id' => 3],
                    ['id' => 4, 'coin' => 1000, 'v' => 100, 'prize_id' => 4],
                    ['id' => 5, 'coin' => 500, 'v' => 3400, 'prize_id' => 5],
                    ['id' => 6, 'coin' => 300, 'v' => 3900, 'prize_id' => 6],
                    ['id' => 7, 'coin' => 0, 'v' => 2539, 'prize_id' => 7]
                ];
                break;
            case 2:
                return [
                    ['id' => 1, 'coin' => 5000, 'v' => 1, 'prize_id' => 8],
                    ['id' => 2, 'coin' => 1000, 'v' => 10, 'prize_id' => 9],
                    ['id' => 3, 'coin' => 500, 'v' => 50, 'prize_id' => 10],
                    ['id' => 4, 'coin' => 200, 'v' => 100, 'prize_id' => 11],
                    ['id' => 5, 'coin' => 100, 'v' => 3400, 'prize_id' => 12],
                    ['id' => 6, 'coin' => 50, 'v' => 3900, 'prize_id' => 13],
                    ['id' => 7, 'coin' => 0, 'v' => 2539, 'prize_id' => 14]
                ];
                break;
            case 3:
                return  [
                    ['id' => 1, 'coin' => 1000, 'v' => 1, 'prize_id' => 15],
                    ['id' => 2, 'coin' => 100, 'v' => 10, 'prize_id' => 16],
                    ['id' => 3, 'coin' => 50, 'v' => 50, 'prize_id' => 17],
                    ['id' => 4, 'coin' => 20, 'v' => 100, 'prize_id' => 18],
                    ['id' => 5, 'coin' => 10, 'v' => 3400, 'prize_id' => 19],
                    ['id' => 6, 'coin' => 5, 'v' => 3900, 'prize_id' => 20],
                    ['id' => 7, 'coin' => 0, 'v' => 2539, 'prize_id' => 21]
                ];
                break;
        }

    }

    //获得邀请奖励的礼物列表
    function getInviteGiftList(){
        //              金币数         坐骑id            坐骑有效时间                领取需求邀请人数
        return array(
            ['id'=>1 , 'coin'=> 8   , 'car_id' => 0 , 'car_time' => 0           , 'nums'=>1    ],
            ['id'=>2 , 'coin'=> 90  , 'car_id' => 0 , 'car_time' => 0           , 'nums'=>10   ],
            ['id'=>3 , 'coin'=> 300 , 'car_id' =>13 , 'car_time' => 604800      , 'nums'=>50   ],
            ['id'=>4 , 'coin'=> 660 , 'car_id' =>13 , 'car_time' => 1296000     , 'nums'=>100  ],
            ['id'=>5 , 'coin'=> 1150, 'car_id' =>13 , 'car_time' => 2592000     , 'nums'=>300  ],
            ['id'=>6 , 'coin'=> 2400, 'car_id' =>13 , 'car_time' => 7776000     , 'nums'=>800  ],
            ['id'=>7 , 'coin'=> 5000, 'car_id' =>13 , 'car_time' => 999999999   , 'nums'=>2000 ]
        );
    }

    // 邀请人数最多十个人  每半个小时刷新一次
    function getTopData($p){
        $top_data = RedisService::get(\UserInviteStatModel::USERS_INVITE_STAT_RANK."_{$p}");
        $limit = 10;
        $offset = $limit * ( $p-2 );
        if (!$top_data) {
            $uids = [];
            $top_list = \UserInviteStatModel::query()->orderby('nums', 'desc')->offset($offset)->limit($limit)->get()->toArray();
            foreach ($top_list as $k => $v)
                $uids[$v['uid']] = $v['nums'];

            if (!empty($uids)) {
                $top_data = \MemberModel::query()->select(['uid', 'uuid', 'nickname', 'thumb'])->whereIn('uid', array_keys($uids))->where('oauth_type','!=','channel')->get()->toArray();
                foreach ($top_data as $k => $v) {
                    $top_data[$k]['uid'] = $v['uid'];
                    $top_data[$k]['uuid'] = $v['uuid'];
                    $top_data[$k]['user_nicename'] = $v['nickname'];
                    $top_data[$k]['nums'] = $uids[$v['uid']];
                    $top_data[$k]['avatar_thumb'] = $this->fetchUserThumb($v['thumb']);
                }
            }

            if ($top_data) {
                $last_names = array_column($top_data, 'nums');
                array_multisort($last_names, SORT_DESC, $top_data);
                RedisService::set(\UserInviteStatModel::USERS_INVITE_STAT_RANK, $top_data, 7200);
            }
        }
        return $top_data ? $top_data : [];
    }

    //领取邀请奖励
    public function receive($uuid,$uid,$id)
    {
        $data = $this->getGift($id);
        if (!$data)
            return ['code'=>1001,'msg'=>'没有找到奖品'];

        $invitedNum = $this->getInviteInfoByUid($uid);
        if ($invitedNum->nums < $data['nums']) {
            return ['code'=>1002,'msg'=>'邀请人数不足'];
        }

        if (RedisService::redis()->get(\UserInviteReceiveLogModel::INVITE_TIME_LIMIT."_".$uid."_".$id)){
            return ['code'=>1007,'msg'=>'同一个奖品半个小时只允许一次'];
        }else{
            RedisService::redis()->set(\UserInviteReceiveLogModel::INVITE_TIME_LIMIT."_".$uid."_".$id,$uid,1800);
        }

        $is_recived = RedisService::redis()->sIsMember(\UserInviteReceiveLogModel::INVITE_LOG."_".$uid,$id);
        if ($is_recived){
            return ['code'=>1003,'msg'=>'你已经领取了'];
        }else{
            $log_data =  \UserInviteReceiveLogModel::query()->where(['uid' => $uid, 'invite_id' => $id])->first();
            if ($log_data)
                return ['code'=>1006,'msg'=>'你已经领取了'];
        }

         try{
            DB::beginTransaction();
             // 增加领取记录
             \UserInviteReceiveLogModel::query()->insert(['uid'=>$uid,'invite_id'=>$id,"created_at"=>TIMESTAMP]);

             // 增加用户金币
             if ($data['coin'] > 0){
                 \MemberModel::incrPk($uid, ['coins' => $data['coin']]);
             }

             if ($data['car_id'] > 0){
                 $endtime   =  time() + $data['car_time'];
                 $car_model = \UserCarModel::query();
                 $usercar=$car_model->where("uid",$uid)->where('carid',$data['car_id'])->first();
                 if ($usercar){
                     $usercar->endtime > time() && $endtime = $usercar->endtime+ $data['car_time'];
                     $data=array(
                         'endtime'=>$endtime,
                         'status'=>0
                     );
                     $car_model->where('id',$usercar->id)->update($data);
                 } else {
                     $data=array(
                         'uid'      =>  $uid,
                         'addtime'  =>  time(),
                         'endtime'  =>  $endtime,
                         'carid'    =>  $data['car_id'],
                         'status'   =>  0,
                     );
                     $car_model->insert($data);
                 }
             }
             RedisService::redis()->sAdd(\UserInviteReceiveLogModel::INVITE_LOG."_".$uid,$id);
             \DB::commit();
             return ['code'=>200,'msg'=>'领取成功'];
         } catch (\Throwable $exception) {
             \DB::rollBack();
             return ['code'=>1005,'msg'=>'领取失败'];
         }
    }

    public function getGift($id){
        $giftlist = $this->getInviteGiftList();
        foreach ($giftlist as $row){
            if ($row['id'] == $id)
                return $row;
        }
        return false;
    }

    //通过uid获得用户信息
    public function getInviteInfoByUid($uid){
        return \UserInviteStatModel::query()->where('uid',$uid)->first();
    }
}