<?php

namespace repositories;

use tools\RedisService;

trait ProxyRepository
{
    public function getProxyByAff($aff = 0){
        $proxy = \UserProxyModel::query()
            ->where('aff',$aff)
            ->first();
        if (!$proxy) {
            $proxy_data = [
                'root_aff' => $this->member['aff'],
                'aff' => $this->member['aff'],
                'proxy_level' => 1,
                'proxy_node' => $this->member['aff'],
                'created_at' => TIMESTAMP,
            ];
            \UserProxyModel::create($proxy_data);
        } else {
            $proxy_data = $proxy->toArray();
        }
        return !empty($proxy_data) ? $proxy_data : [];
    }

    public function getProxyLevel($proxy_data){

        $proxyLevel['level_1'] = $proxyLevel['level_2'] = $proxyLevel['level_3'] = $proxyLevel['level_4'] = 0;

        $level  = $proxy_data['proxy_level'] + 4;
        $proxy_node = $proxy_data['proxy_node'];

        $data = \UserProxyModel::query()
            ->where('proxy_level','<=',$level)
            ->where("proxy_node",'like',"{$proxy_node},%")
            ->limit(5000)
            ->get()
            ->toArray();
        foreach($data as $row){
            $lev = "level_".($row['proxy_level']-$proxy_data['proxy_level']);
            $proxyLevel[$lev]++;
        }
        return $proxyLevel;
    }

    // 总业绩
    public function getProxyDataByAff($aff){

        $result = \UserProxyCashBackModel::query()->where('aff',$aff)->first();
        return is_null($result)?[]:$result->toArray();

    }

    // 当月总业绩
    public function getMonthAmount($aff){
        $monthStart = strtotime(date('Y-m',TIMESTAMP));
        $month_amount = \UserProxyCashBackDetailModel::query()
            ->where('aff',$aff)
            ->where('created_at','>=',$monthStart)
            ->sum("amount");
        return $month_amount;
    }

    // 当月邀请总人数
    public function getMonthInvited($proxy_data){
        $level  = $proxy_data['proxy_level'] + 4;
        // $proxy_node  = intval($proxy_data['proxy_node']) + 4;
        $proxy_node  = $proxy_data['proxy_node'];
        $monthStart = strtotime(date('Y-m',TIMESTAMP));
        $month_amount = \UserProxyModel::query()
            ->where('created_at','>=',$monthStart)
            ->where('proxy_node','like',"{$proxy_node},%")
            ->where('proxy_level','<=',$level)
            ->count();
        return $month_amount;

    }

    private function _getDetailLists($aff, $offset=0, $limit=24)
    {
        if (!$aff && $offset>200){
            return [];
        }

        $key = 'proxy_detailt_' . $aff . '_list_' . $offset.'_'.$limit;
        $items = RedisService::get($key);
        if (!$items) {
            if(is_string($items)) {$items = unserialize($items);};
            $data = \UserProxyCashBackDetailModel::query()
                ->leftJoin('members', 'members.aff','=','user_proxy_cash_back_detail.from_aff')
                ->where('user_proxy_cash_back_detail.aff', $aff)
                ->orderBy('user_proxy_cash_back_detail.created_at', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get()
                ->toArray();
            if($data){
                foreach ($data as $k => $item) {
                    $value = [];
                    unset($items[$k]['from_aff']);
                    unset($items[$k]['aff']);
                    $value['id'] = $item['id'];
                    $value['created_at'] = date('Y-m-d', $item['created_at']);
                    $value['money'] = number_format(($item['amount'] / 100), 2, '.', '');
                    $value['nickname'] = $item['nickname'];
                    $value['uid'] = $item['uid'];
                    $items[] = $value;
                }
            }
            RedisService::set($key, $items, 3600);
        }
        $items = !$items ? [] : $items;
        return $items;
    }

    public function userWithdraw($withdraw_type, $withdraw_account, $withdraw_name, $withdraw_amount)
    {
        \DB::beginTransaction();
        try{
            $insert_data = [
                'uuid' => $this->member['uuid'],
                'type' => $withdraw_type,
                'account' => $withdraw_account,
                'name' => $withdraw_name,
                'amount' => $withdraw_amount,
                'trueto_amount' => $withdraw_amount * (100-\UserWithdrawModel::USER_WITHDRAW_PROXY_RATE)/100,
                'created_at' => TIMESTAMP,
                'updated_at' => TIMESTAMP,
                'withdraw_type' => 1,
                'withdraw_from' => 2,
                'ip'            => USER_IP,
                'address'       => \UserWithdrawModel::convertIPToAddress(USER_IP)
            ];

            \UserWithdrawModel::query()->insert($insert_data);

            $proxyCashBack = $this->getProxyDataByAff($this->member['aff']);

            $update_data = [
                'amount' => $proxyCashBack['amount'] + $withdraw_amount,
                'withdraw_times' => $proxyCashBack['withdraw_times'] + 1,
                'updated_at' => TIMESTAMP,
            ];

            \UserProxyCashBackModel::query()
                ->where('aff', $this->member['aff'])
                ->update($update_data);
            \DB::commit();
            return true;
        } catch (Exception $exception) {
            \DB::rollBack();
            return false;
        }
    }

    public function coinsDetail($type){
        $return_data = [];
        $usergolemodel = \UserGoldLogModel::query()->where("uuid",$this->member['uuid']);
        if($type == 1 || $type == 2 || $type == 0){
            $result = $usergolemodel->orderBy("created_at","desc")
                ->offset($this->offset)
                ->limit($this->limit)
                ->get()
                ->toArray();

            foreach ($result as $key=>$row) {
                $row['coins'] = $row['type'] == 1 ? '+' . $row['gold'] : '-' . $row['gold'];
                $row['created_at'] = date('Y-m-d H:i:s', $row['created_at']);
                unset($row['gold']);
                $return_data[] = $row;
            }
        }else{
        $result = $usergolemodel->select(\DB::raw('SUM(`gold`) as total_coins, mv_id'))
                ->where("type",1)
                ->where("mv_id",">",0)
                ->groupBy('mv_id')
                ->orderBy("total_coins","desc")
                ->offset($this->offset)
                ->limit($this->limit)
                ->get()
                ->toArray();
            foreach ($result as $row) {
               $mv = $this->getMvByID($row['mv_id']);
                if(empty($mv)){
                    continue;
                }
                $mv['cover_thumb'] = $this->fetchMvImage($mv);
                $mv['created_at'] = date('Y-m-d H:i:s', $mv['created_at']);
                $return_data[] = array_merge($row,$mv);
            }
        }
        return $return_data;
    }

    /**
     * @param $withdraw_type
     * @param $withdraw_account
     * @param $withdraw_name
     * @param $money
     * @return bool
     */
    public function insertWithdraw($withdraw_type,$withdraw_account,$withdraw_name,$withdraw_amount,$trueto_amount,$need)
    {
        $insert_data = [
            'uuid' => $this->member['uuid'],
            'type' => $withdraw_type,
            'account' => $withdraw_account,
            'name' => $withdraw_name,
            'amount' => $withdraw_amount,
            'trueto_amount' => $trueto_amount,
            'created_at' => TIMESTAMP,
            'updated_at' => TIMESTAMP,
            'coins' => $need,
            'withdraw_type' => 1,
            'withdraw_from' => 1,
            'ip'            => USER_IP,
            'address'       => \UserWithdrawModel::convertIPToAddress(USER_IP)
        ];

        \UserWithdrawModel::query()->insert($insert_data);
        //\UserWithdrawAnchorModel::query()->where('uid', $this->member['uid'])->update(['withdraw_votes' => \DB::raw("withdraw_votes - {$need}")]);
    }


}
