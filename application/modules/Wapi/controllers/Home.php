<?php

use service\PcService;
use helper\Validator;

class HomeController extends PcBaseController
{
    // 不需要登录 基础数据
    public function configAction(): bool
    {
        try {
            $service = new PcService();
            $config = $service->getConfig();
            $_SERVER['SCRIPT_PARAMS'] = [];
            return $this->showJson($config);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    // 点击上报
    public function reportAction(): bool
    {
        try {
            $Validator = Validator::make($this->data, [
                'id'   => 'required'
            ]);
            $rs = $Validator->fail($msg);
            test_assert(!$rs, $msg);

            $id = $this->data['id'];
            bg_run(function () use ($id){
                PcAdsModel::where('id',$id)->increment('click_number');
            });

            return $this->successMsg('成功');
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }
}
