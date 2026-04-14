<?php

namespace service;


use helper\QueryHelper;
use tools\RedisService;

class UserVideoService
{

    /**
     * UserVideoService constructor.
     * @author xiongba
     */
    public function __construct()
    {
    }

    /**
     * 获取指定用户的视频
     * @param $uid
     * @param $kwy
     * @param $member
     * @return array|mixed
     * @author xiongba
     */
    public function getVideosByUid(\MemberModel $member, $uid, $show_type, $kwy)
    {
        $show_aw = 'no';
        if(in_array($member->vip_level,[6,7])){
            $show_aw = 'all';
        }
        if($member->uid == $uid ){
            $show_aw = 'all';
        }

        list($page, $limit) = QueryHelper::pageLimit();
        $key = sprintf('member:mv:list:%d:%d:%d:%d:%d:%s', $uid, $show_aw, $show_type, $page, $limit, $kwy);
        $list = cached($key)->fetchPhp(function () use ($uid, $kwy, $show_aw, $show_type, $page, $limit){
            return \MvModel::queryBase()
                ->with('user:uid,nickname,thumb,vip_level,sexType,expired_at,uuid,aff')
                ->with('user_topic')
                ->where(['uid' => $uid])
                ->where('type', $show_type)
                ->when($kwy, function ($query, $value) {
                    return $query->where('title', 'like', "%{$value}%");
                })
                ->when($show_aw,function ($query)use($show_aw){
                    if($show_aw =='no'){
                        $query->where('is_aw',0);
                    }
                })
                ->forPage($page, $limit)
                ->orderByDesc('is_top')
                ->orderByDesc('id')
                ->get();
        },600);

        return (new MvService())->v2format($list, $member);
    }


    /**
     * 用户喜欢的视频
     * @param string $uid
     * @param $kwy
     * @param $member
     * @return array|bool|mixed|string
     */
    public function getUserLikesVideoList(\MemberModel $member, $uid, $kwy, $show_type)
    {
        list($page, $limit) = QueryHelper::pageLimit();
        $key = "user:like:mv:{$uid}:{$show_type}:{$page}";
        $kwy && $key = $key.substr(md5($kwy),0,4);
        if ($member->likes_count > 20){
            $items = cached($key)
                ->group('like:' . $uid)
                ->fetchPhp(function ()use($uid, $kwy, $show_type, $page, $limit){
                    $likeVid = \UserLikeModel::query()
                        ->join('mv', 'mv.id', '=', 'user_likes.mv_id')
                        ->where('user_likes.uid', $uid)
                        ->where('mv.status', \MvModel::STAT_CALLBACK_DONE)
                        ->where('mv.is_hide', \MvModel::IS_HIDE_NO)
                        ->where('mv.type', $show_type)
                        ->when($kwy, function ($query, $value) {
                            return $query->where('mv.title', 'like', "%{$value}%");
                        })
                        ->forPage($page, $limit)
                        ->pluck('mv.id')
                        ->toArray();

                    $items = \MvModel::queryBase()
                        ->with('user_topic')
                        ->with('user:uid,nickname,thumb,vip_level,sexType,expired_at,uuid,aff')
                        ->whereIn('id', $likeVid)
                        ->get();
                    return array_keep_idx($items, $likeVid);
                },60);
        }else{
            $likeVid = cached('tb_ul:idv-' . $uid)
                ->expired(300)
                ->serializerJSON()
                ->setSaveEmpty(true)
                ->fetch(function () use ($uid) {
                    return \UserLikeModel::where('uid', $uid)
                        ->orderByDesc('id')
                        ->pluck('mv_id')
                        ->toArray();
                });
            $items = cached($key)
                ->group('like:' . $uid)
                ->fetchPhp(function ()use($likeVid, $kwy, $show_type, $page, $limit){

                    $items = \MvModel::queryBase()
                        ->with('user_topic')
                        ->with('user:uid,nickname,thumb,vip_level,sexType,expired_at,uuid,aff')
                        ->whereIn('id', $likeVid)
                        ->where('type', $show_type)
                        ->when($kwy, function ($query, $value) {
                            return $query->where('title', 'like', "%{$value}%");
                        })
                        ->forPage($page, $limit)
                        ->orderByDesc('id')
                        ->get();
                    return $items;
                },60);

        }
        return (new MvService())->v2format($items, $member);
    }

    /**
     * 用户喜欢的视频
     * @param string $uid
     * @param $kwy
     * @param $member
     * @return array|bool|mixed|string
     */
    public function getUserLikesVideoListError(string $uid, $kwy, \MemberModel $member)
    {
        list($page, $limit) = QueryHelper::pageLimit();
        $redis_kwy = '';
        $kwy && $redis_kwy = substr(md5($kwy),0,6);
        $items = cached(sprintf('user:like:mv:%d:%d:%d:%s',$member->uid,$page,$limit,$redis_kwy))
            ->clearCached()
            ->fetchPhp(function () use ($uid,$kwy,$page,$limit){
                return \UserLikeModel::with(['videos' => function($query) use ($kwy){
                        return $query->with('user_topic')
                            ->when($kwy, function ($query,$value) {
                                return $query->where('title', 'like', "%{$value}%");
                            });
                     }])
                    ->where('uid', $uid)
                    ->orderByDesc('id')
                    ->forPage($page,$limit)
                    ->get()
                    ->pluck('videos');
        },300);

        return (new MvService())->v2format($items, $member);
    }

}