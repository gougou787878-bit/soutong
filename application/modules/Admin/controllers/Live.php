<?php

/**
 * Class LiveController
 * @author xiongba
 * @date 2022-03-07 12:01:02
 */
class LiveController extends BackendBaseController
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
        return function (LiveModel $item) {
            $item->setHidden([]);
            $item->show_str = LiveModel::SHOW_TIPS[$item->show] ?? '';
            $item->status_str = LiveModel::STATUS_TIPS[$item->status] ?? '';
            $item->type_str = LiveModel::TYPE_TIPS[$item->type] ?? '';
            $item->size_str = $item->fr_width . 'X' . $item->fr_height;
            $m3u8 = LiveModel::process_hls($item->hls);
            $item->streams = array_chunk((array)$m3u8, 2);
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return void
     */
    public function indexAction()
    {
        $themes = LiveThemeModel::pluck('name', 'id')->toArray();
        $this->assign('themes', $themes);
        $this->display();
    }


    /**
     * 获取本控制器和哪个model绑定
     * @return string
     */
    protected function getModelClass(): string
    {
        return LiveModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     */
    protected function getPkName(): string
    {
        return 'id';
    }

    protected function getSearchWhereParam()
    {
        $get = $this->getRequest()->getQuery();
        $get['where'] = $get['where'] ?? [];
        $where = [];
        foreach ($get['where'] as $key => $value) {
            if ($value === '__undefined__') {
                continue;
            }
            $value = $this->formatSearchVal($key, $value);
            $key = $this->formatKey($key);
            if (empty($key)) {
                continue;
            }

            if ($value !== '' && $key != 'theme_id') {
                $where[] = [$key, '=', $value];
            }
            if ($value !== '' && $key == 'theme_id') {
                $ids = LiveRelatedModel::where('theme_id', $value)->get()->pluck('live_id');
                $ids = implode(",", $ids->toArray());
                $where[] = [DB::raw("id in ($ids)"), '1'];
            }
        }
        
        return $where;
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

    public function set_attrAction()
    {
        try {
            $ids = $_POST['live_ids'] ?? 0;
            $type = $_POST['type'] ?? LiveModel::TYPE_FREE;
            $coins = abs((int)($_POST['coins'] ?? 0));
            test_assert(isset(LiveModel::TYPE_TIPS[$type]), '类型错误');
            $ids = array_unique(array_filter(explode(",", $ids)));
            test_assert(count($ids), '请选择操作的记录');
            if ($type == LiveModel::TYPE_COINS) {
                test_assert($coins, '金币最大数不能为0');
            }else{
                $coins = 0;
            }
            LiveModel::whereIn('id', $ids)
                ->get()
                ->map(function ($item) use ($type, $coins) {
                    $item->type = $type;
                    $item->coins = $coins;
                    $isOk = $item->save();
                    test_assert($isOk, '系统异常');
                });
            return $this->ajaxSuccessMsg('设置成功');
        } catch (Throwable $e) {
            return $this->ajaxError($e->getMessage());
        }
    }
}