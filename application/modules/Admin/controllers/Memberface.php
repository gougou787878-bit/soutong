<?php

use service\AiSdkService;

/**
 * Class MemberfaceController
 *
 * @date 2024-01-02 20:10:07
 */
class MemberfaceController extends BackendBaseController
{
    /**
     * 列表数据过滤
     * @return Closure
     *
     * @date 2019-12-02 17:08:03
     */
    protected function listAjaxIteration()
    {
        return function (MemberFaceModel $item) {
            $item->status_str = MemberFaceModel::STATUS_TIPS[$item->status];
            $item->type_str = MemberFaceModel::TYPE_TIPS[$item->type];
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     *
     * @date 2024-01-02 20:10:07
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     *
     * @date 2024-01-02 20:10:07
     */
    protected function getModelClass(): string
    {
       return MemberFaceModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     *
     * @date 2024-01-02 20:10:07
     */
    protected function getPkName(): string
    {
        return 'id';
    }

    /**
     * 定义数据操作日志
     * @return string
     *
     * @date 2019-11-04 17:19:41
     */
    protected function getLogDesc(): string {
        // TODO: Implement getLogDesc() method.
        return '';
    }

    public function retryAction()
    {
        try {
            $post = $this->postArray();
            $ary = explode(',', $post['ids'] ?? '');
            $ary = array_filter($ary);
            MemberFaceModel::whereIn('id', $ary)
                ->where('status', '!=',MemberFaceModel::STATUS_SUCCESS)
                ->get()
                ->map(function ($item) {
                    $item->status = MemberFaceModel::STATUS_WAIT;
                    $item->reason = '';
                    $isOk = $item->save();
                    test_assert($isOk, '系统异常');
                    $id = $item->id;
                    bg_run(function () use ($id){
                        AiSdkService::image_face($id);
                    });
                });
            return $this->ajaxSuccessMsg('操作成功');
        } catch (Exception $e) {
            return $this->ajaxError($e->getMessage());
        }
    }
}