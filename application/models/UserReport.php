<?php
class UserReportModel extends EloquentModel
{
    protected $table ='users_report';

    protected $guarded =[];

    /* 举报 */
    public function setReport($uid,$touid,$content){
        return  $this->create(["uid"=>$uid,"touid"=>$touid,'content'=>$content,'addtime'=>time() ] );
    }
}