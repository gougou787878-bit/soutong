<?php

/**
 * 原创视频
 * Class CartoonChaptersController
 */
class CartoonChaptersController extends BackendBaseController
{
    /**
     * 列表数据过滤
     * @return Closure
     */
    protected function listAjaxIteration()
    {
        return function ($item) {
            $item->load('cartoon');
            $item->title = $item->cartoon->title;
            $item->sort_str = '第'.$item->sort.'集';
            $item->type_str = CartoonChaptersModel::TYPE_TIPS[$item->type];
            $item->is_free_str = CartoonChaptersModel::FREE_TIPS[$item->is_free];
            $item->source_url =  getAdminPlayM3u8($item->source,true);
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
     * 获取对应的model名称
     * @return string
     */
    protected function getModelClass(): string
    {
       return CartoonChaptersModel::class;
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
     */
    protected function getLogDesc(): string {
        // TODO: Implement getLogDesc() method.
        return '';
    }

    public function addBatchCoinsAction(){
        try {
            $type = $this->post['is_free'] ?? CartoonChaptersModel::TYPE_VIP;
            $coins = $this->post['coins'] ?? '';
            $ids = explode(',', trim($this->post['$ids'], ','));
            test_assert($ids,'数据异常');
            $data = [
                'coins' => $coins,
                'is_free' => $type
            ];
            switch ($type){
                case CartoonChaptersModel::TYPE_FREE:
                case CartoonChaptersModel::TYPE_VIP:
                    $data['coins'] = 0;
                    break;
                case CartoonChaptersModel::TYPE_COINS:
                    if ($coins <= 0){
                        throw new Exception('金币数不能为0');
                    }
                    $min_coins = 20;
                    $coins = max(abs($coins), $min_coins);
                    $data['coins'] = $coins;
                    break;
                default:
                    test_assert(false, '类型错误');
                    break;
            }
            CartoonChaptersModel::whereIn('id', $ids)->update($data);
            return $this->ajaxSuccess("操作成功");
        }catch (Exception $e){
            return $this->ajaxError($e->getMessage());
        }
    }

}