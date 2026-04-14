<?php
/**
 *
 * @date 2020/2/27
 * @author
 * @copyright kuaishou by KS
 * @todo 传作者合集视频管理服务
 *
 */

namespace service;


use MemberMakerModel;
use MemberModel;

/**
 * Class CreatorService
 * @package service
 */
class CreatorService
{
    /**
     * 申请成为创作者
     * @param $member
     * @param $tag
     * @param $description
     * @param string $type
     * @param string $contact
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public static function applyCreator($member, $tag, $description, $type = '1', $contact = '')
    {

        $has = MemberMakerModel::where(['uuid' => $member['uuid']])->first();
        if ($has) {
          //return $has;
        }
        $applyData = [];
        $applyData['created_at'] = date('Y-m-d H:i:s');
        //$applyData['update_at'] = date('Y-m-d H:i:s');
        $applyData['uuid'] = $member->uuid;
        $applyData['level_num'] = 1;//default;
        $applyData['phone'] = $member->phone;
        $applyData['nickname'] = $member->nickname;
        //$applyData['type'] = $type;
        $applyData['status'] = MemberMakerModel::CREATOR_STAT_ING;
        //$applyData['creator_tag'] = strip_tags($tag);
        //$applyData['creator_desc'] = strip_tags($description);
        $applyData['contact'] = $contact ? strip_tags($contact) : $member->phone;
        $creatorObj = MemberMakerModel::updateOrCreate(['uuid' => $member->uuid], $applyData);
        return $creatorObj;
    }

    //获取推荐创者列表
    public static function getRecommendCreator($recommend_uid_arr)
    {
        return MemberModel::query()
            ->selectRaw('uid,aff,nickname,thumb,person_signnatrue,vip_level,expired_at,fans_count,followed_count,fabulous_count')
            ->whereIn('uid', $recommend_uid_arr)
            ->get()
            ->map(function (MemberModel $member) {
                $mv_list = \MvModel::getMvListByUid($member->uid);
                $mv_list = (new MvService())->v2format($mv_list);
                $member->mv_list = $mv_list;
                return $member;
            });
    }

}