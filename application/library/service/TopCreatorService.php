<?php


namespace service;


use MemberModel;

class TopCreatorService
{

    public function getLike($type, $score, $withscore = null)
    {
        if ('test' == APP_ENVIRON) {
            return ['5130656'=>1, '5000093'=>1];
        }

        $key = 'top:creator:like:';

        if ($type == 'day') {
            $_key = $key . date('Ymd');
            return redis()->zRevRange($_key, 0, $score - 1, $withscore);
        } elseif ($type == 'moon') {
            $_key = $key . date('Y-m');
           return redis()->zRevRange($_key, 0, $score - 1, $withscore);
        } elseif ($type == 'week') {
            $_key = $key . date('Y.W');
            return redis()->zRevRange($_key, 0, $score - 1, $withscore);
        }
    }

    public function incrLike($uid)
    {
        $officialUid = setting('official.uid', 4888000);
        if($officialUid == $uid){
            return ;
        }

        $key = 'top:creator:like:';
        $_key = $key . date('Ymd');
        if (redis()->zIncrBy($_key, 1, $uid) == 1) {
            redis()->expire($_key, 86700);
        }
        $_key = $key . date('Y.W');
        if (redis()->zIncrBy($_key, 1, $uid) == 1) {
            redis()->expire($_key, 86400 * 8);
        }
        $_key = $key . date('Y-m');
        if (redis()->zIncrBy($_key, 1, $uid) == 1) {
            redis()->expire($_key, 86400 * 32);
        }
    }

    public function getUp($type, $score, $withscore = null)
    {
        if ('test' == APP_ENVIRON) {
            return ['5130656'=>1, '5000093'=>1];
        }
        $key = 'top:creator:up:';

        if ($type == 'day') {
            $_key = $key . date('Ymd');
            return redis()->zRevRange($_key, 0, $score - 1, $withscore);
        } elseif ($type == 'moon') {
            $_key = $key . date('Y-m');
            return redis()->zRevRange($_key, 0, $score - 1, $withscore);
        } elseif ($type == 'week') {
            $_key = $key . date('Y.W');
            return redis()->zRevRange($_key, 0, $score - 1, $withscore);
        }
    }

    public function incrUp($uid)
    {
        $officialUid = setting('official.uid', 4888000);
        if($officialUid == $uid){
            return ;
        }
        $key = 'top:creator:up:';
        $_key = $key . date('Ymd');
        if (redis()->zIncrBy($_key, 1, $uid) == 1) {
            redis()->expire($_key, 86700);
        }
        $_key = $key . date('Y.W');
        if (redis()->zIncrBy($_key, 1, $uid) == 1) {
            redis()->expire($_key, 86400 * 8);
        }
        $_key = $key . date('Y-m');
        if (redis()->zIncrBy($_key, 1, $uid) == 1) {
            redis()->expire($_key, 86400 * 32);
        }
    }

    public function getIncome($type, $score, $withscore = null)
    {
        if ('test' == APP_ENVIRON) {
            return ['5130656'=>1, '5000093'=>1];
        }
        $key = 'top:creator:income:';

        if ($type == 'day') {
            $_key = $key . date('Ymd');
            return redis()->zRevRange($_key, 0, $score - 1, $withscore);
        } elseif ($type == 'moon') {
            $_key = $key . date('Y-m');
            return redis()->zRevRange($_key, 0, $score - 1, $withscore);
        } elseif ($type == 'week') {
            $_key = $key . date('Y.W');
            return redis()->zRevRange($_key, 0, $score - 1, $withscore);
        }
    }

    public function incrIncome($uid,$score = 1)
    {
        $officialUid = setting('official.uid', 4888000);
        if($officialUid == $uid){
            return ;
        }
        $key = 'top:creator:income:';
        $_key = $key . date('Ymd');
        if (redis()->zIncrBy($_key, $score, $uid) <80) {
            redis()->expire($_key, 86700);
        }
        $_key = $key . date('Y.W');
        if (redis()->zIncrBy($_key, $score, $uid) <80) {
            redis()->expire($_key, 86400 * 8);
        }
        $_key = $key . date('Y-m');
        if (redis()->zIncrBy($_key, $score, $uid) <80) {
            redis()->expire($_key, 86400 * 32);
        }
    }

    /**
     * @param $type
     * @param $limit
     * @return mixed
     * @author xiongba
     * @date 2020-10-14 20:30:51
     */
    public function getAllRank($type , $limit){
        $uidAry = $this->getLike($type, $limit);
        $user = MemberModel::whereIn('uid', $uidAry)->get(['uid', 'nickname', 'thumb']);
        $user = array_sort_by_idx($user->toArray() , $uidAry , 'uid');
        $ranking[] = [
            'name' => '获赞达人',
            'type' => 'hz',
            'icon' => url_live('/new/xiao/20201014/2020101418031579387.png'),
            'item' => array_column($user , 'avatar_url')
        ];
        $uidAry = $this->getUp($type, $limit);
        $user = MemberModel::whereIn('uid', $uidAry)->get(['uid', 'nickname', 'thumb']);
        $user = array_sort_by_idx($user->toArray() , $uidAry , 'uid');
        $ranking[] = [
            'name' => '上传达人',
            'type' => 'up',
            'icon' => url_live('/new/xiao/20201014/2020101418031579387.png'),
            'item' => array_column($user , 'avatar_url')
        ];
        $uidAry = $this->getIncome($type, $limit);
        $user = MemberModel::whereIn('uid', $uidAry)->get(['uid', 'nickname', 'thumb']);
        $user = array_sort_by_idx($user->toArray() , $uidAry , 'uid');
        $ranking[] = [
            'name' => '收益达人',
            'type' => 'income',
            'icon' => url_live('/new/xiao/20201014/2020101418031579387.png'),
            'item' => array_column($user , 'avatar_url')
        ];
        return $ranking;
    }


}