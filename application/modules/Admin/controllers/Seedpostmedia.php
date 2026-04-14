<?php

/**
 * Class SeedpostmediaController
 *
 * @date 2024-02-28 16:41:13
 */
class SeedpostmediaController extends BackendBaseController
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
            $item->type_str = SeedPostMediaModel::TYPE_TIPS[$item->type]??'';
            $item->relation_str = SeedPostMediaModel::TYPE_RELATE_TIPS[$item->relate_type]??'';
            $item->media_flag = 0;//0默认图片
            if($item->type == SeedPostMediaModel::TYPE_IMG){
                $item->media_url_full = url_cover($item->media_url);
            }elseif($item->type == SeedPostMediaModel::TYPE_VIDEO){
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
     * @date 2024-02-28 16:41:13
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     *
     * @date 2024-02-28 16:41:13
     */
    protected function getModelClass(): string
    {
       return SeedPostMediaModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     *
     * @date 2024-02-28 16:41:13
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