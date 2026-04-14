<?php

use tools\RedisService;

/**
 * class MemberAttentionModel
 *
 * @property int $uid 用户ID
 * @property int $touid 关注人ID
 * @property int $created_at
 *
 * @property-read MemberModel $followed
 *
 * @author xiongba
 * @date 2020-02-26 20:09:08
 *
 * @mixin \Eloquent
 */
class UserAttentionModel extends EloquentModel
{
    const REDIS_USER_FANS_LIST = 'user_fans_list:'; // 用户粉丝记录
    const REDIS_USER_FOLLOWED_LIST = 'user_followed_list:'; // 用户关注记录

    const REDIS_USER_FANS_ITEM = 'user_fans_item:%d:%d:%d'; // 用户粉丝列表
    const REDIS_USER_FANS_ITEM_GROUP = 'user_fans_item:%d';// 用户粉丝列表分组

    const REDIS_USER_FOLLOWED_ITEM = 'user_followed_item:'; // 用户关注列表
    const REDIS_USER_FOLLOWED_ALL = 'user_followed_all:%s:%s:%s'; // 用户关注列表

    protected $table = 'member_attention';

    protected $fillable = ['created_at', 'touid', 'uid'];

    protected $guarded = [];

    public function followed()
    {
        return $this->hasOne(MemberModel::class, 'aff', 'touid');
    }

    public function fans()
    {
        return $this->hasOne(MemberModel::class, 'aff', 'uid');
    }

    public function getattention($data, $fetchColumn = false)
    {
        if ($fetchColumn) {
            $list = $this->where($data)->pluck('touid');
        } else {
            $list = $this->where($data)->select("touid")->get();
        }
        if (!$list) {
            return '';
        }
        return $list->toArray();
    }


    /**
     * @throws RedisException
     */
    public static function listFollowUserAffs($aff): array
    {
        return redis()->sMembers(self::generateId($aff));
    }

    public static function generateId($aff): string
    {
        return 'user:follow:'.$aff;
    }


    public function videos()
    {
        return $this->hasOne(MvModel::class, 'id', 'mv_id')->with('user:uid,nickname,thumb,vip_level,sexType,expired_at,uuid,uid');
    }

    /**
     * @param MemberModel $member
     * @return \Illuminate\Support\Collection
     * @author xiongba
     * @date 2020-02-27 10:24:34
     */
    public static function getList(MemberModel $member)
    {
        $res = cached('user:followed')
            ->hash($member->uid)
            ->expired(1800)
            ->serializerPHP()
            ->setSaveEmpty(true)
            ->fetch(function () use ($member) {
                return \UserAttentionModel::where('uid', $member->uid)
                    ->get(['uid', 'touid', 'created_at']);
            });
        return $res;
    }

    public static function clearFollowUid($uid)
    {
        return cached('user:followed')
            ->hash($uid)
            ->clearCached();
    }

    public static function getIdsById($aff, $to_aff)
    {
        return self::where('uid', $aff)
            ->where('touid', $to_aff)
            ->first();
    }

    public static function getFollowMvs($uid, $type, $page, $limit){
        $ids =  self::query()
            ->join('mv', 'mv.uid', '=', 'member_attention.touid')
            ->where('member_attention.uid', $uid)
            ->where('mv.is_hide', '=', MvModel::IS_HIDE_NO)
            ->where('mv.is_aw', MvModel::AW_NO)
            ->where('mv.type', '=', $type)
            ->orderByDesc('mv.id')
            ->forPage($page, $limit)
            ->pluck('mv.id')
            ->toArray();
        return MvModel::queryBase()
            ->whereIn('id', $ids)
            ->get();
    }

    //获取关注用户数量(短视频数量大于0)
    public static function getCountByMemberShortMv($uid){
        return  self::query()
            ->join('members', 'members.uid', '=', 'member_attention.touid')
            ->where('member_attention.uid', $uid)
            ->where('members.short_videos_count', '>', 0)
            ->count('member_attention.touid');
    }
}