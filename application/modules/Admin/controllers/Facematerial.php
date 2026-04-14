<?php

/**
 * Class FacematerialController
 *
 * @date 2024-01-02 20:10:27
 */
class FacematerialController extends BackendBaseController
{
    /**
     * 列表数据过滤
     * @return Closure
     *
     * @date 2019-12-02 17:08:03
     */
    protected function listAjaxIteration()
    {
        return function (FaceMaterialModel $item) {
            $item->load('cate');
            $item->cate_name = $item->cate ? $item->cate->name : '';
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     *
     * @date 2024-01-02 20:10:27
     */
    public function indexAction()
    {
        $cate = FaceCateModel::get()->pluck('name','id')->toArray();
        $this->assign('face_cate', $cate);
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     *
     * @date 2024-01-02 20:10:27
     */
    protected function getModelClass(): string
    {
       return FaceMaterialModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     *
     * @date 2024-01-02 20:10:27
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

    public function saveAfterCallback($model)
    {
        if ($model && !$model->thumb_w && !$model->thumb_h){
            $url = TB_IMG_ADM_US . parse_url($model->thumb, PHP_URL_PATH);
            list($w, $h) = getimagesize($url);
            $model->thumb_w = $w;
            $model->thumb_h = $h;
            $model->save();
        }
    }

    public function addBatchPayAction(){
        try {
            test_assert($this->getRequest()->isPost(), '请求错误');
            $post = $this->postArray();
            $ary = explode(',', $post['face_ids'] ?? '');
            $ary = array_filter($ary);
            $ary = array_unique($ary);
            $faces = FaceMaterialModel::whereIn('id', $ary)->get();
            $type = $post['type'];
            $coins = (int)$post['coins'];
            test_assert($coins, '金币贴解锁金币数不能小于1');

            transaction(function ()use($faces,$type,$coins){
                /** @var FaceMaterialModel $face */
                foreach ($faces as $face) {
                    $face->type = $type;
                    $face->coins = $coins;
                    $isOK = $face->save();
                    test_assert($isOK,"保存失败");
                }
            });
            return $this->ajaxSuccess('更新成功');
        }catch (Exception $e){
            return $this->ajaxError($e->getMessage());
        }
    }

    public function addBatchCateAction(){
        try {
            test_assert($this->getRequest()->isPost(), '请求错误');
            $post = $this->postArray();
            $ary = explode(',', $post['face_ids'] ?? '');
            $ary = array_filter($ary);
            $ary = array_unique($ary);
            $faces = FaceMaterialModel::whereIn('id', $ary)->get();
            $cate_id = $post['cate_id'];
            test_assert($cate_id, '分类不能为空');

            transaction(function ()use($faces, $cate_id){
                /** @var FaceMaterialModel $face */
                foreach ($faces as $face) {
                    $face->cate_id = $cate_id;
                    $isOK = $face->save();
                    test_assert($isOK,"保存失败");
                }
            });
            return $this->ajaxSuccess('更新成功');
        }catch (Exception $e){
            return $this->ajaxError($e->getMessage());
        }
    }
}