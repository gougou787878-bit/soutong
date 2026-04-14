<?php

use service\CommunityService;
use service\AdService;
use helper\QueryHelper;
use helper\Validator;


class CommunityController extends BaseController
{


    /**
     * 社区首页配置数据 推荐
     * @return bool
     */
    public function homeAction()
    {
        $type = $this->post['type'] ?? 'home';
        $member = request()->getMember();
        $service = new CommunityService();
        $hotTopic = $service->getHotTopicList(); //默认取4-6个
        $ads = AdService::getADsByPosition(AdsModel::POS_COMMUNITY_BANNER);
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
                'api'     => 'api/community/listPost',
                'params'  => ['tag' => "recommend"],
            ],
            [
                'current' => false,//当前tab 默认展示
                'id'      => 2,
                'name'    => '最新',
                'type'    => 'list',
                'api'     => 'api/community/listPost',
                'params'  => ['tag' => "new"],
            ],
            [
                'current' => false,
                'id'      => 3,
                'name'    => '精华',
                'type'    => 'list',
                'api'     => 'api/community/listPost',
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
                    'api'     => 'api/community/follow_topic_post',
                    'params'  => ['tag' => "new"],
                ],
                [
                    'current' => false,
                    'id'      => 3,
                    'name'    => '用户',
                    'type'    => 'list',
                    'api'     => 'api/community/follow_user_post',
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
            $service = new CommunityService();
            list($page, $limit) = QueryHelper::pageLimit();
            $res = $service->listTopics($page, $limit, request()->getMember());
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
            $service = new CommunityService();
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

    public function list_post_by_topic_idAction()
    {
        try {
            $topicId = $this->post['topic_id'];
            $cate = $this->post['cate'];
            if (!in_array($cate, ['new', 'choice', 'recommend', 'video'])) {
                $cate = 'new';//default
            }
            $member = request()->getMember();
            $service = new CommunityService();
            $postData = $service->listTopicPost($member, $cate, $topicId);
            return $this->showJson(['post' => $postData]);
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
            $validator = Validator::make($this->post, [
                'topic_id' => 'required|numeric|min:1', //ID
            ]);
            if ($validator->fail($msg)){
                return $this->errorJson($msg);
            }
            $topicId = (int)$this->post['topic_id'];
            $aff = (int)$this->member['aff'];
            list($flag, $msg, $follow) = (new CommunityService())->toggleFollowTopic($aff, $topicId);
            return $this->showJson([
                'message'   => $msg,
                'is_follow' => $follow
            ]);
        } catch (\Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }


    // 社区发帖
    public function postAction()
    {
        try {
            $validator = Validator::make($this->post, [
                'topic_id' => 'required|numeric|min:1', //话题ID
                'title'    => 'required|min:8', // 标题
                'content'  => 'nullable', //内容
                'medias'   => 'nullable', // 图片或者视频链接
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

            $medias = $this->post['medias'] ?? '';
            //"medias":"[{\"media_url\":\"/storage/emulated/0/Movies/VID_20230615_20084946.mp4\",\"thumb_height\":\"720\",\"thumb_wi
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

            $categoryId = PostModel::TYPE_MIX;

            //test_assert($this->member->isCreator(), '请先申请成为创作者');
            $ipstr = USER_IP;
            $cityName = ($this->position['province'].$this->position['city']) ?: '火星';
            $service = new CommunityService();
            $service->createPost($member, $topicId, $categoryId, $content, $title, $medias, $cityName, $ipstr, $price);
            return $this->successMsg('成功');
        } catch (\Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * @return bool
     * 获取帖子列表 最新 精华
     */
    public function listPostAction()
    {
        try {
            if (isset($this->post['sort'])){
                $cate = $this->post['sort'];
            }else{
                $cate = $this->post['tag'] ?? 'recommend';
            }
            //类型 new-最新帖  choice-精华贴 ,ftopic-关注话题帖子 follow-关注帖 target-指定用户贴
            if (!in_array($cate, ['new', 'choice','recommend','video'])) {
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
            $service = new CommunityService();
            if ($aff){
                $postData = $service->listMemberPost($member, $aff, $kwy);
            }else{
                $postData = $service->listTopicPost($member, $cate, $topicId);
            }

            return $this->showJson(['post' => $postData]);
        } catch (\Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 关注的话题的帖子列表
     * @return bool
     */
    public function follow_topic_postAction()
    {

        $member = request()->getMember();
        $postData = (new CommunityService())->listFollowPosts($member, PostModel::TYPE_FOLLOW_TOPIC);
        return $this->showJson(['post' => $postData]);
    }

    /**
     * 关注的人的 帖子列表
     * @return bool
     */
    public function follow_user_postAction()
    {
        $member = request()->getMember();
        $postData = (new CommunityService())->listFollowPosts($member, PostModel::TYPE_FOLLOW_USER);
        return $this->showJson(['post' => $postData]);

    }

    /**
     * 获取帖子详情
     * @return bool
     */
    public function post_detail_by_idAction()
    {
        try {
            $postId = (int)$this->post['id'];
            $member = request()->getMember();
            $res = (new CommunityService())->getPostDetail($postId, $member);
            //远程广告
            $ads = AdService::getADsByPosition(AdsModel::POSITION_COMMUNITY_DETAIL);
            return $this->showJson(['data' => $res, 'ads' => $ads]);
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
            $service = new CommunityService();
            list($page, $limit) = QueryHelper::pageLimit();
            $res = $service->listSearchPost($word, $aff, $page, $limit);
            return $this->showJson($res);
        } catch (\Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 点赞/取消点赞 帖子
     * @return bool
     */
    public function like_postAction()
    {
        try {
            $id = (int)$this->post['id'];
            $aff = (int)$this->member['aff'];
            if (request()->getMember()->isBan()) {
                throw new Exception('您已被禁言');
            }
            list($flag, $msg, $is_like) = (new CommunityService())->likePost($aff, $id);
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
            $comment_id = (int)$this->post['comment_id'];
            $aff = (int)$this->member['aff'];
            if (request()->getMember()->isBan()) {
                throw new Exception('您已被禁言');
            }
            list($flag, $msg, $is_like) = (new CommunityService())->likeComment($aff, $id, $comment_id);
            return $this->showJson(['message' => $msg, 'is_like' => $is_like]);
        } catch (\Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 帖子 收藏/取消收藏
     * @return bool
     */
    public function toggle_favorit_postAction()
    {
        try {
            $id = (int)$this->post['id'];
            $aff = (int)$this->member['aff'];
            if (request()->getMember()->isBan()) {
                throw new Exception('您已被禁言');
            }
            list($flag, $msg, $favorite) = (new CommunityService())->togglefavoritePost($aff, $id);
            return $this->showJson([
                'message'     => $msg,
                'is_favorite' => $favorite
            ]);
        } catch (\Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 收藏点赞的 帖子列表
     * @return bool
     */
    public function favorit_postAction()
    {
        try {
            $member = request()->getMember();
            $service = new CommunityService();
            $postData = $service->listFavoritPost($member);
            return $this->showJson(['post' => $postData]);
        }catch (\Throwable $e){
            return $this->errorJson($e->getMessage());
        }

    }

    /**
     * 购买的帖子列表
     * @return bool
     */
    public function buy_postAction()
    {
        try {
            $member = request()->getMember();
            $service = new CommunityService();
            $postData = $service->listBuyPost($member);
            return $this->showJson(['post' => $postData]);
        }catch (\Throwable $e){
            return $this->errorJson($e->getMessage());
        }

    }


    /**
     * 解锁帖子
     * @return bool
     */
    public function unlock_postAction()
    {
        try {
            $validator = Validator::make($this->post, [
                'id' => 'required|numeric|min:1',//帖子ID
            ]);
            if ($validator->fail($msg)) {
                return $this->errorJson($msg);
            }
            $postId = (int)$this->post['id'];
            $member = request()->getMember();
            if ($member->isBan()){
                return $this->errorJson('您已被禁言');
            }
            $key = sprintf('post:unlock:%d:%d', $postId, $member->aff);
            if (!\helper\Util::frequency($key, 1, 60)) {
                return $this->errorJson('操作太频繁~');
            }

            $service = new CommunityService();
            $service->reward($member, $postId);
            return $this->successMsg('解锁成功');
        } catch (\Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 获取评论列表
     * @return bool
     */
    public function list_commentsAction()
    {
        try {
            $postId = (int)$this->post['id'];
            $service = new CommunityService();
            $data = $service->listCommentsByPostId($postId, request()->getMember());
            return $this->showJson(['list' => $data]);
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
            $service = new CommunityService();
            $data = $service->listCommentsByPostIdNew($member,$postId);
            return $this->showJson(['list' => $data]);
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
            $service = new CommunityService();
            $data = $service->listCommentsByCommentId($member,$commentId, $page, $limit);
            return $this->showJson(['list' => $data]);
        } catch (\Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 发布评论
     * @return bool
     */
    public function commentAction()
    {
        try {
            $member = request()->getMember();
            $postId = $this->post['post_id'] ?? 0;
            $content = $this->post['content'] ?? '';
            $content = strip_tags($content);
            $medias = $this->post['medias'] ?? '';//评论没有视频
            $aff = $this->member['aff'];
            $nickname = $this->member['nickname'];
            $cityname = ($this->position['province'].$this->position['city']) ?: '火星';
            $postId = (int)$postId;
            if (!$postId || mb_strlen($content) < 2) {
                return $this->errorJson('评论内容至少两个字符');
            } elseif ($member->isBan()) {
                return $this->errorJson('触犯禁言规则，禁止评论,联系管理员～');
            } elseif (!$this->member['vip_level']) {
                 return $this->errorJson('充值VIP才能评论哟～～，赶快进入VIP解锁更多姿势');
            } elseif (mb_strlen($content) > 50) {
                return $this->errorJson('评论内容不符合');
            }
            $key = 'day:comment:num:' . date('Ymd') . $this->member['aff'];
            $commentNum = redis()->get($key);
            $commentNum = $commentNum > 0 ? $commentNum : 0;
            $commentNum = $commentNum + 1;
            $comCommentNum = (int)setting('day_com_comment_num', 30);
            $vipCommentNum = (int)setting('day_vip_comment_num', 30);
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
            if (!\helper\Util::frequency('post:comment:' . $aff, 2, 60)) {
                 return $this->errorJson('评论太频繁~');
            }
            if(true){
//                if (!PostCommentKeywordModel::filterChinese($content)) {
//                    return $this->errorJson('触犯禁言规则#1，禁止评论,联系管理员～');
//                }
                if (!PostCommentKeywordModel::filterUrl($content) || !PostCommentKeywordModel::filterUrl2($content)) {
                    return $this->errorJson('触犯禁言规则#2，禁止评论,联系管理员～');
                }
                if (!PostCommentKeywordModel::filterFont($content)) {
                    return $this->errorJson('触犯禁言规则#3，禁止评论,联系管理员～');
                }
//                if (!PostCommentKeywordModel::filterStrNumber($content)) {
//                    return $this->errorJson('触犯禁言规则#4，禁止评论,联系管理员～');
//                }
                if (!PostCommentKeywordModel::filterKeyword($content)) {
                    return $this->errorJson('触犯禁言规则#5，禁止评论,联系管理员～');
                }
            }

            /** @var PostModel $post */
            $post = PostModel::queryBase()->find($postId);
            if (is_null($post)) {
                return $this->errorJson('查无帖子信息～');
            }
            (new CommunityService())->createPostComment($post, $member, $content, $cityname);
            redis()->set($key, $commentNum, 86400);
            return $this->successMsg('评论成功，请耐心等待审核');
        } catch (\Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    // 发布评论
    public function commentnewAction()
    {
        try {
            $postId = (int)($this->post['post_id'] ?? 0);
            $content = $this->post['content'] ?? '';
            $commentId = (int)($this->post['comment_id'] ?? 0);
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
            $service = new CommunityService();
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
     * 用户发贴收益列表
     * @return bool
     */
    public function post_income_listAction()
    {
        try {
            list($page, $limit) = QueryHelper::pageLimit();
            $service = new CommunityService();
            $data = $service->postIncomeList($page, $limit);
            return $this->showJson(['list'=>$data]);
        } catch (\Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 我的发布的帖子 发布/审核/未通过
     * @return bool
     * 获取帖子列表 最新 精华
     */
    public function my_postAction()
    {
        try {

            $cate = $this->post['stat'] ?? 'release';//release verify refuse
            $member = request()->getMember();
            $service = new CommunityService();
            $where = [];
            $where[] = ['aff', '=', $member->aff];
            if ($cate == 'verify') {
                $where[] = ['status', '=', PostModel::STATUS_WAIT];
            } elseif ($cate == 'refuse') {
                $where[] = ['status', '=', PostModel::STATUS_UNPASS];
            }
            $postData = $service->listPosts($cate, $where, $member);

            return $this->showJson(['post' => $postData]);
        } catch (\Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 社区发布前信息
     * @return bool
     */
    public function pre_post_dataAction()
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
            $data['topic'] = collect(PostTopicModel::getAllTopic())->map(function (PostTopicModel $item){
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

    public function rankAction(){
        try {
            $validator = Validator::make($this->post, [
                'type' => 'required|enum:praize,upload,profit',//获赞 上传 收益
                't_type' => 'required|enum:day,week,month',//日周月
            ]);
            if ($validator->fail($msg)) {
                return $this->errorJson($msg);
            }
            $rankBy = $this->post['type'];
            $rankTime = $this->post['t_type'];
            $member = request()->getMember();
            $service = new CommunityService();
            $data = $service->listRank($member,$rankBy,$rankTime,\MemberRankModel::RANK_NUM);
            return $this->showJson(['list' => $data]);
        } catch (\Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    //排行榜配置
    public function rankConfAction()
    {
        $member = request()->getMember();
        //排行榜
        $tab = [
            [
                'current' => true,
                'id'      => 1,
                'name'    => '获赞',
                'type'    => 'praize',
                'list'    => [
                    [
                        'current' => true,//当前tab 默认展示
                        'id'      => 1,
                        'name'    => '日榜',
                        'type'    => 'day',
                        'api'     => 'api/community/rank',
                        'params'  => ['type' => "praize",'t_type' => 'day'],
                    ],
                    [
                        'current' => false,//当前tab 默认展示
                        'id'      => 2,
                        'name'    => '周榜',
                        'type'    => 'week',
                        'api'     => 'api/community/rank',
                        'params'  => ['type' => "praize",'t_type' => 'week'],
                    ],
                    [
                        'current' => false,//当前tab 默认展示
                        'id'      => 3,
                        'name'    => '月榜',
                        'type'    => 'month',
                        'api'     => 'api/community/rank',
                        'params'  => ['type' => "praize",'t_type' => 'month'],
                    ]
                ]
            ],
            [
                'current' => false,
                'id'      => 2,
                'name'    => '上传',
                'type'    => 'upload',
                'list'    => [
                    [
                        'current' => false,//当前tab 默认展示
                        'id'      => 1,
                        'name'    => '日榜',
                        'type'    => 'day',
                        'api'     => 'api/community/rank',
                        'params'  => ['type' => "upload",'t_type' => 'day'],
                    ],
                    [
                        'current' => false,//当前tab 默认展示
                        'id'      => 2,
                        'name'    => '周榜',
                        'type'    => 'week',
                        'api'     => 'api/community/rank',
                        'params'  => ['type' => "upload",'t_type' => 'week'],
                    ],
                    [
                        'current' => false,//当前tab 默认展示
                        'id'      => 3,
                        'name'    => '月榜',
                        'type'    => 'month',
                        'api'     => 'api/community/rank',
                        'params'  => ['type' => "upload",'t_type' => 'month'],
                    ]
                ]
            ],
            [
                'current' => false,
                'id'      => 3,
                'name'    => '收益',
                'type'    => 'profit',
                'list'    => [
                    [
                        'current' => false,//当前tab 默认展示
                        'id'      => 1,
                        'name'    => '日榜',
                        'type'    => 'day',
                        'api'     => 'api/community/rank',
                        'params'  => ['type' => "profit",'t_type' => 'day'],
                    ],
                    [
                        'current' => false,//当前tab 默认展示
                        'id'      => 2,
                        'name'    => '周榜',
                        'type'    => 'week',
                        'api'     => 'api/community/rank',
                        'params'  => ['type' => "profit",'t_type' => 'week'],
                    ],
                    [
                        'current' => false,//当前tab 默认展示
                        'id'      => 3,
                        'name'    => '月榜',
                        'type'    => 'month',
                        'api'     => 'api/community/rank',
                        'params'  => ['type' => "profit",'t_type' => 'month'],
                    ]
                ]
            ]
        ];
        $service = new CommunityService();
        //推荐用户
        $recMember = $service->getRecommendMember($member);
        //推荐帖子
        $post_list = $service->getRecommendPost($member);
        return $this->showJson(['tab' => $tab,'member' => $recMember,'post_list' => $post_list]);
    }

    public function incomeListAction(){
        try {
            $type = $this->post['type'] ?? 'new';
            $member = request()->getMember();
            $service = new CommunityService();
            $data = $service->incomeList($member,$type);
            return $this->showJson(['list' => $data]);
        } catch (\Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function unlockListAction(){
        try {
            $validator = Validator::make($this->post, [
                'id' => 'required|numeric|min:1', //帖子ID
            ]);
            if ($validator->fail($msg)) {
                return $this->errorJson($msg);
            }
            $id = $this->post['id'];
            $service = new CommunityService();
            $data = $service->unlockList($id);
            return $this->showJson(['list' => $data]);
        } catch (\Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }
}