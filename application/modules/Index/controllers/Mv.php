<?php

use service\AiSdkService;
use service\TopCreatorService;

/**
 * 回调
 */
class MvController extends SiteController
{
    use \repositories\MvRepository,
        \repositories\UsersRepository;

    public function init()
    {
        parent::init();
    }

    /**
     * 上架用户上传的视频 m3u8切片完成后同步
     */
    public function indexAction()
    {
        $data = $this->post ?? array();
        //$crypt = new \tools\CryptService();
        //$check_sign = $crypt->check_sign($data);
        $data = json_decode(json_encode($data), true);
        error_log('视频回调' . print_r($data, true), 3, $this->logFile);
        /** @var MvModel $videoModel */
        if (!$data || !isset($data['mv_id'])) {
            return;
        }

        NotifyLogModel::addByM3u8($data['mv_id'],json_encode($data));
        /** @var MvSubmitModel $object */
        $object = MvSubmitModel::useWritePdo()->where('id', $data['mv_id'])->first();
        if (is_null($object)) {
            errLog('未找到对应视频');
            echo 'success';
            return;
        }
        if ($object->status == MvSubmitModel::STAT_CALLBACK_DONE) {
            if (stripos($object->m3u8, 'mp4') === false) {
                echo 'success';
                return;
            }
        }
        $videoModel = $object->toArray();

        $updateData = [];
        $updateData['m3u8'] = $data['source'];//预览视频 或小视频
        $updateData['status'] = MvModel::STAT_CALLBACK_DONE;
        $updateData['refresh_at'] = TIMESTAMP;
        $updateData['music_id'] = 0;
        (!$videoModel['thumb_width']) && $updateData['thumb_width'] = $data['thumb_width'];
        (!$videoModel['thumb_height']) && $updateData['thumb_height'] = $data['thumb_height'];
        if (!$videoModel['cover_thumb']){
            $data['cover_thumb'] && $updateData['cover_thumb'] = $data['cover_thumb'];
        }
        (!$videoModel['duration']) && $updateData['duration'] = $data['duration'];

        if ($videoModel['via'] == MvModel::VIA_LUSIR || $videoModel['via'] == MvModel::VIA_OFFICAL) {
            //官方来源视频 付费视频处理
        } else {
            $updateData['via'] = MvModel::VIA_USER;
            $updateData['duration'] = $data['duration'];

        }
        /** @var MemberModel $member */
        $member = \MemberModel::useWritePdo()->where('uid', $videoModel['uid'])->first();
        //如果作者不存在了
        if (is_null($member)) {
            $official_url = getOfficialUID();
            $member = MemberModel::useWritePdo()->where('uid', $official_url)->first();
        }
        if(is_null($member)){
            $object->delete();
            echo 'success';
            errLog("no fund user \r\n");
            return;
        }

        try {
            \DB::beginTransaction();
            //更新用户视频统计
            $member->increment('videos_count',1);
            $insertDat = $object->getAttributes();
            $insertDat = array_merge($insertDat, $updateData);
            unset($insertDat['id']);
            /** @var MvModel $releaseMv */
            if ($insertDat['coins'] <= 0) {
                $insertDat['coins'] = 0;
                $insertDat['is_free'] = MvModel::IS_FREE_YES;
            } else {
                $insertDat['is_free'] = MvModel::IS_FREE_NO;
            }
            $releaseMv = \MvModel::create($insertDat);
            if (is_null($releaseMv) || (!$releaseMv->id)) {
                throw new \Exception('发布库新增视频异常，操作失败');
            }
            $itOk = VideoScoreModel::createInit($releaseMv);
            if (empty($itOk)) {
                throw new \Exception('发布库新增视频异常，操作失败');
            }
            \DB::commit();
            //销毁
            $object->delete();
            //messageCenter
            MessageModel::createSystemMessage($member->uuid, MessageModel::SYSTEM_MSG_TPL_MV_PASS,
                ['title' => $releaseMv->title]);
            //处理视频标签关联
            $videoModel['tags'] && MvTagModel::createByAll($releaseMv->id, $releaseMv->tags);
            MvWordsModel::createForTitle($releaseMv->id, $releaseMv->title);
            redis()->del(\MvModel::REDIS_USER_VIDEOS_ITEM . $releaseMv->uid . '_1');
            MemberModel::clearFor($member);
            (new TopCreatorService())->incrUp($releaseMv->uid);//视频上传创作排行统计
            redis()->zAdd(\service\VideoScoreService::VIDEO_INIT_KEY, 1, $releaseMv->id); // 评分系统
            //视频通过数
            SysTotalModel::incrBy('now:mv:pass');
            echo 'success';
        } catch (Exception $exception) {
            \DB::rollBack();
            errLog("\r\n 回调进入发布库失败:".$exception->getMessage());
            return ;
        }
    }

    /**
     * 社区切片回调处理逻辑
     * @return void
     */
    public function post_mediaAction()
    {
        $data = $this->post ?? array();
        $data = json_decode(json_encode($data), true);
        error_log('社区切片回调' . print_r($data, true), 3, $this->logFile);
        if (!$data || !isset($data['mv_id'])) {
            return;
        }
        $data['__name__'] = '社区视频内容';
        NotifyLogModel::addByM3u8($data['mv_id'], json_encode($data));
        /** @var PostMediaModel $media */
        $media = PostMediaModel::useWritePdo()->where('id', $data['mv_id'])->first();
        if (is_null($media)) {
            errLog('未找到社区视频内容');
            echo 'success';
            return;
        }
        if (stripos($media->media_url, 'm3u8') !== false) {
            echo 'success';
            return;
        }

        try {
            transaction(function () use ($data,$media){
                $cover = $data['cover_thumb'] ?? '';
                $data = [
                    'thumb_width'  => $data['thumb_width'] ?? 0,
                    'thumb_height' => $data['thumb_height'] ?? 0,
                    'duration'     => $data['duration'] ?? 0,
                    'media_url'    => $data['source'] ?? '',
                    'status'       => PostMediaModel::STATUS_OK,
                    'updated_at'   => date('Y-m-d H:i:s'),
                ];
                if (!$media->cover){
                    $data['cover'] = $cover;
                }
                if ($media->update($data) <= 0) {
                    throw new Exception('系统异常');
                }
                $has = PostMediaModel::where('pid', $media->pid)
                    ->where('relate_type', $media->relate_type)
                    ->where('type', PostMediaModel::TYPE_VIDEO)
                    ->where('status', PostMediaModel::STATUS_ING)
                    ->first();
                if (!$has) {
                    if ($media->relate_type == PostMediaModel::TYPE_RELATE_POST) {
                        PostModel::where('id', $media->pid)->update([
                            'is_finished' => PostModel::FINISH_OK,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                        //清理缓存
                        PostModel::clearDetailCache($media->pid);
                    }
                }
            });
        } catch (Exception $exception) {
            \DB::rollBack();
            trigger_error('上架视频--处理失败：' . $exception->getMessage());
            exit('fail');
        }
        exit('success');
    }
    /**
     * 社区切片回调处理逻辑
     * @return void
     */
    public function girl_mediaAction()
    {
        $data = $this->post ?? array();
        $data = json_decode(json_encode($data), true);
        error_log('约炮切片回调' . print_r($data, true), 3, $this->logFile);
        if (!$data || !isset($data['mv_id'])) {
            return;
        }
        $data['__name__'] = '约炮视频内容';
        NotifyLogModel::addByM3u8($data['mv_id'], json_encode($data));
        /** @var GirlMediaModel $media */
        $media = GirlMediaModel::useWritePdo()->where('id', $data['mv_id'])->first();
        if (is_null($media)) {
            errLog('未找到社区视频内容');
            echo 'success';
            return;
        }
        if (stripos($media->media_url, 'm3u8') !== false) {
            echo 'success';
            return;
        }

        try {
            transaction(function () use ($data,$media){
                $cover = $data['cover_thumb'] ?? '';
                $data = [
                    'thumb_width'  => $data['thumb_width'] ?? 0,
                    'thumb_height' => $data['thumb_height'] ?? 0,
                    'duration'     => $data['duration'] ?? 0,
                    'media_url'    => $data['source'] ?? '',
                    'status'       => GirlMediaModel::STATUS_OK,
                    'updated_at'   => date('Y-m-d H:i:s'),
                ];
                if (!$media->cover){
                    $data['cover'] = $cover;
                }
                if ($media->update($data) <= 0) {
                    throw new Exception('系统异常');
                }
                $has = GirlMediaModel::where('pid', $media->pid)
                    ->where('relate_type', $media->relate_type)
                    ->where('type', GirlMediaModel::TYPE_VIDEO)
                    ->where('status', GirlMediaModel::STATUS_ING)
                    ->first();
                if (!$has) {
                    if ($media->relate_type == GirlMediaModel::TYPE_RELATE_POST) {
                        GirlModel::where('id', $media->pid)->update([
                            'is_finished' => GirlModel::FINISH_OK,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                        //清理缓存
                        GirlModel::clearDetailCache($media->pid);
                    }
                }
            });
        } catch (Exception $exception) {
            \DB::rollBack();
            trigger_error('上架视频--处理失败：' . $exception->getMessage());
            exit('fail');
        }
        exit('success');
    }

    public function testAction()
    {
        return ;
        /**Array
         * (
         * [mv_id] => 383
         * [duration] => 345
         * [cover_thumb] =>
         * [source] => /play/594f2ae8b675cd9fc66e61640e8890cc/594f2ae8b675cd9fc66e61640e8890cc.m3u8
         * [mp3] =>
         * [thumb_width] => 0
         * [thumb_height] =>
         * [sign] => 3d77904f85f115b65cd7ab8672815310
         * )*/

        $data = [
            'mv_id'=>2,
            'duration'=>345,
            'cover_thumb'=>'',
            'source'=>'/play/594f2ae8b675cd9fc66e61640e8890cc/594f2ae8b675cd9fc66e61640e8890cc.m3u8',
            'mp3'=>'',
            'thumb_width'=>0,
            'thumb_height'=>0,
            'sign'=>'d77904f85f115b65cd7ab8672815310',
        ];
        //$crypt = new \tools\CryptService();
        //$check_sign = $crypt->check_sign($data);
        $data = json_decode(json_encode($data), true);
        error_log('视频回调' . print_r($data, true), 3, $this->logFile);
        /** @var MvModel $videoModel */
        if (!$data || !isset($data['mv_id'])) {
            return;
        }

        NotifyLogModel::addByM3u8($data['mv_id'],json_encode($data));
        /** @var MvSubmitModel $object */
        $object = MvSubmitModel::useWritePdo()->where('id', $data['mv_id'])->first();
        if (is_null($object)) {
            errLog('未找到对应视频');
            echo 'success';
            return;
        }
        if ($object->status == MvSubmitModel::STAT_CALLBACK_DONE) {
            if (stripos($object->m3u8, 'mp4') === false) {
                echo 'success';
                return;
            }
        }
        $videoModel = $object->toArray();

        $updateData = [];
        $updateData['m3u8'] = $data['source'];//预览视频 或小视频
        $updateData['status'] = MvModel::STAT_CALLBACK_DONE;
        $updateData['refresh_at'] = TIMESTAMP;
        $updateData['music_id'] = 0;
        (!$videoModel['thumb_width']) && $updateData['thumb_width'] = $data['thumb_width'];
        (!$videoModel['thumb_height']) && $updateData['thumb_height'] = $data['thumb_height'];
        $data['cover_thumb'] && $updateData['cover_thumb'] = $data['cover_thumb'];
        (!$videoModel['duration']) && $updateData['duration'] = $data['duration'];

        if ($videoModel['via'] == MvModel::VIA_LUSIR || $videoModel['via'] == MvModel::VIA_OFFICAL) {
            //官方来源视频 付费视频处理
        } else {
            $updateData['via'] = MvModel::VIA_USER;
            $updateData['duration'] = $data['duration'];

        }
        /** @var MemberModel $member */
        $member = \MemberModel::useWritePdo()->where('uid', $videoModel['uid'])->first();
        //如果作者不存在了
        if (is_null($member)) {
            $official_url = getOfficialUID();
            $member = MemberModel::useWritePdo()->where('uid', $official_url)->first();
        }
        if(is_null($member)){
            $object->delete();
            echo 'success';
            errLog("no fund user \r\n");
            return;
        }

        try {
            \DB::beginTransaction();
                //更新用户视频统计
                $member->increment('videos_count',1);
                $insertDat = $object->getAttributes();
                $insertDat = array_merge($insertDat, $updateData);
                unset($insertDat['id']);
                /** @var MvModel $releaseMv */
                $releaseMv = \MvModel::create($insertDat);
                if (is_null($releaseMv) || (!$releaseMv->id)) {
                throw new \Exception('发布库新增视频异常，操作失败');
               }
            \DB::commit();
             //销毁
            $object->delete();
            //messageCenter
            MessageModel::createSystemMessage($member->uuid, MessageModel::SYSTEM_MSG_TPL_MV_PASS,
                ['title' => $releaseMv->title]);
            //处理视频标签关联
            $videoModel->tags && MvTagModel::createByAll($releaseMv->id, $releaseMv->tags);
            MvWordsModel::createForTitle($releaseMv->id, $releaseMv->title);
            redis()->del(\MvModel::REDIS_USER_VIDEOS_ITEM . $releaseMv->uid . '_1');
            MemberModel::clearFor($member);
            (new TopCreatorService())->incrUp($releaseMv->uid);//视频上传创作排行统计
            echo 'success';
        } catch (Exception $exception) {
            \DB::rollBack();
            errLog("\r\n 回调进入发布库失败:".$exception->getMessage());
            return ;
        }
    }

    //同步数据
    public function sysMvAction()
    {
        $data = $_POST;
        $sign = $data['sign'];
        if ($this->getSign($data) != $sign){
            exit("签名错误");
        }
        if (!$data['page'] || !$data['limit']){
            exit("参数不全");
        }
        $list  = MvModel::query()
            ->whereBetween('id', [13432, 60000])
            ->forPage($data['page'], $data['limit'])
            ->get()->toArray();
        echo json_encode(['data' => $list]);exit();
    }

    public function sysPostTopicAction(){
        $data = $_POST;
        $sign = $data['sign'];
        if ($this->getSign($data) != $sign){
            exit("签名错误");
        }
        if (!$data['xxx']){
            exit("参数不全");
        }
        $list  = PostTopicModel::queryBase()
            ->where('id', '<>', 73)
            ->get()->toArray();
        echo json_encode(['data' => $list]);exit();
    }

    public function sysPostAction(){
        $data = $_POST;
        $sign = $data['sign'];
        if ($this->getSign($data) != $sign){
            exit("签名错误");
        }
        if (!$data['page'] || !$data['limit']){
            exit("参数不全");
        }
        $list  = PostModel::queryBase()
            ->with('medias')
            ->where('topic_id', '<>', 73)
            ->forPage($data['page'], $data['limit'])
            ->get()->toArray();
        echo json_encode(['data' => $list]);exit();
    }

    public function sysStoryCategoryAction(){
        $data = $_POST;
        $sign = $data['sign'];
        if ($this->getSign($data) != $sign){
            exit("签名错误");
        }
        if (!$data['xxx']){
            exit("参数不全");
        }
        $list  = StoryTabModel::queryBase()
            ->get()->toArray();
        echo json_encode(['data' => $list]);exit();
    }

    public function sysStoryTagsAction(){
        $data = $_POST;
        $sign = $data['sign'];
        if ($this->getSign($data) != $sign){
            exit("签名错误");
        }
        if (!$data['xxx']){
            exit("参数不全");
        }
        $list  = StoryTagsModel::query()
            ->get()->toArray();
        echo json_encode(['data' => $list]);exit();
    }

    public function sysStoryAction(){
        $data = $_POST;
        $sign = $data['sign'];
        if ($this->getSign($data) != $sign){
            exit("签名错误");
        }
        if (!$data['page'] || !$data['limit']){
            exit("参数不全");
        }
        $list  = StoryModel::queryBase()
            ->with('series')
            ->forPage($data['page'], $data['limit'])
            ->get()->toArray();
        echo json_encode(['data' => $list]);exit();
    }

    private function getSign($data)
    {
        unset($data['sign']);
        $signKey = config('app.data_sync_key');
        ksort($data);
        $string = '';
        foreach ($data as $key => $datum) {
            if ($datum === '') {
                continue;
            }
            $string .= "{$key}={$datum}&";
        }
        $string .= 'key=' . $signKey;
        return md5($string);
    }

    /**
     * TG视频回调
     */
    public function tgMvVideoAction()
    {
        $data = $this->post ?? array();
        $data = json_decode(json_encode($data), true);
        error_log('tg视频回调' . print_r($data, true), 3, $this->logFile);
        if (!$data || !isset($data['mv_id'])) {
            return;
        }

        $media = MvTgModel::where('id', $data['mv_id'])
            ->first();

        if (!$media) {
            trigger_error('上架视频--没有找到:' . json_encode($data));
            exit('fail');
        }

        if ($media->status != 3){
            $data = [
                'cover'         => $data['cover_thumb'] ?? '',
                'width'         => $data['thumb_width'] ?? 0,
                'height'        => $data['thumb_height'] ?? 0,
                'duration'      => $data['duration'] ?? 0,
                'm3u8'          => $data['source'] ?? '',
                'status'        => 3,
                'updated_at'    => date('Y-m-d H:i:s'),
            ];
            $isOK = $media->update($data);
            if ($isOK){
//                $file = '/home/python_tg/gtt0105/file/' . basename($media->local_path);
//                //删除文件
//                if (file_exists($file)) {
//                    if (unlink($file)) {
//                        trigger_log("文件已删除: $file");
//                    } else {
//                        trigger_log("删除文件失败: $file");
//                    }
//                } else {
//                    trigger_log("文件不存在: $file");
//                }
            }
        }

        $ct = MvTgModel::where('status', 2)
            ->count('id');
        if ($ct <= 1){
            /** 查出5条切片 */
            MvTgModel::query()
                ->where('status', 1)
                ->orderBy('id')
                ->limit(1)
                ->get()
                ->map(function (MvTgModel $model){
                    $path = $model->local_path;
                    $path = str_replace('gtt0105/file', 'tg_mv', $path);
                    $path = "https://notify.stgay.pro/" . $path;
                    $data = [
                        'uuid'    => 0,
                        'm_id'    => $model->id,
                        'needImg' => 1,
                        'needMp3' => 0,
                        'playUrl' => $path
                    ];
                    $crypt = new \tools\CryptService();
                    $sign = $crypt->make_sign($data);
                    $data['sign'] = $sign;
                    $data['notifyUrl'] = 'https://notify.stgay.pro/index.php?m=mv&a=tgMvVideo';

                    $curl = new \tools\CurlService();
                    $return = $curl->request('http://examine-new.xmyy8.co/queue.php', $data);
                    errLog("post reslice req:" . var_export([$data, $return], true));
                    if ($return != 'success') {
                        trigger_error('审核失败-----' . print_r($return, true));
                        return false;
                    }
                    $model->status = 2;
                    $model->save();
                    return true;
                });
        }

        echo 'success';exit();
    }

    public function ai_htAction()
    {
        AiSdkService::image_face_back();
    }

    //小蓝数据同步
    public function syncXlpMvDataAction(){
        if (!$this->getRequest()->isPost()) {
            exit('fail');
        }
        $post = $_POST;
        if ($post['pwd'] != md5('c1999b118f786d90' . $post['time'])){
            exit('fail');
        }

        trigger_log("sync_blue_data--\n" . print_r($post, true));
        //判读是否存在
        $data = MvModel::where('title', $post['title'])->first();
        if ($data) {
            exit('fail');
        }
        $items = [10065,100039,100047,100037,100114];
        $uid = collect($items)->random();
        $post['uid'] = $uid;
        $post['comment'] = 0;
        $post['is_hide'] = MvModel::IS_HIDE_YES;
        $post['like'] = 0;
        $post['count_pay'] = 0;
        $post['created_at'] = TIMESTAMP;
        $post['type'] = MvModel::TYPE_LONG;
        unset($post['pwd']);
        unset($post['id']);

        try {
            MvModel::create($post);
            //(new TopCreatorService())->incrUp($post['uid']);//视频上传创作排行统计
        } catch (Exception $e) {
            errLog("zycx-error:" . $e->getMessage());
            exit('fail');
        }
        exit('success');
    }

    //小蓝数据同步
    public function syncXlpPostDataAction(){
        if (!$this->getRequest()->isPost()) {
            exit('fail');
        }
        $post = $_POST;
        if ($post['pwd'] != md5('c1999b118f786d90' . $post['time'])){
            exit('签名错误');
        }

        trigger_log("sync_blue_data--\n" . print_r($post, true));
        //判读是否存在
        $data = PostModel::where('_id', $post['id'])->first();
        if ($data) {
            exit('帖子已经存在');
        }

        $_id = $post['id'];
        $medias = $post['medias'];
        unset($post['id']);
        unset($post['medias']);

        $items = [10065,100039,100047,100037,100114];
        $aff = collect($items)->random();
        $data = PostModel::make();
        $data->_id = $_id;
        $data->aff = $aff;
        $data->content = $data['content'] ?? '';
        $data->is_deleted = $post['is_deleted'];
        $data->like_num = $post['like_num'];
        $data->comment_num = 0;
        $data->is_best = $post['is_best'];
        $data->photo_num = $post['photo_num'];
        $data->video_num = $post['video_num'];
        $data->is_finished = $post['is_finished'];
        $data->ipstr = $post['ipstr'];
        $data->cityname = $post['cityname'];
        $data->topic_id = $post['topic_id'];
        $data->view_num = $post['view_num'];
        $data->refresh_at = \Carbon\Carbon::now();
        $data->title = $post['title'];
        $data->price = $post['price'];
        $data->status = PostModel::STATUS_WAIT;
        $data->created_at = $post['created_at'];
        $data->updated_at = $post['updated_at'];
        $data->favorite_num = $post['favorite_num'];
        $data->type = $post['type'];
        $data->set_top = $post['set_top'];
        $data->save();

        foreach ($medias as $v) {
            $tmp = [
                'aff'          => 0,
                'cover'        => trim(parse_url($v['cover'], PHP_URL_PATH), '/'),
                'thumb_width'  => $v['thumb_width'],
                'thumb_height' => $v['thumb_height'],
                'duration'     => $v['duration'],
                'pid'          => $data->id,
                'media_url'    => parse_url($v['media_url'], PHP_URL_PATH),
                'relate_type'  => $v['relate_type'],
                'status'       => $v['status'],
                'type'         => $v['type'],
                'created_at'   => $v['created_at'],
                'updated_at'   => \Carbon\Carbon::now(),
            ];
            $isOk = PostMediaModel::create($tmp);
            test_assert($isOk, '保存图片资源异常');
        }

        exit('success');
    }

    //搜同短视频数据同步
    public function syncShortMvAction(){
        if (!$this->getRequest()->isPost()) {
            exit('fail');
        }
        $post = $_POST;
        if ($post['pwd'] != md5('xkf094kf23f24p4p4k4k4k4k4' . $post['time'])){
            exit('签名错误');
        }
        $page = $post['page'];
        $last_id = $post['last_id'];

        $data = MvModel::queryBase()
            ->where('id','>',$last_id)
            ->where('type',MvModel::TYPE_SHORT)
            ->where('is_aw',MvModel::AW_NO)
            ->forPage($page,100)
            ->toBase()
            ->get();

        echo json_encode(['status' => 1, 'msg' => 'success','data' => $data]);exit(0);
    }
}