<?php

/**
 * Class PostController
 * @author xiongba
 * @date 2023-06-09 20:10:18
 */
class PorngameController extends BackendBaseController
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
        return function (PornGameModel $item) {
            $item->load('medias');
            $item->load('category');
            $item->category_title = $item->category ? $item->category->title : '';
            $item->tags = $item->tags ? explode(',', $item->tags) : '';
            $item->type_str = PornGameModel::TYPE_TIPS[$item->type];
            //封面
            $item->thumb_all = url_cover($item->thumb);
            //组装图片和视频
            $imgs = [];
            foreach ($item->medias as $v) {
                if ($v->type == PostMediaModel::TYPE_IMG) {
                    $v->ori_media_url = parse_url($v->media_url, PHP_URL_PATH);
                    $imgs[] = $v;
                }
            }
            $item->show_imgs = $imgs;
            //构建九张图
            if (count($imgs) < 9){
                $count = 9 - count($imgs);
                for ($i=1;$i<=$count;$i++){
                    $imgs[] = [
                        'media_url' => '',
                        'ori_media_url' => '',
                    ];
                }
            }
            $item->imgs = $imgs;

            //链接
            $pattern = '/\b(?:https?|ftp):\/\/[^\s<>"\'()]+/i';
            // 执行正则表达式匹配
            preg_match_all($pattern, $item->content, $matches);
            $item->links = $matches[0];

            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     * @author xiongba
     * @date 2023-06-09 20:10:18
     */
    public function indexAction()
    {
        $list = PornCategoryModel::where('type', PornCategoryModel::TYPE_COM)->orderByDesc('sort')->get()->pluck('title', 'id')->toArray();
        $this->assign('cat_list', $list);
        $this->display();
    }

    protected function doSave($data)
    {
        // 编辑框的
        $img = array_filter($data['img_url']);
        $tags = $data['tags'];
        $tags = !empty($tags) ? implode(',', $tags) : '';
        unset($data['tags']);
        if ($data['content'] && str_contains($data['content'],'｜')){
            test_assert(false, '下载地址及密码里面有不合法的竖线');
        }
        return transaction(function () use ($data, $img, $tags) {
            /** @var PornGameModel $model */
            $model = $this->fatherSave($data);
            // 清理掉原有资源 重新建立关联数据
            PornMediaModel::where('pid', $model->id)
                ->where('relate_type', PostMediaModel::TYPE_RELATE_POST)
                ->get()
                ->map(function ($item) {
                    $isOk = $item->delete();
                    test_assert($isOk, '删除数据异常');
                });
            // 图片
            foreach ($img as $v) {
                $tmp = [
                    'aff'          => 0,
                    'cover'        => trim(parse_url($v, PHP_URL_PATH), '/'),
                    'thumb_width'  => 0,
                    'thumb_height' => 0,
                    'duration'     => 0,
                    'pid'          => $model->id,
                    'media_url'    => trim(parse_url($v, PHP_URL_PATH), '/'),
                    'relate_type'  => PornMediaModel::TYPE_RELATE_POST,
                    'status'       => PornMediaModel::STATUS_OK,
                    'type'         => PornMediaModel::TYPE_IMG,
                ];
                $isOk = PornMediaModel::create($tmp);
                test_assert($isOk, '保存图片资源异常');
            }

            $model->tags = $tags;
            if (!$data['_pk']){
                $model->created_at = \Carbon\Carbon::now();
                $model->refresh_at = \Carbon\Carbon::now();
                $model->view_count = rand(50000, 200000);
                $model->like_count = intval($model->view_count / rand(10,15));
                $model->buy_fake = intval($model->view_count / rand(20, 25));
            }
            $model->updated_at = \Carbon\Carbon::now();
            $isOk = $model->save();
            test_assert($isOk, '更新状态错误');

            return $model;
        });
    }

    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2023-06-09 20:10:18
     */
    protected function getModelClass(): string
    {
        return PornGameModel::class;
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
        return '';
    }

    public function refreshAction(){
        PornGameModel::where(['id'=>$_REQUEST['id']])->update(['refresh_at'=>date("Y-m-d H:i:s")]);
        return $this->ajaxSuccess('成功');
    }

    public function addBatchCategoryAction(){
        try {
            test_assert($this->getRequest()->isPost(), '请求错误');
            $post = $this->postArray();
            $ary = explode(',', $post['porn_ids'] ?? '');
            $ary = array_filter($ary);
            $ary = array_unique($ary);
            $porn_games = PornGameModel::whereIn('id', $ary)->get();
            $category_id = $post['category_id'];
            test_assert($category_id, '分类必选');

            transaction(function ()use($porn_games, $category_id){
                collect($porn_games)->map(function (PornGameModel $game) use ($category_id){
                    $game->category_id = $category_id;
                    $isOK = $game->save();
                    test_assert($isOK,"保存失败");
                });
            });
            return $this->ajaxSuccess('更新成功');
        }catch (Exception $e){
            return $this->ajaxError($e->getMessage());
        }
    }

    public function addBatchTagsAction(){
        try {
            test_assert($this->getRequest()->isPost(), '请求错误');
            $post = $this->postArray();
            $ary = explode(',', $post['porn_ids'] ?? '');
            $ary = array_filter($ary);
            $ary = array_unique($ary);
            $porn_games = PornGameModel::whereIn('id', $ary)->get();
            $tags = $post['tags'];
            test_assert($tags, '标签必选');
            $tags = implode(',', $tags);

            transaction(function ()use($porn_games, $tags){
                collect($porn_games)->map(function (PornGameModel $game) use ($tags){
                    $game->tags = $tags;
                    $isOK = $game->save();
                    test_assert($isOK,"保存失败");
                });
            });
            return $this->ajaxSuccess('更新成功');
        }catch (Exception $e){
            return $this->ajaxError($e->getMessage());
        }
    }

    public function addBatchPayAction(){
        try {
            test_assert($this->getRequest()->isPost(), '请求错误');
            $post = $this->postArray();
            $ary = explode(',', $post['porn_ids'] ?? '');
            $ary = array_filter($ary);
            $ary = array_unique($ary);
            $porn_games = PornGameModel::whereIn('id', $ary)->get();
            $type = $post['type'] ?? 0;
            $coins = $post['coins'] ?? 0;
            if (in_array($type, [PornGameModel::TYPE_COINS, PornGameModel::TYPE_MIX])){
                test_assert($coins, '金币必须设置');
            }else{
                $coins = 0;
            }

            transaction(function ()use($porn_games, $type, $coins){
                collect($porn_games)->map(function (PornGameModel $game) use ($type, $coins){
                    $game->type = $type;
                    $game->coins = $coins;
                    $isOK = $game->save();
                    test_assert($isOK,"保存失败");
                });
            });
            return $this->ajaxSuccess('更新成功');
        }catch (Exception $e){
            return $this->ajaxError($e->getMessage());
        }
    }

    public function addBatchHotAction(){
        try {
            test_assert($this->getRequest()->isPost(), '请求错误');
            $post = $this->postArray();
            $ary = explode(',', $post['porn_ids'] ?? '');
            $ary = array_filter($ary);
            $ary = array_unique($ary);
            $porn_games = PornGameModel::whereIn('id', $ary)->get();
            $is_hot = $post['is_hot'] ?? 0;

            transaction(function ()use($porn_games, $is_hot){
                collect($porn_games)->map(function (PornGameModel $game) use ($is_hot){
                    $game->is_hot = $is_hot;
                    $isOK = $game->save();
                    test_assert($isOK,"保存失败");
                });
            });
            return $this->ajaxSuccess('更新成功');
        }catch (Exception $e){
            return $this->ajaxError($e->getMessage());
        }
    }

    public function addBatchShowAction(){
        try {
            test_assert($this->getRequest()->isPost(), '请求错误');
            $post = $this->postArray();
            $ary = explode(',', $post['porn_ids'] ?? '');
            $ary = array_filter($ary);
            $ary = array_unique($ary);
            $porn_games = PornGameModel::whereIn('id', $ary)->get();
            $status = $post['status'] ?? 0;

            transaction(function ()use($porn_games, $status){
                collect($porn_games)->map(function (PornGameModel $game) use ($status){
                    $game->status = $status;
                    $isOK = $game->save();
                    test_assert($isOK,"保存失败");
                });
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
            collect($ids)->map(function ($id){
                $porngame = PornGameModel::find($id);
                if (!empty($porngame)){
                    if ($porngame->view_count < 10000){
                        $view_count = rand(50000, 200000);
                        $like_count = intval($view_count / rand(5, 20));
                        $buy_fake = intval($like_count / rand(1, 5));
                        $porngame->view_count = $view_count;
                        $porngame->like_count = $like_count;
                        $porngame->buy_fake = $buy_fake;
                        $porngame->refresh_at = \Carbon\Carbon::now();
                        $porngame->save();
                    }
                }
            });
            return $this->ajaxSuccess("操作成功");
        }catch (Exception $e){
            return $this->ajaxError($e->getMessage());
        }
    }

    protected function saveAfterCallback($model)
    {
        //更新缓存
        //PornGameModel::clearDetailCache($model->id);
    }

    //黄游
    public function totalAction()
    {
        $where = array_merge(
            $this->getSearchPrefixLikeParam(),
            $this->getSearchLikeParam(),
            $this->getSearchWhereParam(),
            $this->getSearchBetweenParam()
        );
        $total = PornGameModel::query()->where($where)->count('id');
        $data = [
            'total'     => $total,
        ];
        return $this->ajaxSuccess($data);
    }
}