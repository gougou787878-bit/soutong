<?php

/**
 * Class PostController
 * @author xiongba
 * @date 2023-06-09 20:10:18
 */
class PostController extends BackendBaseController
{
    use \traits\DefaultActionTrait;
    use \traits\DefaultActionTrait {
        doSave as fatherSave;
    }

    const NOTIFY_PROJECT = [
        'soul' => ['name' => 'soul', 'url' => 'http://soul-index.we-cname2.com/index.php/notify/sync_post'],
    ];

    /**
     * 列表数据过滤
     * @return Closure
     * @author xiongba
     * @date 2019-12-02 17:08:03
     */
    protected function listAjaxIteration()
    {
        return function ($item) {
            $item->set_top_str = $item->set_top?'顶':'未';
            $item->bast_str = PostModel::BEST_TIPS[$item->is_best] ?? '未';
            $item->deleted_str = PostModel::DELETED_TIPS[$item->is_deleted] ?? '';
            $item->finish_str = PostModel::FINISH_TIPS[$item->is_finished] ?? '';
            $item->status_str = PostModel::STATUS_TIPS[$item->status] ?? '';
            $item->category_str = PostModel::TYPE_TIPS[$item->category] ?? '';
            $item->type_str = PostModel::TYPE_PAY_TIPS[$item->type] ?? '';
            $item->topic_str = $item->topic ? $item->topic->name : '';
            $item->user_nickname = $item->member->nickname;
            $item->user_aff = $item->member->aff;
            $item->user_thumb = $item->member->avatar_url;
            $item->user_expired_at_str = date('Y-m-d', strtotime($item->member->expired_at));
            $item->user_vip_level_str = MemberModel::USER_VIP_TYPE[$item->member->vip_level] ?? '未知';
            //组装图片和视频
            $item->img_0 = '';
            $item->img_2 = '';
            $item->img_3 = '';
            $item->img_4 = '';
            $item->img_5 = '';
            $item->img_6 = '';
            $item->img_7 = '';
            $item->video_img = '';
            $item->video_url = '';
            $item->video_duration = 0;
            $imgs = [];
            $videos = [];
            foreach ($item->medias as $v) {
                if ($v->type == PostMediaModel::TYPE_IMG) {
                    $v->media_url = url_cover($v->media_url);
                    $imgs[] = $v;
                }
                if ($v->type == PostMediaModel::TYPE_VIDEO) {
                    $videos[] = $v;
                    $item->video_img = url_cover($v->cover);
                    $item->video_url = $v->media_url;
                    $item->video_duration = $v->duration;
                }
            }

            foreach ($imgs as $k => $val){
                $tmp_key = 'img_'.$k;
                $item->$tmp_key = $val->media_url;
            }

            $item->imgs = $imgs;
            $item->videos = $videos;

            return $item;

        };
    }

    protected function doSave($data)
    {
        // 编辑框的
        if (!isset($data['aff'])) {
            return $this->fatherSave($data);
        }

        $aff = $data['aff'] = (int)($data['aff'] ?? 0);
        $member = MemberModel::firstAff($aff);
        test_assert($member, '未找到用户');
        //图片
        $imgs  = [];
        for($i = 0; $i < 8; $i++){
            $k = 'img_'.$i;
            if ($data[$k]){
                $imgs[] = $data[$k];
            }
        }
        //视频
        $video = [];
        if ($data['video_url']){
            $video['video_url'] = $data['video_url'];
            $video['video_duration'] = $data['video_duration'];
            $video['video_img'] = $data['video_img'];
        }
        return transaction(function () use ($data,$imgs,$video,$member) {
            /** @var PostModel $model */
            $model = $this->fatherSave($data);
            $count = PostMediaModel::where('pid', $model->id)
                ->where('relate_type', PostMediaModel::TYPE_RELATE_POST)
                ->where('type',PostMediaModel::TYPE_IMG)
                ->count('id');
            //原图片数量不超过8张，编辑才有效
            if ($count < 9){
                // 清理掉原有资源 重新建立关联数据
                PostMediaModel::where('pid', $model->id)
                    ->where('relate_type', PostMediaModel::TYPE_RELATE_POST)
                    ->get()
                    ->map(function ($item) {
                        $isOk = $item->delete();
                        test_assert($isOk, '删除数据异常');
                    });

                $photo_num = count($imgs);
                // 图片
                foreach ($imgs as $v) {
                    $tmp = [
                        'aff'          => $data['aff'],
                        'cover'        => '',
                        'thumb_width'  => 0,
                        'thumb_height' => 0,
                        'duration'     => 0,
                        'pid'          => $model->id,
                        'media_url'    => trim(parse_url($v, PHP_URL_PATH), '/'),
                        'relate_type'  => PostMediaModel::TYPE_RELATE_POST,
                        'status'       => PostMediaModel::STATUS_OK,
                        'type'         => PostMediaModel::TYPE_IMG,
                    ];
                    $isOk = PostMediaModel::create($tmp);
                    test_assert($isOk, '保存图片资源异常');
                }
                $model->photo_num = $photo_num;
            }else{
                //清除
                PostMediaModel::where('pid', $model->id)
                    ->where('relate_type', PostMediaModel::TYPE_RELATE_POST)
                    ->where('type',PostMediaModel::TYPE_VIDEO)
                    ->get()
                    ->map(function ($item) {
                        $isOk = $item->delete();
                        test_assert($isOk, '删除数据异常');
                    });
            }
            $video_len = 0;
            $is_finished = PostModel::FINISH_OK;
            // 视频 mp4
            if ($video) {
                if (!$video['video_img']) {
                    test_assert(false, '视频封面必须上传');
                }
                $tmp = [
                    'aff'          => $data['aff'],
                    'cover'        => trim(parse_url($video['video_img'], PHP_URL_PATH), '/'),
                    'thumb_width'  => 0,
                    'thumb_height' => 0,
                    'duration'     => $video['video_duration'],
                    'pid'          => $model->id,
                    'media_url'    => parse_url($video['video_url'], PHP_URL_PATH),
                    'relate_type'  => PostMediaModel::TYPE_RELATE_POST,
                    'status'       => PostMediaModel::STATUS_OK,
                    'type'         => PostMediaModel::TYPE_VIDEO,
                ];
                $isOk = PostMediaModel::create($tmp);
                test_assert($isOk, '保存视频资源异常');
                $video_len++;
            }
            $model->video_num = $video_len;
            $model->is_finished = $is_finished;
            if (!$data['id']){
                $model->created_at = \Carbon\Carbon::now();
                $model->refresh_at = \Carbon\Carbon::now();
            }
            $model->updated_at = \Carbon\Carbon::now();
            //$model->status = PostModel::STATUS_PASS;
            $model->is_deleted = PostModel::DELETED_OK;
            $model->ipstr = '127.0.0.1';
            $model->cityname = '火星';
            $isOk = $model->save();
            test_assert($isOk, '更新状态错误');

            // 更新主题下帖子数据
            $rs = PostTopicModel::where('id', $model->topic_id)->first();
            test_assert($rs, '主题不存在');
            $rs->post_num = PostModel::where('topic_id', $model->topic_id)->where('status', PostModel::STATUS_PASS)->count();
            $isOk = $rs->save();
            test_assert($isOk, '更新主题帖子计数异常');

            if (!$data['_pk']){
                \MemberRankModel::addMemberRank($member->uuid,\MemberRankModel::FIELD_UPLOAD);
            }

            return $model;
        });
    }

    /**
     * 试图渲染
     * @return string
     * @author xiongba
     * @date 2023-06-09 20:10:18
     */
    public function indexAction()
    {
        $topics = PostTopicModel::get()->pluck('name', 'id')->toArray();
        $this->assign('topicArr', $topics);
        $this->assign('topicId', $_GET['topic_id'] ?? '');
        $project = self::NOTIFY_PROJECT;
        $project = array_map(function ($val){
            return $val['name'];
        }, $project);
        $this->assign('notify_project', $project);
        $this->display();
    }

    public function checkAction(){
        $topics = PostTopicModel::get()->pluck('name', 'id')->toArray();
        $this->assign('topicArr', $topics);
        $this->assign('topicId', $_GET['topic_id'] ?? '');
        $this->display();
    }

    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2023-06-09 20:10:18
     */
    protected function getModelClass(): string
    {
       return PostModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2023-06-09 20:10:18
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

    public function refreshAction(){
        PostModel::where(['id'=>$_REQUEST['id']])->update(['refresh_at'=>date("Y-m-d H:i:s")]);
        return $this->ajaxSuccess('成功');
    }

    public function passAction()
    {
        $pk = $_POST['_pk'] ?? 0;
        $model = PostModel::find($pk);
        if ($model->status != PostModel::STATUS_WAIT) {
            return $this->ajaxError('当前状态不可操作');
        }
        $model->status = PostModel::STATUS_PASS;
        $model->updated_at = date("Y-m-d H:i:s");
        $model->refresh_at = date("Y-m-d H:i:s");
        if ($model->save()) {
            PostTopicModel::where('id',$model->topic_id)->increment('post_num');
            $medias = $model->load('medias')->medias;
            if($medias){
                foreach ($medias as $_media){
                    if($_media->type ==PostMediaModel::TYPE_VIDEO && $_media->status != PostMediaModel::STATUS_OK){
                        PostMediaModel::approvedMv($_media);//切片申请
                        PostMediaModel::where('id',$_media->id)->update(['status' => PostMediaModel::STATUS_ING]);
                    }
                }
            }

            //添加上传排行榜
            $postMember = MemberModel::where('aff',$model->aff)->first();
            \MemberRankModel::addMemberRank($postMember->uuid,\MemberRankModel::FIELD_UPLOAD);

            return $this->ajaxSuccess('审核成功');
        }

        return $this->ajaxSuccess('操作失败');
    }

    public function refuseUserUploadAction()
    {
        $pk = $_POST['_pk'] ?? 0;
        $memo = $this->post['refused'] ?? '帖子内容描述与搜同看片平台不符';
        $model = PostModel::find($pk);
        if ($model->status != PostModel::STATUS_WAIT) {
            return $this->ajaxError('当前状态不可操作');
        }
        $model->status = PostModel::STATUS_UNPASS;
        $model->refuse_reason = $memo;
        $model->updated_at = date("Y-m-d H:i:s");
        if ($model->save()) {
            MessageModel::createSystemMessage($model->member->uuid, MessageModel::SYSTEM_MSG_TPL_POST_REFUSE,
                ['title' => $model->title, 'reason' => $memo . '，官方up群: https://lynnconway.me/bluemv']);
            return $this->ajaxSuccess('审核成功');
        }
        return $this->ajaxError('操作失败');
    }

    //帖子后台统计
    public function totalAction()
    {
        $where = array_merge(
            $this->getSearchLikeParam(),
            $this->getSearchWhereParam(),
            $this->getSearchBetweenParam()
        );
        //发帖总数、免费帖子数量、收费帖子数量、发帖总收益
        $query = PostModel::query()->where('status',1)->where($where);
        $totalPostQuery = clone $query;
        $totalVipPostQuery = clone $query;
        $totalCoinsPostQuery = clone $query;
        $totalPostCoinsQuery = clone $query;
        $totalPost = $totalPostQuery->count();
        $totalVipPost = $totalVipPostQuery->where('type', PostModel::TYPE_PAY_VIP)->count();
        $totalCoinsPost = $totalCoinsPostQuery->where('type',  PostModel::TYPE_PAY_COINS)->count();
        $totalPostCoins = $totalPostCoinsQuery->where('price', '>', 0)->sum('reward_amount');

        $data = [
            'totalPost'        => $totalPost,
            'totalVipPost'     => $totalVipPost,
            'totalCoinsPost'   => $totalCoinsPost,
            'totalPostCoins'   => $totalPostCoins,
        ];
        return $this->ajaxSuccess($data);
    }

    public function addBatchPayAction(){
        if (!$this->getRequest()->isPost()) {
            return $this->ajaxError('请求错误');
        }
        $post = $this->postArray();
        $ary = explode(',', $post['post_ids'] ?? '');
        $ary = array_filter($ary);
        $ary = array_unique($ary);
        $posts = PostModel::whereIn('id', $ary)->get();
        $type = $post['type'];
        $coins = (int)$post['price'];
        if ($type == PostModel::TYPE_PAY_COINS && $coins <= 0){
            return $this->ajaxError('金币贴解锁金币数不能小于1');
        }

        try {
            transaction(function ()use($posts,$type,$coins){
                /** @var PostModel $post */
                foreach ($posts as $post) {
                    $post->type = $type;
                    if ($type == PostModel::TYPE_PAY_COINS){
                        $post->price = $coins;
                    }else{
                        $post->price = 0;
                    }
                    $isOK = $post->save();
                    test_assert($isOK,"保存失败");
                    PostModel::clearDetailCache($post->id);
                }
            });
            return $this->ajaxSuccess('更新成功');
        }catch (Exception $e){
            return $this->ajaxError($e->getMessage());
        }
    }

    public function sync_selectAction()
    {
        try {
            $post = $this->postArray();
            $ary = explode(',', $post['ids'] ?? '');
            $ary = array_filter($ary);
            $topicId = $post['topic_id'] ?? 0;
            $aff = $post['aff'] ?? '';
            $aff = explode(",", $aff);
            $aff = array_unique(array_filter($aff));
            $project = $post['project'] ?? '';
            test_assert($topicId, '未设置项目对应话题');
            test_assert(count($aff), '未设置项目对应用户aff');
            $project = self::NOTIFY_PROJECT[$project] ?? '';
            test_assert($project, '请选择项目');
            $url = $project['url'];
            $http = new \tools\HttpCurl();
            PostModel::with('medias')
                ->whereIn('id', $ary)
                ->where('status', PostModel::STATUS_PASS)
                ->where('is_finished', PostModel::FINISH_OK)
                ->get()
                ->map(function ($item) use ($url, $topicId, $aff, $http) {
                    $sync_data = $item->toArray();
                    $sync_data['id'] = strtoupper('xlp') . '_' . $sync_data['id'];
                    $sync_data['topic_id'] = $topicId;
                    $sync_data['aff'] = (int)(count($aff) == 1 ? $aff[0] : $aff[array_rand($aff)]);
                    $sync_data['medias'] = json_encode($sync_data['medias']);
                    $sync_data = array_map(function ($v) {
                        return $v === null ? '' : $v;
                    }, $sync_data);
                    $sync_data['unlock_coins'] = intval($sync_data['price'] * 10);
                    $sync_data['sign'] = $this->getSign($sync_data);
                    $rs = $http->post($url, $sync_data);
                    test_assert($rs == 'success', $rs);
                });
            return $this->ajaxSuccessMsg('同步成功');
        } catch (Throwable $e) {
            return $this->ajaxError($e->getMessage());
        }
    }

    private function getSign($data)
    {
        unset($data['sign']);
        $signKey = '132f1537f85scxpcm59f7e318b9epa51';
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

    public function batchCheckAction(){
        if (!$this->getRequest()->isPost()) {
            return $this->ajaxError('请求错误');
        }
        $post = $this->postArray();
        $ary = explode(',', $post['post_ids'] ?? '');
        $ary = array_filter($ary);
        $ary = array_unique($ary);
        $posts = PostModel::whereIn('id', $ary)
            ->where('status', PostModel::STATUS_WAIT)
            ->get();
        $status = $post['status'];
        if (!in_array($status,[PostModel::STATUS_PASS,PostModel::STATUS_UNPASS])){
            return $this->ajaxError('系统异常');
        }
        $reason = $post['refused'] ?? '帖子内容描述与搜同APP平台不符';

        //通过
        if ($status == PostModel::STATUS_PASS){
            transaction(function ()use($posts){
                /** @var PostModel $post */
                foreach ($posts as $post) {
                    $post->status = \PostModel::STATUS_PASS;
                    $post->updated_at = \Carbon\Carbon::now();
                    $post->refresh_at = \Carbon\Carbon::now();
                    if ($post->save()) {
                        //维护
                        $topic_id = explode(',',$post->topic_id);
                        /** @var PostTopicModel $postTopic */
                        $postTopic = PostTopicModel::where('id',$topic_id)->first();
                        $postTopic->update([
                            'post_num' => $postTopic->post_num + 1,
                            'updated_at' => \Carbon\Carbon::now()
                        ]);
                        $medias = $post->load('medias')->medias;
                        if($medias){
                            /** @var PostMediaModel $_media */
                            foreach ($medias as $_media){
                                if($_media->type ==PostMediaModel::TYPE_VIDEO && $_media->status != PostMediaModel::STATUS_OK){
                                    PostMediaModel::approvedMv($_media);//切片申请
                                    $_media->status = PostMediaModel::STATUS_ING;
                                    $_media->save();
                                }
                            }
                        }
                        //发送系统消息
                        MessageModel::createSystemMessage($post->member->uuid, MessageModel::SYSTEM_MSG_TPL_POST_PASS,
                            ['title' => $post->title]);
                    }
                }
            });
        }else{
            transaction(function () use ($posts,$reason) {
                /** @var PostModel $post */
                foreach ($posts as $post) {
                    $post->status = \PostModel::STATUS_UNPASS;
                    $post->refuse_reason = $reason;
                    $post->updated_at = \Carbon\Carbon::now();
                    if ($post->save()) {
                        MessageModel::createSystemMessage($post->member->uuid, MessageModel::SYSTEM_MSG_TPL_POST_REFUSE,
                            ['title' => $post->title, 'reason' => $reason]);
                    }
                }
            });
        }

        return $this->ajaxSuccess('审核成功');
    }

    public function fakeCommentAction() {
        if (!$this->getRequest()->isPost()) {
            return $this->ajaxError('请求错误');
        }
        $post = $this->postArray();
        $id = $post['_pk'] ?? '';
        $aff = $post['aff'] ?? '';
        $content = $post['content'] ?? '';
        $num = intval($post['num'] ?? 1);
        $num = max($num, 1);
        $num = min($num, 10);

        $arr_aff = [];
        $post = \PostModel::find($id);
        test_assert($post,'此帖子不存在', 422);

        if (!empty($aff)) {
            $exist = MemberModel::where('uid', $aff)->exists();
            if (!$exist) {
                return $this->ajaxError('用户aff不存在');
            }
            $arr_aff[] = $aff;
        }else {
            //指定用户aff还是随机aff
            $numbers = range(1, 100000);        // 创建数组
            shuffle($numbers);                  // 打乱顺序
            $arr_aff = array_slice($numbers, 0, $num);
            $arr_aff = MemberModel::whereIn('uid', $arr_aff)->pluck('aff')->toArray();
        }
        if (empty($arr_aff)){
            return $this->ajaxError('评论用户不能为空');
        }

        //自定义评论
        if (!empty($content)) {
            $rs = $this->createPostComment($arr_aff[0]?? rand(10000, 20000), $id, $content);
        }else {
            for($i=0;$i<$num;$i++) {
                $_aff = $arr_aff[$i] ?? $arr_aff[0];
                $_content = FakeCommentModel::getRandContentByPost();
                test_assert($_content,'随机评论内容为空，请先添加', 422);
                $this->createPostComment($_aff, $id, $_content);
            }
        }
        return $this->ajaxSuccessMsg("评论成功");
    }

    public function createPostComment($aff, $id, $content)
    {
        $data = [
            'post_id'       => $id,
            'pid'           => 0,
            'aff'           => $aff,
            'comment'       => $content,
            'status'        => PostCommentModel::STATUS_PASS,
            'refuse_reason' => '',
            'is_finished'   => 1,
            'ipstr'         => USER_IP,
            'is_top'        => \PostCommentModel::TOP_NO,
            'cityname'      => '火星',
            'created_at'    => date('Y-m-d H:i:s'),
        ];
        $comment = PostCommentModel::create($data);
        test_assert($comment,'添加评论失败', 422);
        //统计
        PostModel::where('id', $comment->post_id)->increment('comment_num');
        return true;
    }
}