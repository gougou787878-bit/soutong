<?php

use helper\QueryHelper;
use helper\Validator;
use service\AdService;
use service\PitTopService;
use service\WeekService;

class SystemController extends BaseController
{
    use \repositories\SystemRepository, \repositories\SmsRepository, \repositories\ExchangeCodeRepository;
    public function IndexAction()
    {
        $ads = AdService::getADsByPosition(AdsModel::POSITION_SCREEN);
        if ($ads) {
            $rand = array_rand($ads);
            $ads = $ads[$rand];
        }

        $data = [
            'enableRecharge' => true, // 是否开启充值
            'versions' => $this->getUpdate($_POST['version'], $_POST['oauth_type']),
            'screen' => $ads,
        ];

        $this->showJson($data);
    }

    /**
     * 发送短信
     * @throws \Yaf\Exception
     */
    public function smsAction()
    {
        $mobile_prefix = $this->post['prefix'] ?? '86';
        $phone = $this->post['phone'] ?? '';
        $verify_code = $this->post['verify_code'] ?? '';
        if ($mobile_prefix == '' or $phone == '') {
            throw new \Yaf\Exception('请将手机号码填写完整', 422);
        }
        $member = request()->getMember();
        try {
            $this->seed($phone, $mobile_prefix, $member, $verify_code);
        } catch (Exception $e) {
            return $this->errorJson($e->getMessage(), $e->getCode());
        }
        $this->showJson(['success' => true, 'msg' => '发送短信成功']);
    }

    /**
     * 失败域名反馈
     * @throws Exception
     */
    public function domainAction()
    {
        $domain = $this->post['domain'] ?? '';
        if ($domain == '') {
            throw new \Exception('参数错误', 422);
        }
        $domain = explode(',', $domain);
        foreach ($domain as $item) {
            AreaLogModel::create([
                'uuid' => $this->member['uuid'],
                'url' => $item,
                'ip' => USER_IP,
                'sick' => 0
            ]);
        }
        $this->showJson(['success' => true, 'msg' => '提交成功']);
    }

    /**
     * 短信国家码
     */
    public function countryAction()
    {
        $list = (new SMSCountryModel)->getList();
        $this->showJson($list);
    }

    public function exchangeAction()
    {
        $code = $this->post['code'] ?? '';
        $code = trim($code);
        if ($code == '') {
            throw new \Yaf\Exception('请输入正确的参数', 422);
        }

        $this->handleExchangeCode($code);


        return $this->showJson(['success' => true, 'msg' => '兑换成功']);
    }
    /**
     * 广告-点击统计
     * @return bool
     */
    public function adsclickAction()
    {
        try {
            $validator = Validator::make($this->post, [
                'id' => 'required|numeric|min:1',
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $id = $this->post['id'];

            jobs([AdsModel::class, 'reportRemote'], [$id, date('Y-m-d H:i:s')]);
            return $this->showJson(['success' => true, 'msg' => '提交成功']);
//
//            $flag = AdsModel::where('id',$id)->increment('click_number',1);
//            if($flag){
//            }
//            $this->showJson(['success' => true, 'msg' => '提交成功']);
        }catch (Throwable $e){
            return $this->errorJson($e->getMessage());
        }
    }
    /**
     * 应用中心-点击
     * @return bool
     */
    public function appclickAction()
    {
        try {
            $validator = Validator::make($this->post, [
                'id' => 'required|numeric|min:1',
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $id = $this->post['id'];
            //点击数上报
            jobs([AdsModel::class, 'reportRemote'], [$id, date('Y-m-d H:i:s')]);
//
//            AdsAppModel::incrDownLoadNumber($id, 1);
            return $this->showJson(['success' => true, 'msg' => '提交成功']);
        }catch (Throwable $e){
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 应用中心
     * @return bool
     */
    public function appcenterAction()
    {
        $adsData = AdService::getADsByPosition(AdsModel::POSITION_APP_CENTER);
        $appData = AdService::getAdsAppList();
        $return = [
            'banner' => $adsData,
            'apps'   => $appData//不展示搜同 男蜜圈
        ];
        $this->showJson($return);
    }

    /**
     * 进站必涮
     * @return bool
     */
    public function pitTopAction()
    {
        list($page , $limit) = QueryHelper::pageLimit();
        $ads = [];
        $data = [];
        if ($page == 0) {
            $ads = AdService::getADsByPosition(AdsModel::POSITION_SITE_TOP);
        }
        $data = PitTopService::getTopicList(request()->getMember(), $page, $limit);
        return $this->showJson(['ads' => $ads, 'list' => $data]);
    }
    /**
     * 每周必看列表
     * @return bool|void
     */
    public function weekAction(){

        list($limit, $offset, $page) = QueryHelper::restLimitOffset();
        $ads = [];
        $data = [];
        if($page == 0){
            $ads = AdService::getADsByPosition(AdsModel::POSITION_WEEK);
        }
        $data = WeekService::getTopicList($limit,$offset,$page);
        return $this->showJson(['ads' => $ads, 'list' => $data]);
    }
    /**
     * 每周必看 根据week_id获取视频  分页查询
     * @param int $limit 20
     * @param int $page 1
     *
     * @return bool|void
     */
    public function weekMvAction()
    {
        $topic_id = $this->post['week_id'] ?? WeekService::getMaxWeekID();
        if (!$topic_id) {
            return $this->showJson([]);
        }
        $data = WeekService::getMVList($topic_id,request()->getMember());
        return $this->showJson($data);
    }

    /**
     * 静态资源
     */
    public function resourceAction(){
        $resource = [];
        //支付相关
        $resource['pm_icon'] = [
            "pm_icon_ap"     => "/new/xblues/pm_icon_ap.png",
            "pm_icon_pb"     => "/new/xblues/pm_icon_pb.png",
            "pm_icon_ph"     => "/new/xblues/pm_icon_ph.png",
            "pm_icon_pt"     => "/new/xblues/pm_icon_pt.png",
            "pm_icon_pv"     => "/new/xblues/pm_icon_pv.png",
            "pm_icon_pw"     => "/new/xblues/pm_icon_pw.png",
            "prod_coin_2"    => "/new/xblues/prod_coin_2.png",
            "prod_coin_3"    => "/new/xblues/prod_coin_3.png",
            "pp_result_code" => "/new/xblues/pp_result_code.png"
        ];
        //应用本身系统相关
        $resource['sys_icon'] = [
            "sys_xlp_logo"  => "/new/xblues/sys_xlp_logo.png",
            "cmt_by_author" => "/new/xblues/cmt_by_author.png",
            "cmt_mbr_icon"  => "/new/xblues/cmt_mbr_icon.png",
            "ctr_back_01"   => "/new/xblues/ctr_back_01.png",
            "ctr_back_02"   => "/new/xblues/ctr_back_02.png"
        ];
        //用户相关
        $resource['per_icon'] = [
            "per_ticket_0" => "/new/xblues/per_ticket_0.png",
            "per_ticket_1" => "/new/xblues/per_ticket_1.png",
            "per_invite_1" => "/new/xblues/per_invite_1.png",
            "per_invite_2" => "/new/xblues/per_invite_2.png",
            "per_invite_3" => "/new/xblues/per_invite_3.png",
            "per_invite_4" => "/new/xblues/per_invite_4.png",
            "per_invite_5" => "/new/xblues/per_invite_5.png",
            "per_invite_6" => "/new/xblues/per_invite_6.png"
        ];
        //推广相关
        $resource['share_icon'] = [
            "share_bg0_icon" => "/new/xblues/share_bg0_icon.png",
            "share_bg1_icon" => "/new/xblues/share_bg1_icon.png",
            "share_bg2_icon" => "/new/xblues/share_bg2_icon.png",
            "share_bg3_icon" => "/new/xblues/share_bg3_icon.png",
            "share_bg5_icon" => "/new/xblues/share_bg5_icon.png",
            "share_bg9_icon" => "/new/xblues/share_bg9_icon.png"
        ];

        // 会员等级图片
        $resource['per_imbr'] = [
            "per_imbr_11" => "/new/ads/20211011/2021101114505886063.png",
            "per_imbr_12" => "/new/ads/20211011/2021101114512472076.png",
            "per_imbr_13" => "/new/ads/20211011/2021101114515681979.png",
            "per_imbr_14" => "/new/ads/20211011/2021101114522172027.png"
        ];

        $resource = array_map(function ($item) {
            return array_map(function ($item_value) {
                return url_ads($item_value);
            }, $item);
        }, $resource);
        return $this->showJson($resource);
    }

    public function downloadAction(){
        try {
            $validator = Validator::make($this->post, [
                'mv_id' => 'required|numeric|min:1',//视频ID
            ]);
            if ($validator->fail($msg)) {
                return $this->errorJson($msg);
            }
            $mvId = (int)($this->post['mv_id']);
            $type = $this->post['type']??'mv';
            $member = request()->getMember();
            if($member->isBan()){
                throw new Exception('你已被禁言，请联系管理员');
            }

            if (!frequencyLimit(5, 1, $member)) {
                throw new Exception('短时间内下载太頻繁了,稍后再试试');
            }

            if (!$member->is_vip){
                throw new Exception('下载权限不足');
            }

            /** @var UserDownloadModel $download_info */
//            $download_info = UserDownloadModel::findByAff($member->aff);
//            if (empty($download_info) || $download_info->val <= 0){
//                throw new Exception('下载次数已用完');
//            }

            if($type == 'original'){
                OriginalVideoModel::setWatchUser($member);
                $original_info = OriginalVideoModel::find($mvId);
                test_assert($original_info, '视频不存在');
                test_assert($original_info->is_pay, '下载权限不足');

                $hls = $original_info->source;
                $title = $original_info->original->title;
            }elseif($type == 'cartoon'){
                CartoonChaptersModel::setWatchUser($member);
                $cartoon_info = CartoonChaptersModel::find($mvId);
                test_assert($cartoon_info, '视频不存在');
                test_assert($cartoon_info->is_pay, '下载权限不足');

                $hls = $cartoon_info->source;
                $title = $cartoon_info->cartoon->title;
            }else{
                MvModel::setWatchUser($member);
                $mv_info = MvModel::find($mvId);
                test_assert($mv_info, '视频不存在');
                test_assert($mv_info->is_pay, '下载权限不足');

                $hls = $mv_info->full_m3u8 ?: $mv_info->m3u8;
                $title = $mv_info->title;
            }

            //扣减次数
            UsersProductPrivilegeModel::hasPrivilegeAndSubTimePrivilege(USER_PRIVILEGE, PrivilegeModel::RESOURCE_TYPE_SYSTEM, PrivilegeModel::PRIVILEGE_TYPE_DOWNLOAD, $member->aff);

            $return = [
                'is_permit'         => 1,
                'message'           => "您正在下载【{$title}】",
                'resource_download' => getPlayUrlPwa($hls),
            ];

            return $this->showJson($return);
        } catch (\Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }
}
