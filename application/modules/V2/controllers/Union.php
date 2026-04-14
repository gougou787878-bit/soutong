<?php

/**
 * 联盟数据 不要删除  删除割鸡鸡 联盟数据逻辑控制
 * Class UnionController
 */
class UnionController extends BaseController
{
    public $post = [];

    const SIGN_KEY = '0f702e281f2af501df319d35e1efe67e';
    const ENCRYPT_KEY = '44a0e4371d8a23239821f7a3d284bf51';

    public function init()
    {

        //得到数据之前。需要对post数据解密，解密方式使用 LibCrypt类进行处理
        //得到数据之前。需要对post数据解密，解密方式使用 LibCrypt类进行处理
        $rowInput = file_get_contents("php://input");
        parse_str($rowInput , $_POST);
        $_POST = $this->crypt()->checkInputData($_POST, false);
        $this->post = $_POST;
        errLog("時間：".date('YmdHi')."\r\n DecryptPostData:".var_export($this->post,true).PHP_EOL);
        /*$this->post=[
            'gateway'=>'channel',
            'agent_user'=>'predog',
            'agent_name'=>'黃蓉',
            'agent_id'=>'3',
        ];*/
    }

    protected function crypt(){
        $crypt = new LibCrypt();
        $crypt->setKey(self::SIGN_KEY, self::ENCRYPT_KEY);
        return $crypt;
    }

    public function indexAction()
    {
        $type = $this->post['gateway'] ?? 'member';
        switch ($type) {
            case 'member':
                return $this->getMember();
            case 'order':
                return $this->getOrder();
            case 'income_statistical':
                return $this->getIncomeStatistical();
            case 'channel':
                return $this->bindChanel();
            case 'sync.domain':
                return $this->syncDomain();
        }
    }


    public function syncDomain(){
        $share = setting('global.share');
        list($domain) = explode(',' , $share);
        if (empty($domain)){
            return $this->showJson([]);
        };

        $ary = parse_url($domain);
        $domain = $ary['host'] ?? '';
        if (isset($ary['port'])) {
            $domain .= ':' . $ary['port'];
        }
        return $this->showJson(['domain'=>$domain]);
    }

    /**
     * 取用户信息
     * @return bool
     * @author xiongba
     */
    protected function getMember()
    {
        list($where, $limit, $offset) = $this->parseCommonArgs('build_id', 'regdate');
        $model = MemberModel::where($where);
        $all = (clone $model)->limit($limit)->offset($offset)->orderBy('uid', 'desc')
            ->get()
            ->map(function (MemberModel $item) {
                //格式化用户数据
                return [
                    'member_id'         => $item->uid,
                    'member_aff'        => generate_code($item->aff),
                    'member_invited_by' => generate_code($item->invited_by),
                    'member_nickname'   => $item->nickname,
                    'oauth_type'        => $item->oauth_type,
                    'member_level'      => ExperLevelModel::getLevel($item->consumption),
                    'member_created'    => $item->regdate,
                ];
            });
        $returnData = [
            'data'  => $all->toArray(), // 返回数据
            'count' => $model->count(), // 统计数据
        ];
        return $this->showJson($returnData);
    }

    /**
     * 订单数组
     * @return bool
     */
    private function getOrder()
    {
        list($where, $limit, $offset) = $this->parseCommonArgs('build_id', 'created_at');
        $status = $this->post['status'] ?? null;
        $aff = $this->post['aff'] ?? null;
        if ($status !== null) {
            if ($status == 1) {
                $where[] = ['status', '=', OrdersModel::STATUS_SUCCESS];
            } elseif ($status == 0) {
                $where[] = ['status', '=', OrdersModel::STATUS_WAIT];
            }
        }
        if (!empty($aff)) {
            $where[] = ['uuid', '=', $this->uuid2aff($aff)];
        }
        $flag = $this->post['flag'] ?? null;
        if (empty($flag)) {
            $where[] = ['order_type', '=', OrdersModel::TYPE_VIP];
        } elseif ($flag === 'coin') {
            $where[] = ['order_type', '=', OrdersModel::TYPE_GLOD];
        }

        $model = OrdersModel::where($where);
        $all = (clone $model)->limit($limit)->offset($offset)->orderBy('id', 'desc')
            ->get()
            ->map(function (OrdersModel $item) {
                if ($item->status == OrdersModel::STATUS_FAILED) {
                    $status = 3;
                } elseif ($item->status == OrdersModel::STATUS_SUCCESS) {
                    $status = 2;
                } elseif ($item->status == OrdersModel::STATUS_PAYING) {
                    $status = 1;
                } else {
                    $status = 0;
                }
                //格式化订单数据
                return [
                    'order_id'             => $item->id,
                    'order_sn'             => $item->order_id,
                    'order_amount_str'     => sprintf("%.2f", $item->amount / 100),
                    'order_pay_amount_str' => sprintf("%.2f", $item->pay_amount / 100),
                    'order_desc'           => $item->order_id,
                    'member_aff'          => $this->uuid2aff($item->uuid),
                    'order_status'         => $status,
                    'order_created'        => $item->created_at,
                ];
            });
        $successModel = (clone $model)->where('status', '=', OrdersModel::STATUS_SUCCESS);
        $returnData = [
            'success_count' => $successModel->count(),
            'success_money' => sprintf("%.2f", $successModel->sum('pay_amount') / 100),
            'total_money'   => sprintf("%.2f", $model->sum('amount') / 100),
            'data'          => $all->toArray(), // 返回数据
            'count'         => $model->count(), // 统计数据
        ];
        return $this->showJson($returnData);
    }


    protected function uuid2aff($uuid){
        $model = MemberModel::where('uuid','=' , $uuid)->first();
        if ($model){
            return generate_code($model->aff);
        }
        return '';
    }
    protected function aff2uuid($aff){
        $model = MemberModel::where('aff','=' , get_num($aff))->first();
        if ($model){
            return $model->uuid;
        }
        return null;
    }

    /**
     * 返回收益数据
     * @return bool
     */
    private function getIncomeStatistical()
    {
        if (empty($_POST['channel'])){
            return  $this->showJson(['count'=>0 , 'data'=>[]]);
        }
        if (empty($_POST['start_time'])){
            $_POST['start_time'] = date('Y-m-d');
        }
        $where = [
            ['build_id', '=', $_POST['channel']],
            ['updated_at', '>=', strtotime($_POST['start_time'])],
            ['updated_at', '<', strtotime($_POST['start_time'] . ' 23:59:59')],
            ['status' , '=' , OrdersModel::STATUS_SUCCESS]
        ];

        $flag = $this->post['flag'] ?? null;
        if (empty($flag)) {
            $where[] = ['order_type', '=', OrdersModel::TYPE_VIP];
        } elseif ($flag === 'coin') {
            $where[] = ['order_type', '=', OrdersModel::TYPE_GLOD];
        }
        //\DB::enableQueryLog();
        $model = OrdersModel::where($where);
        $all = (clone $model)->orderBy('id', 'desc')
            ->get(DB::raw("sum(pay_amount) as income_amount"))
            ->map(function (OrdersModel $item) {
                //格式化收益数据
                return [
                    'income_amount' => $item->income_amount / 100,
                    'income_date'   => $_POST['start_time'],
                    'add_member'    => $this->getAddMember($_POST['start_time'], $this->post['channel']),
                ];
            });
        $returnData = [
            'data'  => $all->toArray(), // 返回数据
            'count' => $model->count(), // 统计数据
        ];
        $d = \DB::getQueryLog();
        //errLog("sql:".var_export($d,true));
        return $this->showJson($returnData);
    }

    private function getAddMember($date, $channel)
    {
        return MemberModel::where(['build_id' => $channel])
            ->where([
                ['regdate', '>=', strtotime("$date 00:00:00")],
                ['regdate', '<', strtotime("$date 23:59:59")],
            ])->count('uid');
    }

    protected function bindChanel()
    {
        $prefix = 'st';
        $agentId = $this->post['agent_id'] ?? '';
        $agentUser = $this->post['agent_user'] ?? '';
        $agentName = $this->post['agent_name'] ?? '';
        $parent_channel = $this->post['parent_channel'] ?? '';
        $rate = $this->post['rate'] ?? '50';

        $parentRow = null;
        if($parent_channel){
            $parentRow = AgentsUserModel::where([
                'channel'=>$parent_channel,
                'agent_level'=>1,
            ])->first();
            if (is_null($parentRow)) {
                return $this->showJson([], 0, '无效父级渠道标识#' . $parent_channel);
            }
        }

        $hasRow = AgentsUserModel::where(['root_id' => $agentId, 'username' => $agentUser])->first();
        if (is_null($hasRow)) {
            try {
                \DB::beginTransaction();
                $lastRow = AgentsUserModel::query()->orderByDesc('id')->first();
                $channel = $prefix . str_pad($lastRow->id + 1, 4, 0, STR_PAD_LEFT);
                if ($parentRow) {
                    $channel = $parentRow->channel;
                }

                //用户
                $insertData = [];
                $insertData['nickname'] = $agentName;
                $insertData['username'] = $agentUser;
                $insertData['oauth_type'] = 'channel';
                $insertData['oauth_id'] = md5($agentUser . $agentId . TIMESTAMP);
                $insertData['uuid'] = md5($insertData['oauth_type'] . $insertData['oauth_id']);
                $insertData['role_id'] = 20;//默认角色
                $insertData['invited_by'] = $parentRow?$parentRow->aff:0;
                $insertData['build_id'] = $channel;
                $insertData['regdate'] = TIMESTAMP;
                $insertData['lastvisit'] = TIMESTAMP;
                $member = MemberModel::create($insertData);
                $member->aff = $member->uid;
                $member->save();
                //代理关系 非必需
                UserProxyModel::updateOrCreate(['root_aff' => $member->aff, 'aff' => $member->aff, 'proxy_level' => 1],
                    ['proxy_node' => $member->aff, 'created_at' => TIMESTAMP]);
                //渠道用户
                $insertAgent = [];
                $insertAgent['username'] = $agentUser;
                $insertAgent['password'] = md5($agentUser . date('Ymd'));
                $insertAgent['parent_id'] = $parentRow ? $parentRow->id : 1;
                $insertAgent['root_id'] = $agentId;
                $insertAgent['phone'] = '';
                $insertAgent['channel'] = $channel;
                $insertAgent['channel_name'] = $agentName;
                $insertAgent['agent_level'] = $parentRow ? $parentRow->agent_level + 1 : 1;
                $insertAgent['aff'] = $member->aff;
                $insertAgent['rate'] = $rate;
                $insertAgent['rate_gold'] = $rate;
                $insertAgent['created_at'] = date('Y-m-d H:i:s',TIMESTAMP);
                $hasRow = AgentsUserModel::create($insertAgent);
                if(is_null($hasRow)){
                    throw new Exception('创建渠道失败~');
                }
                \DB::commit();
            } catch (Exception $e) {
                errLog("\r\n createChan:" . var_export($e, true));
                \DB::rollBack();
                return $this->showJson([], 0, '创建渠道失败#'.$e->getMessage());
            }
        }
        $affCode = generate_code($hasRow->aff);
        $return = [];
        $return['product_chan'] = $hasRow->channel;
        //https://a.kslive.tv/chan/k1000/EdJY
        $return['product_chan_link'] = getShareURL() . "/chan/{$hasRow->channel}/{$affCode}";
        $return['extend'] = $this->formatReturnData($hasRow->toArray());
        $return['aff'] = $hasRow->aff;
        $backData['data'] = $return;
        return $this->showJson($backData);
    }
    private function formatReturnData($agentsUserData)
    {
        return [
            'username'=>$agentsUserData['username'],
            'root_id'=>$agentsUserData['root_id'],
            'channel'=>$agentsUserData['channel'],
            'channel_name'=>$agentsUserData['channel_name'],
            'agent_level'=>$agentsUserData['agent_level'],
            'aff'=>$agentsUserData['aff'],
            'rate'=>$agentsUserData['rate'],
            'rate_gold'=>$agentsUserData['rate_gold'],
            'created_at'=>$agentsUserData['created_at'],
            'id'=>$agentsUserData['id'],
        ];
    }

    /**
     * 返回的数据需要加密
     * @return bool
     */
    public function showJson($data, $status = 1, $msg = '')
    {   //return $this->getResponse()->setBody(json_encode($data));
        errLog("時間：".date('YmdHi')."\r\n ResponseUnionPostData:".var_export(['req'=>$this->post,'rep'=>[$data,$status,$msg]],true).PHP_EOL);
        $returnData = $this->crypt()->replyData($data);
        return $this->getResponse()->setBody($returnData);

    }

    /**
     * 解析公共参数
     * @param string $channelColumn 渠道字段名称
     * @param string $timeColumn 时间范围字段
     * @return array 返回参数[条件数组, limit , offset]
     */
    protected function parseCommonArgs($channelColumn, $timeColumn)
    {
        $channel = $this->post['channel'] ?? null;
        $limit = intval($this->post['limit'] ?? 20);
        $page = intval($this->post['page'] ?? 1);
        $page = $page <= 1 ? 0 : $page - 1;
        $offset = $page * $limit;
        $where = [
            [$channelColumn, '=', $channel]
        ];

        $start = $this->post['start_time'] ?? date('Y-m-d');
        $end = $this->post['end_time'] ?? date('Y-m-d');
        if (!empty($start)) {
            $where[] = [$timeColumn, '>=', strtotime($start)];
        }
        if (!empty($end)) {
            $where[] = [$timeColumn, '<', strtotime("{$end} 23:59:59")];
        }
        return [$where, $limit, $offset];
    }
}