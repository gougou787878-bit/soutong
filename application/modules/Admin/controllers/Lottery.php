<?php

class LotteryController extends BackendBaseController
{

    /**
     * 列表数据过滤
     * @return Closure
     */
    protected function listAjaxIteration()
    {
        return function (LotteryBaseModel $item) {
            $item->type_str = LotteryBaseModel::TYPE_TIPS[$item->type] ?? '';
            $item->win_str = LotteryBaseModel::WIN_TIPS[$item->is_win] ?? '';
            $item->show_str = LotteryBaseModel::SHOW_TIPS[$item->is_show] ?? '';
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     */
    public function indexAction()
    {
        $this->display();
    }

    /**
     * 获取本控制器和哪个model绑定
     * @return string
     */
    protected function getModelClass(): string
    {
        return LotteryBaseModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     */
    protected function getPkName(): string
    {
        return 'id';
    }

    /**
     * 定义数据操作日志
     * @return string
     * @author fei
     */
    protected function getLogDesc(): string
    {
        return '';
    }

    public function generate_lotteryAction()
    {
        try {
            //判断奖次是否不为空
            $count = redis()->sCard(LotteryBaseModel::LOTTERY_SET);
            if ($count > 0){
                throw new Exception('奖池不为空，请删除奖次后重试');
            }
            // 重新生成中奖池
            $data = LotteryBaseModel::orderByDesc('sort')
                ->get()
                ->map(function ($item){
                    $item->makeHidden(['created_at','updated_at','sort','icon']);
                    return $item;
                })
                ->toArray();

            $members = [];
            foreach ($data as $v) {
                for ($i = 1; $i <= $v['rate']; $i++) {
                    $reward = $v;
                    unset($reward['rate']);
                    $reward['rand_m32'] = md5(mt_rand(1000000, 9999999));
                    $members[] = json_encode($reward);
                }
            }
            shuffle($members);
            redis()->sAddArray(LotteryBaseModel::LOTTERY_SET, $members);

            return $this->ajaxSuccess('成功');
        }catch (Throwable $e){
            return $this->ajaxError($e->getMessage());
        }
    }

    //清空奖池
    public function delete_lotteryAction(){
        try {
            LotteryBaseModel::clearCache();
            return $this->ajaxSuccess('成功');
        }catch (Throwable $e){
            return $this->ajaxError($e->getMessage());
        }
    }

    //清空中奖列表
    public function delete_luckyAction(){
        try {
            redis()->del('lottery_top_list');
            return $this->ajaxSuccess('成功');
        }catch (Throwable $e){
            return $this->ajaxError($e->getMessage());
        }
    }
}