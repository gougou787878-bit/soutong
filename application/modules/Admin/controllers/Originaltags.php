<?php

use tools\HttpCurl;

/**
 * Class OriginaltagsController
 * @author xiongba
 * @date 2020-09-24 22:40:12
 */
class OriginaltagsController extends BackendBaseController
{
    /**
     * 列表数据过滤
     * @return Closure
     * @author xiongba
     * @date 2019-12-02 17:08:03
     */
    protected function listAjaxIteration()
    {
        return function ($item) {
            $item->catgory_str = OriginalTagsModel::CATEGORY_TIPS[$item->category];
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     * @author xiongba
     * @date 2020-09-24 22:40:12
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2020-09-24 22:40:12
     */
    protected function getModelClass(): string
    {
        return OriginalTagsModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2020-09-24 22:40:12
     */
    protected function getPkName(): string
    {
        return 'id';
    }

    /**
     * 定义数据操作日志
     * @return string
     * @author xiongba
     * @date 2019-11-04 17:19:41
     */
    protected function getLogDesc(): string
    {
        // TODO: Implement getLogDesc() method.
        return '';
    }

    public function refreshAction()
    {
        $url ="http://192.46.228.177:6529/gol_cate";
        $resjson  =(new HttpCurl())->remoteGet($url);
        if($resjson){
            $res = json_decode($resjson,true);

            $arr = OriginalTagsModel::CATEGORY_TIPS;
            $cat_arr =  array_flip($arr);

            if($res){
                foreach ($res  as $value){
                    $cate = $cat_arr[$value['type_name']];
                    if($value['list']){
                        foreach ($value['list'] as $val){
                            if($val['cate_name'] != '全部'){
                                OriginalTagsModel::updateOrCreate(['name'=>$val['cate_name']],['category'=>$cate]);
                            }
                        }
                    }
                }
            }
        }else{
            return $this->ajaxError('无数据');
        }
        return $this->ajaxSuccessMsg('标签更新成功');
    }
}