<?php

/**
 * Class MessageController
 * @author xiongba
 * @date 2020-05-21 19:57:41
 */
class MessageController extends BackendBaseController
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
            $item->created_at = $item->created_at?date('Y-m-d H:i',$item->created_at):'';
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     * @author xiongba
     * @date 2020-05-21 19:57:41
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2020-05-21 19:57:41
     */
    protected function getModelClass(): string
    {
       return MessageModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2020-05-21 19:57:41
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
    protected function postArray($setPost = null)
    {
        $post = parent::postArray();
        $post['created_at'] = TIMESTAMP;
        return $post;
    }

    /**
     * @param $model
     * @return bool|void
     */
    protected function saveAfterCallback($model)
    {
        if (is_null($model)) {
            return;
        }
        MessageModel::clearCachedKeys();
        return true;
    }
}