<?php

use service\EsService;
use service\TopicService;

/**
 * Class MvController
 * @author xiongba
 * @date 2020-03-03 18:21:06
 */
class MvController extends BackendBaseController
{
    /**
     * 列表数据过滤
     * @return Closure
     * @author xiongba
     * @date 2019-12-02 17:08:03
     */
    protected function listAjaxIteration()
    {
        /**
         * @param MvModel $item
         * @return mixed
         * @author xiongba
         * @date 2020-03-03 17:23:38
         */
        return function ($item) {

            $item->href = $item->m3u8?getAdminPlayM3u8($item->m3u8,true):'';
            $item->full_href = $item->m3u8?getAdminPlayM3u8($item->m3u8,true):'';
            $item->coins = $item->coins;
            $item->title = htmlspecialchars($item->title);
            $item->statusname = MvModel::STAT[$item->status];
            $item->created_at = date('Y-m-d H:i', $item->created_at);
            $item->refresh_at && $item->refresh_at = date('Y-m-d H:i', $item->refresh_at);
            $item->thumb = $item->cover_thumb;
            $item->img_thumb = $item->cover_thumb;
            $item->img_gif_thumb = $item->gif_thumb;
            $item->tagsname = htmlspecialchars($item->tags);
            $item->free_str = 'VIP';
            $item->buy_num = $item->count_pay;
            if($item->collect_id > 0 ){
                $item->topic_title = '<span class="color:green">'.$item->topic->title.'</span>';
            }else{
                $item->topic_title  = '';
            }
            if ($item->coins > 0) {
                //$item->buy_num = MvPayModel::getBuyMvNum($item->id);
                $item->free_str = '金币';
            }
            $item->nickname = $item->user ? $item->user->nickname : "用户已注销";

            $constructName = '';
            if ($item->construct_id > 0) {
                $construct = ConstructModel::where('id', $item->construct_id)->first();
                $constructName = $construct ? $construct->title : '';
            }
            $item->construct_name = $constructName;

            return $item;
        };
    }

    public function tagsListAction()
    {
        return $this->ajaxSuccess(TagsModel::orderBy('id', 'DESC')->pluck('name'));
    }


    public function setTags($val, $data, $pk)
    {
        return join(',', array_map('trim', $val));
    }


    /**
     * 拒绝用户请求
     * @return bool
     * refused: 您上传的视频质量、清晰度还不够好，建议在丰富一下内容再次上传
     * _pk: 93777
     * status: 2
     */
    public function refuseUserUploadAction()
    {
        $id = $this->post['_pk'] ?? 0;
        $model = MvModel::find($id);
        if (empty($model) || $model->status != 0) {
            return $this->ajaxError('当前状态不可拒绝');
        }
        $curl_data = [
            'timestamp' => TIMESTAMP,
            'playUrl'   => $model->m3u8,
            'sign'      => md5(TIMESTAMP . (config('mp4.slice_key')) . $model->m3u8)
        ];
        $curl = new \tools\CurlService();
        $re = $curl->request(config('mp4.destroy'), $curl_data);
        if ($re == 'success' || $re == '文件不存在') {
            \MvModel::where('id', $id)->update([
                'status' => MvModel::STAT_REFUSE,
                'is_hide'=>MvModel::IS_HIDE_YES
            ]);
            //messageCenter
            $member = MemberModel::where(['uid' => $model->uid])->first();
            $member && MessageModel::createSystemMessage($member->uuid, MessageModel::SYSTEM_MSG_TPL_MV_REFUSE,
                ['title' => $model->title, 'reason' => trim($this->post['refused'],'"\'') ?? '视频模糊有水印']);
            return $this->ajaxSuccess('操作成功');
        } else {
            return $this->ajaxError('操作失败',-9999,$re);
        }
    }

    /**
     * 切换推荐状态
     * @return bool
     */
    public function switchRecommendAction()
    {
        $id = $this->post['_pk'] ?? 0;
        $model = MvModel::find($id);
        if (empty($model)) {
            return $this->ajaxError('当前状态不能切换推荐状态');
        }
        try {
            $is_recommend = $model->is_recommend == MvModel::RECOMMEND_YES ? MvModel::RECOMMEND_NO : MvModel::RECOMMEND_YES;
            list($status, $re) = $this->status2Success($id, ['is_recommend' => $is_recommend]);
            if ($status) {
                $this->ajaxSuccess('审核成功');
            } else {
                $this->ajaxError('审核失败#' . var_export($re, true), -9999, $re);
            }
        } catch (Exception $e) {
            $this->ajaxError($e->getMessage());
        }
    }

    /**
     * 刷新缓存
     * @return bool
     */
    public function refreshAction()
    {
        $id = $this->post['_pk'] ?? 0;
        $model = MvModel::find($id);
        if (empty($model)) {
            return $this->ajaxError('当前状态不能切换推荐状态');
        }
        cached(sprintf(MvModel::REDIS_MV_DETAIL,$id))->clearCached();
        $this->ajaxSuccess('刷新成功');
    }

    public function addBatch18Action(){
        $is_18= $this->post['is_18'] ?? 0;
        $mv_ids = explode(',', trim($this->post['series_mv_id'], ','));
        if (!$mv_ids){
            return $this->ajaxError('数据异常');
        }
        try {
            MvModel::whereIn('id',$mv_ids)->update(['is_18' => $is_18]);
            //同步ES
            bg_run(function () use ($mv_ids){
                MvModel::whereIn('id', $mv_ids)->get()->map(function ($item){
                    EsService::syncMv($item);
                });
            });
            return $this->ajaxSuccess("操作成功");
        }catch (Exception $e){
            return $this->ajaxError($e->getMessage());
        }
    }
    
    /**
     * 用户上传的视频通过审核
     */
    public function upFeatureAction()
    {
        $id = $this->post['_pk'] ?? 0;
        $model = MvModel::find($id);
        if (empty($model)) {
            return $this->ajaxError('当前状态不能切换推荐状态');
        }
        try {
            $is_recommend = $model->is_feature == MvModel::IS_FEATURE_YES ? MvModel::IS_FEATURE_NO : MvModel::IS_FEATURE_YES;
            list($status, $re) = $this->status2Success($id, ['is_feature' => $is_recommend]);
            if ($status) {
                $this->ajaxSuccess('审核成功');
            } else {
                $this->ajaxError('审核失败#' . var_export($re, true), -9999, $re);
            }
        } catch (Exception $e) {
            $this->ajaxError($e->getMessage());
        }
    }


    public function createBeforeCallback($model)
    {
        /** @var MvModel $model */
        //$model->uid = setting('official.uid', 4888000);
        $model->uid = getOfficialUID();
        $model->via = MvModel::VIA_OFFICAL;
        $model->created_at = time();
    }

    public function saveAfterCallback($model)
    {
        /** @var MvModel $model */
        if (empty($model)) {
            return;
        }
        $tags = $model->tags;
        if (is_string($tags)) {
            $tags = explode(',', $tags);
        }
        MvTagModel::deleteMvNoTag($model->id ,$tags);
        if ($model->status == MvModel::STAT_CALLBACK_DONE) {
            MvTagModel::createByAll($model->id, $tags);
        }
        bg_run(function () use ($model){
            EsService::syncMv($model);
            //刷新时间
            $model->refresh_at = TIMESTAMP;
            $model->save();
        });
    }

    /**
     * 用户上传的视频通过审核
     */
    public function approvedUserUploadAction()
    {
        $id = $this->post['_pk'] ?? 0;
        try {
            $row = \MvModel::find($id);
            if (empty($row)) {
                throw new \Exception('视频不存在');
            }
            if ($row->status != MvModel::STAT_UNREVIEWED){
              return  $this->ajaxSuccess('当前状态不可操作');
            }
            list($status, $re) = $this->status2Success($id);
            if ($status) {
                $this->ajaxSuccess('审核成功');
            } else {
                $this->ajaxError('审核失败#' . var_export($re, true), -9999, $re);
            }
        } catch (Exception $e) {
            $this->ajaxError($e->getMessage());
        }
    }


    public function delAction()
    {
        $pkName = $this->getPkName();
        $where = [$pkName => $_POST['_pk']];
        $model = MvModel::where($where)->first();
        /** @var MvModel $model */
        $model->status = MvModel::STAT_REMOVE;
        $model->is_top = MvModel::IS_TOP_NO;
        $model->save();
        bg_run(function () use ($model){
            EsService::sync('mv', 'del', $model->id);
        });
        return $this->ajaxSuccessMsg('操作成功');
    }

    public function delAllAction()
    {
        return $this->ajaxError('功能不存在');
    }


    protected function status2Success($id, $values = [])
    {
        $row = \MvModel::where('id', $id)->first();
        if (empty($row)) {
            throw new \Exception('视频不存在');
        }
        if ($row->status == MvModel::STAT_UNREVIEWED) {
            $re = $this->approvedMv($row);
            if ($re == setting('approvedUserUpload', 'success')) {
                $row->update(array_merge(['status' => MvModel::STAT_CALLBACK_ING], $values));
                return [true, '审核成功'];
            } else {
                return [false, $re];
            }
        } else {
            $row->update($values);
            return [true, '审核成功'];
        }
    }


    /**
     * 重新切片申请回调
     */
    public function retrysliceAction()
    {
        $id = $this->post['_pk'] ?? 0;
        $row = \MvModel::query()->where(['id' => $id, 'status' => MvModel::STAT_CALLBACK_ING])->first();
        if (empty($row)) {
            return $this->ajaxError('切片回调已处理');
        }

        $re = $this->approvedMv($row);
        if ($re == 'success') {
            $this->ajaxSuccess('切片回调已处理成功');
        } else {
            $this->ajaxError('切片回调处理失败');
        }
    }


    /**
     * 重新切片申请回调
     */
    public function avsliceAction()
    {
        $id = $this->post['_pk'] ?? 0;
        $row = \MvModel::query()->where(['id' => $id])->first();
        if (empty($row)) {
            return $this->ajaxError('切片回调已处理');
        }
        $re = $this->approvedMv($row);
        if ($re == 'success') {
            $this->ajaxSuccess('切片回调已处理成功');
        } else {
            $this->ajaxError('切片回调处理失败');
        }
    }


    /**
     * @param MvModel|object $model
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
        $crypt = new \tools\CryptService();
        $sign = $crypt->make_sign($data);
        $data['sign'] = $sign;
        $configPub = (new ConfigModel)->getConfig();
        if (ini_get('yaf.environ') === 'test'){
            $configPub['site'] = 'http://banana_rn.hyys.info';
        }
        $data['notifyUrl'] = $configPub['site'] . "/index.php?&m=Mv&a=index";
        $curl = new \tools\CurlService();
        $return = $curl->request(config('mp4.accept'), $data);
        errLog("reslice req:".var_export([$data,$return],true));
        return $return;
    }

    /**
     * 试图渲染
     * @return string
     * @author xiongba
     * @date 2020-03-03 18:21:06
     */
    public function indexAction()
    {
        $arr = PcTabModel::where('status', PcTabModel::STATUS_YES)
            ->get()->pluck('tab_name', 'tab_id')->toArray();
        $topic_arr = TopicModel::queryBase()->get()->pluck('title','id')->toArray();

        $constructs = ConstructModel::queryBase()->with('navigation')
            ->where('type', ConstructModel::TYPE_COMMON)
            ->get()
            ->map(function (ConstructModel $item){
                if (empty($item->navigation)){
                    return null;
                }
                $name = $item->navigation->title . '-' . $item->title;
                return [$item->id => $name];
            })->filter()->values()->toArray();

//        var_dump($constructs);exit();

        $this->assign('constructs', $constructs);
        $this->assign('pcTab', $arr);
        $this->assign('topic_arr', $topic_arr);
        $this->display();
    }

    public function avAction()
    {
        $this->display('mv/av');
    }
    protected function listAjaxWhere() {
        $where = [];
        if (isset($_GET['is_gov'])) {
            //$where[] = ['uid', '=', getOfficialUID()];
            $where[] = ['coins', '>', 0];
        } else {
            //小视频
            //$where[] = ['coins', '=', 0];
        }
        $isSetFree = isset($_GET['_is_free']);
        if ($isSetFree && $_GET['_is_free'] != '__undefined__') {
            $ifFree = intval($_GET['_is_free']);
            if ($ifFree == '0') {
                $where[] = ['coins', '!=', 0];
            } elseif ($ifFree == '1') {
                $where[] = ['coins', '=', 0];
            }
        }

        return $where;
    }

    protected function getSearchWhereParam() {
        $get = $this->getRequest()->getQuery();
        $get['where'] = $get['where'] ?? [];
        $where = [];
        foreach ($get['where'] as $key => $value) {
            if ($value ==='__undefined__'){
                continue;
            }
            $key = $this->formatKey($key);
            if (empty($key)){
                continue;
            }
            $value = $this->formatSearchVal($key, $value);
            if ($value !=='') {
                if ($key == 'construct_id' && $value == '-1'){
                    $value = 0;
                }
                $where[] = [$key, '=', $value];
            }
        }
        return $where;
    }


    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2020-03-03 18:21:06
     */
    protected function getModelClass(): string
    {
        return MvModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2020-03-03 18:21:06
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

    /**
     * 每次編輯更新片源時間
     * @param null $setPost
     * @return mixed
     */
//    protected function postArray($setPost = null)
//    {
//        $post = parent::postArray();
//        $post['refresh_at'] = TIMESTAMP;
//        return $post;
//    }


    /**
     *
     * 视频后台统计
     *
     * @return bool
     */
    public function totalAction()
    {
        /*$data = [
            'totalMV' => 100,
            'totalGoldMV'  => 100,
            'totalXiao' => 100,
            'totalPassed' => 100,
        ];
        return $this->ajaxSuccess($data);*/
        $where = array_merge(
            $this->getSearchLikeParam(),
            $this->getSearchWhereParam(),
            $this->getSearchBetweenParam()
        );
        $query =MvModel::query()->where($where);
        $totalGoldMVQuery = clone $query;
        $totalGoldXiaoQuery = clone $query;
        $totalPassedQuery = clone $query;
        $totalMV = $query->count('id');
        $totalGoldMV = $totalGoldMVQuery->where('coins','>',0)->count('id');
        $totalXiao = $totalGoldXiaoQuery->where('coins','=',0)->count('id');
        $totalPassed = $totalPassedQuery->whereIn('status',[MvModel::STAT_CALLBACK_DONE,MvModel::STAT_CALLBACK_ING])->count('id');
        $totalProfit = '0';
        if ($_GET['where']['uid'] != '__undefined__' && !empty($_GET['where']['uid'])){
            $totalProfitQuery = clone $query;
            $totalProfit = $totalProfitQuery
                ->where('status', MvModel::STAT_CALLBACK_DONE)
                ->selectRaw('sum(count_pay * coins) as totalProfit')
                ->first();
            $totalProfit = $totalProfit->totalProfit;
        }

        $data = [
            'totalMV'       => $totalMV,
            'totalGoldMV'   => $totalGoldMV,
            'totalXiao'     => $totalXiao,
            'totalPassed'   => $totalPassed,
            'totalProfit'   => $totalProfit,
        ];
        return $this->ajaxSuccess($data);
    }


    /**
     * 同步gtv
     */
    public function syncAvAction()
    {
        $id = $this->post['_pk'] ?? 0;
        $model = MvModel::where('id', '=', $id)->first();
        if(is_null($model)){
            return $this->ajaxSuccess('查无数据~');
        }
        try{
            $data = $model->getAttributes();
            $curl = new \tools\CurlService();
            $return = $curl->request(SYNC_GTV_URL, $data);
            //errLog("sync req:".var_export([$data,$return],true));
            $returnArr = json_decode($return,true);
            if($returnArr['status'] == 0){
                $model->increment('music_id',1);// music_id 已经弃用  作为同步标识
                return $this->ajaxSuccess('同步成功#'.$returnArr['msg']);
            }
            //{"status":0,"msg":"ok","data":[]}
            return $this->ajaxSuccess('同步失败#'.$returnArr['msg']);
            //return $return;
        }catch (\Yaf\Exception $e){
            return $this->ajaxSuccess('同步失败'.$e->getMessage());
        }
        return $this->ajaxSuccess('同步成功~');
    }
    /**
     * 同步BLUED
     */
    public function syncNanYanAvAction()
    {
        $id = $this->post['_pk'] ?? 0;
        $model = MvModel::where('id', '=', $id)->first();
        if(is_null($model)){
            return $this->ajaxSuccess('查无数据~');
        }
        try{
            $data = $model->getAttributes();
            $data['time'] = TIMESTAMP;
            $data['pwd'] = md5('c1999b118f786d90' . $data['time']);
            $curl = new \tools\CurlService();
            $return = $curl->request(SYNC_BLUED_URL, $data);
            //errLog("sync req:".var_export([$data,$return],true));
            if($return == "success"){
                //$model->increment('music_id',1);// music_id 已经弃用  作为同步标识
                return $this->ajaxSuccess('同步成功');
            }
            //{"status":0,"msg":"ok","data":[]}
            return $this->ajaxSuccess('同步失败');
            //return $return;
        }catch (\Yaf\Exception $e){
            return $this->ajaxSuccess('同步失败'.$e->getMessage());
        }
    }
    /**
     * 加入合集
     * @return bool
     * refused: 您上传的视频质量、清晰度还不够好，建议在丰富一下内容再次上传
     * _pk: 93777
     * status: 2
     */
    public function addTopicAction()
    {
        $id = $this->post['_pk'] ?? 0;
        $topic_id = $this->post['topic_id'];
        $model = MvModel::find($id);
        if (empty($model) || $model->status != MvModel::STAT_CALLBACK_DONE) {
            return $this->ajaxError('当前状态不能加入合集');
        }
        if (empty($this->post['topic_id'])) {
            return $this->ajaxError('加入合集不能为空');
        }
        $insertData = [
            'topic_id' => (int)$topic_id,
            'mv_id'    => $id,
        ];
        $msg = '';
        try {

            $exists = TopicRelationModel::query()->where('mv_id',$id)->exists();
            if($exists){
                return $this->ajaxError('已加入合集');
            }
            if (TopicRelationModel::addTopicMv($insertData)) {
                $model->collect_id  = $topic_id;
                $model->save();
                //维护视频数量
                TopicModel::query()->where('id',$topic_id)->increment('video_count',1,['refresh_at'=>TIMESTAMP]);
                //清楚缓存
                TopicService::clearTopicMV($topic_id);

                cached(sprintf(MvModel::REDIS_MV_DETAIL,$id))->clearCached();

                return $this->ajaxSuccess('操作成功');
            }
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            errLog("addTopicAction \r\n {$msg}");
        }
        return $this->ajaxError('操作失败#' . $msg);
    }

    public function batchAddTopicAction(){
        try {
            $topic_id = $this->post['topic_id']?? '';
            $mv_ids = explode(',', trim($this->post['mv_ids'], ','));
            test_assert($topic_id, '为选择合集');
            test_assert($mv_ids, '未选择视频');
            /** @var TopicModel $topic */
            $topic =  TopicModel::query()->where('id',$topic_id)->first();
            test_assert($topic, '合集不存在');
            if (!is_null($topic) && $mv_ids) {

                collect($mv_ids)->map(function ($_vid) use ($topic) {
                    $exists = TopicRelationModel::query()->where('mv_id',$_vid)->exists();
                    if(!$exists){
                        $insertData = [
                            'topic_id' => $topic->id,
                            'mv_id'    => $_vid,
                        ];
                        /** @var MvModel $model */
                        $model = MvModel::find($_vid);
                        if (TopicRelationModel::addTopicMv($insertData)) {
                            $model->collect_id  = $topic->id;
                            $model->save();
                            //维护视频数量
                            $topic->increment('video_count');
                            //清楚缓存
                            TopicService::clearTopicMV($topic->id);
                            cached(sprintf(MvModel::REDIS_MV_DETAIL,$_vid))->clearCached();
                        }
                    }
                });
                return $this->ajaxSuccessMsg("操作成功");
            }
            return $this->ajaxError('空数据无操作');
        } catch (\Throwable $e) {
            return $this->ajaxError($e->getMessage());
        }
    }
    /**
     * 批量视频推荐
     * @return bool
     */
    public function pcRecommendAction()
    {
        try {
            $tab_id = $this->post['tab_id'] ?? '';
            $mv_ids = explode(',', trim($this->post['series_mv_id'], ','));
            test_assert($tab_id, '导航未选择');
            test_assert($mv_ids, '未选择视频');
            $tab = PcTabModel::find($tab_id);
            if (!is_null($tab) && $mv_ids) {
                collect($mv_ids)->map(function ($_vid) use ($tab) {
                    /** @var PcMvRecommendModel $pcRecommend */
                    $pcRecommend = PcMvRecommendModel::where('mv_id',$_vid)->first();
                    if ($pcRecommend) {
                        if ($pcRecommend->tab_id != $tab->tab_id){
                            $pcRecommend->tab_id = $tab->tab_id;
                            $pcRecommend->updated_at = \Carbon\Carbon::now();
                            $pcRecommend->save();
                        }
                    }else{
                        $data = [
                            'tab_id'     => $tab->tab_id,
                            'mv_id'      => $_vid,
                            'created_at' => \Carbon\Carbon::now(),
                            'updated_at' => \Carbon\Carbon::now()
                        ];
                        PcMvRecommendModel::insert($data);
                    }
                });
                return $this->ajaxSuccessMsg("操作成功");
            }
            return $this->ajaxError('空数据无操作');
        } catch (\Throwable $e) {
            return $this->ajaxError($e->getMessage());
        }
    }

    /**
     * 批量视频展示
     * @return bool
     */
    public function batchIsShowAction()
    {
        try {
            $is_hide = $this->post['is_hide'] ?? 0;
            $mv_ids = explode(',', trim($this->post['series_mv_id'], ','));
            test_assert($mv_ids, '未选择视频');
            MvModel::whereIn('id', $mv_ids)->get()->map(function (MvModel $item) use ($is_hide){
                $item->is_hide = $is_hide;
                if ($item->isDirty()){
                    $item->save();
                    bg_run(function () use ($item){
                        EsService::syncMv($item);
                    });
                }
            });
            return $this->ajaxSuccessMsg("操作成功");

        } catch (\Throwable $e) {
            return $this->ajaxError($e->getMessage());
        }
    }

        /**
     * 批量视频展示
     * @return bool
     */
    public function batchModifyCoinsAction()
    {
        try {
            $new_coins = $this->post['new_coins'] ?? 0;
            $mv_ids = explode(',', trim($this->post['series_mv_id'], ','));
            test_assert($mv_ids, '未选择视频');
            MvModel::whereIn('id', $mv_ids)->get()->map(function (MvModel $item) use ($new_coins){
                $item->coins = $new_coins;
                if ($item->isDirty()){
                    $item->save();
                    bg_run(function () use ($item){
                        EsService::syncMv($item);
                    });
                }
            });
            return $this->ajaxSuccessMsg("操作成功");

        } catch (\Throwable $e) {
            return $this->ajaxError($e->getMessage());
        }
    }


    /**
     * 批量同步51乱伦
     * @return bool
     */
    public function batchAddSyncAction()
    {
        try {
            $mv_ids = explode(',', trim($this->post['series_mv_id'], ','));
            test_assert($mv_ids, '未选择视频');
            $type = $this->post['type'];
            $sys_app = [
                'hj' => 'http://hjsq.we-cname.com/index.php/notify/syncData',
                'gg' => SYNC_BLUED_URL,
                'gtv' => SYNC_GTV_URL,
            ];
            MvModel::whereIn('id', $mv_ids)->get()->map(function (MvModel $item) use ($type, $sys_app) {
                if ($type == 'hj'){
                    $item->addHidden([]);
                    $http = new \tools\HttpCurl();
                    $arr = $item->toArray();
                    $arr['_id'] = 'xl_' . $arr['id'];
                    $arr['p_id'] = 'xl_' . $arr['id'];
                    $arr['release_at'] = date('Y-m-d H:i:s');
                    $arr['from'] = 'xlp';
                    $arr['source'] = $arr['m3u8'];
                    $arr['cover_full'] = $arr['gif_thumb'];
                    $arr['created_at'] = $arr['onshelf_tm'];
                    unset($arr['tags_list']);
                    $arr['sign'] = $this->getSign($arr);
                    $http->post($sys_app[$type], $arr);
                }elseif ($type == 'gg'){
                    $data = $item->getAttributes();
                    $data['time'] = TIMESTAMP;
                    $data['pwd'] = md5('c1999b118f786d90' . $data['time']);
                    $curl = new \tools\CurlService();
                    $curl->request($sys_app[$type], $data);
                }elseif ($type == 'gtv'){
                    $data = $item->getAttributes();
                    $curl = new \tools\CurlService();
                    $curl->request($sys_app[$type], $data);
                }
            });
            return $this->ajaxSuccessMsg("操作成功");

        } catch (\Throwable $e) {
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

    public function batchAddConstructAction()
    {
        $id = $this->post['mv_ids'] ?? null;
        $construct_id = $this->post['construct_id'] ?? null;
        if (!$id || !$construct_id) {
            return $this->ajaxError('参数不能为空');
        }
        $id = explode(',', $id);

        try {
            $list = MvModel::selectRaw('id, construct_id')->whereIn('id', $id)->get();
            $construct_id = (int)$construct_id;
            $list->each(function ($item) use ($construct_id) {
                $item->construct_id = $construct_id;
                $item->saveOrFail();
            });
            return $this->ajaxSuccessMsg('操作成功');
        } catch (\Throwable $e) {
            return $this->ajaxError($e->getMessage());
        }
    }

    public function addBatchTagsAction(){
        try {
            $tags = $this->post['tags'] ?? '';
            $mv_ids = explode(',', trim($this->post['series_mv_id'], ','));
            if (!$tags || !$mv_ids){
                return $this->ajaxError('数据异常');
            }
            transaction(function () use ($tags, $mv_ids) {
                collect($mv_ids)->each(function ($mv_id) use ($tags) {
                    /** @var MvModel $mv */
                    $mv = MvModel::find($mv_id);
                    test_assert($mv,'视频不存在');
                    $old = explode(',', $mv->tags);
                    $tags = array_merge($tags, $old);
                    $tags = array_unique($tags);
                    $tags = array_filter($tags);
                    $tags = implode(',', $tags);
                    $mv->tags = $tags;
                    if ($mv->isDirty()) {
                        $mv->save();
                        bg_run(function () use ($mv){
                            EsService::syncMv($mv);
                        });
                    }
                });
            });
            return $this->ajaxSuccess("操作成功");
        } catch (Exception $e) {
            return $this->ajaxError($e->getMessage());
        }
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
        $post = \MvModel::find($id);
        test_assert($post,'此视频不存在', 422);

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
            $rs = $this->createMvComment($arr_aff[0]?? rand(10000, 20000), $id, $content);
        }else {
            for($i=0;$i<$num;$i++) {
                $_aff = $arr_aff[$i] ?? $arr_aff[0];
                $_content = FakeCommentModel::getRandContentByMv();
                test_assert($_content,'随机评论内容为空，请先添加', 422);
                $this->createMvComment($_aff, $id, $_content);
            }
        }
        return $this->ajaxSuccessMsg("评论成功");
    }

    public function createMvComment($aff, $id, $content)
    {
        $data = [
            'mv_id'    => $id,
            'c_id'     => 0,
            'uid'      => $aff,
            'comment'  => $content,
            'ipstr'    => '127.0.0.1',
            'cityname' => '火星',
            'status'   => CommentModel::STATUS_SUCCESS
        ];
        $comment = CommentModel::create($data);
        test_assert($comment,'添加评论失败', 422);
        //统计
        MvModel::where('id', $comment->mv_id)->increment('comment');
        return true;
    }
}