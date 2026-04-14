<?php

use service\PcService;
use service\PcMvService;
use helper\Validator;
use helper\QueryHelper;

class MvController extends PcBaseController
{
    public function indexAction(): bool
    {
        try {
            $navs = array_column(PcService::MV_NAVS, 'value');
            $navs = implode(',', $navs);
            $validator = Validator::make($this->data, [
                'sort' => 'required|enum:' . $navs,
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $sort = $this->data['sort'];
            list($page, $limit) = QueryHelper::pageLimit();

            $service = new PcMvService();
            $data = $service->homeMvs($sort, $page, $limit);
            $_SERVER['SCRIPT_PARAMS'] = [$sort, $page, $limit];
            return $this->showJson($data);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function tab_detailAction()
    {
        try {
            $validator = Validator::make($this->data, [
                'id' => 'required|numeric|min:1',
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $tab_id = $this->data['id'];

            $service = new PcMvService();
            $data = $service->tabDetail($tab_id);
            $_SERVER['SCRIPT_PARAMS'] = [$tab_id];
            return $this->showJson($data);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function list_mvsAction(): bool
    {
        try {
            $navs = array_column(PcService::MV_NAVS, 'value');
            $navs = implode(',', $navs);
            $validator = Validator::make($this->data, [
                'id'    => 'required|numeric|min:1',
                'sort'  => 'required|enum:' . $navs,
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $tab_id = (int)$this->data['id'];
            $sort = $this->data['sort'];
            list($page, $limit) = QueryHelper::pageLimit();

            $service = new PcMvService();
            $data = $service->listMvs($tab_id,$sort, $page, $limit);
            $_SERVER['SCRIPT_PARAMS'] = [$tab_id,$sort, $page, $limit];
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

            $mvId = (int)$this->data['id'];
            $member = $this->member;
            $service = new PcMvService();
            $data = $service->getMvDetail($mvId, $member);
            $_SERVER['SCRIPT_PARAMS'] = [$mvId];
            return $this->showJson($data);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function recommend_mvsAction(): ?bool
    {
        try {
            $validator = Validator::make($this->data, [
                'id' => 'required|numeric|min:1',
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $mvId = (int)$this->data['id'];
            $service = new PcMvService();
            $data = $service->listRecommendMvs($mvId);
            $_SERVER['SCRIPT_PARAMS'] = [$mvId];
            return $this->showJson($data);
        } catch (Throwable $e) {
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

            $mvId = (int)$this->data['id'];
            list($page, $limit) = QueryHelper::pageLimit();

            $service = new PcMvService();
            $data = $service->listMvComments($mvId, $page, $limit);
            $_SERVER['SCRIPT_PARAMS'] = [$mvId, $page, $limit];
            return $this->showJson($data);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function commentAction(): bool
    {
        try {
            $validator = Validator::make($this->data, [
                'mv_id'   => 'required|numeric|min:1',
                'content' => 'required',
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $member = $this->member;
            test_assert($member, '您没有登录');

            $mvId = (int)$this->data['mv_id'];
            $cID = $this->data['c_id'] ?? '0';
            $content = strip_tags($this->data['content']);
            if (mb_strlen($content) <2) {
                return $this->errorJson('评论内容至少两个字符');
            }

            if (mb_strlen($content) > 255) {
                return $this->errorJson('评论内容不符合');
            }

            $this->verifyMemberSayRole();
            $this->verifyFrequency();

            $service = new PcMvService();
            $service->comment($member, $mvId, $cID, $content);

            return $this->successMsg('评论成功,请等待审核');
        } catch (Throwable $e) {
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
            $tab_id = $this->data['tab_id'] ?? 0;
            test_assert($word, '搜索关键字不能为空');

            list($page, $limit) = QueryHelper::pageLimit();
            $service = new PcMvService();
            $data = $service->listSearch($tab_id, $word, $page, $limit);
            return $this->showJson($data);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function favoriteAction(): ?bool
    {
        try {
            $validator = Validator::make($this->data, [
                'id' => 'required|numeric|min:1',
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $member = $this->member;
            test_assert($member, '您没有登录');
            $id = (int)$this->data['id'];

            if ($member->role_id == MemberModel::USER_ROLE_LEVEL_BANED) {
                return $this->errorJson('您已经被禁言');
            }
            if (!frequencyLimit(10, 3, $member)) {
                return $this->errorJson('短时间内赞操作太頻繁了,稍后再试试');
            }

            $service = new PcMvService();
            $data = $service->favorite($member, $id);
            return $this->showJson($data);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function list_favoriteAction(): bool
    {
        try {
            $member = $this->member;
            test_assert($member, '您没有登录');
            list($page, $limit) = QueryHelper::pageLimit();

            $service = new PcMvService();
            $data = $service->listFavorite($member, $page, $limit);
            return $this->showJson($data);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function comment_likeAction(): ?bool
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

            if ($member->role_id == MemberModel::USER_ROLE_LEVEL_BANED) {
                return $this->errorJson('您已经被禁言');
            }
            $service = new PcMvService();
            $service->commentLike($member, $id);
            return $this->successMsg('操作成功');
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }
}
