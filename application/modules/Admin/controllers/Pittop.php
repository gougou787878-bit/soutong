<?php

use service\PitTopService;

/**
 * Class PittopController
 * 
 * @date 2020-11-10 17:54:34
 */
class PittopController extends BackendBaseController
{
    /**
     * 列表数据过滤
     * @return Closure
     * 
     * @date 2019-12-02 17:08:03
     */
    protected function listAjaxIteration()
    {
        return function ($item) {
            $item->created_at = $item->created_at?date('Y-m-d H:i',$item->created_at):'';
            $item->mv_img_thumb = $item->mv->cover_thumb_url;
            $item->mv_title = $item->mv->title;
            $item->mv_coins = $item->mv->coins;
            $item->mv_pre_href = getAdminPlayM3u8($item->mv->m3u8);
            $item->mv_full_href = $item->mv_pre_href;
            if ($item->mv_coins) {
                $item->mv_full_href = getAdminPlayM3u8($item->mv->full_m3u8);
            }
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     * 
     * @date 2020-11-10 17:54:34
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     * 
     * @date 2020-11-10 17:54:34
     */
    protected function getModelClass(): string
    {
       return PitTopModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * 
     * @date 2020-11-10 17:54:34
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
    /**
     * 获取对应的model query
     * @return string
     * 
     * @date 2019-11-04 17:20:15
     */
    protected function getModelQuery(){

        return PitTopModel::with('mv');
    }
    protected function postArray($setPost = null)
    {
        $post = parent::postArray();
        if(!isset($post['_pk']) || !$post['_pk']){
            $post['created_at'] = TIMESTAMP;
        }
        return $post;
    }

    /**
     * @param $model
     */
    protected function saveAfterCallback($model)
    {
        if (is_null($model)) {
            return;
        }
        PitTopService::clearCache();

    }
}