<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class FindReplyModel
 *
 * @property int $comment 评论条数
 * @property int $created_at
 * @property int $find_id 求片编号
 * @property int $id
 * @property int $is_accept 被采纳
 * @property int $praize 点赞条数
 * @property int $status
 * @property int $coins
 * @property string $uuid 推荐人
 *
 * @property MemberModel $member
 *
 * @author xiongba
 * @date 2020-07-10 16:05:18
 *
 * @mixin \Eloquent
 */
class FindReplyModel extends Model
{
    use \traits\EventLog;
    protected $table = "find_reply";

    protected $primaryKey = 'id';

    protected $fillable = ['comment', 'created_at', 'find_id', 'is_accept', 'praize', 'status', 'uuid', 'coins'];

    protected $guarded = 'id';

    public $timestamps = false;

    const STATUS_INIT = 0;
    const STATUS_REJECT = 1;
    const STATUS_PASS = 2;

    const STATUS = [
        self::STATUS_INIT   => '待审核',
        self::STATUS_REJECT => '拒绝',
        self::STATUS_PASS   => '审核通过',
    ];

    const IS_ACCEPT_NO = 0;
    const IS_ACCEPT_YES = 1;

    const IS_ACCEPT = [
        self::IS_ACCEPT_NO  => '未采纳',
        self::IS_ACCEPT_YES => '被采纳',
    ];

    const FIND_REPLY_COUNT = "find:reply:count:%d";
    const FIND_REPLY_COUNT_GROUP = "find:reply:count:group";
    const FIND_REPLY_LIST = "find:reply:list:%d:%d:%d";
    const FIND_REPLY_LIST_GROUP = "find:reply:list:group";

    const FIND_REPLY_SELF_LIST = "find:reply:self:list:%d:%d:%d";
    const FIND_REPLY_SELF_LIST_GROUP = "find:reply:self:list:group";

    protected $appends = ['created_str', 'like_num_str', 'reply_num_str', 'is_like', 'status_str', 'is_accept_str'];

    public function myfind(){
        return self::hasOne(FindModel::class,'id','find_id');
    }

    public function getStatusStrAttribute()
    {
        return self::STATUS[$this->attributes['status'] ?? self::STATUS_INIT];
    }

    public function getIsAcceptStrAttribute()
    {
        return self::IS_ACCEPT[$this->attributes['is_accept'] ?? self::IS_ACCEPT_NO];
    }

    public function member()
    {
        return $this->hasOne(MemberModel::class, 'uuid', 'uuid');
    }

    public function getIsLikeAttribute()
    {
        //是否有点赞
        $id = $this->attributes['id'] ?? null;
        if (isset($id)) {
            return FindReplyLikesModel::where([
                'reply_id' => $id,
                'uuid'     => request()->getMember()->uuid
            ])->count() > 0 ? 1 : 0;
        }
        return 0;
    }


    public function getCreatedStrAttribute()
    {
        return date('Y-m-d H:i:s', $this->attributes['created_at']);
    }

    public function getLikeNumStrAttribute()
    {
        return (string)($this->attributes['praize'] ?? 0);
    }

    public function getReplyNumStrAttribute()
    {
        return (string)($this->attributes['comment'] ?? 0);
    }

    /**
     * @param null $select
     * @return MvModel[]|\Illuminate\Support\Collection
     * @author xiongba
     * @date 2020-07-22 16:45:45
     */
    public function getMvAry($select = null)
    {
        $vid = FindReplyMvModel::where('reply_id', $this->id)->pluck('mv_id')->toArray();
        $query = MvModel::queryBase()->whereIn('id', $vid);
        if (!empty($select)) {
            $query->select($select);
        }
        return $query->get();
    }

    public static function getCountByFindId($find_id){
        $key = sprintf(self::FIND_REPLY_COUNT,$find_id);
        return cached($key)
            ->group(self::FIND_REPLY_COUNT_GROUP)
            ->chinese('求片回复列表计数')
            ->fetchJson(function () use($find_id) {
                return self::where('find_id',$find_id)->count();
            },600);
    }

    public static function getListByFindId($find_id,$page,$limit){
        $key = sprintf(self::FIND_REPLY_LIST,$find_id,$page,$limit);
        return cached($key)
            ->group(self::FIND_REPLY_LIST_GROUP)
            ->chinese('求片回复列表')
            ->fetchPhp(function () use($find_id,$page,$limit) {
                return self::with('member:uuid,nickname,username,uid,auth_status,followed_count,fans_count,videos_count,thumb')
                    ->where('find_id',$find_id)
                    ->orderByDesc('is_accept')
                    ->orderByDesc('id')
                    ->forPage($page,$limit)
                    ->get()
                    ->map(function (FindReplyModel $item){
                        if (empty($item->member)){
                            return null;
                        }
                        $item->makeHidden(['praize', 'comment', 'created_at']);
                        return $item;
                    })->filter()->values();
            },600);
    }

}
