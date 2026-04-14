<?php

/**
 * Class ProductController
 */
class ProductController extends BackendBaseController
{
    /**
     * 列表数据过滤
     * @return Closure
     * @author
     * @date 2019-12-02 17:08:03
     */
    public $payWay = [
        'payway_wechat' => '微信',
        'payway_bank'   => '银联',
        'payway_alipay' => '支付宝',
        'payway_visa'   => 'visa',
        'payway_huabei' => '花呗',
        'payway_agent'  => '代理',
        'payway_ecny'  => '数字rmb'
    ];
    protected function listAjaxIteration()
    {
        $payway = $this->payWay;
        return function (ProductModel $item)use($payway) {
            $pay_name = [];
            if ($item->payway_wechat == 1) array_push($pay_name, $payway['payway_wechat']);
            if ($item->payway_bank == 1) array_push($pay_name, $payway['payway_bank']);
            if ($item->payway_alipay == 1) array_push($pay_name, $payway['payway_alipay']);
            if ($item->payway_visa == 1) array_push($pay_name, $payway['payway_visa']);
            if ($item->payway_huabei == 1) array_push($pay_name, $payway['payway_huabei']);
            if ($item->payway_agent == 1) array_push($pay_name, $payway['payway_agent']);
            if ($item->payway_ecny == 1) array_push($pay_name, $payway['payway_ecny']);
            $item->img_url = $item->img ? url_ads($item->img) : '';
            $item->vip_icon_url = $item->vip_icon ? url_ads($item->vip_icon) : '';
            $item->pay_name = implode(',', $pay_name);
            $item->vip_tips =  MemberModel::USER_VIP_TYPE[$item->vip_level];
            $item->map_str = $item->getMapToString();
            $item->privilege_str = $item->getPrivilegeToString();
            $item->rightIds = ProductRightMapModel::where('product_id', $item->id)->get()->pluck('product_right_id')->filter()->values()->toArray();
            $item->privIds = ProductPrivilegeModel::where('product_id', $item->id)->get()->pluck('privilege_id')->filter()->values()->toArray();
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return string
     * @author
     * @date 2021-04-24 11:32:02
     */
    public function indexAction()
    {
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     * @author
     * @date 2021-04-24 11:32:02
     */
    protected function getModelClass(): string
    {
        return ProductModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     * @author
     * @date 2021-04-24 11:32:02
     */
    protected function getPkName(): string
    {
        return 'id';
    }

    /**
     * 定义数据操作日志
     * @return string
     * @author
     * @date 2019-11-04 17:19:41
     */
    protected function getLogDesc(): string {
        // TODO: Implement getLogDesc() method.
        return '';
    }

    /**
     * 每次編輯更新片源時間
     * @param null $setPost
     * @return mixed
     */
    protected function postArray($setPost = null)
    {
        $post = parent::postArray();
        //print_r($post);
        if (count($post) > 3) {
            $keys = array_keys($this->payWay);
            foreach ($keys as $_key) {
                if (isset($post[$_key]) && $post[$_key] == 'on') {
                    $post[$_key] = 1;
                } else {
                    $post[$_key] = 0;
                }
            }
        }
        //print_r($post);die;
        return $post;
    }

    public function saveAfterCallback($model)
    {
        ProductModel::clearRedisCache('1');
        ProductModel::clearRedisCache('2');
    }

    function _delActionAfter()
    {
        ProductModel::clearRedisCache('1');
        ProductModel::clearRedisCache('2');
    }

    /**
     * 加入系列包
     * right[0]: 4
     * right[3]: 7
     * right[6]: 10
     * right[9]: 13
     * _pk: 40247
     */
    public function addRightAction()
    {
        $id = $this->post['_pk'] ?? 0;
        $series = $this->post['right'] ?? [];
        if (!$series) {
            return $this->ajaxError('至少选择一个权益');
        }
        /** @var ProductModel $model */
        $model = ProductModel::find($id);
        if (is_null($model) || $model->type != ProductModel::TYPE_VIP) {
            return $this->ajaxError('商品无效');
        }
        //获取旧的权益ID
        $old_ids =  ProductRightMapModel::where('product_id', $model->id)
            ->get()
            ->pluck('product_right_id')
            ->filter()
            ->values()
            ->toArray();
        //新增的ID
        $new_add_ids = array_diff($series, $old_ids);
        $insertData = collect($new_add_ids)->map(function ($right_id) use ($model) {
            if (ProductRightMapModel::where(['product_right_id' => $right_id, 'product_id' => $model->id])->exists()) {
                return null;
            }
            return [
                'product_right_id' => $right_id,
                'product_id'       => $model->id,
                'status'           => 1
            ];
        })->filter()->toArray();;
        //去掉ID
        $delete_ids = array_diff($old_ids, $series);
        try {
            transaction(function () use ($insertData, $delete_ids, $model){
                if ($insertData) {
                    $flag = ProductRightMapModel::insert($insertData);
                    test_assert($flag, "操作失败请重试");
                }
                if ($delete_ids){
                    $flag = ProductRightMapModel::where('product_id', $model->id)->whereIn('product_right_id', $delete_ids)->delete();
                    test_assert($flag, "操作失败请重试");
                }
            });
            return $this->ajaxSuccessMsg("操作成功");
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            errLog("ProductRightMapModel \r\n {$msg}");
        }
        return $this->ajaxError('操作失败#' . $msg);
    }

    /**
     * 加入产品权限
     * right[0]: 4
     * right[3]: 7
     * right[6]: 10
     * right[9]: 13
     * _pk: 40247
     */
    public function addPrivAction()
    {
        $id = $this->post['_pk'] ?? 0;
        $series = $this->post['priv'] ?? [];
        if (!$series) {
            return $this->ajaxError('至少选择一个权益');
        }
        /** @var ProductModel $model */
        $model = ProductModel::find($id);
        if (is_null($model) || $model->type != ProductModel::TYPE_VIP) {
            return $this->ajaxError('商品无效');
        }
        //获取旧的权限ID
        $old_ids =  ProductPrivilegeModel::where('product_id', $model->id)
            ->get()
            ->pluck('privilege_id')
            ->filter()
            ->values()
            ->toArray();
        //新增的ID
        $new_add_ids = array_diff($series, $old_ids);
        $insertData = collect($new_add_ids)->map(function ($right_id) use ($model) {
            if (ProductPrivilegeModel::where(['privilege_id' => $right_id, 'product_id' => $model->id])->exists()) {
                return null;
            }
            return [
                'privilege_id' => $right_id,
                'product_id'   => $model->id,
                'value'        => 1,
                'created_at'   => date('Y-m-d H:i:s')
            ];
        })->filter()->toArray();;
        //删除的ID
        $delete_ids = array_diff($old_ids, $series);
        try {
            transaction(function () use ($insertData, $delete_ids, $model){
                if ($insertData) {
                    ProductPrivilegeModel::insert($insertData);
                }
                if ($delete_ids){
                    ProductPrivilegeModel::where('product_id', $model->id)->whereIn('privilege_id', $delete_ids)->delete();
                }
            });
            return $this->ajaxSuccessMsg("操作成功");
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            errLog("ProductPrivilegeModel \r\n {$msg}");
        }
        return $this->ajaxError('操作失败#' . $msg);
    }
}