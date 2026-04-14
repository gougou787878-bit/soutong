<?php

/**
 * 缓存类
 * Class CacheController
 */
class CacheController extends AdminController
{
    private $redis;

    public function init()
    {
        parent::init();
        $this->redis = new \tools\RedisService();
    }


    public function indexAction()
    {
        $flag = trim($this->Request['flag'] ?? '');

        $query = CacheKeysModel::orderBy('id', 'desc')->groupBy('name')->offset($this->pageStart)
            ->limit($this->perPageNum);
        if ($flag) {
            $query->where(function ($query) use ($flag) {
                $query->Where('name', 'like', "%$flag%");
                $query->orWhere('key', 'like', "%$flag%");
            });
            $query_link = "d.php?mod=cache&flag=$flag";
        } else {
            $query_link = 'd.php?mod=cache';
        }

        $data = $query->get(['*'], false)->toArray();
        $topic = [];
        foreach ($data as $value) {
//            $value['created_at'] = date('Y-m-d H:i:s', $value['created_at']);
//            $value['updated_at'] = date('Y-m-d H:i:s', $value['updated_at']);
            $topic[] = $value;
        }
        if (count($topic) < $this->perPageNum) {
            $page_arr['html'] = sitePage($query_link, 1);
        } else {
            $page_arr['html'] = sitePage($query_link);
        }
        $this->getView()
            ->assign('topic', $topic)
            ->assign('flag', $flag)
            ->assign('page_arr', $page_arr)
            ->display('cache/index.phtml');
    }


    public function resetCacheAction()
    {
        $name = JAddSlashes($this->get['name'] ?? '');
        $pos = mb_stripos($name,'*');
        if ($name) {
            $result = CacheKeysModel::where('name', '=', $name)->select(['*'], false)->get(['*'], false)->toArray();
            foreach ($result as $val) {
                if (false === $pos) {  // 排除固定缓存
                    CacheKeysModel::where('name', '=', $val['name'])->delete();
                }
                $this->redis->del($val['key'].'*');
            }
            $this->Messager("清除成功", 'd.php?mod=cache');
        } else {
            $this->Messager("未传入缓存名称", 'd.php?mod=cache');
        }
    }

    /**
     * 刷新所有缓存
     */
    public function refreshAction()
    {
        CacheKeysModel::truncate();
        (new \tools\RedisService())->flushall();
        $this->Messager("清除成功", 'd.php?mod=cache');
    }

}


