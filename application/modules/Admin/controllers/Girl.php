<?php

/**
 * Class GirlController
 * @author xiongba
 * @date 2023-06-09 20:10:18
 */
class GirlController extends BackendBaseController
{
    use \traits\DefaultActionTrait;
    use \traits\DefaultActionTrait {
        doSave as fatherSave;
    }

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
            $item->bast_str = GirlModel::BEST_TIPS[$item->is_best] ?? '未';
            $item->deleted_str = GirlModel::DELETED_TIPS[$item->is_deleted] ?? '';
            $item->finish_str = GirlModel::FINISH_TIPS[$item->is_finished] ?? '';
            $item->status_str = GirlModel::STATUS_TIPS[$item->status] ?? '';
            $item->category_str = GirlModel::TYPE_TIPS[$item->category] ?? '';
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
                if ($v->type == GirlMediaModel::TYPE_IMG) {
                    $v->media_url = url_cover($v->media_url);
                    $imgs[] = $v;
                }
                if ($v->type == GirlMediaModel::TYPE_VIDEO) {
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

//        $aff = $data['aff'] = (int)($data['aff'] ?? 0);
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
        return transaction(function () use ($data,$imgs,$video) {
            /** @var GirlModel $model */
            $model = $this->fatherSave($data);
            $count = GirlMediaModel::where('pid', $model->id)
                ->where('relate_type', GirlMediaModel::TYPE_RELATE_POST)
                ->where('type',GirlMediaModel::TYPE_IMG)
                ->count('id');
            //原图片数量不超过8张，编辑才有效
            if ($count < 9){
                // 清理掉原有资源 重新建立关联数据
                GirlMediaModel::where('pid', $model->id)
                    ->where('relate_type', GirlMediaModel::TYPE_RELATE_POST)
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
                        'relate_type'  => GirlMediaModel::TYPE_RELATE_POST,
                        'status'       => GirlMediaModel::STATUS_OK,
                        'type'         => GirlMediaModel::TYPE_IMG,
                    ];
                    $isOk = GirlMediaModel::create($tmp);
                    test_assert($isOk, '保存图片资源异常');
                }
                $model->photo_num = $photo_num;
            }else{
                //清除
                GirlMediaModel::where('pid', $model->id)
                    ->where('relate_type', GirlMediaModel::TYPE_RELATE_POST)
                    ->where('type',GirlMediaModel::TYPE_VIDEO)
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
                    'relate_type'  => GirlMediaModel::TYPE_RELATE_POST,
                    'status'       => GirlMediaModel::STATUS_OK,
                    'type'         => GirlMediaModel::TYPE_VIDEO,
                ];
                $isOk = GirlMediaModel::create($tmp);
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
            $model->status = GirlModel::STATUS_PASS;
//            $model->is_deleted = GirlModel::DELETED_NO;
            $model->ipstr = '127.0.0.1';
            $isOk = $model->save();
            test_assert($isOk, '更新状态错误');

            // 更新主题下帖子数据
            $rs = GirlTopicModel::where('id', $model->topic_id)->first();
            test_assert($rs, '主题不存在');
            $rs->girl_num = GirlModel::where('topic_id', $model->topic_id)->where('status', GirlModel::STATUS_PASS)->count();
            $isOk = $rs->save();
            test_assert($isOk, '更新主题帖子计数异常');


            return $model;
        });
    }
    protected function saveAfterCallback($model)
    {
        if (is_null($model)) {
            return;
        }
        GirlModel::clearDetailCache($model->id);
    }
    /**
     * 试图渲染
     * @return string
     * @author xiongba
     * @date 2023-06-09 20:10:18
     */
    public function indexAction()
    {
        $topics = GirlTopicModel::get()->pluck('name', 'id')->toArray();
        $this->assign('topicArr', $topics);
        $this->assign('topicId', $_GET['topic_id'] ?? '');
        $this->display();
    }

    public function checkAction(){
        $topics = GirlTopicModel::get()->pluck('name', 'id')->toArray();
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
       return GirlModel::class;
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
        GirlModel::where(['id'=>$_REQUEST['id']])->update(['refresh_at'=>date("Y-m-d H:i:s")]);
        return $this->ajaxSuccess('成功');
    }

    public function passAction()
    {
        $pk = $_POST['_pk'] ?? 0;
        $model = GirlModel::find($pk);
        if ($model->status != GirlModel::STATUS_WAIT) {
            return $this->ajaxError('当前状态不可操作');
        }
        $model->status = GirlModel::STATUS_PASS;
        $model->updated_at = date("Y-m-d H:i:s");
        $model->refresh_at = date("Y-m-d H:i:s");
        if ($model->save()) {
            GirlTopicModel::where('id',$model->topic_id)->increment('girl_num');
            $medias = $model->load('medias')->medias;
            if($medias){
                foreach ($medias as $_media){
                    if($_media->type ==GirlMediaModel::TYPE_VIDEO && $_media->status != GirlMediaModel::STATUS_OK){
                        GirlMediaModel::approvedMv($_media);//切片申请
                        GirlMediaModel::where('id',$_media->id)->update(['status' => GirlMediaModel::STATUS_ING]);
                    }
                }
            }

            return $this->ajaxSuccess('审核成功');
        }

        return $this->ajaxSuccess('操作失败');
    }

    public function refuseUserUploadAction()
    {
        $pk = $_POST['_pk'] ?? 0;
        $memo = $this->post['refused'] ?? '帖子内容描述与搜同看片平台不符';
        $model = GirlModel::find($pk);
        if ($model->status != GirlModel::STATUS_WAIT) {
            return $this->ajaxError('当前状态不可操作');
        }
        $model->status = GirlModel::STATUS_UNPASS;
        $model->refuse_reason = $memo;
        $model->updated_at = date("Y-m-d H:i:s");
        if ($model->save()) {
            MessageModel::createSystemMessage($model->member->uuid, MessageModel::SYSTEM_MSG_TPL_GIRL_REFUSE,
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
        $query = GirlModel::query()->where($where);
        $totalPostQuery = clone $query;
        $totalVipPostQuery = clone $query;
        $totalCoinsPostQuery = clone $query;
        $totalPostCoinsQuery = clone $query;
        $totalPost = $totalPostQuery->count();
        $totalVipPost = $totalVipPostQuery->where('price', 0)->count();
        $totalCoinsPost = $totalCoinsPostQuery->where('price', '>', 0)->count();
        $totalPostCoins = $totalPostCoinsQuery->where('price', '>', 0)->sum('reward_amount');

        $data = [
            'totalPost'        => $totalPost,
            'totalVipPost'     => $totalVipPost,
            'totalCoinsPost'   => $totalCoinsPost,
            'totalPostCoins'   => $totalPostCoins,
        ];
        return $this->ajaxSuccess($data);
    }
}