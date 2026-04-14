<?php

/**
 * Class MvsubmitController
 * @author xiongba
 * @date 2020-11-12 10:56:40
 */
class MvsubmitController extends BackendBaseController
{
    /**
     * 列表数据过滤
     * @return Closure
     * @author xiongba
     * @date 2019-12-02 17:08:03
     */
    protected function listAjaxIteration()
    {
        return function (MvSubmitModel $item) {
            if (str_contains($item->m3u8,"https://")){
                $item->href = $item->m3u8;
            }else{
                $item->href = $item->full_m3u8 ? url_videoMP4($item->full_m3u8):($item->m3u8?url_videoMP4($item->m3u8):'');
            }
            if ($item->user) {
                $item->nickname = $item->user->nickname;
            } else {
                $item->nickname = '用户已注销';
            }
            /** @var MemberCreatorModel $model */
            $model = MemberCreatorModel::where('uid', $item->uid)->first();
            $jjCount = $model->mv_refuse;
            $item->video_count = $model->mv_check;
            if ($model->mv_refuse == 0 || $model->mv_check == 0){
                $item->refuse_rate = '0%';
            }else{
                $item->refuse_rate = sprintf("%d%%" ,$model->mv_refuse / $model->mv_check * 100);
            }
            $item->refuse_num = $jjCount;
            return $item;
        };
    }

    /**
     * @param MvModel|MvSubmitModel|object $model
     * @return bool|string
     * @author xiongba
     * @date 2020-03-03 19:53:48
     */
    protected function approvedMv($model)
    {
        $data = [
            'uuid'    => 'fasdfddfasdfdjfajkodfs09ds0r23089df',
            'm_id'    => $model->id,
            'needMp3' => $model->music_id == 0 ? 1 : 0,
            'needImg' => empty($model->cover_thumb) ? 1 : 0,
            'playUrl' => $model->m3u8,
        ];
        if ($model->cover_thumb == '/new/xiao/20201120/2020112018294744986.jpeg') {//test img-covery
            $data['needImg'] = 1;
        }
        $crypt = new \tools\CryptService();
        $sign = $crypt->make_sign($data);
        $data['sign'] = $sign;
        $data['notifyUrl'] =SYSTEM_NOTIFY_SLICE_URL;
        $configPub = (new ConfigModel)->getConfig();
        if (ini_get('yaf.environ') === 'test') {
            $configPub['site'] = 'http://banana_rn.hyys.info';
            $data['notifyUrl'] ='https://sky.hyys.info/index.php?&m=mv&a=index';
        }
        $curl = new \tools\CurlService();
        $return = $curl->request(config('mp4.accept'), $data);
        //errLog("reslice req:" . var_export([$data, $return], true));
        return $return;
    }

    public function passAction()
    {
        $pk = $_POST['_pk'] ?? 0;
        $model = MvSubmitModel::find($pk);
        if ($model->status != MvSubmitModel::STAT_UNREVIEWED) {
            return $this->ajaxError('当前状态不可操作');
        }
        $model->status = MvSubmitModel::STAT_CALLBACK_ING;
        if ($model->save()) {
            $re = $this->approvedMv($model);
            if ($re == setting('approvedUserUpload', 'success')) {
                $this->addReviewMvLog($model);
                return $this->ajaxSuccess('审核成功');
            } else {
                return $this->ajaxError($re);
            }
        }
        return $this->ajaxSuccess('操作失败');
    }

    public function refuseUserUploadAction()
    {
        $id = $this->post['_pk'] ?? 0;
        $model = MvSubmitModel::find($id);
        if (empty($model)) {
            return $this->ajaxError('当前状态不可拒绝');
        }
        if ($model->status != MvSubmitModel::STAT_UNREVIEWED) {
            return $this->ajaxError('当前状态不可操作');
        }
        $curl_data = [
            'timestamp' => TIMESTAMP,
            'playUrl'   => $model->m3u8,
             'sign'      => md5(TIMESTAMP . (config('mp4.slice_key')) . $model->m3u8)
        ];
        $curl = new \tools\CurlService();
        $re = $curl->request(config('mp4.destroy'), $curl_data);
        if ($re == 'success' || $re == '文件不存在') {
            if ($model->update(['status' => MvSubmitModel::STAT_REFUSE])) {
                if ($model->user) {
                    $memo = $this->post['refused'] ?? '视频模糊有水印';
                    MessageModel::createSystemMessage($model->user->uuid, MessageModel::SYSTEM_MSG_TPL_MV_REFUSE,
                        ['title' => $model->title, 'reason' => $memo . '，官方up群: https://lynnconway.me/bluemv']);
                }
                $this->addReviewMvLog($model);
                MemberCreatorModel::where('uid', $model->uid)->increment('mv_refuse');
                return $this->ajaxSuccess('操作成功');
            }
        } else {
            return $this->ajaxError('操作失败');
        }
    }

    private function addReviewMvLog(MvSubmitModel $model)
    {
        AdminLogModel::addReviewMv($this->getUser()->username, sprintf('审视频[%d]#(%d)%s', $model->uid, $model->id, $model->title));
        MemberCreatorModel::where('uid', $model->uid)->increment('mv_check');
    }

    /**
     * 试图渲染
     * @return string
     * @author xiongba
     * @date 2020-11-12 10:56:40
     */
    public function indexAction()
    {
        $this->display();
    }

    /**
     * 试图渲染
     * @return string
     * @author xiongba
     * @date 2020-11-12 10:56:40
     */
    public function waitAction()
    {
        $list = MvSubmitModel::with('user:uid,nickname')
            ->where('status', MvModel::STAT_UNREVIEWED)
            ->where('task_at', '<', time())
            ->limit(10)
            ->get()
            ->map($this->listAjaxIteration());
        $ids = $list->pluck('id');
        MvSubmitModel::whereIn('id', $ids)->update(['task_at' => time() + 2800]);

        $this->assign('list', $list->toArray());
        $this->assign('admin_css', true);
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2020-11-12 10:56:40
     */
    protected function getModelClass(): string
    {
        return MvSubmitModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2020-11-12 10:56:40
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
    public function setTags($val, $data, $pk)
    {
        return join(',', array_map('trim', $val));
    }
}