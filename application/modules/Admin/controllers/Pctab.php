<?php

/**
 * Class PctabController
 */
class PctabController extends BackendBaseController
{
    /**
     * 列表数据过滤
     * @return Closure
     * @date 2019-12-02 17:08:03
     */
    protected function listAjaxIteration()
    {
        return function ($item) {
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     * @date 2020-12-08 15:41:38
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     * @date 2020-12-08 15:41:38
     */
    protected function getModelClass(): string
    {
       return PcTabModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @date 2020-12-08 15:41:38
     */
    protected function getPkName(): string
    {
        return 'tab_id';
    }

    /**
     * 定义数据操作日志
     * @return string
     * @date 2019-11-04 17:19:41
     */
    protected function getLogDesc(): string {
        // TODO: Implement getLogDesc() method.
        return '';
    }
    protected function postArray($setPost = null) {
        $post = parent::postArray();

        if (!empty($post['tags_str'])) {
            $post['tags_str'] = join(',', $post['tags_str']);
        }
        return $post;
    }

//    protected function saveAfterCallback($model)
//    {
//        if (is_null($model)) {
//            return;
//        }
//        TabService::clearTabList();
//    }
}