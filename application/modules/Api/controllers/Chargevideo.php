<?php


use service\ProductService;

/**
 * Class ChargeVideoController
 * @author xiongba
 * @date 2020-11-02 15:37:12
 */
class ChargevideoController extends BaseController
{

    /**
     * 迭代版本，金币商城
     */
    public function indexAction()
    {
        $tabArray = $this->getTabAry(explode(',', setting('charge.video.tag', '自慰,人妻,国语对白')));
        $adsData = \service\AdService::getDiamondPlaza(
            AdsModel::POSITION_DIAMOND_VIDEO_PLAZA
            , $this->member
            , $this->token());
        $data = [
            'ads' => $adsData,
            'tab' => $tabArray,
        ];
        return $this->showJson($data);
    }

    /**
     * 迭代版本，vip专享,官方主场折扣商城
     */
    public function vipIndexAction()
    {
        $tabArray = $this->getTabAry(explode(',', setting('vip.charge.video.tag', '自慰,人妻,国语对白')));
        $adsData = \service\AdService::getDiamondPlaza(AdsModel::POSITION_DIAMOND_VIDEO_PLAZA, $this->member,
            $this->token());
        array_shift($tabArray);
        $data = [
            'ads' => $adsData,
            'tab' => $tabArray,
        ];
        return $this->showJson($data);
    }

    /**
     * 迭代版本，up主视频商城
     */
    public function upIndexAction()
    {
        $tabArray = $this->getTabAry(explode(',', setting('up.charge.video.tag', '自慰,人妻,国语对白')));
        $adsData = \service\AdService::getDiamondPlaza(AdsModel::POSITION_DIAMOND_VIDEO_PLAZA, $this->member,
            $this->token());
        $data = [
            'ads' => $adsData,
            'tab' => $tabArray,
        ];
        return $this->showJson($data);
    }

    protected function getTabAry($chargeVideoTag)
    {
        $tabArray = [
            [
                'current' => false,
                'name'    => '关注',
                'api'     => '/api/chargeVideo/chargeForFollow',
                'type'    => 'follow', //前端通过使用本值，调用不同的接口 //
                'params'  => ''
            ],
            [
                'current' => true,
                'name'    => '推荐',
                'api'     => '/api/chargeVideo/recommend',
                'type'    => 'recommend',//前端通过使用本值，调用不同的接口 //
                'params'  => ''
            ],
        ];

        foreach ($chargeVideoTag as $value) {
            $tabArray[] = [
                'current' => false,
                'name'    => $value,
                'api'     => '/api/chargeVideo/tag',
                'type'    => 'tag', //前端通过使用本值，调用不同的接口 //
                'params'  => $value
            ];
        }
        return $tabArray;
    }


    /**
     * 使用标签收费视频
     * @return bool
     * @author xiongba
     */
    public function tagAction()
    {
        //errLog("tagAction".var_export($_POST,1));

        $tagName = $_POST['tag'] ?? null;
        $lastIndex = intval($_POST['lastIndex'] ?? 0);
        if (empty($tagName)) {
            return $this->errorJson('标签不能为空');
        }
        $official = $_POST['official'] ?? null;
        if ($official !== null) {
            $official = (bool)$official;
        }
        $service = new \service\MvService;
        $data = $service->getChargeMvByCached(request()->getMember(), $lastIndex, $tagName, $official);
        return $this->showJson($data);
    }

    /**
     * 获取用户关注的用户发布的收费视频
     * @return bool
     * @author xiongba
     */
    public function chargeForFollowAction()
    {
        $service = new \service\MvService;
        $data = $service->getChargeMvByFollow(request()->getMember());
        return $this->showJson($data);
    }

    /**
     * 获取用户关注的用户发布的收费视频
     * @return bool
     * @author xiongba
     */
    public function followAction()
    {
        return $this->chargeForFollowAction();
    }

    /**
     * 推荐视频
     * @return bool
     * @author xiongba
     */
    public function recommendAction()
    {
        $service = new \service\MvService;
        $lastIndex = intval($_POST['lastIndex'] ?? 0);
        $official = $_POST['official'] ?? null;
        if ($official !== null) {
            $official = (bool)$official;
        }
        //$data = $service->getChargeRecommend($lastIndex , $this->member['uid']);
        $data = $service->getChargeRecommendNew($lastIndex, $this->member['uid'], $official);
        return $this->showJson($data);
    }

    /**
     * 购买过的视频
     * @author xiongba
     * @date 2020-03-18 11:38:13
     */
    public function maiguoAction()
    {
        try {
            $lastIndex = intval($this->post['lastIndex'] ?? 0);
            $show_type = intval($this->post['show_type'] ?? 0);
            $member = request()->getMember();
            $service = new \service\MvService();
            $data = $service->getBought($member, $show_type, $lastIndex);
            return $this->showJson($data);
        }catch (Throwable $exception){
            return $this->errorJson($exception->getMessage());
        }
    }

    /**
     * 购买视频。自动从余额中扣除用户的金币
     * @return bool
     * @author xiongba
     */
    public function buyAction()
    {
        return $this->forward('Api', 'Userbuy', 'video');
    }

}