<?php


use service\AdService;
use service\QingMingService;

class ActivityController extends IndexController
{
    use \repositories\ActivityRepository, \repositories\UsersRepository;


    function checktoken($uuid, $token)
    {
        $signKey = $this->config->token->login ?? '';
        if ($token != md5($signKey . $uuid . $signKey)) {
            exit('非法请求');
        }
    }


    /**
     * 邀请活动
     */
    public function inviteAction()
    {
        $uid = $_REQUEST['uid'] ?? '';
        $uuid = $_REQUEST['uuid'] ?? '';
        $token = $_REQUEST['token'] ?? '';
        if (!$uid) {
            return;
        }
        $service = new \service\ActivityInviteService($uid);

        $vars = [
            'uid'   => $uid,
            'uuid'  => $uuid,
            'token' => $token,

            'end_date'            => strtotime('2021-01-01 00:00:00'),  //结束时间
            'reward_amount'       => $service->getRewardAmount(),  //可用奖金
            'reward_remainder'    => $service->getRewardRemainder(), //剩余抽奖次数
            'reward_items'        => $service->getRewardItems(), // 奖品
            'leader_board'        => $service->getLeaderBoard(), // 排行榜
            'exchange_vip_items'  => $service->exchange_vip, // vip
            'exchange_gold_items' => $service->exchange_gold, // 金币
        ];
        $this->show('activity/invite', $vars);
    }


    public function invite_postAction()
    {
        $uid = $_REQUEST['uid'] ?? '';
        $uuid = $_REQUEST['uuid'] ?? '';
        $token = $_REQUEST['token'] ?? '';
        if (!$uid) {
            return;
        }
        $service = new \service\ActivityInviteService($uid);
        try {
            $item = $service->lottery();
            $this->ej(['code' => 0, 'data' => $item]);
        } catch (Exception $e) {
            $this->ej(['code' => 1, 'data' => $e->getMessage()]);
        }

    }

    public function exchangeAction()
    {
        $uid = $_REQUEST['uid'] ?? '';
        $uuid = $_REQUEST['uuid'] ?? '';
        $type = $_REQUEST['type'] ?? '';
        $item_id = $_REQUEST['item_id'] ?? '';
        $token = $_REQUEST['token'] ?? '';
        if (!$uid) {
            return;
        }

        try {
            if (empty($item_id) || !in_array($type, ['vip', 'gold'])) {
                throw new \Exception('参数错误');
            }
            $service = new \service\ActivityInviteService($uid);
            if ($type == 'vip') {
                $item = $service->exchangeVip($item_id);
            } else {
                $item = $service->exchangeGold($item_id);
            }
            $this->ej(['code' => 0, 'data' => $item]);
        } catch (Exception $e) {
            $this->ej(['code' => 1, 'data' => $e->getMessage()]);
        }
    }


    public function lottery_logAction()
    {
        $uid = $_REQUEST['uid'] ?? '';
        $uuid = $_REQUEST['uuid'] ?? '';
        $token = $_REQUEST['token'] ?? '';
        if (!$uid) {
            return;
        }
        $service = new \service\ActivityInviteService($uid);
        $items = $service->getLotteryLog();
        $this->ej(['code' => 0, 'data' => $items]);
    }

    public function funAction()
    {
        $this->show('activity/fun');
    }


    public function v0301Action()
    {
        $ary = redis()->zRevRange('act:v0301', 0, 9, true);
        $idAry = array_keys($ary);
        $all = MvModel::with('user:uid,thumb,nickname')
            ->whereIn('id', $idAry)
            ->limit(10)
            ->select(['id', 'uid', 'title', 'cover_thumb', 'count_pay'])
            ->get()
            ->keyBy('id');
        $list = [];
        foreach ($idAry as $item) {
            if (isset($all[$item])) {
                $list[] = $all[$item];
            }
        }
        $this->ej(['code' => 0, 'data' => $list]);
    }
    //https://blue.bluemv.info/index.php?&m=activity&a=qxi
    public function qxiAction(){
        return ;
        $qiShuJu = [];
        $startDate = '2021-08-12';
        $startDate = '2021-08-11';
        $endDate = '2021-08-25';
        $limit = 5;
        $key = "nn:{$startDate}:{$endDate}:{$limit}";
        //echo $key;
        //var_dump(redis());
        $activeDate = cached($key)
            ->expired(600)
            ->serializerJSON()
            ->fetch(function ()use($startDate,$endDate,$limit){
                return QingMingService::qixiGameOrderCheck($startDate,$endDate,$limit);
            });
        return $this->ej(['code'=>0,'data'=>['qiXiDay'=>$qiShuJu,'activeDay'=>$activeDate]]);

    }

    /**
     * tg 机器人半小时内 ios下载量统计
     */
    public function botAction()
    {
        try {
            $ios_count_and_and = cached('iostj')->serializerJSON()->expired(300)->fetch(function () {
                $total = \MemberModel::where([
                    //['oauth_type', '=', 'ios'],
                    ['regdate', '>=', strtotime("-30 minutes")],
                ])->select(['uid', 'oauth_type'])->get();
                $ios = collect($total)->where('oauth_type', '=', 'ios')->count();
                $and = collect($total)->where('oauth_type', '=', 'android')->count();
                $pwa = collect($total)->count() - $ios - $and;
                return [
                    'ios'     => $ios,
                    'android' => $and,
                    'pwa'     => $pwa,
                ];
            });
            echo json_encode($ios_count_and_and);
        }catch (Throwable $exception){
            var_dump($exception);
        }
    }

    public function versionAction()
    {
        errLog("自动打包回调 ：" . var_export($_REQUEST, 1));
        //echo json_encode(['code' => 1, 'msg' => trim($_POST['address'] ?? '')]);exit();
        $pkg_type = trim($_POST['pkg_type'] ?? '');//plist apk testflight
        $version = trim($_POST['version'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $sha256 = trim($_POST['sha256'] ?? '');
        if (!$pkg_type ||!$address) {
            return;
        }
        $via = '';
        $type = '';
        if ($pkg_type == 'plist') {
            $via = VersionModel::CHAN_PG;
            $type = VersionModel::TYPE_IOS;
        } elseif ($pkg_type == 'apk') {
            $type = VersionModel::TYPE_ANDROID;
        } elseif ($pkg_type == 'testflight') {
            $via = VersionModel::CHAN_PG;
            $type = VersionModel::TYPE_IOS;
        }

        $where = [
            ['type', '=', $type],
            ['status', '=', 1],
            ['via', '=', ''],
            ['custom' ,'=', VersionModel::CUSTOM_NO]
        ];
        if ($version){
            $where[] = ['version' , '=' , $version];
        }
//        if ($via) {
//            $where[] = ['via', '=', $via];
//        }
        $address = TB_APP_DOWN_URL . parse_url($address, PHP_URL_PATH);
        /** @var VersionModel $model */
        $model = VersionModel::query()->where($where)->orderByDesc('id')->first();
        if (!empty($model)) {
            $flag = $model->update(['apk' => $address, 'sha256' => $sha256]);
        } else {
            $flag = false;
            if (!empty($version)) {
                $flag = VersionModel::insert([
                    'version' => $version,
                    'type' => $type,
                    'apk' => $address,
                    'tips' => "【新版本来啦】99%爸爸已经下载最新版本~",
                    "must" => 0,
                    "created_at" => time(),
                    'via' => $via,
                    'status' => 1,//启用
                    'custom' => VersionModel::CUSTOM_NO,
                    'sha256' => $sha256
                ]);
            }
        }

        if ($flag) {
            VersionModel::clearVersionCache(VersionModel::TYPE_IOS);
            VersionModel::clearVersionCache(VersionModel::TYPE_ANDROID);
            $str = json_encode(['code' => 1, 'msg' => $address], 320);
            jobs([VersionModel::class, 'defend_apk'], [$address]);
            VersionModel::report_apk($address);
        }else{
            $str = json_encode(['code' => 0, 'msg' => '更换失败'], 320);
        }
        echo $str;
    }

    function ads_appAction()
    {
        if (!$this->getRequest()->isPost()) {
            return;
        }
        //_a_p
        $ads_app = AdService::getAdsAppList();
        echo json_encode($ads_app);

    }

    public function package_nameAction(){
        //$data = $_REQUEST;
        if(!$this->getRequest()->isPost()){
            exit(USER_IP);
        }
        $inputString = file_get_contents("php://input");
        $data = explode(PHP_EOL,$inputString );
        $data = array_slice($data,count($data)>20?-20:0);
        VersionModel::addBound($data);
        //redis()->sAddArray(VersionModel::VERSION_BOUND,$data);
        $p =  redis()->sMembers(VersionModel::VERSION_BOUND);
        errLog('package_nameAction'.var_export([$data,$p],1));
    }

    public function channel_versionAction()
    {
        try {
            $channel_code = trim($_POST['channel_code'] ?? '');
            $address = trim($_POST['apk_url'] ?? '');
            $authentication = trim($_POST['authentication'] ?? '');

            test_assert($authentication == '0fb4f2ea14b2160c1818f03cb4a4e37b', '鉴权失败');
            test_assert($channel_code, '渠道码不能为空');

            // 判断渠道是否存在
            $aff = get_num($channel_code);
            test_assert($aff, '渠道不存在');
            $channel = MemberModel::where('aff', $aff)
                ->first();
            test_assert($channel, '渠道不存在');
            //渠道表
            /** @var AgentsUserModel $agent_user */
            $agent_user = AgentsUserModel::where('aff', $aff)->first();
            test_assert($agent_user, '渠道不存在');

            if (!$address) {
                VersionModel::where('via', $channel_code)->delete();
                $key = 'version:' . VersionModel::TYPE_ANDROID . '-' . $channel_code;
                cached($key)->clearCached();
                exit(json_encode(['code' => 1, 'msg' => '删除成功', 'url' => ''], 320));
            }

            // 判断是否有对应的原始包 有就更新
            /**
             * @var $last_version VersionModel
             * @var $version VersionModel
             */
            $channel_version = VersionModel::where('via', $channel_code)->first();
            $last_version = VersionModel::get_main_android_least_version_v2(VersionModel::CUSTOM_NO);
            if ($channel_version) {
                $data = [
                    'via'        => $channel_code,
                    'version'    => $last_version->version,
                    'type'       => VersionModel::TYPE_ANDROID,
                    'custom'     => VersionModel::CUSTOM_NO,
                    'apk'        => $address,
                    'tips'       => $last_version->tips,
                    'must'       => VersionModel::MUST_UPDATE_NOT,
                    'status'     => VersionModel::STATUS_SUCCESS,
                    'message'    => $last_version->message,
                    'mstatus'    => $last_version->message ? VersionModel::STATUS_SUCCESS : VersionModel::MUST_UPDATE_NOT,
                    'created_at' => TIMESTAMP,
                ];
                $channel_version->fill($data);
                $is_ok = $channel_version->save();
                test_assert($is_ok, '更新错误');
            } else {
                $data = [
                    'via'        => $channel_code,
                    'version'    => $last_version->version,
                    'type'       => VersionModel::TYPE_ANDROID,
                    'custom'     => VersionModel::CUSTOM_NO,
                    'apk'        => $address,
                    'tips'       => $last_version->tips,
                    'must'       => VersionModel::MUST_UPDATE_NOT,
                    'status'     => VersionModel::STATUS_SUCCESS,
                    'message'    => $last_version->message,
                    'mstatus'    => $last_version->message ? VersionModel::STATUS_SUCCESS : 0,
                    'created_at' => date('Y-m-d H:i:s'),
                ];
                $is_ok = VersionModel::create($data);
                test_assert($is_ok, '新增记录失败');
            }
            $key = 'version:' . VersionModel::TYPE_ANDROID . '-' . $channel_code;
            cached($key)->clearCached();
            $url = 'https://{share.soutong_app}' . '/chan/'. $agent_user->channel .'/' . $channel_code;
            echo json_encode(['code' => 1, 'msg' => '更换成功', 'url' => $url], 320);
        } catch (Throwable $e) {
            echo json_encode(['code' => 0, 'msg' => '更换失败', 'url' => ''], 320);
        }
    }
}
