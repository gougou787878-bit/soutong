<?php

use helper\Validator;
use helper\QueryHelper;
use service\AdService;
use service\ApiAiMagicService;

class AimagicController extends BaseController
{
    public function list_materialAction()
    {
        
        try {
            $service = new ApiAiMagicService();
            list($page, $limit) = QueryHelper::pageLimit();
            $data = $service->list_material($page, $limit);
            $ads = AdService::getADsByPosition(AdsModel::POSITION_AI_HL_BANNER);
            return $this->listJson($data, ['ads' => $ads]);
        }catch (Throwable $e) {
            return $this->errorJson('系统错误,请稍后重试');
        }
    }

    public function pre_magicAction(){
        try {
            $member = request()->getMember()->refresh();
            $service = new ApiAiMagicService();
            $res = $service->getMagicPreData($member);
            return $this->showJson($res);
        } catch (Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    public function generate_videoAction()
    {
        try {
            $validator = Validator::make($this->post, [
                'material_id' => 'required',
                'thumb'       => 'required',
                'thumb_w'     => 'required',
                'thumb_h'     => 'required',
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $material_id = (int)$this->post['material_id'];
            $thumb = trim($this->post['thumb']);
            $thumb_w = (int)$this->post['thumb_w'];
            $thumb_h = (int)$this->post['thumb_h'];
            $type = (int)$this->post['type'];
            $member = request()->getMember()->refresh();
            $service = new ApiAiMagicService();
            $service->generate_video($member, $type, $material_id, $thumb, $thumb_w, $thumb_h);
            return $this->successMsg('上传成功,等待处理');
        } catch (Exception $e) {
            return $this->errorJson($e->getMessage(), $e->getCode());
        } catch (Throwable $e) {
            return $this->errorJson('系统错误,请稍后重试', $e->getCode());
        }
    }

    public function my_generate_videoAction()
    {
        try {
            $validator = Validator::make($this->post, [
                'status' => 'required|numeric',
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $status = (int)$this->post['status'];
            list($page, $limit) = QueryHelper::pageLimit();
            $member = request()->getMember();
            $service = new ApiAiMagicService();
            $data = $service->list_my_generate_video($member, $status, $page, $limit);
            return $this->showJson($data);
        } catch (Exception $e) {
            return $this->errorJson($e->getMessage());
        } catch (Throwable $e) {
            return $this->errorJson('系统错误,请稍后重试');
        }
    }

    public function del_generate_videoAction(): bool
    {
        try {
            $validator = Validator::make($this->post, [
                'ids' => 'required',
            ]);
            $rs = $validator->fail($msg);
            test_assert(!$rs, $msg);

            $member = request()->getMember();
            $ids = $this->post['ids'];
            $service = new ApiAiMagicService();
            $service->del_generate_video($member, $ids);

            return $this->successMsg('操作成功');
        } catch (Exception $e) {
            return $this->errorJson($e->getMessage());
        } catch (Throwable $e) {
            return $this->errorJson('系统错误,请稍后重试');
        }
    }
}