<?php

/**
 * Class FindreplycommentController
 * @author xiongba
 * @date 2020-07-10 16:05:39
 */
class FindreplycommentController extends BackendBaseController
{
    protected $imgStyle = 'width: 40px;height: 40px';
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

    public function logAction()
    {
        $reply_id = $_GET['reply_id'];

        $this->imgStyle = 'width: 100%;';

        $query = FindReplyCommentModel::with('member')->where('reply_id' , $reply_id);
            //->where('status', '=', ChatlogModel::STATUS_NORMAL)
        $data = $query->get()->map($this->listAjaxIteration());


        $this->assign('items', $data->toArray());
        $this->display();
    }


    public function getModelObject()
    {
        return FindReplyCommentModel::with(['member']);
    }

    /**
     * 试图渲染
     * @return string
     * @author xiongba
     * @date 2020-07-10 16:05:39
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2020-07-10 16:05:39
     */
    protected function getModelClass(): string
    {
       return FindReplyCommentModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2020-07-10 16:05:39
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
}