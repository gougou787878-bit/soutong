<?php

use helper\Util;
use helper\Validator;
use helper\QueryHelper;
use service\AdService;
use service\AiService;

class AiController extends BaseController
{

    public function ai_navAction(){
        try {
            $service = new AiService();
            $list = $service->ai_nav();
            $ads = AdService::getADsByPosition(AdsModel::POSITION_AI_HL_BANNER);

            return $this->listJson($list, ['ads' => $ads]);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function pre_stripAction()
    {
        try {
            $member = request()->getMember()->refresh();
            $service = new AiService();
            $res = $service->getStripPreData($member);
            return $this->showJson($res);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function stripAction()
    {
        try {
            $validator = Validator::make($this->post, [
                'thumb'   => 'required',
                'thumb_w' => 'required',
                'thumb_h' => 'required',
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $thumb = trim($this->post['thumb']);
            $thumb_w = (int)$this->post['thumb_w'];
            $thumb_h = (int)$this->post['thumb_h'];
            $type = (int)$this->post['type'] ?? 0;
            $member = request()->getMember()->refresh();
            Util::PanicFrequency($member->aff,1,10);
            $service = new AiService();
            $service->strip($member, $thumb, $thumb_w, $thumb_h);
            return $this->successMsg('上传成功,等待处理');
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage(), $e->getCode());
        }
    }

    public function my_stripAction(){
        try {
            $validator = Validator::make($this->post, [
                'status' => 'required|numeric',
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $status = $this->post['status'];
            $member = request()->getMember();
            list($page, $limit) = QueryHelper::pageLimit();
            $service = new AiService();
            $data = $service->list_my_strip($member, $status, $page, $limit);
            return $this->listJson($data);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }
    //换头导航
    public function list_face_cateAction(){
        try {
            $service = new AiService();
            $data = $service->list_face_nav();
            return $this->listJson($data);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    //换头素材列表
    public function list_face_materialAction()
    {
        try {
            $validator = Validator::make($this->post, [
                'search_id' => 'required|numeric|min:1',
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $id = (int)$this->post['cate_id'] ?? 0;
            $searchId = (int)$this->post['search_id'];
            $member = request()->getMember();
            list($page, $limit) = QueryHelper::pageLimit();
            $service = new AiService();
            $data = $service->list_face_material($member, $id, $page, $limit,$searchId);
            $ads = [];
            if ($page == 1){
                $ads = AdService::getADsByPosition(AdsModel::POSITION_AI_HL_BANNER);
            }
          
            return $this->listJson($data, ['ads' => $ads]);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function pre_faceAction(){
        try {
            $id = $this->post['id'] ?? 0;
            $member = request()->getMember()->refresh();
            $service = new AiService();
            $res = $service->getFacePreData($member, $id);
            return $this->showJson($res);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function change_faceAction()
    {
        try {
            $validator = Validator::make($this->post, [
                'material_id' => 'required|numeric|min:1',
                'thumb'       => 'required',
                'thumb_w'     => 'required',
                'thumb_h'     => 'required',
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $id = (int)$this->post['material_id'];
            $thumb = trim($this->post['thumb']);
            $thumb_w = (int)$this->post['thumb_w'];
            $thumb_h = (int)$this->post['thumb_h'];
            $type = (int)$this->post['type'] ?? 0;
            $member = request()->getMember()->refresh();
            Util::PanicFrequency($member->aff,1,10);
            $service = new AiService();
            $service->change_face($member, $id, $type, $thumb, $thumb_w, $thumb_h);
            return $this->successMsg('上传成功,等待处理');
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage(), $e->getCode());
        }
    }

    public function customize_faceAction()
    {
        try {
            $validator = Validator::make($this->post, [
                'ground'   => 'required',
                'ground_w' => 'required',
                'ground_h' => 'required',
                'thumb'    => 'required',
                'thumb_w'  => 'required',
                'thumb_h'  => 'required',
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $ground = trim($this->post['ground']);
            $ground_w = (int)$this->post['ground_w'];
            $ground_h = (int)$this->post['ground_h'];
            $thumb = trim($this->post['thumb']);
            $thumb_w = (int)$this->post['thumb_w'];
            $thumb_h = (int)$this->post['thumb_h'];
            $type = (int)$this->post['type'] ?? 0;
            $member = request()->getMember()->refresh();
            Util::PanicFrequency($member->aff,1,10);
            $service = new AiService();
            $service->customize_face($member, $type, $ground, $ground_w, $ground_h, $thumb, $thumb_w, $thumb_h);
            return $this->successMsg('上传成功,等待处理');
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage(), $e->getCode());
        }
    }

    public function my_faceAction()
    {
        try {
            $validator = Validator::make($this->post, [
                'status' => 'required|numeric',
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $status = $this->post['status'];
            list($page, $limit) = QueryHelper::pageLimit();
            $member = request()->getMember();
            $service = new AiService();
            $data = $service->list_my_face($member, $status, $page, $limit);
            return $this->listJson($data);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

        // 发布评论
    public function commentnewAction()
    {
        try {
            $postId = (int)$this->post['material_id'] ?? 0;
            $content = $this->post['content'] ?? '';
            $commentId = (int)$this->post['comment_id'] ?? 0;
            $cityname = ($this->position['province'].$this->position['city']) ?: '火星';
            $member =request()->getMember();
            // dd($postId ,$content,$commentId, $cityname,$member);
            if (!$postId && !$commentId) {
                throw new Exception('素材或者评论ID至少得存在一个');
            }
            //1分钟5条
            \helper\Util::PanicFrequency($member->aff,5,60);
            if (mb_strlen($content) < 2) {
                return $this->errorJson('评论内容至少两个字符');
            }
            if ($member->isBan()) {
                return $this->errorJson('触犯禁言规则，禁止评论,联系管理员～');
            }
            if (!$member->is_vip) {
                return $this->errorJson('充值VIP才能评论哟～～，赶快进入VIP解锁更多姿势');
            }
            if (mb_strlen($content) > 50) {
                return $this->errorJson('最多可评论50字');
            }
            $key = 'day:material:comment:num:' . date('Ymd') . $this->member['aff'];
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
//            if (!PostCommentKeywordModel::filterStrNumber($content)) {
//                return $this->errorJson('触犯禁言规则#4，禁止评论,联系管理员～');
//            }
            if (!PostCommentKeywordModel::filterKeyword($content)) {
                return $this->errorJson('触犯禁言规则#5，禁止评论,联系管理员～');
            }
            $service = new AiService();
            if ($commentId > 0) {
                $service->createComComment($member, $commentId, $content, $cityname);
            } else {
                $service->createPostCommentNew($member, $postId, $content, $cityname);
            }
            return $this->successMsg('评论成功，请耐心等待审核'); 
              
        } catch (\Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }


    /**
     * 素材收藏/点赞 评论点赞
     * @return bool
     */
    public function like_commentAction()
    {
        try {
            $id = (int)$this->post['id'];
            $type = (int)$this->post['type'];
            $action_type = (int)$this->post['action_type'];
            $member = request()->getMember();
            if ($member->isBan()) {
                throw new Exception('您已被禁言');
            }
            $res = (new AiService())->likeComment($member, $id, $type, $action_type);
            return $this->showJson($res);
        } catch (\Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    //评论列表
    public function list_commentsnewAction(){
        try {
            $validator = Validator::make($this->post, [
                'id' => 'required|numeric|min:1',//帖子ID
            ]);
            if ($validator->fail($msg)) {
                return $this->errorJson($msg);
            }
            $member = request()->getMember();
            $postId = (int)$this->post['id'];
            $service = new AiService();
            $data = $service->listCommentsByPostIdNew($member,$postId);
            return $this->listJson($data);
        } catch (\Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }
    
    // 评论详情分页
    public function commentsAction()
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
            $service = new AiService();
            $data = $service->listCommentsByCommentId($member,$commentId, $page, $limit);
            return $this->showJson(['list' => $data]);
        } catch (\Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }

        /**
     * 我的喜欢
     * @return bool
     */
    public function my_likingAction()
    {
        try {
            $member = request()->getMember();
            $uid = $member->uid;
            list($page, $limit) = QueryHelper::pageLimit();
            $data = FaceMaterialUserLikeModel::getUserData($uid, $page, $limit);
            return $this->listJson($data);
        }catch (Throwable $e){
            return $this->errorJson($e->getMessage());
        }
    }

}