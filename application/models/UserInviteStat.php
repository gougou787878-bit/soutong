<?php

// 邀请计数排行表

class UserInviteStatModel extends EloquentModel {

    protected $table = 'users_invite_stat';

    const USERS_INVITE_STAT_RANK = "users_invite_stat_ranklist";

    protected $fillable = [
        'uid',
        'nums'
    ];

    public function getUserInviteByUid($uid)
    {
        return $this->where('uid',$uid)->value('nums');
    }

    public function getList($p)
    {
        $limit = 10;
        $start = ($p -1) * $limit;
        $list = $this->limit(10)->offset($start)->orderBy('nums','desc')->get();

        if (!$list){
            return [];
        }
        return $list->toArray();
    }

    public function addNumsBy($uid)
    {
        $invite = $this->where('uid',$uid)->first();
        if ($invite){
            $this->where('uid',$uid)->update(['nums' => DB::raw("nums + 1")]);
        }else {
            $this->create([
                'uid' => $uid,
                'nums' => 1
            ]);
        }
    }
}