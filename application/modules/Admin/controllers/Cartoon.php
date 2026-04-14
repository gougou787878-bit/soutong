<?php

/**
 * 动漫
 * Class CartoonController
 */
class CartoonController extends BackendBaseController
{
    /**
     * 列表数据过滤
     * @return Closure
     */
    protected function listAjaxIteration()
    {
        return function ($item) {
            $item->load('cate');
            $item->is_series_str = CartoonModel::SERIES_TIPS[$item->is_series];
            $item->category_str = $item->cate ? $item->cate->title : '未绑定';
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     */
    public function indexAction()
    {
        $category = CartoonCategoryModel::where('status', CartoonCategoryModel::STATUS_OK)->pluck('title', 'id')->toArray();
        $this->assign('category', $category);
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     */
    protected function getModelClass(): string
    {
       return CartoonModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     */
    protected function getPkName(): string
    {
        return 'id';
    }

    /**
     * 定义数据操作日志
     * @return string
     */
    protected function getLogDesc(): string {
        // TODO: Implement getLogDesc() method.
        return '';
    }

    //批量设置分类
    public function addBatchCategoryAction(){
        try {
            $ids = explode(',', trim($this->post['ids'], ','));
            test_assert($ids,'数据异常');
            $category_id = $this->post['category_id'];
            test_assert($category_id, '分类ID未选择');
            CartoonModel::whereIn('id', $ids)->update(['category_id' => $category_id]);
            return $this->ajaxSuccess("操作成功");
        }catch (Exception $e){
            return $this->ajaxError($e->getMessage());
        }
    }

}