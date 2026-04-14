<?php

use helper\QueryHelper;
use service\OriginalService;

class OriginalController extends BaseController
{
    /**
     * 页面数据
     * @return void
     */
    public function homeAction(){

        $data['tab'] = [
            [
                'key'=>'type',
                'name'=>'影片类型',
                'items'=>OriginalTagsModel::getTagsByCate('type')
            ],
            [
                'key'=>'plot',
                'name'=>'剧情类型',
                'items'=>OriginalTagsModel::getTagsByCate('plot')
            ],
            [
                'key'=>'area',
                'name'=>'地区',
                'items'=>OriginalTagsModel::getTagsByCate('area')
            ],
            [
                'key'=>'lgbt',
                'name'=>'LGBTQ+',
                'items'=>OriginalTagsModel::getTagsByCate('lgbt')
            ]
        ];
        $data['sort'] = [
            [
                'key'=>'see',
                'name'=>'正在看',
            ],
            [
                'key'=>'hot',
                'name'=>'最热',
            ],
            [
                'key'=>'recommend',
                'name'=>'推荐',
            ],
            [
                'key'=>'new',
                'name'=>'最新',
            ],
            [
                'key'=>'sale',
                'name'=>'畅销',
            ],
            [
                'key'=>'rand',
                'name'=>'随机',
            ]
        ];

        return $this->showJson($data);

    }

    //原创列表
    public function listAction(){
        try {
            $sort = $this->post['sort'] ?? 'new';
            $tab = $this->post['tab'] ?? 'type';
            $kwy = $this->post['kwy'] ?? '';
            $service =  new OriginalService;
            $data = $service->getList($tab,$kwy,$sort);
            return $this->showJson($data);
        }catch (Throwable $exception){
            return $this->errorJson($exception->getMessage());
        }
    }

    /**
     * 搜索
     * @return bool|null
     */
    public function searchAction(){
        $kwy = $this->post['kwy'] ?? '';
        $service =  new OriginalService;
        $data = $service->getList('search',$kwy);
        return $this->showJson($data);
    }
    /**
     * 按标签搜索
     * @return bool|null
     */
    public function list_tagAction(){
        $sort = $this->post['sort'] ?? 'new';
        $type  = $this->post['type']??'';
        if($type == 'newest'){
            $sort = 'new';
        }
        if($type == 'hottest'){
            $sort = 'hot';
        }
        $kwy = $this->post['kwy'] ?? '';
        $service =  new OriginalService;
        $data = $service->getList('tag',$kwy,$sort);
        return $this->showJson($data);
    }


    /**
     * 详情
     * @return bool|null
     */
    public function detailAction(){
        $id = $this->post['id'] ?? 0;
        $selected = $this->post['selected'] ?? 1;
        $service  =  new OriginalService;
        try {
            $detail =  $service->getDetail($id,$selected);
            $data['detail'] =  $detail;
            $data['recommend'] =  $service->getRecommendByTags($detail['tags'],$id);
            $data['ads'] =  \service\AdService::getADsByPosition(AdsModel::POS_ORIGINAL_BANNER);
            return $this->showJson($data);
        }catch (Throwable $e){
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 点赞
     * @return bool|null
     */
    public function likeAction(){
        try {
            $id = (int)$this->post['id'];
            $aff = (int)$this->member['aff'];
            if (request()->getMember()->isBan()) {
                throw new Exception('您已被禁言');
            }
            list($flag, $msg, $is_like) = (new OriginalService())->likeOriginal($aff, $id);
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
        $aff = (int)$this->member['aff'];
        list($page,$limit) = QueryHelper::pageLimit();
        $data = OriginalUserLikeModel::listLikeOriginal($aff,$page,$limit);
        return $this->showJson($data);
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
            $service = new OriginalService();

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
        $id = (int)$this->post['id'];
        $member = request()->getMember();
        $data = OriginalService::listCommentsByPostId($member,$id);
        return $this->showJson($data);
    }

    /**
     * 点赞/取消点赞 评论
     * @return bool
     */
    public function like_commentAction()
    {
        try {
            $id = (int)$this->post['id'];
            $comment_id = (int)$this->post['comment_id'] ??0;
            $aff = (int)$this->member['aff'];
            if (request()->getMember()->isBan()) {
                throw new Exception('您已被禁言');
            }
            list($flag, $msg, $is_like) = (new OriginalService())->likeComment($aff, $id, $comment_id);
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
            $id = (int)$this->post['id'];
            if (request()->getMember()->isBan()) {
                throw new Exception('您已被禁言');
            }
            $data = OriginalService::buyOriginal($id);
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
        $member = request()->getMember();
        $uid = $member->uid;
        list($page, $limit) = QueryHelper::pageLimit();
        $data = OriginalPayModel::getUserBuyData($uid, $page, $limit);
        return $this->showJson($data);
    }


    /**
     * 提交观看记录
     * @return bool
     */
    public function watchingAction()
    {
        $this->initMember();//入口不处理验证用户 这里单独处理
        $id = $this->post['id'] ?? '';
        OriginalModel::where('id',$id)->increment('play_count');
        return $this->successMsg('操作成功');
    }

}
