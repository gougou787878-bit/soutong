<?php

/**
 * class MemberHotRankModel
 *
 * @property string $created_at
 * @property int $id
 * @property string $mv_ids 视频ID(多个逗号隔开)
 * @property int $sort
 * @property int $status
 * @property int $uid
 * @property string $updated_at
 *
 * @date 2024-10-18 20:50:33
 *
 * @mixin \Eloquent
 */
class MemberHotRankModel extends EloquentModel
{
    protected $table = "member_hot_rank";
    protected $primaryKey = 'id';
    protected $fillable = [
        'created_at',
        'mv_ids',
        'sort',
        'status',
        'uid',
        'updated_at'
    ];

    protected $guarded = 'id';
    public $timestamps = true;

    const STATUS_NO = 0;
    const STATUS_OK = 1;
    const STATUS_TIPS = [
        self::STATUS_NO => '下架',
        self::STATUS_OK => '上架',
    ];

    const CK_HOT_RANK = "ck:hot:rank:%d";
    const GP_HOT_RANK = "gp:hot:rank";
    const CN_HOT_RANK = "热榜-推荐用户列表";

    public static function getList($page){
        $cacheKey = sprintf(self::CK_HOT_RANK, $page);
        return cached($cacheKey)
            ->group(self::GP_HOT_RANK)
            ->chinese(self::CN_HOT_RANK)
            ->fetchPhp(function () use ($page){
                return self::query()
                    ->where('status', self::STATUS_OK)
                    ->orderByDesc('sort')
                    ->orderByDesc('id')
                    ->forPage($page, 5)
                    ->get(['id', 'uid', 'mv_ids'])
                    ->map(function ($item){
                        $member = MemberModel::where('uid', $item->uid)->select(['uid', 'aff', 'nickname', 'thumb','videos_count'])->first();
                        $mv_ids = explode(',', $item->mv_ids);
                        $mv_list = MvModel::queryBase()
                            ->whereIn('id', $mv_ids)
                            ->where('uid', $item->uid)
                            ->get();
                        $item->member = $member;
                        $item->mv_list = array_sort_by_idx($mv_list, $mv_ids, 'id');
                        return $item;
                    });
            });
    }
}
