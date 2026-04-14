<?php

use helper\Util;
use service\ApiLiveService;
use helper\QueryHelper;
use helper\Validator;


class LiveController extends BaseController
{
    public function navAction()
    {
        try {

            $service = new ApiLiveService();
            $list = $service->list_nav();
            return $this->showJson($list);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function recAction()
    {
        try {
            list($page, $limit) = QueryHelper::pageLimit();
            $service = new ApiLiveService();
            $list = $service->rec_live($page, $limit);
            return $this->showJson($list);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function indexAction()
    {
        try {
            $validator = Validator::make($this->post, [
                'id' => 'required|numeric|min:1'
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $id = (int)$this->post['id'];
            list($page, $limit) = QueryHelper::pageLimit();

            $service = new ApiLiveService();
            $list = $service->list_live($id, $page, $limit);
            return $this->showJson($list);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function detailAction()
    {
        try {
            $validator = Validator::make($this->post, [
                'id' => 'required|numeric|min:1'
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $id = (int)$this->post['id'];
            $member = request()->getMember();
            $service = new ApiLiveService();
            $data = $service->detail($member, $id);
            return $this->showJson($data);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function recommendAction()
    {
        try {
            $validator = Validator::make($this->post, [
                'id' => 'required|numeric|min:1'
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $id = (int)$this->post['id'];
            $member = $this->member;
            list($page, $limit) = QueryHelper::pageLimit();

            $service = new ApiLiveService();
            $data = $service->recommend($member, $id, $page, $limit);
            return $this->showJson($data);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    //点赞
    public function likeAction(){
        try {
            $validator = Validator::make($this->post, [
                'id' => 'required|numeric|min:1', //直播ID
            ]);
            if ($validator->fail($msg)){
                $this->errorJson($msg);
            }
            $id = $this->post['id'];
            $member = request()->getMember();
            Util::PanicFrequency($member->aff . ':live:like:' . $id, 1, 10);
            test_assert(!$member->isBan(), '你已被禁言');
            $service = new ApiLiveService();
            $result = $service->like($member, $id);
            $this->showJson($result);
        }catch (Throwable $e){
            $this->errorJson($e->getMessage());
        }
    }

    //收藏
    public function favoriteAction(){
        try {
            $validator = Validator::make($this->post, [
                'id' => 'required|numeric|min:1', //直播ID
            ]);
            if ($validator->fail($msg)){
                $this->errorJson($msg);
            }
            $id = $this->post['id'];
            $member = request()->getMember();
            Util::PanicFrequency($member->aff . ':live:favorite:' . $id, 1, 10);
            test_assert(!$member->isBan(), '你已被禁言');
            test_assert($member->is_reg, '仅注册用户才能收藏');
            $service = new ApiLiveService();
            $result = $service->favorite($member, $id);
            $this->showJson($result);
        }catch (Throwable $e){
            $this->errorJson($e->getMessage(), $e->getCode());
        }
    }

    //收藏列表
    public function list_favoriteAction(){
        try {
            $member = request()->getMember();
            $service = new ApiLiveService();
            list($page, $limit) = QueryHelper::pageLimit();
            $list = $service->listFavorite($member, $page, $limit);
            return $this->listJson($list);
        }catch (\Throwable $e){
            return $this->errorJson($e->getMessage());
        }
    }

    public function buyAction()
    {
        try {
            $validator = Validator::make($this->post, [
                'id' => 'required|numeric|min:1',//直播ID
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $live_id = (int)$this->post['id'];
            $member =request()->getMember();

            $service = new ApiLiveService();
            $rs = $service->buy($member, $live_id);
            return $this->showJson($rs);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage(), $e->getCode());
        }
    }

    public function list_buyAction()
    {
        try {
            $member = request()->getMember();
            list($page, $limit) = QueryHelper::pageLimit();

            $service = new ApiLiveService();
            $data = $service->list_buy($member, $page, $limit);
            return $this->showJson($data);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function searchAction()
    {
        try {
            $validator = Validator::make($this->post, [
                'word' => 'required'
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $word = trim($this->post['word']);
            $prohibit = setting('prohibit_search', '');
            $prohibit_words = array_filter(array_unique(explode(",", $prohibit)));
            foreach ($prohibit_words as $v) {
                if (strpos($word, $v) !== false) {
                    return $this->showJson(collect([]));
                }
            }

            $member = request()->getMember();
            list($page, $limit) = QueryHelper::pageLimit();

            $service = new ApiLiveService();
            $list = $service->search($member, $word, $page, $limit);
            return $this->showJson($list);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    // 获取评论列表
    public function comment_listAction()
    {
        try {
            $validator = Validator::make($this->post, [
                'id' => 'required|numeric|min:1',//直播ID
            ]);
            if ($validator->fail($msg)) {
                return $this->errorJson($msg);
            }
            $member = request()->getMember();
            $live_id = (int)$this->post['id'];
            list($page, $limit) = QueryHelper::pageLimit();
            $service = new ApiLiveService();
            $data = $service->listCommentsByLiveId($member, $live_id, $page, $limit);
            return $this->listJson($data);
        } catch (\Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    // 评论详情分页
    public function comments_replyAction()
    {
        try {
            $validator = Validator::make($this->post, [
                'comment_id' => 'required|numeric|min:1',//评论ID
            ]);
            if ($validator->fail($msg)) {
                return $this->errorJson($msg);
            }
            $member = request()->getMember();
            $commentId = (int)$this->post['comment_id'];
            list($page, $limit) = QueryHelper::pageLimit();
            $service = new ApiLiveService();
            $data = $service->listCommentsByCommentId($member, $commentId, $page, $limit);
            return $this->listJson($data);
        } catch (\Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    // 发布评论
    public function commentAction()
    {
        try {
            $liveId = isset($this->post['live_id']) ? (int)$this->post['live_id'] : 0;
            $commentId = isset($this->post['comment_id']) ? (int)$this->post['comment_id'] : 0;
            $content = $this->post['content'] ?? '';
            $cityName = ($this->position['province'].$this->position['city']) ?: '火星';
            $member =request()->getMember();
            if (!$liveId && !$commentId) {
                throw new Exception('直播ID或者评论ID至少存在一个');
            }
            if ($member->isBan()) {
                return $this->errorJson('触犯禁言规则，禁止评论,联系管理员～');
            }
            //1分钟5条
            Util::PanicFrequency($member->aff,5,60);
            if (mb_strlen($content) < 2) {
                return $this->errorJson('评论内容至少两个字符');
            }
            if (mb_strlen($content) > 50) {
                return $this->errorJson('最多可评论50字');
            }
            $key = 'day:audio:comment:num:' . date('Ymd') . $member->aff;
            $commentNum = redis()->get($key);
            $commentNum = $commentNum > 0 ? $commentNum : 0;
            $commentNum = $commentNum + 1;
            $comCommentNum = 30;
            $vipCommentNum = 100;
            if ($member->is_vip) {
                if ($commentNum > $vipCommentNum) {
                    return $this->errorJson('VIP限制每日评论次数为' . $vipCommentNum . '次');
                }
            } else {
                if ($commentNum > $comCommentNum) {
                    return $this->errorJson('普通用户限制每日评论次数为' . $comCommentNum . '次');
                }
            }
//            if (!PostCommentKeywordModel::filterChinese($content)) {
//                return $this->errorJson('触犯禁言规则#1，禁止评论,联系管理员～');
//            }
//            if (!PostCommentKeywordModel::filterUrl($content) || !PostCommentKeywordModel::filterUrl2($content)) {
//                return $this->errorJson('触犯禁言规则#2，禁止评论,联系管理员～');
//            }
//            if (!PostCommentKeywordModel::filterFont($content)) {
//                return $this->errorJson('触犯禁言规则#3，禁止评论,联系管理员～');
//            }
//            if (!PostCommentKeywordModel::filterStrNumber($content)) {
//                return $this->errorJson('触犯禁言规则#4，禁止评论,联系管理员～');
//            }
//            if (!PostCommentKeywordModel::filterKeyword($content)) {
//                return $this->errorJson('触犯禁言规则#5，禁止评论,联系管理员～');
//            }
            $service = new ApiLiveService();
            if ($commentId > 0) {
                $service->createComComment($member, $commentId, $content, $cityName);
            } else {
                $service->createPostComment($member, $liveId, $content, $cityName);
            }
            return $this->successMsg('评论成功，请耐心等待审核');
        } catch (\Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function rewardAction()
    {
        try {
            $validator = Validator::make($this->post, [
                'id'    => 'required|numeric|min:1', //直播ID
                'coins' => 'required|numeric|min:1', //金币数
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $id = (int)$this->post['id'];
            $coins = (int)$this->post['coins'];
            $member = request()->getMember();

            $service = new ApiLiveService();
            $service->reward($member, $id, $coins);
            return $this->successMsg('打赏成功');
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage(), $e->getCode());
        }
    }
}