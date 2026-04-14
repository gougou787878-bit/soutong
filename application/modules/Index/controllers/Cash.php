<?php
/**
 * 提现记录
 */

class CashController extends IndexController
{
    private $status = [
        '0'=>'审核中',
        '1'=>'已完成',
        '2'=>'未通过',
    ];
    /**
     * 提现记录
     */
    function indexAction() {
        $token = $_REQUEST["token"] ??'';
        $uuid = $_REQUEST["uuid"] ??'';
        $uid = $_REQUEST["uid"] ??'';
        $list = [];
        if($uuid && $token){
            $list = UserWithdrawModel::where('uuid', $uuid)
                ->orderBy('created_at', 'desc')
                ->offset(0)
                ->limit(50)
                ->get()->toArray();
            if($list){
                foreach ($list as $k => $v) {
                    $list[$k]['created_at'] = date('Y.m.d', $v['created_at']);
                    $list[$k]['status_name'] = UserWithdrawModel::STATUS_TEXT[$v['status']];
                }
            }
        }

        $this->view->assign("token", $token);
        $this->view->assign("list", $list);
        $this->view->assign("uid", $uuid);
        $this->show('cash');
    }

    /**
     * ajax分页
     */
    public function getlistmoreAction()
    {
        $uid=$_REQUEST['uid'];
        $uuid = $this->member['uuid'];
        $token=$_REQUEST['token'];

        $p=$_REQUEST['page'];
        $pnums=50;
        $start=($p-1)*$pnums;

        $list = UserWithdrawModel::where('uuid',$uuid)
            ->orderBy('created_at','desc')
            ->offset($start)
            ->limit($pnums)
            ->get()->toArray();

        foreach($list as $k=>$v){

            $list[$k]['addtime']=date('Y.m.d',$v['addtime']);
            //$list[$k]['status_name']=$this->status[$v['status']];
            $list[$k]['status_name']= UserWithdrawModel::STATUS_TEXT[$v['status']];;
        }

        $nums=count($list);
        if($nums<$pnums){
            $isscroll=0;
        }else{
            $isscroll=1;
        }

        $result=array(
            'data'=>$list,
            'nums'=>$nums,
            'isscroll'=>$isscroll,
        );
        return $this->ej($result);
    }

}