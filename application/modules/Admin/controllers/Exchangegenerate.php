<?php

/**
 * Class ExchangegenerateController
 * @author xiongba
 * @date 2020-03-10 16:57:11
 */
class ExchangegenerateController extends BackendBaseController
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
     * @return string
     * @author xiongba
     * @date 2020-03-10 16:57:11
     */
    public function indexAction()
    {
        $this->display();
    }

    /**
     * @return int
     * @see https://blog.csdn.net/u013303402/article/details/60139840
     */
    protected function uniqid()
    {
        $arr = gettimeofday();
        $number = ($arr['sec'] * 100000 + $arr['usec'] / 10);
        $tmp = $number & 0x7FFFFFFF;
        $logId = $tmp | 0x80000000;
        return $logId;
    }


    protected function forCreate(ExchangeGenerateModel $model)
    {
        $post = $model->toArray();
        $num = $post['num'];
        unset($post['admin_id'], $post['memo'], $post['id'], $post['num']);
        $data = [];
        $post['status'] = ExchangeCodeModel::STATUS_SUCCESS;
        for ($i = 0; $i < $num; $i++) {
            $post['code'] = substr(md5($this->uniqid()), 5, 10);
            $post['serial_number'] = $model->id;
            $data[] = $post;
            usleep(10);
        }
        ExchangeCodeModel::insert($data);
    }


    public function delAllAction()
    {
    }

    public function delAction()
    {
    }

    public function downAction()
    {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.date('Y-m-d H:i:s').'.txt"');
        $id = $this->get['id'] ?? 0;


        $all = ExchangeCodeModel::where([
            'serial_number' => $id
        ])->get();
        /** @var ExchangeCodeModel[] $all */
        $data = "邀请码\t\t\t类型\t\t状态\r\n";
        foreach ($all as $item) {
            $data .= sprintf("%s\t\t%s\t\t%s\r\n", $item->code, ExchangeCodeModel::TYPE[$item->type],
                ExchangeCodeModel::STATUS[$item->status]);
        }
        echo $data;
        die;

    }


    public function cloneAction()
    {
        $id = $this->post['id'] ?? 0;
        try {
            /** @var ExchangeGenerateModel $model */
            $model = ExchangeGenerateModel::find($id);
            $post = $model->toArray();
            $post['admin_id'] = $_SESSION['uid'];
            $post['memo'] = '克隆批号:' . $id;
            unset($post['id'], $post['created_at'], $post['updated_at']);
            $model = ExchangeGenerateModel::make($post);
            $model->saveOrFail();
            $this->forCreate($model);
            return $this->ajaxSuccessMsg('操作成功');
        } catch (\Throwable $e) {
            return $this->ajaxError($e->getMessage());
        }
    }

    public function recallAction()
    {
        $id = $this->post['id'] ?? 0;
        try {
            /** @var ExchangeGenerateModel $model */
            $model = ExchangeGenerateModel::find($id);
            $model->status = ExchangeGenerateModel::STATUS_FAIL;
            $model->saveOrFail();
            ExchangeCodeModel::where([
                'serial_number' => $id,
                'status'        => ExchangeCodeModel::STATUS_SUCCESS
            ])->update(['status' => ExchangeCodeModel::STATUS_FAIL]);
            return $this->ajaxSuccessMsg('操作成功');
        } catch (\Throwable $e) {
            return $this->ajaxError($e->getMessage());
        }
    }

    public function saveAction()
    {
        $post = $this->postArray();
        $post['admin_id'] = $_SESSION['uid'];
        $post['validity'] = strtotime($post['validity']);
        $post['status'] = ExchangeGenerateModel::STATUS_SUCCESS;
        //$post['updated_at'] = $post['created_at'] = time();
        try {
            /** @var ExchangeGenerateModel $model */
            $model = ExchangeGenerateModel::make($post);
            $model->saveOrFail();
            $this->forCreate($model);
            return $this->ajaxSuccessMsg('操作成功');
        } catch (\Throwable $e) {
            return $this->ajaxError($e->getMessage());
        }
    }

    /**
     * 获取对应的model名称
     * @return string
     * @author xiongba
     * @date 2020-03-10 16:57:11
     */
    protected function getModelClass(): string
    {
        return ExchangeGenerateModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author xiongba
     * @date 2020-03-10 16:57:11
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
}