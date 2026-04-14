<?php

use helper\QueryHelper;
use service\CreatorService;
use service\ProxyService;
use service\UserVideoService;

class AuthController extends BaseController
{

    /**
     * 申请认证 原创和约炮
     * @return void
     */
    public function applyAction(){
        $type = $this->post['type'] ?? 0;
        $member = request()->getMember();
        //频率控制
        \helper\Util::PanicFrequency(sprintf("auth_apply-%d",$this->member['uid']),1,10,'操作太频繁,5秒后重试');
        try {
            if($type == 1){
                $video  = $this->post['video'] ?? '';
                if(!$video){
                    throw new \Exception('请上传认证视频');
                }
                $info = MemberAuthModel::query()
                    ->where('uuid',$member->uuid)->where('type',1)->first();
                if($info){
                    if(in_array($info->status,[0,2])){
                        $info->update(['status'=>1,'video'=>$video]);
                    }elseif($info->status == 4){
                        throw new \Exception('已认证');
                    }elseif($info->status == 3){
                        throw new \Exception('认证被封禁不可申请');
                    }else{
                        throw new \Exception('审核中,请耐心等待');
                    }
                }else{
                    throw new \Exception('数据异常,请重新进入申请流程');
                }
            }else{
                $info = MemberAuthModel::query()
                    ->where('uuid',$member->uuid)->where('type',0)->first();
                if($info){
                    if(in_array($info->status,[0,2])){
                        $info->update(['status'=>1]);
                    }elseif($info->status == 4){
                        throw new \Exception('已认证');
                    }elseif($info->status == 3){
                        throw new \Exception('认证被封禁不可申请');
                    }else{
                        throw new \Exception('审核中,请耐心等待');
                    }
                }else{
                    $insertData['uuid'] = $member->uuid;
                    $insertData['nickname'] = $member->nickname;
                    $insertData['type'] = 0;
                    $model =  MemberAuthModel::create($insertData);
                    if(!$model){
                        throw new \Exception('系统异常');
                    }
                }
            }
            return $this->showJson(['msg' => '操作成功']);
        } catch (\Throwable $e) {
            return $this->errorJson($e->getMessage());
        }


    }

    /**
     * 约炮认证界面
     * @return bool
     */
    public function infoAction(){

        $member  = request()->getMember();
        //频率控制
        \helper\Util::PanicFrequency(sprintf("auth_info-%d",$this->member['uid']),1,10,'操作太频繁,5秒后重试');
        $info = MemberAuthModel::query()
            ->where('uuid',$member->uuid)->where('type',1)->first();
        $data = [];
        $tips1 = '*录制的视频不会对外公开，若涉嫌诈骗平台将予以追究';
        $tips2 = '温馨提示：请上传当下录制的视频，且视频中说出屏幕上的数字，10-30秒淡妆出镜视频，为更快通过，请联系官方确认';
        $data['tips1'] = $tips1;
        $data['tips2'] = $tips2;
        $number_code = rand(1000,9999);
        try {
            if($info){
                $data['msg'] = MemberAuthModel::AUTH_STATUS_OPT[$info->status];
                $data['status'] = $info->status;

                if(in_array($info->status,[0,2])){
                    $data['number_code'] = $number_code;
                    $data['msg'] = '立即申请';

                    $info->update(['number_code'=>$number_code]);
                }
                elseif($info->status == 4){
                    $data['msg'] = '已通过审核';
//                    throw new \Exception('已通过审核');
                }elseif($info->status == 3){
                    $data['msg'] = '申请被封禁不可以申请';
//                    throw new \Exception('申请被封禁不可以申请');
                }else{
                    $data['msg'] = '审核中,请耐心等待';
//                    throw new \Exception('审核中,请耐心等待');
                }
            }else{
                $insertData['number_code'] = rand(1000,9999);
                $insertData['uuid'] = $member->uuid;
                $insertData['nickname'] = $member->nickname;
                $insertData['type'] = 1;
                $model =  MemberAuthModel::create($insertData);
                if($model){
                    $data['number_code'] = $model->number_code;
                    $data['status'] = 0;
                    $data['tips1'] = $tips1;
                    $data['tips2'] = $tips2;
                    $data['msg'] = '立即申请';
                }else{
                    throw new \Exception('数据错误');
                }
            }
            return $this->showJson($data);
        } catch (\Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }

    /**
     * 社区认证界面
     * @return bool
     */
    public function post_infoAction(){

        $member  = request()->getMember();
        //频率控制
        \helper\Util::PanicFrequency(sprintf("auth_info-%d",$this->member['uid']),1,10,'操作太频繁,5秒后重试');
        $info = MemberAuthModel::query()
            ->where('uuid',$member->uuid)->where('type',0)->first();
        $data = [];
        $tips = '1、平台不会通过个人收取任何入驻费用
2、请与官方群管理员或APP客服确认真实性
3、更多问题请添加官方管理员咨询';
        $contact = [
            [
                'name'=>'官方飞机群',
                'value'=>'https://t.me/soutongshequ'
            ]
        ];
        try {
            if($info){
                $data['msg'] = MemberAuthModel::AUTH_STATUS_OPT[$info->status];
                $data['status'] = $info->status;
                $data['tips'] = $tips;
                $data['contact'] = $contact;
                if(in_array($info->status,[0,2])){
//                    $data['status'] = 0;
                    $data['msg'] = '立即申请';
                }elseif($info->status == 4){
                    $data['msg'] = '已通过审核';
                }elseif($info->status == 3){
                    $data['msg'] = '申请被封禁不可以申请';
                }else{
                    $data['msg'] = '审核中,请耐心等待';
                }
            }else{
                $data['status'] = 0;
                $data['tips'] = $tips;
                $data['contact'] = $contact;
                $data['msg'] = '立即申请';
            }
            return $this->showJson($data);
        } catch (\Throwable $e) {
            return $this->errorJson($e->getMessage());
        }
    }


}
