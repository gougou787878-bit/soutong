<?php


/**
 * Class KeywordsearchController
 * @author xiongba
 * @date 2019-12-27 20:34:47
 */
class SearchindexController extends BackendBaseController
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
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return void
     */
    public function indexAction()
    {
        $this->display(null, ['hotKeyword' => setting('search.hot.words')]);
    }

    public function setWord($value, &$data)
    {
        $data['word_hash'] = SearchIndexModel::generateWordHash($value);
        return $value;
    }

    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2019-12-27 20:34:47
     */
    protected function getModelClass(): string
    {
        return SearchIndexModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2019-12-27 20:34:47
     */
    protected function getPkName(): string
    {
        return 'id';
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2019-11-04 17:19:41
     */
    protected function getLogDesc(): string
    {
        return '搜索词';
    }

    function saveAfterCallback($model)
    {
        if(!is_null($model)){
            $_hasKey = \SearchIndexModel::generateWordHash($model->word);
            redis()->del("ss:{$_hasKey}");
        }
    }


    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2019-12-27 20:34:47
     */
    protected function refreshHotSearchAction()
    {
        $data = SearchIndexModel::getHotSearch(setting('search.hot.limit', 6));
        $words = [];
        foreach ($data as $word) {
            $words[] = $word->word;
        }
        SettingModel::set('search.hot.words', join(',', $words));
        AdminLogModel::addOther($this->getUser()->username, '刷新了热搜榜');
        return $this->ajaxSuccess('ok');
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2019-12-27 20:34:47
     */
    protected function setHotSearchAction()
    {
        $data = $_POST['data'] ?? null;
        if (empty($data)) {
            return $this->ajaxSuccess('ok');
        }
        $data = collect(explode(',', $data))->map('trim')->filter()->unique()->toArray();
        if (empty($data)) {
            return $this->ajaxSuccess('ok');
        }

        $old = collect(explode(',', trim(setting('search.hot.words', ''))))->map('trim')->filter()->unique()->toArray();
        $data = array_merge($data, $old);
        if (empty($data)) {
            return $this->ajaxSuccess('ok');
        }
        $data = array_slice($data, 0, setting('search.hot.limit', 6));
        SettingModel::set('search.hot.words', join(',', $data));

        AdminLogModel::addOther($this->getUser()->username, '设置了热搜榜');
        return $this->ajaxSuccess('ok');
    }
}