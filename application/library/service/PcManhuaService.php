<?php

namespace service;

use PcMhTabModel;
use MhModel;
use MemberModel;
use MhSrcModel;
use MhFavoritesModel;

/**
 * Class PcManhuaService
 * @package service
 */
class PcManhuaService
{
    public function tabDetail($tab_id){
        return PcMhTabModel::getDetail($tab_id);
    }

    public function list($tab_id,$sort,$page,$limit){
       $mhTab = PcMhTabModel::getDetail($tab_id);
       test_assert($mhTab,'导航不存在');

        return MhModel::listByTab($mhTab,$sort,$page,$limit);
    }

    public function searchManhua($tab_id,$kwy,$page,$limit)
    {
        $tagStr = null;
        if ($tab_id){
            /** @var PcMhTabModel $mhTab */
            $mhTab = PcMhTabModel::getDetail($tab_id);
            test_assert($mhTab,'导航不存在');
            $tagStr = $mhTab->tags_str;
        }

        return MhModel::searchList($tagStr,$kwy,$page,$limit);
    }

    public function getDetailData($id,MemberModel $member = null)
    {
        /** @var \MhModel $detail */
        $detail = \MhModel::getRow($id);
        test_assert($detail,'查无漫画信息');

        $detail->user_vip = 0;
        if ($member){
            $detail = $detail->watchByUser($member);
            $detail->user_vip = $member->is_vip;
        }
        $detail->load('series');
        $detail->now_total= 0 ;//目前总章节
        $detail->from_episode = 1;//从第0 还是第1开始
        if ($detail->series) {
            $detail->now_total = collect($detail->series)->count();
            $detail->from_episode = $detail->series[0]->episode;
        }
        //后台执行
        bg_run(function () use ($id){
            MhModel::addView($id);
        });

        return $detail;
    }

    public function readManhua(MemberModel $member, $m_id, $s_id)
    {
        /** @var MhModel $detail */
        $detail = MhModel::getRow($m_id);
        test_assert($detail,"查无漫画信息");
        $detail = $detail->watchByUser($member);
        if (($member->is_vip && $detail->coins==0 )|| ($detail->is_pay)||($detail->newest_series>5 && $s_id<=2)
        ) {
            return MhSrcModel::getSeriesSrc($m_id, $s_id);
        }
        test_assert(false,'无权查看漫画章节详细信息');
    }


    public function guessByManHuaLike($manhua_id)
    {
        return MhModel::guessByManHuaLike($manhua_id, 8);
    }

    public function getFavorites(MemberModel $member, $comics_id)
    {
        /** @var MhModel $mh */
        $mh = \MhModel::getRow($comics_id);
        test_assert($mh,'漫画记录不存在');
        $uid = $member->uid;
        /** @var MhFavoritesModel $mhFavorites */
        $mhFavorites = MhFavoritesModel::hasLike($uid, $mh->id);

        if ($mhFavorites) {
            MhFavoritesModel::where(['id' => $mhFavorites->id])->delete();
            bg_run(function () use ($comics_id){
                MhModel::where('id',$comics_id)->decrement('favorites');
            });
            return [
                'is_favorite' => 0,
                'msg'    => '收藏取消'
            ];
        } else {
            MhFavoritesModel::insert([
                'uid'        => $uid,
                'mh_id'      => $mh->id,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            bg_run(function () use ($comics_id){
                MhModel::where('id',$comics_id)->increment('favorites');
            });
            return [
                'is_favorite' => 1,
                'msg'    => '收藏成功'
            ];
        }
    }

   public function getLikeList(MemberModel $member ,$page, $limit){
       return MhFavoritesModel::getUserData($member->uid, $page, $limit);
   }
}