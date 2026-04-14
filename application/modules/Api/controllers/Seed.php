<?php

use helper\Validator;
use service\AdService;
use service\SeedService;
use helper\QueryHelper;

class SeedController extends BaseController
{

    public function postAction()
    {
        try {
            $types = array_column(SeedService::SORT_NAV, 'type');
            $types = implode(",", $types);
            $validator = Validator::make($this->post, [
                'sort'     => 'required|enum:' . $types,
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $sort = trim($this->post['sort']);
            $member = request()->getMember();
            list($page, $limit) = QueryHelper::pageLimit();
            $service = new SeedService();
            $data = $service->list_post($member, $sort, $page, $limit);
            return $this->showJson($data);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function detailAction()
    {
        try {
            $validator = Validator::make($this->post, [
                'id' => 'required|numeric|min:1',
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $id = (int)$this->post['id'];
            $member = request()->getMember();
            $service = new SeedService();
            $data = $service->post_detail($member, $id);
            if (version_compare($_POST['version'], AdsModel::ADS_VERSION, '>')) {
                //远程广告
                $ads = AdService::getADsByPosition(AdsModel::POSITION_SEED_DETAIL);
                return $this->showJson(['data' => $data, 'ads' => $ads]);
            }
            return $this->showJson($data);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function buyAction()
    {
        try {
            $validator = Validator::make($this->post, [
                'id' => 'required|numeric|min:1',
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $id = (int)$this->post['id'];
            $member = request()->getMember()->refresh();
            $service = new SeedService();
            $data = $service->buy($member, $id);
            return $this->showJson(['link' => $data]);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage(), $e->getCode());
        }
    }

    public function post_commentsAction()
    {
        try {
            $validator = Validator::make($this->post, [
                'id' => 'required|numeric|min:1',//帖子ID
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $postId = (int)$this->post['id'];
            $member = request()->getMember();
            list($page, $limit) = QueryHelper::pageLimit();
            $service = new SeedService();
            $data = $service->list_first_comments($member, $postId, $page, $limit);
            return $this->listJson($data);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function commentsAction()
    {
        try {
            $validator = Validator::make($this->post, [
                'comment_id' => 'required|numeric|min:1',//评论ID
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $commentId = (int)$this->post['comment_id'];
            $member = request()->getMember();
            list($page, $limit) = QueryHelper::pageLimit();
            $service = new SeedService();
            $data = $service->list_second_comments($member, $commentId, $page, $limit);
            return $this->listJson($data);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    // 发布评论
    public function commentAction()
    {
        try {
            $member = request()->getMember();
            $postId = $this->post['seed_id'] ?? 0;
            $content = $this->post['content'] ?? '';
            $medias = $this->post['medias'] ?? '';
            $commentId = $this->post['comment_id'] ?? 0;
            $cityname = ($this->position['province'].$this->position['city']) ?: '火星';
            $postId = (int)$postId;
            $commentId = (int)$commentId;

            if (!$postId && !$commentId) {
                test_assert(false, '帖子或者评论ID至少得存在一个');
            }

            if ($medias) {
                $medias = htmlspecialchars_decode($medias);
                $medias = json_decode($medias, true);
            } else {
                $medias = [];
            }
            //1分钟5条
            \helper\Util::PanicFrequency($member->aff,5,60);
            if (mb_strlen($content) < 2) {
                return $this->errorJson('评论内容至少两个字符');
            }
            if ($member->isBan()) {
                return $this->errorJson('触犯禁言规则，禁止评论,联系管理员～');
            }
            if ($content) {
                test_assert(mb_strlen($content) <= 50, '最多可评论50字');
            }
            $key = 'day:comment:num:' . date('Ymd') . $member->aff;
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
            if (!PostCommentKeywordModel::filterChinese($content)) {
                return $this->errorJson('触犯禁言规则#1，禁止评论,联系管理员～');
            }
            if (!PostCommentKeywordModel::filterUrl($content) || !PostCommentKeywordModel::filterUrl2($content)) {
                return $this->errorJson('触犯禁言规则#2，禁止评论,联系管理员～');
            }
            if (!PostCommentKeywordModel::filterFont($content)) {
                return $this->errorJson('触犯禁言规则#3，禁止评论,联系管理员～');
            }
            if (!PostCommentKeywordModel::filterStrNumber($content)) {
                return $this->errorJson('触犯禁言规则#4，禁止评论,联系管理员～');
            }
            if (!PostCommentKeywordModel::filterKeyword($content)) {
                return $this->errorJson('触犯禁言规则#5，禁止评论,联系管理员～');
            }
            $service = new SeedService();
            if ($commentId > 0) {
                $service->create_com_comment($member, $commentId, $content, $medias, $cityname);
            } else {
                $service->create_post_comment($member, $postId, $content, $medias, $cityname);
            }

            return $this->successMsg('评论成功，请耐心等待审核');
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }


    //点赞/取消点赞
    public function likeAction()
    {
        try {
            $validator = Validator::make($this->post, [
                'type' => 'required|enum:post,comment', //点赞类型 post帖子 comment评论
                'id'   => 'required|numeric|min:1', //帖子ID
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $id = (int)$this->post['id'];
            $type = $this->post['type'];
            $member = request()->getMember();
            \helper\Util::PanicFrequency($member->aff,5,60);
            if ($member->isBan()) {
                return $this->errorJson('触犯禁言规则，禁止评论,联系管理员～');
            }

            $service = new SeedService();
            $data = $service->like($member, $type, $id);
            return $this->showJson($data);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function list_buyAction()
    {
        try {
            $member = request()->getMember();
            list($page, $limit) = QueryHelper::pageLimit();
            $service = new SeedService();
            $data = $service->list_buy_post($member, $page, $limit);
            return $this->listJson($data);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function list_likeAction()
    {
        try {
            $member = request()->getMember();
            list($page, $limit) = QueryHelper::pageLimit();
            $service = new SeedService();
            $data = $service->list_like_post($member, $page, $limit);
            return $this->listJson($data);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    //收藏/取消收藏
    public function favoriteAction()
    {
        try {
            $validator = Validator::make($this->post, [
                'id'   => 'required|numeric|min:1', //帖子ID
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $id = (int)$this->post['id'];
            $member = request()->getMember();
            \helper\Util::PanicFrequency($member->aff,5,60);
            test_assert(!$member->isBan(), '你已被禁言');
            test_assert($member->is_reg, '仅注册用户才能收藏');

            $service = new SeedService();
            $data = $service->favorite($member, $id);
            return $this->showJson($data);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function list_my_favoriteAction()
    {
        try {
            $member = request()->getMember();
            list($page, $limit) = QueryHelper::pageLimit();
            $service = new SeedService();
            $res = $service->listMyFavoriteSeeds($member, $page, $limit);
            return $this->listJson($res);
        } catch (\Exception $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function searchAction()
    {
        try {
            $validator = Validator::make($this->post, [
                'word' => 'required',
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $word = trim($this->post['word']);
            $member = request()->getMember();
            list($page, $limit) = QueryHelper::pageLimit();
            $service = new SeedService();
            $data = $service->list_search_post($member, $word, $page, $limit);
            return $this->listJson($data);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }
}