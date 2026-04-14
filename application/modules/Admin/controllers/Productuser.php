<?php

use \Carbon\Carbon;

/**
 * Class ProductuserController
 *
 * @date 2022-03-30 16:44:25
 */
class ProductuserController extends BackendBaseController
{
    /**
     * 列表数据过滤
     * @return Closure
     *
     * @date 2019-12-02 17:08:03
     */
    protected function listAjaxIteration()
    {
        return function (ProductUserModel $item) {
            $item->load('product')->product;
            $item->vip_level_str =  MemberModel::USER_VIP_TYPE[$item->vip_level];

            return $item;
        };
    }

    /**
     * 保存数据
     * @return bool
     */
    public function saveAction()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->ajaxError('请求错误');
        }
        $post = $this->postArray();
        if (!$post['product_id'] || !$post['aff']) {
            return $this->ajaxError('参数必填');
        }
        /** @var MemberModel $member */
        $member = MemberModel::firstAff($post['aff']);
        if (!$member){
            return $this->ajaxError('aff填写错误，没有此用户');
        }
        $product = ProductModel::find($post['product_id']);
        //添加VIP信息
        if ($member->vip_level <= abs($product->vip_level)) {
            $member->vip_level = $product->vip_level;
        }
        $member->expired_at = $product->valid_date * 86400 + max($member->expired_at, TIMESTAMP);

        if ($member->isDirty()) {
            $isOk = $member->save();
            if (empty($isOk)) {
                return $this->ajaxError('用户会员添加异常');
            }
        }
        ProductUserModel::buyVIPProduct($member, $product);
        MemberModel::clearFor($member);

        return $this->ajaxSuccessMsg('操作成功');
    }

    protected function deleteAfterCallback($model ,$isDelete)
    {
        if ($isDelete) {
            \UsersProductPrivilegeModel::where([
                ['product_id','=',$model->product_id],
                ['aff','=',$model->aff],
            ])->delete();
            /** @var MemberModel $member */
            $member = MemberModel::firstAff($model->aff);
            $member->vip_level = MemberModel::VIP_LEVEL_NO;
            $member->expired_at = 0;
            if ($member->isDirty()) {
                $member->save();
                MemberModel::clearFor($member);
            }

            cached(sprintf(ProductUserModel::USER_PRODUCT_AFF, $member->aff))->clearCached();
            cached(UsersProductPrivilegeModel::REDIS_KEY_USER_PRIVILEGE.$model->aff)->clearCached();
            cached('user:vip:upgrade:' . $model->aff)->clearCached();
        }
    }

    /**
     * 试图渲染
     * @return string
     *
     * @date 2022-03-30 16:44:25
     */
    public function indexAction()
    {
        $this->assign('vipData', ProductModel::getAdminVIPDataList());
        $this->display();
    }


    /**
     * 获取对应的model名称
     * @return string
     *
     * @date 2022-03-30 16:44:25
     */
    protected function getModelClass(): string
    {
        return ProductUserModel::class;
    }

    /**
     * 定义数据操作的表主键名称
     * @return string
     *
     * @date 2022-03-30 16:44:25
     */
    protected function getPkName(): string
    {
        return 'id';
    }

    /**
     * 定义数据操作日志
     * @return string
     *
     * @date 2019-11-04 17:19:41
     */
    protected function getLogDesc(): string
    {
        // TODO: Implement getLogDesc() method.
        return '';
    }

    protected function postArray($setPost = null)
    {
        $post = request()->getPost();
        $post['created_at'] = date("Y-m-d H:i:s");
        $post['type'] = 0;
        return $post;
    }
}