<?php

use service\EventTrackerService;
use service\TabService;
use service\AdService;

class SearchController extends BaseController
{

    /**
     * 迭代版本，搜索页面 v1.1搜索
     */
    public function indexAction()
    {
        $is_ios = (($this->post['oauth_type']??'') == 'ios');
        $searchService = new \service\SearchService;
        $data = [
            'hotSearch' => $searchService->getHotKeyword($is_ios),
            'ads'       => $searchService->getIndexAdList(request()->getMember(), $this->token()),
            "recommend" => TabService::getSearchList(),
            "rank_list" => $searchService->hotSearchRank(),
        ];
        return $this->showJson($data);
    }

    /**
     * 搜索视频
     * @return bool
     * @throws Throwable
     * @author xiongba
     */
    public function mvAction()
    {
        try {
            $Validator = \helper\Validator::make($this->post, [
                'kwy'  => 'required',
            ]);
            if ($Validator->fail($msg)) {
                return $this->errorJson($msg);
            }
            $kwy = $this->post['kwy'];
            $style = $this->post['show_type'] ?? MvModel::TYPE_LONG;
            if (!in_array($style, [MvModel::TYPE_LONG, MvModel::TYPE_SHORT])){
                return $this->errorJson('视频类型参数错误');
            }
            $kwy = strip_tags($kwy);
            if (mb_strlen($kwy) < 2) {
                return $this->errorJson('至少两位搜索关键字');
            }
            if (preg_match('/[\xf0-\xf7].{3}/', $kwy)) { //过滤Emoji表情
                return $this->errorJson('不支持[Emoji]表情');
            }
            $member = request()->getMember();
            \helper\Util::PanicFrequency($member->uid, 20, 60, "uid: {$member->uid} #{$kwy}# 1分钟内只能搜索10次");
            $service = new \service\SearchService();
            if (preg_match("/^#([1-9]+\d*)$/U", $kwy, $p)) {//eg:指定编号搜索  #666
                if ($this->page == 1) {
                    $data = $service->searchByMVID($p[1]);
                } else {
                    $data = ['total' => 0, 'list' => []];
                }
            } else {
                $data = $service->searchMvNew(trim($kwy), $style);

                //公司上报
                (new EventTrackerService(
                    $member->oauth_type,
                    $member->invited_by,
                    $member->uid,
                    $member->oauth_id,
                    $_POST['device_brand'] ?? '',
                    $_POST['device_model'] ?? ''
                ))->addTask([
                    'event'                 => EventTrackerService::EVENT_KEYWORD_SEARCH,
                    'keyword'               => trim($kwy),
                    'search_result_count'   => $data['total']
                ]);
                
            }
            return $this->showJson($data);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 搜索用户
     * @return bool
     * @throws Throwable
     * @author xiongba
     */
    public function userAction()
    {
        $kwy = $_POST['kwy'] ?? null;
        $kwy = strip_tags($kwy);
        if (mb_strlen($kwy)<2) {
            return $this->errorJson('至少两位搜索关键字');
        }
        if(preg_match('/[\xf0-\xf7].{3}/', $kwy)){ //过滤Emoji表情
            return $this->errorJson('不支持的关键字信息');
        }
        $member = request()->getMember();
        \helper\Util::PanicFrequency($member->uid, 10, 60, '1分钟内只能搜索10次');
        $service = new \service\SearchService();
        try {
            $data = $service->searchUser($kwy, $member);
            return $this->showJson($data);
        } catch (Throwable $e) {
            $this->errLog($e->getMessage());
            return $this->errorJson('关键字分析错误');
        }
    }

}