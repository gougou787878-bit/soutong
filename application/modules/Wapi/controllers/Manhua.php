<?php

use helper\QueryHelper;
use helper\Validator;
use service\PcManhuaService;

/**
 * Class ManhuaController
 */
class ManhuaController extends PcBaseController
{

    public function tab_detailAction()
    {
        try {
            $validator = Validator::make($this->data, [
                'id' => 'required|numeric|min:1',
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $tab_id = $this->data['id'];

            $service = new PcManhuaService();
            $data = $service->tabDetail($tab_id);
            $_SERVER['SCRIPT_PARAMS'] = [$tab_id];
            return $this->showJson($data);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function listAction(){
        try {
            $validator = Validator::make($this->data, [
                'id' => 'required|numeric|min:1',
                'sort' => 'required',
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $tab_id = $this->data['id'];
            $sort = $this->data['sort'];
            list($page,$limit) = QueryHelper::pageLimit();
            $service = new PcManhuaService();
            $data = $service->list($tab_id,$sort,$page,$limit);
            $_SERVER['SCRIPT_PARAMS'] = [$tab_id,$sort,$page,$limit];
            return $this->showJson($data);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function searchAction(){
        try {
            $validator = Validator::make($this->data, [
                'kwy' => 'required'
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $tab_id = $this->data['id'] ?? 0;
            $kwy = $this->data['kwy'];
            $kwy = strip_tags($kwy);
            if (mb_strlen($kwy) < 2) {
                return $this->errorJson('至少两位搜索关键字');
            }
            if (preg_match('/[\xf0-\xf7].{3}/', $kwy)) { //过滤Emoji表情
                return $this->errorJson('不支持[Emoji]表情');
            }
            $kwy = emoji_reject($kwy);

            list($page,$limit) = QueryHelper::pageLimit();
            $service = new PcManhuaService();
            $data = $service->searchManhua($tab_id,$kwy,$page,$limit);
            return $this->showJson(['list' => $data]);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 漫画章节目录 详细页面
     */
    public function detailAction()
    {
        try {
            $validator = Validator::make($this->data, [
                'id' => 'required|numeric|min:1',
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $id = $this->data['id'];
            $service = new PcManhuaService();
            $data = $service->getDetailData($id,$this->member);
            $_SERVER['SCRIPT_PARAMS'] = [$id];
            return $this->showJson($data);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 漫画阅读
     */
    public function readAction()
    {
        try {
            $validator = Validator::make($this->data, [
                'id'   => 'required|numeric|min:1',
                's_id' => 'required|numeric|min:1',
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);
            $member = $this->member;
            test_assert($member, '您没有登录');

            $comics_id = $this->data['id'];
            $series_id = $this->data['s_id'];
            $service = new PcManhuaService();
            $data = $service->readManhua($member, $comics_id, $series_id);
            return $this->showJson($data);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 漫画 推荐列表
     * @return bool
     */
    public function recommendAction()
    {
        try {
            $validator = Validator::make($this->data, [
                'id'   => 'required|numeric|min:1',
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $man_hua_id = $this->data['id'];
            $service = new PcManhuaService();
            $data = $service->guessByManHuaLike($man_hua_id);
            $_SERVER['SCRIPT_PARAMS'] = [$man_hua_id];
            return $this->showJson($data);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 点赞收藏
     * @return bool
     */
    public function likingAction()
    {
        try {
            $validator = Validator::make($this->data, [
                'id'   => 'required|numeric|min:1',
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);
            $member = $this->member;
            test_assert($member, '您没有登录');

            if ($member->isBan()){
                return $this->errorJson('涉嫌违规，不允许收藏');
            }
            if (!frequencyLimit(10, 3, $member)) {
                return $this->errorJson('短时间内赞操作太頻繁了,稍后再试试');
            }
            $id = $this->data['id'];
            $service = new PcManhuaService();
            $data = $service->getFavorites($member, $id);
            return $this->showJson($data);
        } catch (Throwable $e) {
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
            $member = $this->member;
            test_assert($member, '您没有登录');

            list($page, $limit) = QueryHelper::pageLimit();
            $service = new PcManhuaService();
            $data = $service->getLikeList($member,$page,$limit);
            return $this->showJson(['list' => $data]);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

}