<?php

class CoinrecordModel extends EloquentModel
{

    protected $table='coinrecord';
    protected $fillable = [
        'type',
        'action',
        'uuid',
        'touuid',
        'giftid',
        'giftcount',
        'totalcoin',
        'showid',
        'addtime',
        'game_banker',
        'game_action',
        'mark',
        'live_updated_at'
    ];

    protected $guarded = [];

    /* 获取用户本场贡献 */
    public function getContribut($uid,$liveuid,$showid){
        $sum=$this->where('action','sendgift')
            ->where('uid',$uid)
            ->where('touid',$liveuid)
            ->where('showid',$showid)
            ->sum('totalcoin');
        if(!$sum){
            $sum=0;
        }

        return $sum;
    }

    public function getWeekContribute($uid,$starttime=0,$endtime=0){
        $contribute='0';
        if($uid>0){
            $query = $this->query();
            $query->whereIn('action',['sendgift','buyguard'])->where('uid',$uid);
            if($starttime>0 ){
                $query->where('addtime','>',$starttime);
            }
            if($endtime>0 ){
                $query->where('addtime','<',$endtime);
            }
            $contribute = $query->sum('totalcoin');
            if(!$contribute){
                $contribute=0;
            }
        }

        return $contribute;
    }

    public function withUser()
    {
        return $this->hasOne(UserModel::class,'id','uid');
    }

    /* 贡献榜 */
    public function getContributeList($touid, $p)
    {
        $pnum = 50;
        $start = ($p - 1) * $pnum;

        $rs = $this->select("uid",DB::raw('SUM(totalcoin) as total'))
            ->where('touid', $touid)->groupBy("uid")->orderBy("total",'desc')
            ->offset($start)->limit( $pnum)->get();
        if ($rs){
            $rs = $rs->toArray();
        }
        foreach ($rs as $k => $v) {
            $rs[$k]['userinfo'] = getUserInfo($v['uid']);
        }

        return $rs;
    }

    // 给主播榜
    public function getContributeTopList($touid,$crypt = false)
    {
        $rs = $this->select("uid")
            ->where('touid', $touid)->where('uid','!=',$touid)->groupBy("uid")->limit( 3)->get();

        if (!$rs){
            return [];
        }
        $rs = $rs->toArray();
        $return = [];
        foreach ($rs as $k => $v) {
            $touid = getUserInfo($v['uid'],0,$crypt);
            $return[]= $touid['avatar'];
        }
        return $return;
    }

    public function getSendGiftList($uid,$type = 0 ,$p)
    {
        if ($type  == 1){
            $where = [
                'touid' => $uid,
                'action' =>'sendgift'
            ];
        } else {
            $where = [
                'uid' => $uid,
                'action' =>'sendgift'
            ];
        }
        $list = $this->_getGiftList($where, $p);

        $retrun = [];
        foreach ($list as $k => $value){
            $user_info = getUserInfo($value['uid']);
            $retrun[$k]['nickname'] = $user_info['nickname'];
            $retrun[$k]['gift_name'] = $value->withGift->giftname;
            $retrun[$k]['send_gift_count'] = $value->giftcount;
            $retrun[$k]['total_coin'] = $value->totalcoin;
        }
        return $retrun;
    }


    private function _getGiftList($where,$p = 1)
    {
       $start = $p * 50;
       $list = $this->select("uid",'giftid','giftcount','totalcoin')
           ->with('withGift')->orderBy("addtime",'desc')->where($where)
           ->offset($start)->limit(50)->get();

       return $list;
    }

    public function withGift()
    {
        return $this->hasOne(GiftModel::class,'id','giftid');
    }

    public function withToUser()
    {
        return $this->hasOne(UserModel::class,'id','touid');
    }
}
