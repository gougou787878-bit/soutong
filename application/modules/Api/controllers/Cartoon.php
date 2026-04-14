<?php

use helper\QueryHelper;
use helper\Validator;
use service\CartoonService;

class CartoonController extends BaseController
{
    public function constructAction(){
        try {
            $member = request()->getMember();
            list($page, $limit) = QueryHelper::pageLimit();

            $service = new CartoonService();
            $result = $service->construct($member, $page, $limit);
            $this->showJson($result);
        }catch (Throwable $e){
            $this->errorJson($e->getMessage());
        }
    }

    //列表
    public function listAction(){
        try {
            $validator = Validator::make($this->post, [
                'id' => 'required|numeric|min:1', //ID
            ]);
            if ($validator->fail($msg)){
                return $this->errorJson($msg);
            }
            $id = $this->post['id'];
            $sort = $this->post['sort'] ?? 'like';
            $member = request()->getMember();
            list($page, $limit) = QueryHelper::pageLimit();
            $service = new CartoonService();
            $result = $service->getList($member, $id, $sort, $page, $limit);
            return $this->showJson($result);
        }catch (Throwable $exception){
            return $this->errorJson($exception->getMessage());
        }
    }

    /**
     * 搜索
     * @return bool|null
     */
    public function searchAction(){
        try {
            $validator = Validator::make($this->post, [
                'word' => 'required', //kwy
            ]);
            if ($validator->fail($msg)){
                $this->errorJson($msg);
            }
            $kwy = $this->post['word'];
            $member = request()->getMember();
            list($page, $limit) = QueryHelper::pageLimit();
            $service = new CartoonService();
            $result = $service->search($member, $kwy, $page, $limit);
            $this->showJson($result);
        }catch (Throwable $exception){
            return $this->errorJson($exception->getMessage());
        }
    }


    /**
     * 按标签搜索
     * @return bool|null
     */
    public function list_tagAction(){
        try {
            $validator = Validator::make($this->post, [
                'tag' => 'required', //tag
            ]);
            if ($validator->fail($msg)){
                $this->errorJson($msg);
            }
            $tag = $this->post['tag'];
            $sort = $this->post['sort'];
            $member = request()->getMember();
            list($page, $limit) = QueryHelper::pageLimit();
            $service = new CartoonService();
            $result = $service->tagList($member, $tag, $sort, $page, $limit);
            $this->showJson($result);
        }catch (Throwable $exception){
            return $this->errorJson($exception->getMessage());
        }
    }


    /**
     * 详情
     * @return bool|null
     */
    public function detailAction(){
        try {
            $validator = Validator::make($this->post, [
                'id' => 'required', //tag
            ]);
            if ($validator->fail($msg)){
                $this->errorJson($msg);
            }
    
            $id = $this->post['id'];
            $selected = $this->post['selected'] ?? 1;
            $member = request()->getMember();
    
            $service = new CartoonService();
            $detail = $service->getDetail($member, $id, $selected);
            $tags = $detail['tags'] ?? [];  // ✅ 防止未定义
    
            $data = [
                'detail'    => $detail,
                'recommend' => $service->getRecommendByTags($tags, $id),
                'ads'       => \service\AdService::getADsByPosition(AdsModel::POSITION_CARTOON_DETAIL),
            ];
    
            return $this->showJson($data);
        } catch (Throwable $exception) {
            return $this->errorJson($exception->getMessage());
        }
    }
    

    /**
     * 点赞
     * @return bool|null
     */
    public function likeAction(){
        try {
            $validator = Validator::make($this->post, [
                'id' => 'required', //tag
            ]);
            if ($validator->fail($msg)){
                $this->errorJson($msg);
            }
            $id = (int)$this->post['id'];
            $member = request()->getMember();
            if ($member->isBan()) {
                throw new Exception('您已被禁言');
            }
            $service = new CartoonService();
            list($flag, $msg, $is_like) = $service->like($member, $id);
            return $this->showJson(['message' => $msg, 'is_like' => $is_like]);
        } catch (\Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 我的收藏 点赞
     * @return bool|null
     */
    public function like_listAction(){
        try {
            $member = request()->getMember();
            list($page,$limit) = QueryHelper::pageLimit();
            $service = new CartoonService();
            $data = $service->list_like($member, $page,$limit);
            return $this->showJson($data);
        }catch (Throwable $exception){
            return $exception->getMessage();
        }
    }

    // 发布评论
    public function commentAction()
    {
        try {
            $id = (int)$this->post['id'] ?? 0;
            $content = $this->post['content'] ?? '';
            $cityname = ($this->position['province'].$this->position['city']) ?: '火星';
            $commentId = (int)$this->post['comment_id'] ?? 0;
            $member =request()->getMember();
            if (!$id && !$commentId) {
                throw new Exception('帖子或者评论ID至少得存在一个');
            }
            //1分钟5条
            \helper\Util::PanicFrequency($member->aff,5,60);
            if (mb_strlen($content) < 2) {
                return $this->errorJson('评论内容至少两个字符');
            }
            if ($member->isBan()) {
                return $this->errorJson('触犯禁言规则，禁止评论,联系管理员～');
            }
//            if (!$member->is_vip) {
//                return $this->errorJson('充值VIP才能评论哟～～，赶快进入VIP解锁更多姿势');
//            }
            if (mb_strlen($content) > 50) {
                return $this->errorJson('最多可评论50字');
            }
            $key = 'day:comment:num:' . date('Ymd') . $this->member['aff'];
            $commentNum = redis()->get($key);
            $commentNum = $commentNum > 0 ? $commentNum : 0;
            $commentNum = $commentNum + 1;
            $comCommentNum = 30;
            $vipCommentNum = 100;
            $vipLevel = $this->member['vip_level'];
            if ($vipLevel > 0) {
                if ($commentNum > $vipCommentNum) {
                    return $this->errorJson('VIP限制每日评论次数为' . $vipCommentNum . '次');
                }
            } else {
                if ($commentNum > $comCommentNum) {
                    return $this->errorJson('普通用户限制每日评论次数为' . $comCommentNum . '次');
                }
            }
            if (!PostCommentKeywordModel::filterChinese($content)) {
                return $this->errorJson('触犯禁言规则#1，禁止评论,联系管理员～');
            }
            if (!PostCommentKeywordModel::filterUrl($content) || !PostCommentKeywordModel::filterUrl2($content)) {
                return $this->errorJson('触犯禁言规则#2，禁止评论,联系管理员～');
            }
            if (!PostCommentKeywordModel::filterFont($content)) {
                return $this->errorJson('触犯禁言规则#3，禁止评论,联系管理员～');
            }
            if (!PostCommentKeywordModel::filterKeyword($content)) {
                return $this->errorJson('触犯禁言规则#5，禁止评论,联系管理员～');
            }
            $service = new CartoonService();

            if ($commentId > 0) {
                $service->createComComment($member, $commentId, $content, $cityname);
            } else {
                $service->createComment($member, $id, $content, $cityname);
            }

            return $this->successMsg('评论成功');
        } catch (\Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 评论列表
     */
    public function comment_listAction(){
        try {
            $validator = Validator::make($this->post, [
                'id' => 'required|numeric|min:1', //ID
            ]);
            if ($validator->fail($msg)){
                $this->errorJson($msg);
            }
            $id = (int)$this->post['id'];
            list($page,$limit) = QueryHelper::pageLimit();
            $member = request()->getMember();
            $service = new CartoonService();
            $data = $service->listCommentsByPostId($member, $id, $page,$limit);
            return $this->showJson($data);
        }catch (Throwable $exception){
            return $exception->getMessage();
        }
    }

    /**
     * 点赞/取消点赞 评论
     * @return bool
     */
    public function like_commentAction()
    {
        try {
            $validator = Validator::make($this->post, [
                'comment_id' => 'required|numeric|min:1', //ID
            ]);
            if ($validator->fail($msg)){
                $this->errorJson($msg);
            }
            $comment_id = (int)$this->post['comment_id'];
            $member = request()->getMember();
            if ($member->isBan()) {
                throw new Exception('您已被禁言');
            }
            $service = new CartoonService();
            list($msg, $is_like) = $service->likeComment($member, $comment_id);
            return $this->showJson(['message' => $msg, 'is_like' => $is_like]);
        } catch (\Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 购买
     * @return bool|null
     */
    public function buyAction(){
        try {
            $validator = Validator::make($this->post, [
                'id' => 'required|numeric|min:1', //ID
            ]);
            if ($validator->fail($msg)){
                $this->errorJson($msg);
            }
            $id = (int)$this->post['id'];
            $member = request()->getMember();
            if ($member->isBan()) {
                throw new Exception('您已被禁言');
            }
            $service = new CartoonService();
            $data = $service->buy($member, $id);
            return $this->showJson($data);
        } catch (\Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 我的购买
     * @return bool
     */
    public function my_buyAction()
    {
        try {
            $member = request()->getMember();
            list($page, $limit) = QueryHelper::pageLimit();
            $service = new CartoonService();
            $data = $service->list_buy($member, $page, $limit);
            return $this->showJson($data);
        }catch (Throwable $exception){
            return $exception->getMessage();
        }
    }

}
