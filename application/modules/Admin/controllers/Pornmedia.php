<?php

/**
 * Class PornmediaController
 *
 * @date 2024-04-01 15:50:53
 */
class PornmediaController extends BackendBaseController
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
            $item->media_flag = 0;//0默认图片
            if($item->type == PornMediaModel::TYPE_IMG){
                $item->media_url_full = url_cover($item->media_url);
            }elseif($item->type == PornMediaModel::TYPE_VIDEO){
                $extension = pathinfo($item->media_url, PATHINFO_EXTENSION);
                $item->cover = url_cover($item->cover);
                if($extension == 'mp4'){
                    $item->media_flag = 1;//1 mp4
                    $item->media_url_full = $item->media_url;
                }elseif($extension == 'm3u8'){
                    $item->media_flag = 2;;//2 m3u8
                    $item->media_url_full =  getAdminPlayM3u8($item->media_url,true);
                }
            }
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     *
     * @date 2024-04-01 15:50:53
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     *
     * @date 2024-04-01 15:50:53
     */
    protected function getModelClass(): string
    {
       return PornMediaModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     *
     * @date 2024-04-01 15:50:53
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
}