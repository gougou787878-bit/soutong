<?php

/**
 * Class AdsappController
 * @author xiongba
 * @date 2020-10-21 12:30:57
 */
class AdsappController extends BackendBaseController
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
            $result = [];
            $result = $item->toArray();
            //$result['img_url'] = url_ads($result['img_url']);
            $result['created_at'] = date('Y-m-d', $result['created_at']);
            return $result;
        };
    }

    /**
     * 试图渲染
     * @return string
     * @author xiongba
     * @date 2020-10-21 12:30:57
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2020-10-21 12:30:57
     */
    protected function getModelClass(): string
    {
       return AdsAppModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2020-10-21 12:30:57
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
    protected function getLogDesc(): string {
        // TODO: Implement getLogDesc() method.
        return '';
    }
    protected function postArray($setPost = null)
    {
        $post = request()->getPost();
        $post['created_at'] = TIMESTAMP;
        return $post;
    }

    function _saveActionAfter()
    {
        AdsAppModel::clearRedisCache();
    }

    function _delActionAfter()
    {
        AdsAppModel::clearRedisCache();
    }
    /**
     * 域名批量替换处理
     * @return bool
     */
    public function batchUpdateAction()
    {
        $post = $_POST;
        //print_r($_POST);
        /**Array
         * (
         * [old_url] => aff006.org
         * [new_url] => aff006.tv
         * [old_aff_domain] => aff006.org
         * [new_aff_domain] => aff006.tv
         * )*/

        //一对
        $old_url = $post['old_url'] ?? '';
        $old_url = trim($old_url);
        $new_url = $post['new_url'] ?? '';
        $new_url = trim($new_url);
        $flag = 0;
        if ($old_url && $new_url) {
            $flag = AdsAppModel::query()->where('link_url', 'like', "%{$old_url}%")->update([
                'link_url'        => DB::raw("REPLACE(link_url, '{$old_url}', '{$new_url}')")
            ]);
        }
        //第二对
        $old_aff_url = $post['old_aff_domain'] ?? '';
        $old_aff_url = trim($old_aff_url);
        $new_aff_url = $post['new_aff_domain'] ?? '';
        $new_aff_url = trim($new_aff_url);
        //REPLACE(string, from_string, new_string)
        $flag2 = 0;
        if ($old_aff_url && $new_aff_url) {
            $flag2 = AdsAppModel::query()->where('link_url', 'like', "%{$old_aff_url}%")->update([
                'link_url'        => DB::raw("REPLACE(link_url, '{$old_aff_url}', '{$new_aff_url}')")
            ]);
        }
        AdminLogModel::addOther($this->getUser()->username, "更新域名：" . var_export($_POST, true));
        AdsAppModel::clearRedisCache();
        return $this->ajaxSuccessMsg("执行影响：域名1：{$flag}条；域名2：{$flag2}条");
    }

}