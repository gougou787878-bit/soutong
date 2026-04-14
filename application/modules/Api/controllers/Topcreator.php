<?php


use service\TopCreatorService;

class TopcreatorController extends BaseController
{


    /**
     * 运营推荐配置
     * @author xiongba
     * @date 2020-09-29 20:20:51
     */
    public function recommendAction()
    {
        $config = setting('top:creator:recommend');
        if (empty($config)) {
            return $this->showJson([
                'author'  => null,
                'mv_list' => null
            ]);
        }


        list($uid, $vidStr) = explode(':', $config);
        $vidAry = explode(',', $vidStr);
        if (count($vidAry) > 50) {
            $vidAry = array_slice($vidAry, 0, 50);
        }
        $mvList = MvModel::queryBase()->with('user_topic')->whereIn('id', $vidAry)->get();
        $member = MemberModel::where('uid', $uid)->first([
            'uid',
            'uuid',
            'thumb',
            'nickname',
            'expired_at',
            'vip_level',
            'fans_count',
            'followed_count',
            'fabulous_count',
            'likes_count',
            'videos_count',
            //'coins',
            //'coins_total',
            'thumb',
            'auth_status',
            'aff',
            'person_signnatrue'
        ]);
        //$member->addHidden(['phone','oauth_id','oauth_type','regip','uuid']);
        /** @var MemberModel $member */
        $member->watchByUser(request()->getMember());
        $member->thumb = $member->avatar_url;
        //$followedUids = redis()->sMembers(\UserAttentionModel::REDIS_USER_FOLLOWED_LIST . $this->member['uid']);
        //$member->is_attention = (int)in_array($uid, $followedUids);
        return $this->showJson([
            'author'  => $member,
            'mv_list' => (new \service\MvService())->v2format($mvList)
        ]);
    }


    public function likeAction()
    {
        $type = $this->post['type']??'day';
        MemberModel::setWatchUser(request()->getMember());
        $uidWithScore = (new TopCreatorService)->getLike($type, 30, true);
        if($uidWithScore){
            $uid = array_keys($uidWithScore);
            //errLog("likeAction:".var_export($uid,1));
            $member = MemberModel::whereIn('uid', $uid)->get(['uid', 'aff', 'nickname', 'thumb'])
                ->map(function ($item) use ($uidWithScore) {
                    $item->likes_count = $uidWithScore[$item->uid];
                    return $item;
                });
            $result = array_sort_by_idx($member, $uid, 'uid');
            return $this->showJson($result);
        }
        $this->showJson([]);
    }


    public function upAction()
    {
        $type = $this->post['type']??'day';
        $uidWithScore = (new TopCreatorService)->getUp($type, 30, true);
        if($uidWithScore) {
            $uid = array_keys($uidWithScore);
            $selfMember = request()->getMember();
            MemberModel::setWatchUser($selfMember);
            $member = MemberModel::whereIn('uid', $uid)->get(['uid', 'aff', 'nickname', 'thumb'])
                ->map(function (MemberModel $item) use ($uidWithScore) {
                    $item->videos_count = $uidWithScore[$item->uid];
                    return $item;
                });
            $result = array_sort_by_idx($member, $uid, 'uid');
            return $this->showJson($result);
        }
        $this->showJson([]);
    }

    public function incomeAction()
    {
        $type = $this->post['type']??'day';
        $uidWithScore = (new TopCreatorService)->getIncome($type, 30, true);
        if($uidWithScore){
            $uid = array_keys($uidWithScore);
            MemberModel::setWatchUser(request()->getMember());
            $member = MemberModel::whereIn('uid', $uid)->get(['uid', 'aff', 'nickname', 'thumb'])
                ->map(function ($item) use ($uidWithScore) {
                    $item->votes = $uidWithScore[$item->uid];
                    return $item;
                });
            $result = array_sort_by_idx($member, $uid, 'uid');
            return $this->showJson($result);
        }
        $this->showJson([]);
    }




}