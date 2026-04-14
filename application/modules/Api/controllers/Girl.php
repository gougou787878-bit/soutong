<?php

use service\CommunityService;
use service\GirlService;
use service\AdService;
use helper\QueryHelper;
use helper\Validator;


class GirlController extends BaseController
{

    /**
     * 城市列表
     * @return bool|void
     */
    public function areaAction()
    {
        $hot_city = \service\GirlService::getHotAreaDataList();
        array_unshift($hot_city, ['id' => 0, 'areaname' => '全国']);
        try {
            $return['hot_city'] = $hot_city;
            $return['all_city'] = \service\GirlService::getAreaDataList();
            return $this->showJson($return);
        } catch (\Throwable $e) {
            $this->errorJson($e->getMessage());
        }
    }
    /**
     * 约炮首页配置数据 推荐
     * @return bool
     */
    public function homeAction()
    {
        $type = $this->post['type'] ?? 'home';
        $member = request()->getMember();

        $hotTopic =  GirlTopicModel::listHotTopics();//默认取4-6个
        $ads = AdService::getADsByPosition(AdsModel::POS_GIRL_BANNER);
        $service = new CommunityService();
        //排行榜
        $praize_list = $service->listRank($member,'praize','day',3);
        $ranking[] = [
            'name' => '获赞达人',
            'type' => 'hz',
            'icon' => url_live('/new/xiao/20201014/2020101418031579387.png'),
            'item' => array_column($praize_list , 'avatar_url')
        ];
        $upload_list = $service->listRank($member,'upload','day',3);
        $ranking[] = [
            'name' => '上传达人',
            'type' => 'up',
            'icon' => url_live('/new/xiao/20201014/2020101418031579387.png'),
            'item' => array_column($upload_list , 'avatar_url')
        ];
        $profit_list = $service->listRank($member,'profit','day',3);
        $ranking[] = [
            'name' => '收益达人',
            'type' => 'income',
            'icon' => url_live('/new/xiao/20201014/2020101418031579387.png'),
            'item' => array_column($profit_list , 'avatar_url')
        ];
        $tab = [
            [
                'current' => true,//当前tab 默认展示
                'id'      => 1,
                'name'    => '推荐',
                'type'    => 'list',
                'api'     => 'api/girl/list_girl',
                'params'  => ['tag' => "recommend"],
            ],
            [
                'current' => false,//当前tab 默认展示
                'id'      => 2,
                'name'    => '最新',
                'type'    => 'list',
                'api'     => 'api/girl/list_girl',
                'params'  => ['tag' => "new"],
            ],
            [
                'current' => false,
                'id'      => 3,
                'name'    => '精华',
                'type'    => 'list',
                'api'     => 'api/girl/list_girl',
                'params'  => ['tag' => "choice"],
            ],
        ];
        if ($type !== 'home') {
            $tab = [
                [
                    'current' => false,
                    'id'      => 3,
                    'name'    => '话题',
                    'type'    => 'list',
                    'api'     => 'api/girl/follow_topic_post',
                    'params'  => ['tag' => "new"],
                ],
                [
                    'current' => false,
                    'id'      => 3,
                    'name'    => '用户',
                    'type'    => 'list',
                    'api'     => 'api/girl/follow_user_post',
                    'params'  => ['tag' => "new"],
                ],
            ];
        }
        return $this->showJson(['ads' => $ads, 'topic' => $hotTopic, 'tab' => $tab,'rank_list' => $ranking]);
    }

    /**
     * 所有话题列表
     * @return bool
     */
    public function topicsAction()
    {
        try {
            list($page, $limit) = QueryHelper::pageLimit();
            $res = GirlTopicModel::listTopics($page, $limit, request()->getMember());
            return $this->showJson($res);
        } catch (\Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 获取话题详情
     * @return bool
     */
    public function topic_detailAction()
    {
        try {
            $validator = Validator::make($this->post, [
                'topic_id' => 'required|numeric|min:1', //话题ID
            ]);
            if ($validator->fail($msg)) {
                throw new Exception($msg);
            }
            $id = (int)$this->post['topic_id'];
            $service = new GirlService();
            $res = $service->getTopicDetail($id, request()->getMember());
            return $this->showJson($res);
        } catch (\Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 根据话题获取帖子列表
     * @return bool
     */

    public function list_girl_by_topicAction()
    {
        try {
            $topicId = $this->post['topic_id'];
            $cate = $this->post['cate'];
            if (!in_array($cate, ['new', 'choice'])) {
                $cate = 'new';//default
            }
            $member = request()->getMember();
            $service = new GirlService();
            $data = $service->listTopicPost($member, $cate, $topicId);
            return $this->showJson($data);
        } catch (\Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 话题关注/取消关注
     * @return bool
     */
    public function toggle_follow_topicAction()
    {
        try {
            $topicId = (int)$this->post['topic_id'];
            $topicId = max($topicId, 1);
            $aff = (int)$this->member['aff'];
            list($flag, $msg, $follow) = (new GirlService())->toggleFollowTopic($aff, $topicId);
            return $this->showJson([
                'message'   => $msg,
                'is_follow' => $follow
            ]);
        } catch (\Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }


    // 约炮发帖
    public function postAction()
    {
        try {
            $validator = Validator::make($this->post, [
                'topic_id' => 'required|numeric|min:1', //话题ID
                'title'    => 'required|min:8', // 标题
                'content'  => 'nullable', //内容
                'medias'   => 'nullable', // 图片或者视频链接
                'contact'  =>'nullable',
                'cityname'  =>'nullable',
            ]);
            if ($validator->fail($msg)) {
                throw new Exception($msg);
            }

            $topicId = (int)$this->post['topic_id'];
            $title = trim($this->post['title']);
            $title = strip_tags($title);
            $content = $this->post['content'] ?? '';
            $content = strip_tags($content);
            $price = $this->post['price'] ?? 0;
            $contact = $this->post['contact'] ?? 0;
            $city = $this->post['cityname'] ?? '';

            $medias = $this->post['medias'] ?? '';
            $medias = htmlspecialchars_decode($medias);
            $medias = json_decode($medias, true);
            $medias = is_array($medias) ? $medias : [];
            $vipLevel = $this->member['vip_level'];

            if (empty($medias) && !$content) {
                throw new Exception('内容与媒体文件必须存在一个');
            }

            if ($vipLevel <= 0) {
                throw new Exception('只有VIP才可以发帖');
            }

            $member = request()->getMember();

            if ($member->isBan()) {
                throw new Exception('您已被禁言');
            }

            $blackList = MvBackUserModel::getBackUserList();
            if ($blackList && in_array($member->uid, $blackList)) {
                return $this->errorJson('你已经被禁止发帖，如有问题请咨询客服~~');
            }

            $categoryId = GirlModel::TYPE_MIX;

            //test_assert($this->member->isCreator(), '请先申请成为创作者');
            $ipstr = USER_IP;
            $cityName = ($this->position['province'].$this->position['city']) ?: '火星';
            if($city){
                $cityName = $city;
            }
            $service = new GirlService();
            $service->createPost($member, $topicId, $categoryId, $content, $title, $medias, $cityName, $ipstr, $price,$contact);
            return $this->successMsg('成功');
        } catch (\Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * @return bool
     * 获取帖子列表 最新 精华
     */
    public function list_girlAction()
    {
        try {
            $cate = $this->post['tag'] ?? 'recommend';
            //类型 new-最新帖  choice-精华贴 ,ftopic-关注话题帖子 follow-关注帖 target-指定用户贴
            if (!in_array($cate, ['new', 'choice','recommend'])) {
                $cate = 'recommend';//default
            }
            $topicId = $this->post['topic_id'] ?? 0;
            $aff = $this->post['aff'] ?? 0;//指定用户的字帖列表
            //关键子搜索
            $kwy = $this->post['kwy'] ?? '';
            if ($kwy){
                $kwy = strip_tags($kwy);
                if (mb_strlen($kwy) < 2) {
                    return $this->errorJson('至少两位搜索关键字');
                }
                if (preg_match('/[\xf0-\xf7].{3}/', $kwy)) { //过滤Emoji表情
                    return $this->errorJson('不支持[Emoji]表情');
                }
                $kwy = emoji_reject($kwy);
            }

            $member = request()->getMember();
            $service = new GirlService();
            if ($aff){
                $postData = $service->listMemberPost($member, $aff, $kwy);
            }else{
                $postData = $service->listTopicPost($member, $cate, $topicId);
            }

            return $this->showJson(['list' => $postData]);
        } catch (\Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 关注的话题的帖子列表
     * @return bool
     */
    public function follow_topic_girltAction()
    {

        $member = request()->getMember();
        $postData = (new GirlService())->listFollowPosts($member->aff, GirlModel::TYPE_FOLLOW_TOPIC,$member);
        return $this->showJson(['list' => $postData]);
    }

    /**
     * 关注的人的 约炮列表
     * @return bool
     */
    public function follow_user_girlAction()
    {
        $member = request()->getMember();
        $postData = (new GirlService())->listFollowPosts($member->aff, GirlModel::TYPE_FOLLOW_USER,$member);
        return $this->showJson(['list' => $postData]);

    }

    /**
     * 获取帖子详情
     * @return bool
     */
    public function detail_girlAction()
    {
        try {
            $id = (int)$this->post['id'];
            $member = request()->getMember();
            $res = (new GirlService())->getDetail($id, $member);
            return $this->showJson(['data' => $res]);
        } catch (\Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }


    /**
     * 点赞/取消点赞 帖子
     * @return bool
     */
    public function like_girlAction()
    {
        try {
            $id = (int)$this->post['id'];
            $aff = (int)$this->member['aff'];
            if (request()->getMember()->isBan()) {
                throw new Exception('您已被禁言');
            }
            list($flag, $msg, $is_like) = (new GirlService())->likeGirl($aff, $id);
            return $this->showJson(['message' => $msg, 'is_like' => $is_like]);
        } catch (\Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 点赞/取消点赞 评论
     * @return bool
     */
    public function like_commentAction()
    {
        try {
            $id = (int)$this->post['id'];
            $comment_id = (int)$this->post['comment_id']??0;
            $aff = (int)$this->member['aff'];
            if (request()->getMember()->isBan()) {
                throw new Exception('您已被禁言');
            }
            list($flag, $msg, $is_like) = (new GirlService())->likeComment($aff, $id, $comment_id);
            if(!$flag){
                throw new Exception($msg);
            }
            return $this->showJson(['message' => $msg, 'is_like' => $is_like]);
        } catch (\Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }


    /**
     * 收藏点赞的约炮列表
     * @return bool
     */
    public function favorit_girlAction()
    {
        try {
            $member = request()->getMember();
            $service = new GirlService();
            $postData = $service->listFavoritPost($member);
            return $this->showJson(['list' => $postData]);
        }catch (\Throwable $e){
            return $this->errorJson($e->getMessage());
        }

    }
    /**
     * 解锁帖子
     * @return bool
     */
    public function unlock_girlAction()
    {
        try {
            $validator = Validator::make($this->post, [
                'id' => 'required|numeric|min:1',//帖子ID
            ]);
            if ($validator->fail($msg)) {
                return $this->errorJson($msg);
            }
            $id = (int)$this->post['id'];
            $member = request()->getMember();
            if ($member->isBan()){
                return $this->errorJson('您已被禁言');
            }
            $key = sprintf('girl:unlock:%d:%d', $id, $member->aff);
            if (!\helper\Util::frequency($key, 1, 60)) {
                return $this->errorJson('操作太频繁~');
            }

            $service = new GirlService();
            $res =  $service->unlock_contact($member, $id);

            return $this->showJson($res);
        } catch (\Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }
    /**
     * 购买的帖子列表
     * @return bool
     */
    public function list_unlock_girlAction()
    {
        try {
            $member = request()->getMember();
            $service = new GirlService();
            $postData = $service->listBuyGirl($member);
            return $this->showJson(['post' => $postData]);
        }catch (\Throwable $e){
            return $this->errorJson($e->getMessage());
        }

    }
    //评论列表
    public function list_commentAction(){
        try {
            $validator = Validator::make($this->post, [
                'id' => 'required|numeric|min:1',//帖子ID
            ]);
            if ($validator->fail($msg)) {
                return $this->errorJson($msg);
            }
            $member = request()->getMember();
            $postId = (int)$this->post['id'];
            $service = new GirlService();
            $data = $service->listCommentsByPost($member,$postId);
            return $this->showJson(['list' => $data]);
        } catch (\Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }


    // 发布评论
    public function commentAction()
    {
        try {
            $postId = $this->post['id'] ?? 0;
            $content = $this->post['content'] ?? '';
            $commentId = $this->post['comment_id'] ?? 0;
            $cityname = ($this->position['province'].$this->position['city']) ?: '火星';
            $member =request()->getMember();
            if (!$postId && !$commentId) {
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
            if (!$member->is_vip) {
                return $this->errorJson('充值VIP才能评论哟～～，赶快进入VIP解锁更多姿势');
            }
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
//            if (!PostCommentKeywordModel::filterStrNumber($content)) {
//                return $this->errorJson('触犯禁言规则#4，禁止评论,联系管理员～');
//            }
            if (!PostCommentKeywordModel::filterKeyword($content)) {
                return $this->errorJson('触犯禁言规则#5，禁止评论,联系管理员～');
            }
            $service = new GirlService();
            if ($commentId > 0) {
                $service->createComComment($member, $commentId, $content, $cityname);
            } else {
                $service->createPostComment($member, $postId, $content, $cityname);
            }
            return $this->successMsg('评论成功，请耐心等待审核');
        } catch (\Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 我的发布的帖子 发布/审核/未通过
     * @return bool
     * 获取帖子列表 最新 精华
     */
    public function myAction()
    {
        try {

            $cate = $this->post['stat'] ?? 'release';//release 通过 verify 待审核 refuse 未通过
            $member = request()->getMember();
            $service = new GirlService();
            $where = [];
            $where[] = ['aff', '=', $member->aff];
            if ($cate == 'verify') {
                $where[] = ['status', '=', GirlModel::STATUS_WAIT];
            } elseif ($cate == 'refuse') {
                $where[] = ['status', '=', GirlModel::STATUS_UNPASS];
            }
            $postData = $service->listPosts($cate, $where, $member);

            return $this->showJson(['list' => $postData]);
        } catch (\Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 社区发布前信息
     * @return bool
     */
    public function pre_girl_dataAction()
    {
        try {

            $data = [];
            $data['rule'] =[
                [
                    'title'        => '搜同社区用户使用规则',
                    'descriptioin' => <<<DOC
1、禁止上传未成年、兽交、真实强奸、枪支、偷拍、侵害他人隐私等违规视频内容##
2、禁止在视频中添加个人联系方式、植入广告、水印##
3、视频中当事人均需18岁以上，且当事人均同意视频被上传分享##
4、允许用户为保护隐私，可对视频中人物面部等重要部分遮挡或打马赛克##
6、上传的视频将由平台进行审核，成功上传后，请注意查收反馈##
7、金币视频因可获得收益，为保护购买视频用户权益，上传视频后使用权归平台所有，特殊情况请联系平台客服##
8、上传视频中包含内容重复、画面模糊、带有水印等问题将无法通过审核
DOC

                ],
                [
                    'title'        => '视频内容上传规范',
                    'descriptioin' => <<<DOC
1、禁止上传未成年、兽交、真实强奸、枪支、偷拍、侵害他人隐私等违规视频内容##
2、禁止在视频中添加个人联系方式、植入广告、水印##
3、视频中当事人均需18岁以上，且当事人均同意视频被上传分享##
4、允许用户为保护隐私，可对视频中人物面部等重要部分遮挡或打马赛克##
6、上传的视频将由平台进行审核，成功上传后，请注意查收反馈##
8、金币视频因可获得收益，为保护购买视频用户权益，上传视频后使用权归平台所有，特殊情况请联系平台客服
DOC

                ],

            ];
            $data['topic'] = collect(GirlTopicModel::getAllTopic())->map(function (GirlTopicModel $item){
                if(is_null($item)){
                    return null;
                }
                return [
                    'topic_id'           => $item->id,
                    'topic_name'         => $item->name,
                    'topic_name_formate' => '#' . $item->name,
                ];
            })->filter()->values();
            return $this->showJson($data);
        } catch (\Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }
    /**
     * 搜索帖子
     * @return bool
     */
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
            $aff = $this->member['aff'];
            $service = new GirlService();
            list($page, $limit) = QueryHelper::pageLimit();
            $res = $service->listSearchPost($word, $aff, $page, $limit);
            return $this->showJson($res);
        } catch (\Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }

}