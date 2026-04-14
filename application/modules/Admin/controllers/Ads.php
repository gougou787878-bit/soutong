<?php

/**
 * Class AdsController
 *
 * @date 2021-04-23 18:10:12
 */
class AdsController extends BackendBaseController
{
    /**
     * 列表数据过滤
     * @return Closure
     *
     * @date 2019-12-02 17:08:03
     */
    protected function listAjaxIteration()
    {
        return function (AdsModel $item) {
            $item->mv_m3u8_full = '';
            if ($item->mv_m3u8){
                $item->mv_m3u8_full = getAdminPlayM3u8($item->mv_m3u8);
            }
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     *
     * @date 2021-04-23 18:10:12
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     *
     * @date 2021-04-23 18:10:12
     */
    protected function getModelClass(): string
    {
        return AdsModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     *
     * @date 2021-04-23 18:10:12
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
    protected function getLogDesc(): string
    {
        // TODO: Implement getLogDesc() method.
        return '';
    }

    function saveAfterCallback($model)
    {
        if(!is_null($model)){
            AdsModel::clearRedisCache($model->position);
        }
    }

    private function deleteAfterCallback($model, $isDelete)
    {
        if ($isDelete){
            $username = $this->getUser()->username;
            $new_pos = AdsModel::POSITION[$model->position];
            $new_status = AdsModel::STATUS[$model->status];
            $new_link = $model->url;
            $ads_type = AdsModel::ADS_TYPE[$model->type];
            if (!in_array($model->type, [1, 3]) ){
                return;
            }
            $mk_time = date('Y-m-d H:i:s');
            $msg = <<<MSG
操作人员: $username
项目名称: 搜同
广告类型: $ads_type ID:#$model->id
操作: 删除
旧值: 
    位置: $new_pos
    状态: $new_status
    链接: $new_link
操作时间: $mk_time
MSG;
            $this->tgReport($msg, $new_link);
        }
    }

    public function createAfterCallback($model)
    {
        $username = $this->getUser()->username;
        $new_pos = AdsModel::POSITION[$model->position];
        $new_status = AdsModel::STATUS[$model->status];
        $new_link = $model->url;
        $ads_type = AdsModel::ADS_TYPE[$model->type];
        if (!in_array($model->type, [1, 3]) ){
            return;
        }
        $mk_time = date('Y-m-d H:i:s');
        $msg = <<<MSG
操作人员: $username
项目名称: 搜同
广告类型: $ads_type ID:#$model->id
操作: 新增
新值: 
    位置: $new_pos
    状态: $new_status
    链接: $new_link
操作时间: $mk_time
MSG;
        $this->tgReport($msg, $new_link);
    }

    public function updateAfterCallback($model, $oldModel)
    {
        $username = $this->getUser()->username;
        $old_pos = AdsModel::POSITION[$oldModel->position];
        $new_pos = AdsModel::POSITION[$model->position];
        $old_status = AdsModel::STATUS[$oldModel->status];
        $new_status = AdsModel::STATUS[$model->status];
        $old_link = $oldModel->url;
        $new_link = $model->url;
        $ads_type = AdsModel::ADS_TYPE[$model->type];
        if ($old_link == $new_link && $old_status == $new_status){
            return;
        }
        if (!in_array($model->type, [1, 3]) && !in_array($oldModel->type, [1, 3])){
            return;
        }
        $mk_time = date('Y-m-d H:i:s');
        $msg = <<<MSG
操作人员: $username
项目名称: 搜同
广告类型: $ads_type ID:#$model->id
操作: 更新
原值: 
    位置: $old_pos
    状态: $old_status
    链接: $old_link
新值: 
    位置: $new_pos
    状态: $new_status
    链接: $new_link
操作时间: $mk_time
MSG;
        $this->tgReport($msg, $new_link);
    }

    //TG上报
    private function tgReport($content, $link){
        $time = TIMESTAMP;
        $sign = md5($time .'er9bEFZko1lUkyCsUtFWO3WtFnTN');
        $url = 'https://tg.microservices.vip/index.php?m=index&a=sendMessage';
        $data = [
            'app_name' => SYSTEM_ID,
            'msg' => $content,
            'timestamps' => $time,
            'sign' => $sign,
            'newest_link' => $link
        ];
        (new \tools\HttpCurl())->post($url, $data);
    }

    function _delActionAfter()
    {
        $p = $this->postArray();
        $model = AdsModel::where('id',$p['_pk'])->first();
        if(!is_null($model)){
            AdsModel::clearRedisCache($model->position);
        }

    }

    /**
     * 删除数据
     * 后台全局公共方法
     * @return mixed
     * @author xiongba
     * @date 2019-11-08 11:19:24
     */
    public function delAction() {
        if (!$this->getRequest()->isPost()) {
            return $this->ajaxError('请求错误');
        }
        $post = $this->postArray();
        $model = AdsModel::where('id', $post['_pk'])->first();
        if (empty($model)){
            return $this->ajaxSuccessMsg('操作成功');
        }

        try {
            $isOk = $model->delete();
            if ($isOk){
                $this->deleteAfterCallback($model, true);
                return $this->ajaxSuccessMsg('操作成功');
            }
            return $this->ajaxError('操作错误');
        }catch (Throwable $e){
            $this->deleteAfterCallback($model, false);
            return $this->ajaxError('操作错误');
        }
    }

    public function refreshAction()
    {
        foreach (AdsModel::POSITION as $_p){
            AdsModel::clearRedisCache($_p);
        }
        return $this->ajaxSuccess('成功');
    }
}