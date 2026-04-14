<?php

/**
 * Class PostmediaController
 * @author xiongba
 * @date 2023-06-09 20:11:01
 */
class PostmediaController extends BackendBaseController
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
            $item->load('post');
            $item->type_str = PostMediaModel::TYPE[$item->type]??'';
            $item->media_flag = 0;//0默认图片
            if($item->type == PostMediaModel::TYPE_IMG){
                $item->media_url_full = url_cover($item->media_url);
            }elseif($item->type == PostMediaModel::TYPE_VIDEO){
                $extension = pathinfo($item->media_url, PATHINFO_EXTENSION);
                if($extension == 'mp4'){
                    $item->media_flag = 1;//1 mp4
                    if (str_contains($item->media_url,"https://")){
                        $item->media_url_full = $item->media_url;
                    }else{
                        $item->media_url_full = url_videoMP4($item->media_url);
                    }
                }elseif($extension == 'm3u8'){
                    $item->media_flag = 2;;//2 m3u8
                    $item->media_url_full =  getAdminPlayM3u8($item->media_url,true);
                }
            }
            return $item;
        };
    }

    public function saveAction()
    {
        try {
            $data = $this->postArray();
            trigger_log(var_export($data,true));
            $pid = $data['pid'];
            $media_url = $data['media_url'];
            if (empty($pid) || empty($media_url)) {
                return $this->ajaxError('参数不对');
            }
            /** @var PostModel $post */
            $post = PostModel::find($pid);
            test_assert($post,'帖子不存在');
            $media = [
                'aff'          => $post->aff,
                'relate_type'  => \PostMediaModel::TYPE_RELATE_POST,
                'pid'          => $post->id,
                'media_url'    => $media_url,
                'thumb_width'  => 0,
                'thumb_height' => 0,
                'cover'        => $media_url,
                'type'         => PostMediaModel::TYPE_IMG,
                'status'       => PostMediaModel::STATUS_OK,
                'created_at'   => \Carbon\Carbon::now(),
                'updated_at'   => \Carbon\Carbon::now(),
            ];
            $is_ok = \PostMediaModel::create($media);
            test_assert($is_ok,'数据异常');
            $is_ok = $post->increment('photo_num');
            test_assert($is_ok,'数据异常');
            return $this->ajaxSuccessMsg('操作成功');
        }catch (Throwable $e){
            return $this->ajaxError($e->getMessage());
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
        try {
            if (!$this->getRequest()->isPost()) {
                return $this->ajaxError('请求错误');
            }
            $post = $this->postArray();
            $model = PostMediaModel::find($post['_pk']);
            test_assert($model,'操作错误');
            $is_ok = $model->delete();
            test_assert($is_ok,'系统异常');
            if ($model->type == PostMediaModel::TYPE_IMG){
                PostModel::where('id',$model->pid)->where('photo_num','>',0)->decrement('photo_num');
            }else{
                PostModel::where('id',$model->pid)->where('video_num','>',0)->decrement('video_num');
            }
            return $this->ajaxSuccessMsg('操作成功');
        }catch (Throwable $e){
            return $this->ajaxError($e->getMessage());
        }
    }

    /**
     * 删除数据
     * 后台全局公共方法
     * @return mixed
     * @author xiongba
     * @date 2019-11-08 11:19:24
     */
    public function delAllAction() {
        if (!$this->getRequest()->isPost()) {
            return $this->ajaxError('请求错误');
        }
        $post = $this->postArray();
        $ary = explode(',', $post['value'] ?? '');

        try {
            \DB::beginTransaction();
            foreach ($ary as $id) {
                if (empty($id)) {
                    continue;
                }
                /** @var PostMediaModel $model */
                $model = PostMediaModel::where('id',$id)->first();
                test_assert($model,'数据异常');
                $is_ok = $model->delete();
                test_assert($is_ok,'数据异常');
                if ($model->type == PostMediaModel::TYPE_IMG){
                    PostModel::where('id',$model->pid)->where('photo_num','>',0)->decrement('photo_num');
                }else{
                    PostModel::where('id',$model->pid)->where('video_num','>',0)->decrement('video_num');
                }
            }
            \DB::commit();
            return $this->ajaxSuccessMsg('操作成功');
        } catch (\Exception $e) {
            \DB::rollBack();
            return $this->ajaxError('操作错误');
        }
    }

    /**
     * 试图渲染
     * @return string
     * @author xiongba
     * @date 2023-06-09 20:11:01
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2023-06-09 20:11:01
     */
    protected function getModelClass(): string
    {
       return PostMediaModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2023-06-09 20:11:01
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


    /**
     * 资源切片
     * @return bool|mixed
     */
    public function passAction()
    {
        $pk = $_POST['_pk'] ?? 0;
        $model = PostMediaModel::find($pk);
        if ($model->status == PostMediaModel::STATUS_OK) {
            return $this->ajaxError('当前状态不可操作');
        }
        $model->updated_at = date('Y-m-d H:i:s');
        $model->status = PostMediaModel::STATUS_ING;
        if ($model->save()) {
            $re = PostMediaModel::approvedMv($model);
            if (stripos($re, 'success') !== false) {
                return $this->ajaxSuccess('切片操作成功');
            }
        }
        return $this->ajaxSuccess('操作失败');
    }
}