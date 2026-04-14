<?php

class SeedpostController extends BackendBaseController
{
    use \traits\DefaultActionTrait;
    use \traits\DefaultActionTrait {
        doSave as fatherSave;
    }

    /**
     * 列表数据过滤
     * @return Closure
     */
    protected function listAjaxIteration()
    {
        return function (SeedPostModel $item) {
            $item->load('medias');
            $item->status_str = SeedPostModel::STATUS_TIPS[$item->status] ?? '';
            $item->finished_str = SeedPostModel::FINISHED_TIPS[$item->is_finished] ?? '';
            $item->type_str = SeedPostModel::TYPE_TIPS[$item->type] ?? '';
            $imgs = [];
            $videos = [];
            $item->video_img = '';
            $item->video_url = '';
            $item->media_url = '';
            $item->video_duration = 0;
            $medias = $item->medias;
            $medias = $medias->toArray();
            foreach ($medias as $v) {
                if ($v['type'] == SeedPostMediaModel::TYPE_IMG) {
                    $v['media_url'] = url_cover($v['media_url']);
                    $imgs[] = $v;
                }
                if ($v['type'] == SeedPostMediaModel::TYPE_VIDEO) {
                    $videos[] = $v;
                    $item->video_img = $v['cover'];
                    $item->media_url = $v['media_url'];
                    if (substr($item->media_url, -4) == '.mp4') {
                        $item->video_url = 'http://play.xmyy8.co/' . $v['media_url'];
                    }else{
                        $item->video_url = getAdminPlayM3u8($v['media_url']);
                    }
                    $item->video_duration = $v['duration'];
                }
            }
            $item->show_imgs = $imgs;
            if (count($imgs) < 17){
                $count = 17 - count($imgs);
                for ($i=1;$i<=$count;$i++){
                    $imgs[] = [
                        'media_url' => '',
                        'ori_media_url' => '',
                    ];
                }
            }
            $item->imgs = $imgs;
            $item->videos = $videos;
            unset($item->medias);
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取本控制器和哪个model绑定
     * @return string
     */
    protected function getModelClass(): string
    {
        return SeedPostModel::class;
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
     * @author xiongba
     */
    protected function getLogDesc(): string
    {
        return '';
    }

    public function deleteAfterCallback($model, $isDelete)
    {
        if ($model && $isDelete) {
            SeedPostMediaModel::where('pid', $model->id)
                ->where('relate_type', SeedPostMediaModel::TYPE_RELATE_POST)
                ->delete();
        }
    }

    protected function doSave($data)
    {
        // 编辑框的
        if (!isset($data['topic_id'])) {
            return $this->fatherSave($data);
        }

        return transaction(function () use ($data) {
            $model = $this->fatherSave($data);
            //图片
            $imgs = array_filter($data['img_url']);
            //视频
            $video = [];
            if ($data['video_url']){
                $video['video_url'] = $data['video_url'];
                $video['video_duration'] = $data['video_duration'];
                $video['video_img'] = $data['video_img'];
                if (end($video['video_url']) == 'mp4') {
                    test_assert(false, '不能填MP4地址,只能填m3u8地址');
                }
            }
            // 清理掉原有资源 重新建立关联数据
            SeedPostMediaModel::where('pid', $model->id)
                ->where('relate_type', SeedPostMediaModel::TYPE_RELATE_POST)
                ->get()
                ->map(function ($item) {
                    $isOk = $item->delete();
                    test_assert($isOk, '删除数据异常');
                });

            $photo_num = 0;
            $video_len = 0;
            // 图片
            foreach ($imgs as $v) {
                $tmp = [
                    'cover'        => trim(parse_url($v, PHP_URL_PATH), '/'),
                    'thumb_width'  => 0,
                    'thumb_height' => 0,
                    'duration'     => 0,
                    'pid'          => $model->id,
                    'media_url'    => trim(parse_url($v, PHP_URL_PATH), '/'),
                    'relate_type'  => SeedPostMediaModel::TYPE_RELATE_POST,
                    'status'       => SeedPostMediaModel::STATUS_OK,
                    'type'         => SeedPostMediaModel::TYPE_IMG,
                ];
                $isOk = SeedPostMediaModel::create($tmp);
                test_assert($isOk, '保存图片资源异常');
                $photo_num++;
            }
            $is_finished = SeedPostModel::FINISHED_OK;
            // 视频 mp4
            if ($video && $video['video_url']) {
                $tmp = [
                    'cover'        => trim(parse_url($video['video_img'], PHP_URL_PATH), '/'),
                    'thumb_width'  => 0,
                    'thumb_height' => 0,
                    'duration'     => $video['video_duration'],
                    'pid'          => $model->id,
                    'media_url'    => trim(parse_url($video['video_url'], PHP_URL_PATH), '/'),
                    'relate_type'  => SeedPostMediaModel::TYPE_RELATE_POST,
                    'status'       => SeedPostMediaModel::STATUS_OK,
                    'type'         => SeedPostMediaModel::TYPE_VIDEO,
                ];
                $isOk = SeedPostMediaModel::create($tmp);
                test_assert($isOk, '保存视频资源异常');
                $video_len = 1;
            }
            $model->is_finished = $is_finished;
            $model->photo_ct = $photo_num;
            $model->video_ct = $video_len;
            $isOk = $model->save();
            test_assert($isOk, '更新状态错误');

            SeedPostModel::clear_seed_detail($model->id);
            return $model;
        });
    }

    public function batchAddTopicAction(){
        if (!$this->getRequest()->isPost()) {
            return $this->ajaxError('请求错误');
        }
        $post = $this->postArray();
        $ary = explode(',', $post['post_ids'] ?? '');
        $ary = array_filter($ary);
        $ary = array_unique($ary);
        $posts = SeedPostModel::whereIn('id', $ary)->get();
        $topic_id = $post['topic_id'];

        try {
            transaction(function ()use($posts,$topic_id){
                /** @var SeedPostModel $post */
                foreach ($posts as $post) {
                    $post->topic_id = $topic_id;
                    $isOK = $post->save();
                    test_assert($isOK,"保存失败");
                    SeedPostModel::clear_seed_detail($post->id);
                }
            });
            return $this->ajaxSuccess('更新成功');
        }catch (Exception $e){
            return $this->ajaxError($e->getMessage());
        }
    }

    public function addBatchPayAction(){
        if (!$this->getRequest()->isPost()) {
            return $this->ajaxError('请求错误');
        }
        $post = $this->postArray();
        $ary = explode(',', $post['post_ids'] ?? '');
        $ary = array_filter($ary);
        $ary = array_unique($ary);
        $posts = SeedPostModel::whereIn('id', $ary)->get();
        $type = $post['type'];
        $coins = (int)$post['coins'];
        if ($type == SeedPostModel::TYPE_COIN && $coins <= 0){
            return $this->ajaxError('金币种子解锁金币数不能小于1');
        }

        try {
            transaction(function ()use($posts,$type,$coins){
                /** @var SeedPostModel $post */
                foreach ($posts as $post) {
                    $post->type = $type;
                    if ($type == SeedPostModel::TYPE_COIN){
                        $post->coins = $coins;
                    }else{
                        $post->coins = 0;
                    }
                    $isOK = $post->save();
                    test_assert($isOK,"保存失败");
                    SeedPostModel::clear_seed_detail($post->id);
                }
            });
            return $this->ajaxSuccess('更新成功');
        }catch (Exception $e){
            return $this->ajaxError($e->getMessage());
        }
    }


    public function addBatchDeleteAction(){
        if (!$this->getRequest()->isPost()) {
            return $this->ajaxError('请求错误');
        }
        $post = $this->postArray();
        $ary = explode(',', $post['post_ids'] ?? '');
        $ary = array_filter($ary);
        $ary = array_unique($ary);
        $posts = SeedPostModel::whereIn('id', $ary)->get();
        $status = $post['status'];

        try {
            transaction(function ()use($posts,$status){
                /** @var SeedPostModel $post */
                foreach ($posts as $post) {
                    $post->status = $status;
                    $isOK = $post->save();
                    test_assert($isOK,"保存失败");
                    SeedPostModel::clear_seed_detail($post->id);
                }
            });
            return $this->ajaxSuccess('更新成功');
        }catch (Exception $e){
            return $this->ajaxError($e->getMessage());
        }
    }

    public function addBatchRefreshAction(){
        $ids = explode(',', trim($this->post['ids'], ','));
        if (!$ids){
            return $this->ajaxError('数据异常');
        }
        try {
            $data = [
                'created_at' => \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ];
            SeedPostModel::whereIn('id',$ids)->update($data);
            return $this->ajaxSuccess("操作成功");
        }catch (Exception $e){
            return $this->ajaxError($e->getMessage());
        }
    }
}