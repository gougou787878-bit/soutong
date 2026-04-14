<?php

use service\MvService;

/**
 * 作品管理 相关逻辑控制
 */
class WorksController extends BaseController
{

    /**
     * 作品-所有
     * @return mixed
     */
    public function allAction()
    {
        $data = $this->doWorks([
            ['status', '!=', MvModel::STAT_REMOVE],
        ]);
        return $this->showJson($data);
    }

    /**
     * 作品 - 已发布
     * @return mixed
     */
    public function releaseAction()
    {
        $kwy = $this->post['kwy'] ?? '';
        $where = [
            ['status', '=', MvModel::STAT_CALLBACK_DONE],
            ['is_hide', '=', MvModel::IS_HIDE_NO],
        ];
        if (!empty($kwy)){
            $where[] = ['title', 'like', "%{$kwy}%"];
        }
        $data = $this->doWorks($where);
        return $this->showJson($data);
    }

    /**
     * 作品 - 审核（未通过）
     * @return mixed
     */
    public function rejectAction()
    {
        $list = MvSubmitModel::where('uid', $this->member['uid'])
            ->whereIn('status', [MvSubmitModel::STAT_REFUSE])
            ->forPage($this->page, $this->limit)
            ->orderByDesc('id')
            ->get();

        $data = (new MvService())->v2format($list, false);
        return $this->showJson($data);
    }

    /**
     * 作品 - 审核（待审核）
     * @return mixed
     */
    public function waitAction()
    {
        $list = MvSubmitModel::where('uid', $this->member['uid'])
            ->whereIn('status', [MvSubmitModel::STAT_UNREVIEWED,MvSubmitModel::STAT_CALLBACK_ING])
            ->forPage($this->page, $this->limit)
            ->orderByDesc('id')
            ->get();

        $data = (new MvService())->v2format($list, false);
        return $this->showJson($data);
    }

    /**
     * 作品 - 已发布并且隐藏状态（下架）
     * @return mixed
     */
    public function hideAction()
    {
        $data = $this->doWorks([
            ['status', '=', MvModel::STAT_CALLBACK_DONE],
            ['is_hide', '=', MvModel::IS_HIDE_YES],
        ]);
        return $this->showJson($data);
    }

    /**
     * 作品  已发布 下架-》 上架视频
     * @return mixed
     */
    public function upShelvesAction()
    {
        $mv_id = $this->post['mv_id'] ?? 0;
        $member = request()->getMember();
        $data = (new MvService())->setUserWorks([
            ['id', '=', $mv_id],
            ['uid', '=', $member->uid],
            ['status', '=', MvModel::STAT_CALLBACK_DONE],
            ['is_hide', '=', MvModel::IS_HIDE_YES],
        ], [
            'is_hide' => MvModel::IS_HIDE_NO
        ]);

        return $this->showJson([
            'success' => true,
            'msg'     => '操作成功'
        ]);
    }

    /**
     * 作品  已发布-》  下架视频
     * @return mixed
     */
    public function downShelvesAction()
    {
        if(setting("is.open.up",null)){
            return $this->showJson([
                'success' => true,
                'msg'     => '操作成功'
            ]);
        }
        $mv_id = $this->post['mv_id'] ?? 0;
        $member = request()->getMember();
        $data = (new MvService())->setUserWorks([
            ['id', '=', $mv_id],
            ['uid', '=', $member->uid],
            ['status', '=', MvModel::STAT_CALLBACK_DONE],
            ['is_hide', '=', MvModel::IS_HIDE_NO],
        ], ['is_hide' => MvModel::IS_HIDE_YES]);
        return $this->showJson([
            'success' => true,
            'msg'     => '操作成功'
        ]);
    }

    /**
     * @param array $where
     */
    protected function doWorks($where = [])
    {
        $member = request()->getMember();
        return (new MvService())->getUserWorks($member, $where);
    }


    /**
     * 作品 - 逻辑删除 对用户不可逆  永久删除
     * @return mixed
     */
    public function removeAction()
    {
        if(setting("is.open.up",null)){
            return $this->showJson([
                'success' => true,
                'msg'     => '操作成功'
            ]);
        }
        $mv_id = $this->post['mv_id'] ?? 0;
        $member = request()->getMember();
        $data = (new MvService())->setUserWorks([
            ['id', '=', $mv_id],
            ['uid', '=', $member->uid],
            ['is_hide', '=', MvModel::IS_HIDE_NO],
        ], [
            'is_hide' => MvModel::IS_HIDE_YES,
            'status' => MvModel::STAT_REMOVE,
        ]);
        return $this->showJson([
            'success' => true,
            'msg'     => '操作成功'
        ]);
    }

}