<?php

use helper\QueryHelper;
use helper\Util;
use helper\Validator;
use service\PornGameService;

/**
 * 黄游相关接口
 *
 * Class PornController
 */
class PorngameController extends BaseController
{

    public function constructAction(){
        try {
            $member = request()->getMember();
            list($page, $limit) = QueryHelper::pageLimit();

            $service = new PornGameService();
            $result = $service->construct($member, $page, $limit);
            $this->showJson($result);
        }catch (Throwable $e){
            $this->errorJson($e->getMessage());
        }
    }

    //点赞
    public function likeAction(){
        try {
            $validator = Validator::make($this->post, [
                'id' => 'required|numeric|min:1', //ID
            ]);
            if ($validator->fail($msg)){
                $this->errorJson($msg);
            }
            $id = $this->post['id'];
            $member = request()->getMember();
            Util::PanicFrequency($member->aff . ':porngame:like:' . $id, 1, 10);
            test_assert(!$member->isBan(), '你已被禁言');
            $service = new PornGameService();
            $result = $service->like($member, $id);
            $this->showJson($result);
        }catch (Throwable $e){
            $this->errorJson($e->getMessage());
        }
    }

    //点赞列表
    public function list_likeAction(){
        try {
            $member = request()->getMember();
            $service = new PornGameService();
            list($page, $limit) = QueryHelper::pageLimit();
            $list = $service->listLike($member, $page, $limit);
            return $this->listJson($list);
        }catch (\Throwable $e){
            return $this->errorJson($e->getMessage());
        }
    }

    public function listAction(){
        try {
            $id = $this->post['id'] ?? 0;
            $member = request()->getMember();
            if ($member->is_vip){
                $sort = $this->post['sort'] ?? 'new';
            }else{
                $sort = $this->post['sort'] ?? 'hot';
            }
            list($page, $limit) = QueryHelper::pageLimit();
            $service = new PornGameService();
            $result = $service->list($member, $id, $sort, $page, $limit);
            $this->listJson($result);
        }catch (Throwable $e){
            $this->errorJson($e->getMessage());
        }
    }

    public function detailAction(){
        try {
            $validator = Validator::make($this->post, [
                'id' => 'required|numeric|min:1', //黄游ID
            ]);
            if ($validator->fail($msg)){
                $this->errorJson($msg);
            }
            $id = $this->post['id'];
            $member = request()->getMember();

            $service = new PornGameService();
            $result = $service->detail($member, $id);
            $this->showJson($result);
        }catch (Throwable $e){
            $this->errorJson($e->getMessage());
        }
    }

    // 获取评论列表
    public function comment_listAction()
    {
        try {
            $validator = Validator::make($this->post, [
                'id' => 'required|numeric|min:1',//黄游ID
            ]);
            if ($validator->fail($msg)) {
                return $this->errorJson($msg);
            }
            $member = request()->getMember();
            $porn_id = (int)$this->post['id'];
            list($page, $limit) = QueryHelper::pageLimit();
            $service = new PornGameService();
            $data = $service->listCommentsByPornId($member, $porn_id, $page, $limit);
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
            $service = new PornGameService();
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
            $porn_id = isset($this->post['porn_id']) ? (int)$this->post['porn_id'] : 0;
            $commentId = isset($this->post['comment_id']) ? (int)$this->post['comment_id'] : 0;
            $content = $this->post['content'] ?? '';
            $cityName = ($this->position['province'].$this->position['city']) ?: '火星';
            $member =request()->getMember();
            if (!$porn_id && !$commentId) {
                throw new Exception('黄游ID或者评论ID至少存在一个');
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
            $key = 'day:porn:comment:num:' . date('Ymd') . $member->aff;
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
            $service = new PornGameService();
            if ($commentId > 0) {
                $service->createComComment($member, $commentId, $content, $cityName);
            } else {
                $service->createPostComment($member, $porn_id, $content, $cityName);
            }
            return $this->successMsg('评论成功，请耐心等待审核');
        } catch (\Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function buyAction(){
        try {
            $validator = Validator::make($this->post, [
                'id' => 'required|numeric|min:1',//黄游ID
                'type' => 'required|enum:1,2',//支付方式 1 次数 2 金币
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $porn_id = (int)$this->post['id'];
            $type = (int)$this->post['type'];
            $member =request()->getMember()->refresh();

            $service = new PornGameService();
            $service->buy($member, $porn_id, $type);
            return $this->successMsg('购买成功');
        } catch (\Exception $e) {
            return $this->errorJson($e->getMessage(), $e->getCode());
        }
    }

    public function list_buyAction(){
        try {
            $member = request()->getMember();
            list($page, $limit) = QueryHelper::pageLimit();
            $service = new PornGameService();
            $res = $service->listBuy($member, $page, $limit);
            return $this->listJson($res);
        } catch (\Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    // 搜索
    public function searchAction()
    {
        try {
            $validator = Validator::make($this->post, [
                'word' => 'required',
            ]);
            if ($validator->fail($msg)) {
                throw new Exception($msg);
            }
            $word = trim($this->post['word']);
            $service = new PornGameService();
            list($page, $limit) = QueryHelper::pageLimit();
            $list = $service->listSearch($word, $page, $limit);
            return $this->listJson($list);
        } catch (\Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    //标签列表
    public function tagsListAction()
    {
        try {
            $validator = Validator::make($this->post, [
                'tag' => 'required',
                'sort' => 'required'
            ]);
            if ($validator->fail($msg)) {
                throw new Exception($msg);
            }
            $tag = trim($this->post['tag']);
            $sort = trim($this->post['sort']);
            $service = new PornGameService();
            list($page, $limit) = QueryHelper::pageLimit();
            $list = $service->listTag($tag, $sort, $page, $limit);
            return $this->listJson($list);
        } catch (\Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }
}