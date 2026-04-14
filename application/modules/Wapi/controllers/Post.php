<?php

use service\PcPostService;
use service\PcService;
use helper\Validator;
use helper\QueryHelper;

class PostController extends PcBaseController
{

    // 板块详情
    public function topic_detailAction(): bool
    {
        try {
            $validator = Validator::make($this->data, [
                'topic_id' => 'required|numeric|min:1',
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $member = $this->member;
            $topicId = $this->data['topic_id'];

            $service = new PcPostService();
            $data = $service->topicDetail($member, $topicId);
            $_SERVER['SCRIPT_PARAMS'] = [$topicId];
            return $this->showJson($data);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function list_postsAction(): bool
    {
        try {
            $navs = array_column(PcService::POST_NAVS, 'value');
            $navs = implode(',', $navs);
            $validator = Validator::make($this->data, [
                'id'   => 'required|numeric|min:1',
                'sort' => 'required|enum:' . $navs,
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $topicId = $this->data['id'];
            $sort = $this->data['sort'];
            list($page, $limit) = QueryHelper::pageLimit();

            $service = new PcPostService();
            $data = $service->listPosts($topicId, $sort, $page, $limit);
            $_SERVER['SCRIPT_PARAMS'] = [$topicId, $sort, $page, $limit];
            return $this->showJson($data);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function detailAction(): ?bool
    {
        try {
            $validator = Validator::make($this->data, [
                'id' => 'required|numeric|min:1'
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $postId = (int)$this->data['id'];
            $member = $this->member;
            $service = new PcPostService();
            $data = $service->getPostDetail($postId, $member);
            $_SERVER['SCRIPT_PARAMS'] = [$postId];
            return $this->showJson($data);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function topic_followAction(): ?bool
    {
        try {
            $validator = Validator::make($this->data, [
                'id' => 'required|numeric|min:1',
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $member = $this->member;
            test_assert($member, '您没有登录');
            $id = (int)($this->data['id']);

            $service = new PcPostService();
            $data = $service->topicFollow($member, $id);
            return $this->showJson($data);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function list_topic_followAction(): ?bool
    {
        try {
            $member = $this->member;
            test_assert($member, '您没有登录');
            list($page, $limit) = QueryHelper::pageLimit();

            $service = new PcPostService();
            $data = $service->listTopicFollow($member, $page, $limit);
            return $this->showJson($data);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function list_user_postsAction(): ?bool
    {
        try {
            $validator = Validator::make($this->data, [
                'aff' => 'required|numeric|min:1',
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $aff = (int)$this->data['aff'];
            list($page, $limit) = QueryHelper::pageLimit();

            $service = new PcPostService();
            $data = $service->listUserPosts($aff, $page, $limit);
            $_SERVER['SCRIPT_PARAMS'] = [$aff, $page, $limit];
            return $this->showJson($data);
        } catch (\Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function searchAction(): ?bool
    {
        try {
            $validator = Validator::make($this->data, [
                'word' => 'required',
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $word = trim($this->data['word']);
            test_assert($word, '搜索关键字不能为空');
            $topic_id = $this->data['topic_id'] ?? 0;
            list($page, $limit) = QueryHelper::pageLimit();

            $service = new PcPostService();
            $data = $service->listSearch($topic_id, $word, $page, $limit);
            return $this->showJson($data);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function likeAction(): ?bool
    {
        try {
            $validator = Validator::make($this->data, [
                'id' => 'required|numeric|min:1',
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $id = (int)$this->data['id'];
            $member = $this->member;
            test_assert($member, '您没有登录');

            $service = new PcPostService();
            $data = $service->like($member, $id);
            return $this->showJson($data);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function list_likeAction(): ?bool
    {
        try {
            $member = $this->member;
            test_assert($member, '您没有登录');
            list($page, $limit) = QueryHelper::pageLimit();

            $service = new PcPostService();
            $data = $service->listLike($member, $page, $limit);
            return $this->showJson($data);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function createAction(): ?bool
    {
        try {
            $validator = Validator::make($this->data, [
                'topic_id' => 'required|numeric|min:1', //话题ID
                'title'    => 'required|min:8', // 标题
                'content'  => 'nullable', //内容
                'medias'   => 'nullable', // 图片或者视频链接
            ]);
            if ($validator->fail($msg)) {
                throw new Exception($msg);
            }

            $member = $this->member;
            test_assert($member, '您没有登录');

            $topicId = (int)$this->data['topic_id'];
            $title = trim($this->data['title']);
            $title = strip_tags($title);
            $content = $this->data['content'] ?? '';
            $content = strip_tags($content);
            $price = $this->data['price'] ?? 0;

            $medias = $this->data['medias'] ?? '';
            $medias = htmlspecialchars_decode($medias);
            $medias = json_decode($medias, true);
            $medias = is_array($medias) ? $medias : [];

            if (empty($medias) && !$content) {
                return $this->errorJson("内容与媒体文件必须存在一个");
            }
            if (mb_strlen($content) > 50000){
                return $this->errorJson("您发的内容太多了,无法保存");
            }

            if ($member->isBan()) {
                return $this->errorJson("您已被禁言");
            }

            $blackList = MvBackUserModel::getBackUserList();
            if ($blackList && in_array($member->uid, $blackList)) {
                return $this->errorJson('你已经被禁止发帖，如有问题请咨询客服~~');
            }

            $categoryId = PostModel::TYPE_MIX;

            //test_assert($this->member->isCreator(), '请先申请成为创作者');
            $ipstr = USER_IP;
            $cityName = ($this->position['province'].$this->position['city']) ?: '火星';
            $service = new PcPostService();
            $service->createPost($member, $topicId, $categoryId, $content, $title, $medias, $cityName, $ipstr, $price);
            return $this->successMsg('成功');
        } catch (\Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function commentAction(): ?bool
    {
        try {
            $member = $this->member;
            test_assert($member,"您没有登录");

            $validator = Validator::make($this->data, [
                'comment'      => 'required', // 内容
                'post_id'      => 'nullable', //帖子ID
                'comment_id'   => 'nullable', // 评论ID
            ]);
            if ($validator->fail($msg)) {
                throw new Exception($msg);
            }

            $postId = (int)$this->data['post_id'] ?? 0;
            $content = $this->data['comment'] ?? '';
            $commentId = (int)$this->data['comment_id'] ?? 0;
            $cityname = ($this->position['province'].$this->position['city']) ?: '火星';
            if (!$postId && !$commentId) {
                return $this->errorJson('帖子或者评论ID至少得存在一个');
            }
            //1分钟5条
            \helper\Util::PanicFrequency($member->aff,5,60);
            if (mb_strlen($content) < 2) {
                return $this->errorJson('评论内容至少两个字符');
            }
            if ($member->isBan()) {
                return $this->errorJson('触犯禁言规则，禁止评论,联系管理员～');
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
            if (!PostCommentKeywordModel::filterKeyword($content)) {
                return $this->errorJson('触犯禁言规则#5，禁止评论,联系管理员～');
            }
            $service = new PcPostService();
            if ($commentId > 0) {
                $service->createComComment($member, $commentId, $content, $cityname);
            } else {
                $service->comment($member, $postId, $content, $cityname);
            }

            return $this->successMsg('评论成功，请耐心等待审核');
        } catch (\Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function list_commentsAction(): ?bool
    {
        try {
            $validator = Validator::make($this->data, [
                'id' => 'required|numeric|min:1',
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $postId = (int)$this->data['id'];
            list($page, $limit) = QueryHelper::pageLimit();

            $service = new PcPostService();
            $data = $service->listComments($postId, $page, $limit);
            $_SERVER['SCRIPT_PARAMS'] = [$postId, $page, $limit];
            return $this->showJson($data);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function commentsAction(): ?bool
    {
        try {
            $validator = Validator::make($this->data, [
                'comment_id' => 'required|numeric|min:1',
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $comment_id = (int)$this->data['comment_id'];
            list($page, $limit) = QueryHelper::pageLimit();

            $service = new PcPostService();
            $data = $service->listCommentsByComment($comment_id, $page, $limit);
            $_SERVER['SCRIPT_PARAMS'] = [$comment_id, $page, $limit];
            return $this->showJson($data);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function list_my_postsAction(): ?bool
    {
        try {
            $validator = Validator::make($this->data, [
                'status' => 'enum:0,1,2',
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $member = $this->member;
            $status = $this->data['status'];
            test_assert($member, '您没有登录');
            list($page, $limit) = QueryHelper::pageLimit();

            $service = new PcPostService();
            $data = $service->listMyPosts($member, $status, $page, $limit);
            return $this->showJson($data);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    // 我的评论
    public function list_my_commentAction(): bool
    {
        try {
            test_assert($this->member, '您没有登录');
            $service = new PcPostService();
            list($page, $limit) = QueryHelper::pageLimit();
            $data = $service->listMyComments($this->member, $page, $limit);
            return $this->showJson($data);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    // 我的回复
    public function list_replyAction(): bool
    {
        try {
            test_assert($this->member, '您没有登录');
            $service = new PcPostService();
            list($page, $limit) = QueryHelper::pageLimit();
            $data = $service->listReply($this->member, $page, $limit);
            return $this->showJson($data);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }
}
