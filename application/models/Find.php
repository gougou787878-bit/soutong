<?php

use Illuminate\Database\Eloquent\Model;

/**
 * class FindModel
 *
 * @property int $coins 发片赏金
 * @property int $created_at 
 * @property int $id 
 * @property string $img 求片图片 3张 可能多张 json 序列化
 * @property int $like 喜欢
 * @property string $nickname 昵称
 * @property int $reply 回复
 * @property int $status 0 默认待审核 1 通过
 * @property string $title 求片描述
 * @property int $total_coins 累计总赏金
 * @property int $is_match 是否分配
 * @property int $is_back  过期是否退回
 * @property int $vid  引用视频的编号  如果是的话
 * @property int $is_top  是否置顶
 * @property string $uuid 用户
 * @property int $is_finish 是否完成，结束后，不能操作采纳状态
 *
 * @property-read MemberModel $member
 * @property-read FindReplyModel $withReply
 *
 * @author xiongba
 * @date 2020-07-09 23:38:36
 *
 * @mixin \Eloquent
 */
class FindModel extends EloquentModel
{

    protected $table = "find";

    protected $primaryKey = 'id';

    protected $fillable = [
        'coins',
        'created_at',
        'img',
        'like',
        'reply',
        'status',
        'title',
        'total_coins',
        'uuid',
        'is_match',
        'is_back',
        'is_finish',
        'vid',
        'is_top'
    ];

    protected $guarded = 'id';

    public $timestamps = false;

    const REPLY_MAX_TTL = 96 * 3600;//单次赏金求片最大期限为96小时

    const MIN_FIND_COINS = 2;//订单vip会员 发片最少需金币

    const STAT_TO_CHECK = 0;//待审核
    const STAT_PASS = 1;//审核通过
    const STAT_REFUSE = 2;//拒绝
    const STAT = [
        self::STAT_TO_CHECK => '审核中',
        self::STAT_PASS     => '通过',
        self::STAT_REFUSE   => '拒绝',
    ];

    const MACTH_YES = 1;//匹配  求片赏金分配完成 结束
    const MACTH_DEFAULT = 0;//缺省  默认
    const MACTH_NO = 2;//不采纳
    const MATCH = [
        self::MACTH_DEFAULT => '默认',
        self::MACTH_YES     => '采纳',
        self::MACTH_NO      => '不采纳',
    ];

    const BACK_YES = 1;//过期已退回
    const BACK_DEFAULT = 0;//缺省  默认


    const IS_FINISH_NO = 0;//缺省  默认
    const IS_FINISH_YES = 1;//完成
    const IS_FINISH = [
        self::IS_FINISH_NO  => '默认',
        self::IS_FINISH_YES => '完成',
    ];

    const REDIS_FIND_LIST = 'redis:find:list:%d:%d:%s:%s:%s';
    const REDIS_FIND_LIST_GROUP = 'redis:find:list:group';
    const REDIS_FIND_DETAIL = 'redis:find:detail:%d';
    const REDIS_FIND_DETAIL_GROUP = 'redis:find:detail:group';

    const USER_FIND_LIST = 'user:find:list:%d:%d:%d';
    const USER_FIND_LIST_GROUP = 'user:find:list:group';

    protected $appends = ['created_str', 'img_list','status_str','is_match_str'];

    public function getCreatedStrAttribute()
    {
        return date('Y-m-d H:i:s', $this->attributes['created_at'] ?? 0);
    }

    public function getImgListAttribute()
    {
        $ary = json_decode($this->attributes['img'] ?? '[]', true);
        if (json_last_error() != 0) {
            return [];
        }
        if (!is_array($ary)){
            return [];
        }
        foreach ($ary as &$item){
            if (strpos($item ,'://') !== false){
                $item = parse_url($item , PHP_URL_PATH);
            }
            $item = url_cover($item);
        }
        unset($item);
        return $ary;
    }

    public function getStatusStrAttribute()
    {
        return self::STAT[$this->attributes['status'] ?? self::STAT_TO_CHECK];
    }

    public function getIsMatchStrAttribute()
    {
        return self::MATCH[$this->attributes['is_match'] ?? self::MACTH_DEFAULT];
    }

    public function mv(){
        if($this->vid){
            return self::hasOne(MvModel::class,'id','vid');
        }
        return null;
    }


    public function withReply(){
        return $this->hasMany(FindReplyModel::class , 'find_id' , 'id');
    }

    /**
     * 求片用户关联
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function member()
    {
        return self::hasOne(MemberModel::class, 'uuid', 'uuid');
    }

    /**
     * 能够获得多少赏金  通用
     *
     * 1、用户推荐的视频，收费视频所有人都需付费（包含求片人）
     * 2、赏金8:2分，用户8，平台2（不足1元按1元算）
     */
    public function canGetCoins()
    {
        $total_coin = $this->total_coins;
        //搜同直接返回
        return $total_coin;
//
//        if ($total_coin <= 1) {
//            return $total_coin;
//        }
//        return ceil($total_coin * 0.8);
    }

    /**
     * @return array
     */
    public function getImagesAttribute():array
    {
        return $this->getImgListAttribute();
    }

    public function mvInfo(){
        $vid = $this->vid;
        if($vid == 0){
            return null;
        }
        /** @var MvModel $row */
        //$row = MvModel::where('id',$this->vid)->with('member:uuid,nickname,thumb,followed_count,auth_status')->first();
        $row = cached('find:mv:detail:'.$vid)
            ->fetchPhp(function () use ($vid){
                return MvModel::where('id',$vid)->first();
        });
        if(is_null($row)){
            return null;
        }
        $getMember = request()->getMember();
        if (!$row->member) {
            return null;
        }
        //$row->member && $row->member->thumb = url_avatar($row->member->thumb);
        $d = (new \service\MvService())->formatItem($row,$getMember);
        return [
            'mv'        => $d,
            'mv_member' => $d->user
        ];
        return [
            'mv'=>[
                'id'=>$row->id,
                'mv_type'=>$row->mv_type,
                'member_uuid'=>$row->member_uuid,
                'title'=>$row->title,
                'source_240'=>url_video($row->source_240),
                'thumb_cover'=>url_cover($row->thumb_cover),
                'count_play'=>$row->count_play,
            ],
            'mv_member'=>$row->member
        ];
    }



    /**
     * @param $uuid
     * @param $title
     * @param $image
     * @param int $status
     * @param int $gold
     * @param int $vid
     * @return CompilationModel|Model
     */
    static function addData($uuid, $title, $image, $gold = 0, $vid = 0, $status = 0)
    {
        return self::create([
            'uuid'        => $uuid,
            'title'       => $title,
            'img'         => json_encode($image),
            'status'      => self::STAT_TO_CHECK,
            'date'        => date('Y-m-d', TIMESTAMP),
            'coins'       => $gold,
            'total_coins' => $gold,
            'created_at'  => TIMESTAMP,
            'like'        => 0,
            'reply'       => 0,
            'is_match'    => self::MACTH_DEFAULT,
            'vid'         => $vid,
        ]);
    }

    static function getRow($find_id)
    {
        return cached(sprintf(self::REDIS_FIND_DETAIL,$find_id))
            ->group(self::REDIS_FIND_DETAIL_GROUP)
            ->chinese('求片详情')
            ->fetchPhp(function () use ($find_id){
                return FindModel::where('id', '=', $find_id)
                    ->with('member:uuid,uid,nickname,thumb,followed_count,auth_status')
                    ->first();
        },300);
    }

    /**
     * 他人查看 求片基础查询
     * @return \Illuminate\Database\Eloquent\Builder
     */
    static function queryBase(){
        return self::where('status',self::STAT_PASS);
    }
    /**
     * 个人中心查看 求片基础查询
     * @return \Illuminate\Database\Query\Builder
     */
    static function queryAll(){
        return self::whereIn('status',[self::STAT_TO_CHECK,self::STAT_PASS]);
    }
    static function clearCache($find_id)
    {
        //清理详情
        cached(sprintf(self::REDIS_FIND_DETAIL,$find_id))->clearCached();
        //清理列表
        cached('')->clearGroup(FindModel::REDIS_FIND_LIST_GROUP);
    }

}
