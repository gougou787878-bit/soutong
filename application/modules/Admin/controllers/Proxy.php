<?php

use http\Client\Curl\User;
use tools\HttpCurl;

class ProxyController extends AdminController
{
    use \repositories\UsersRepository,
        \repositories\ProxyRepository;

    public $withdraw = array(
        1 => "bankcard",
    );

    public $withdraw_from = UserWithdrawModel::DRAW_TYPE;

    public $status = array(
        0 => "待审核",
        1 => "审批打款",
        2 => "已完成",
        3 => "已解冻",
        4 => "提现失败",
        5 => "冻结中",
    );

    /**
     * 代理统计
     */
    public function indexAction()
    {
        $aff = $this->get['aff'] ?? '';
        $result = \UserProxyCashBackModel::query()
            ->when(intval($aff) > 0, function ($query) use ($aff) {
                $query->where('aff', $aff);
            })
            ->orderBy('id', 'desc')
            ->offset($this->pageStart)
            ->limit($this->perPageNum)
            ->get()
            ->toArray();

        $topic = [];
        foreach ($result as $key => $value) {
            $value['created_at'] = date('Y-m-d H:i:s', $value['created_at']);
            $value['updated_at'] = date('Y-m-d H:i:s', $value['updated_at']);
            $value['level_1'] = $value['level_1'] / 100;
            $value['level_2'] = $value['level_2'] / 100;
            $value['level_3'] = $value['level_3'] / 100;
            $value['level_4'] = $value['level_4'] / 100;
            $value['sum'] = $value['level_1'] + $value['level_2'] + $value['level_3'] + $value['level_4'];
            $topic[] = $value;
        }
        $query_link = "d.php?mod=proxy&code=index&aff={$aff}";
        $page_arr['html'] = sitePage($query_link);
        $this->getView()
            ->assign('topic', $topic)
            ->assign('aff', $aff)
            ->assign('page', $page_arr)
            ->display('proxy/index.phtml');
    }

    public function relationAction()
    {

        $aff = $this->get['aff'] ?? '';
        $root_aff = $this->get['root_aff'] ?? '';
        $query_link = "d.php?mod=proxy&code=relation&aff={$aff}&root_aff={$root_aff}";
        $result = \UserProxyModel::query()
            ->when(intval($aff) > 0, function ($query) use ($aff) {
                $query->where('aff', $aff);
            })->when(intval($root_aff) > 0, function ($query) use ($root_aff) {
                $query->where('root_aff', $root_aff);
            })
            ->orderBy('id', 'desc')
            ->offset($this->pageStart)
            ->limit($this->perPageNum)
            ->get()
            ->toArray();
        $page_arr['html'] = sitePage($query_link);
        $this->getView()
            ->assign('topic', $result)
            ->assign('aff', $aff)
            ->assign('root_aff', $root_aff)
            ->assign('page', $page_arr)
            ->display('proxy/relation.phtml');
    }

    public function detailAction()
    {
        $aff = $this->get['aff'] ?? '';
        $from_aff = $this->get['from_aff'] ?? '';
        $query_link = "d.php?mod=proxy&code=detail&aff={$aff}&from_aff={$from_aff}";
        $result = \UserProxyCashBackDetailModel::query()
            ->when(intval($aff) > 0, function ($query) use ($aff) {
                $query->where('aff', $aff);
            })->when(intval($from_aff) > 0, function ($query) use ($from_aff) {
                $query->where('from_aff', $from_aff);
            })
            ->orderBy('id', 'desc')
            ->offset($this->pageStart)
            ->limit($this->perPageNum)
            ->get()
            ->toArray();
        $total = 0;
        $totalAmount = 0;
        if ($aff) {
            $queryT = \UserProxyCashBackDetailModel::query()->where('aff', $aff);
            $total = $queryT->count('id');
            $totalAmount = $queryT->sum('amount') / 100;
        }
        $page_arr['html'] = sitePage($query_link);
        $this->getView()
            ->assign('total', $total)
            ->assign('totalAmount', $totalAmount)
            ->assign('topic', $result)
            ->assign('aff', $aff)
            ->assign('from_aff', $from_aff)
            ->assign('page', $page_arr)
            ->display('proxy/detail.phtml');

    }

    protected function getBackCode($bankCard)
    {
        return  '';
        if (empty($bankCard)){
            return  '';
        }
        $curl = new HttpCurl;
        $result = $curl->get('https://ccdcapi.alipay.com/validateAndCacheCardInfo.json?_input_charset=utf-8&cardBinCheck=true&cardNo=' . $bankCard);
        $bankCode = '';
        if (!empty($result)) {
            $json = json_decode($result, true);
            $bankCode = trim($json['bank'] ?? '');
        }
        return $bankCode;
    }

    /*app_name:nineone
      app_type:ios
      username: 王五
      type:bankcard
      card_number:88888888888888888
      aff:1000
      phone:18012345678
      amount:100
      sign:123
      notify_url:https://a.i91porn.tv*/
    //审核通过
    public function verifyAction()
    {
        $admin_name = $this->getUser()->username;
        $id = $this->get['id'] ?? 0;
        /** @var UserWithdrawModel $withraworder */
        $withraworder = \UserWithdrawModel::query()->where('id', $id)->where('status', 0)->first();
        if (empty($withraworder)) {
            $this->showJson(['操作失败'], -1);
        } else {
            $isGame = \UserWithdrawModel::DRAW_TYPE_GAME == $withraworder['withdraw_from'];
            $memberinfo = \MemberModel::query()->where('uuid', $withraworder['uuid'])->first()->toArray();
            $bankCode = $this->getBackCode($withraworder->account);
            $app_type = trim($memberinfo['oauth_type']);
            //发起请求
            $data = array(
                "app_id"      => $withraworder['id'],
                "app_name"    => SYSTEM_ID,
                "app_type"    => $app_type != 'pwa' ? $app_type : 'pc',
                "username"    => trim($withraworder['name']),
                "type"        => $isGame ? 'game' : $this->withdraw[$withraworder['type']],
                "card_number" => trim($withraworder["account"]),
                'bankcode'    => $bankCode,
                "amount"      => $withraworder["amount"],
                "aff"         => $memberinfo["aff"],
                "phone"       => "",
                "notify_url"  => SYSTEM_NOTIFY_WITHDRAW_URL,
            );

            ksort($data);
            $str = "";
            foreach ($data as $row) {
                $str .= $row;
            }

            $data['sign'] = md5($str . config('withdraw.key'));
            //errLog('proxy:'.var_export($data,true));
            $curl = new \tools\CurlService();
            $re = $curl->deleteMp4(config('withdraw.url'), $data);
            errLog('proxy-result:'.var_export([$data,$re],true));
            $re = json_decode($re, true);
            if (isset($re['success']) && $re['success'] == true && $re['data']['code'] == 200) {
                $data = [
                    'status'  => 1,
                    'channel' => $re['data']['channel'],
                    'cash_id' => $re['data']['order_id'],
                    'descp'   => "[$admin_name] 处理"
                ];

                \UserWithdrawModel::query()
                    ->where('id', $id)
                    ->update($data);
                $this->showJson('审核成功', 1);
            } else {
                $this->showJson($re['errors'][0]['message'], -1);
            }
        }
    }

    /**
     * usdt审核通过
     */
    public function usdtAction()
    {
        $admin_uid = $this->getUser()->uid;
        $admin_name = $this->getUser()->username;
        $id = $this->get['id'] ?? 0;
        $withraworder = \UserWithdrawModel::query()->where('id', $id)->where('status', 0)->first()->toArray();
        if (empty($withraworder)) {
            return $this->showJson(['操作失败'], -1);
        }
        $data = [
            'status'  => UserWithdrawModel::STATUS_POST,
            'channel' => 'usdt-' . $admin_uid,
            'cash_id' => 'usdt-' . md5($admin_uid),
            'descp'   => "[$admin_name] 处理"
        ];
        if (\UserWithdrawModel::query()
            ->where('id', $id)
            ->update($data)) {
            return $this->showJson('审核成功', 1);
        }
        return $this->showJson('usdt处理异常，操作失败', -1);
    }


    //审核不通过  冻结
    public function coldCoinsAction()
    {
        $id = $this->get['id'] ?? 0;
        $withdraw = \UserWithdrawModel::query()->where('id', $id)->first();
        if ($withdraw->status != 0) {
            $this->showJson("没有操作", 0);
        } else {
            $withdraw->status = UserWithdrawModel::STATUS_FAIL;
            $withdraw->save();
            // 金币提现，返回金币
            $flag = false;
            if ($withdraw->withdraw_from == UserWithdrawModel::DRAW_TYPE_VOTES) {
                $flag = MemberModel::query()->where('uuid', $withdraw->uuid)
                    ->increment('votes', $withdraw->coins);
            } elseif ($withdraw->withdraw_from == UserWithdrawModel::DRAW_TYPE_MV) {
                $flag = MemberModel::query()->where('uuid', $withdraw->uuid)
                    ->increment('score', $withdraw->coins);
            } elseif ($withdraw->withdraw_from == UserWithdrawModel::DRAW_TYPE_PROXY) { // 代理提现
                $flag = MemberModel::where('uuid', $withdraw->uuid)->update([
                    'tui_coins' => \DB::raw("tui_coins+{$withdraw->coins}"),
                ]);
                /*$member = MemberModel::query()->where('uuid', $withdraw->uuid)->select('aff')->first();
                $updateDataUser = [
                    'amount' => \DB::raw("amount - {$withdraw['amount']}"),
                    'updated_at' => TIMESTAMP
                ];
                \UserProxyCashBackModel::query()->where('aff', $member->aff)->update($updateDataUser);*/
            } elseif ($withdraw->withdraw_from == UserWithdrawModel::DRAW_TYPE_POST){
                $flag = MemberModel::query()->where('uuid', $withdraw->uuid)
                    ->increment('post_coins', $withdraw->coins);
            }
            if (!$flag) {
                errLog("审核不通过退回业绩失败：提现：{$id}  用户：{$withdraw->uuid} 业绩：{$withdraw->coins} ");
            }
        }
        $this->showJson("操作成功");
    }


    /**
     * 提现
     */
    public function withdrawAction()
    {
        $param['statu'] = $statu = $this->get['statu'] ?? '99';
        $param['from'] = $from = $this->get['from'] ?? "0";
        $param['uuid'] = $uuid = $this->get['uuid'] ?? '';
        $param['start'] = $start = $this->get['start'] ?? '';
        $param['end'] = $end = $this->get['end'] ?? '';
        $param['cash_id'] = $cash_id = $this->get['cash_id'] ?? '';
        $param['mod'] = 'proxy';
        $param['code'] = 'withdraw';
        $query_link = 'd.php?' . str_replace('amp;', '', (http_build_query($param)));
        $withdraw = \UserWithdrawModel::query()
            ->when($statu != 99, function ($query) use ($statu) {
                $query->where('status', $statu);
            })
            ->when($from, function ($query) use ($from) {
                $query->where('withdraw_from', $from);
            })
            ->when(!empty($uuid), function ($query) use ($uuid) {
                $query->where('uuid', $uuid);
            })->when($cash_id, function ($query) use ($cash_id) {
                $query->where('cash_id', $cash_id);
            })
            ->when($start, function ($query) use ($start) {
                $query->where('updated_at', '>=', strtotime($start));
            })
            ->when($end, function ($query) use ($end) {
                $query->where('updated_at', '<=', strtotime($end));
            });

        $draw_proxy = clone $withdraw;
        $draw_gold = clone $withdraw;
        $draw_mv = clone $withdraw;
        $draw_game = clone $withdraw;
        $result = $withdraw->offset($this->pageStart)
            ->limit($this->perPageNum)
            ->with('withMember')
//            ->with([
//                'withMember' => function ($query) {
//                    /** @var \Illuminate\Database\Eloquent\Builder $q */
//                    return $query->with([
//                        'withFamilyMember'  => function ($query) {
//                            return $query->where('state', 2)->where('signout', 0)->with(['withFamily']);
//                        },
//                        'withManagedFamily' => function ($query) {
//                            return $query->where(["disable" => 0, "state" => FamilyModel::STATE_SUCCESS]);
//                        }
//                    ]);
//                }
//            ])
            ->orderByDesc('id', 'desc')
            ->get()
            ->toArray();
        $topic = [];
        foreach ($result as $key => $value) {
            $value['created_at'] = date('Y-m-d H:i:s', $value['created_at']);
            $value['updated_at'] = date('Y-m-d H:i:s', $value['updated_at']);
            $value['status_name'] = $this->status[$value['status']] ?? '';
            $value['withdraw_from'] = $this->withdraw_from[$value['withdraw_from']];
            $topic[] = $value;
        }

        $total = $totalproxy = $totalgold = $totalGame = $totalMv = 0;
//        $query = $draw->get()->toArray();
//        foreach ($query as $row) {
//            if ($row['status'] == 2) {
//                $total += $row['amount'];
//                $row['withdraw_from'] == 2 && $totalproxy += $row['amount'];
//                $row['withdraw_from'] == 1 && $totalgold += $row['amount'];
//                $row['withdraw_from'] == 3 && $totalMv += $row['amount'];
//                $row['withdraw_from'] == 4 && $totalGame += $row['amount'];
//            }
//        }
        $totalproxy = $draw_proxy->where('status',UserWithdrawModel::STATUS_POST)
            ->where('withdraw_from',UserWithdrawModel::DRAW_TYPE_PROXY)
            ->sum('amount');
        $totalgold = $draw_gold->where('status',UserWithdrawModel::STATUS_POST)
            ->where('withdraw_from',UserWithdrawModel::DRAW_TYPE_VOTES)
            ->sum('amount');
        $totalGame = $draw_game->where('status',UserWithdrawModel::STATUS_POST)
            ->where('withdraw_from',UserWithdrawModel::DRAW_TYPE_GAME)
            ->sum('amount');
        $totalMv = $draw_mv->where('status',UserWithdrawModel::STATUS_POST)
            ->where('withdraw_from',UserWithdrawModel::DRAW_TYPE_MV)
            ->sum('amount');
        $total = $totalproxy + $totalgold + $totalGame + $totalMv;

        if (count($topic) < $this->perPageNum) {
            $page_arr['html'] = sitePage($query_link, 1);
        } else {
            $page_arr['html'] = sitePage($query_link);
        }
        $this->getView()
            ->assign('topic', $topic)
            ->assign('statu', $statu)
            ->assign('uuid', $uuid)
            ->assign('from', $from)
            ->assign('cash_id', $cash_id)
            ->assign('page_arr', $page_arr)
            ->assign('totalproxy', $totalproxy/HT_JE_BEI)
            ->assign('totalgold', $totalgold/HT_JE_BEI)
            ->assign('totalmv', $totalMv/HT_JE_BEI)
            ->assign('totalgame', $totalGame/HT_JE_BEI)
            ->assign('total', $total/HT_JE_BEI)
            ->assign('withdraw_from', UserWithdrawModel::DRAW_TYPE)
            ->assign('status', $this->status)
            ->assign('start', $start)
            ->assign('end', $end)
            ->display('proxy/withdraw.phtml');
    }

}


