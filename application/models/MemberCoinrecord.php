<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class MemberCoinrecordModel
 *
 * @property int $id
 * @property string $type 收支类型
 * @property string $action 收支行为
 * @property int $uid 用户ID
 * @property int $touid 对方ID
 * @property float $totalcoin 总价
 * @property float $reachcoin 分层后到账
 * @property int $coin_type  0 砖石  1  余额
 * @property int $addtime 添加时间
 * @property int $mark 标识 扣量展示 默认0
 * @property int $relation_id 关联标识
 * @property string $desc 描述
 *
 *
 * @date 2022-03-24 12:19:51
 *
 * @mixin \Eloquent
 */
class MemberCoinrecordModel extends Model
{

    protected $table = "member_coinrecord";

    protected $primaryKey = 'id';

    protected $fillable = [
        'type',
        'action',
        'uid',
        'touid',
        'totalcoin',
        'reachcoin',
        'coin_type',
        'addtime',
        'mark',
        'relation_id',
        'desc'
    ];

    protected $guarded = 'id';

    public $timestamps = false;
    protected $appends = ['add_time_str','log_sn','action_str'];

    const TYPE_INCOME = 'income';
    const TYPE_EXPEND = 'expend';
    const TYPE = [
        self::TYPE_INCOME => '收入',
        self::TYPE_EXPEND => '支出',
    ];

    const COIN_TYPE_GOLD = 0;//钻石
    const COIN_TYPE_BLANCE = 1;//余额

    const COIN_TYPE = [
        self::COIN_TYPE_GOLD   => '钻石',
        self::COIN_TYPE_BLANCE => '余额',
    ];

    //ACTION_IN_XXX  收入类行为 必须这样定义
    const ACTION_IN_MV = 'mv_income';
    const ACTION_IN_ORDER = 'order';
    const ACTION_IN_ORDER_GIFT = 'active';
    const ACTION_IN_PROXY_ZHI = 'proxy_zhi';
    const ACTION_IN_PROXY_KUA = 'proxy_kua';
    //ACTION_EX_XXX  支出类行为 必须这样定义
    const ACTION_EX_MV = 'mv';
    const ACTION_EX_SPECIAL = 'special';
    const ACTION_EX_PIC = 'pic';
    const ACTION_EX_STORY = 'story';
    const ACTION_EX_COMICS = 'mh';
    const ACTION_EX_CLUB = 'club';

    const ACTION_IN_MEET_PROFIT = 'meetProfit';
    const ACTION_IN_CHAT_PROFIT = 'chatProfit';
    const ACTION_IN_MEET = 'refundMeet';
    const ACTION_EX_MEET = 'buyMeet';
    const ACTION_EX_MEET_DEPOSIT = 'buyMeetDeposit';
    const ACTION_EX_VERIFY = 'verify';
    const ACTION_IN_VERIFY = 'refundverify';
    const ACTION_IN_CHAT = 'refundChat';
    const ACTION_EX_CHAT = 'buyChat';
    const ACTION_EX_POST = 'unlockPost';
    const ACTION_EX_AI_TY = 'aiTy';
    const ACTION_EX_AI_HL = 'aiTl';
    const ACTION_EX_AI_HT = 'aiTt';
    const ACTION_EX_AI_DRAW = 'aiDraw';
    const ACTION_EX_AI_MAGIC = 'aiMagic';
    const ACTION_EX_AI_NOVEL = 'aiNovel';
    const ACTION_EX_REWARD = 'reward';
    const ACTION_IN_RECEIVE = 'receive';
    const ACTION_EX_SUBSCRIBE = 'subscribe';
    const ACTION_EX_SEED = 'buySeed';
    const ACTION_EX_PORN_GAME = 'buyPorn';
    const ACTION_EX_CONTENT = 'buyContent';
    const ACTION_VIP_UPGRADE = 'vipUpgrade';
    const ACTION_EX_AUDIO = 'buyAudio';
    const ACTION_EX_LOTTERY = 'lottery';
    const ACTION_EX_LIVE = 'buyLive';
    const ACTION_EX_LIVE_REWARD = 'rewardLive';
    const ACTION_EX_THEATER = 'buyTheater';
    const ACTION_EX_THEATER_C = 'buyTheaterC';
    const ACTION_EX_SKIT = 'buySkit';
    const ACTION_EX_SKIT_C = 'buySkitC';
    const ACTION_AI_CREATER = 'ai_creater';
    const ACTION_AI_CHAT = 'ai_chat';
    const ACTION_EX_IM_BUY = 'im_buy';
    const ACTION_EX_GROUP_CHAT = 'buygroupchat';
    const ACTION_EX_GROUP_CHAT_MSG = 'buygroupchat_msg';
    const ACTION = [
        self::ACTION_IN_MV          => '视频收益',
        self::ACTION_IN_ORDER       => '订单充值',
        self::ACTION_IN_ORDER_GIFT  => '活动赠送',
        self::ACTION_IN_PROXY_ZHI   => '直推收益',
        self::ACTION_IN_PROXY_KUA   => '跨级收益',
        self::ACTION_IN_MEET_PROFIT => '约啪收益',
        self::ACTION_IN_CHAT_PROFIT => '聊天收益',
        self::ACTION_IN_VERIFY      => '押金退回',
        self::ACTION_EX_MV          => '视频购买',
        self::ACTION_EX_MEET        => '约啪扣除',
        self::ACTION_EX_MEET_DEPOSIT        => '约啪押金',
        self::ACTION_EX_VERIFY      => '认证押金',
        self::ACTION_IN_MEET        => '约啪退款',
        self::ACTION_EX_CHAT        => '聊天扣除',
        self::ACTION_IN_CHAT        => '聊天退款',
        self::ACTION_EX_POST        => '解锁帖子',
        self::ACTION_EX_AI_TY       => 'AI脱衣',
        self::ACTION_EX_AI_HL       => 'AI换脸',
        self::ACTION_EX_AI_HT       => 'AI换头',
        self::ACTION_EX_AI_DRAW     => 'AI绘画',
        self::ACTION_EX_AI_MAGIC    => 'AI图生视频',
        self::ACTION_EX_AI_NOVEL    => 'AI小说创作',
       // self::ACTION_EX_REWARD      => '打赏',
       // self::ACTION_IN_RECEIVE     => '收到打赏',
        self::ACTION_EX_PIC         => '图集解锁',
        self::ACTION_EX_STORY       => '小说解锁',
       // self::ACTION_EX_COMICS      => '漫画解锁',
       // self::ACTION_EX_SPECIAL     => '特价包',
        self::ACTION_EX_SUBSCRIBE     => '订阅用户',
        self::ACTION_EX_SEED        => '解锁种子',
        self::ACTION_EX_PORN_GAME   => '解锁黄游',
        self::ACTION_EX_CONTENT     => '解锁文章',
        self::ACTION_VIP_UPGRADE    => 'VIP升级',
        self::ACTION_EX_AUDIO       => '解锁有声',
        self::ACTION_EX_LOTTERY     => '抽奖',
        self::ACTION_EX_LIVE        => '解锁直播',
        self::ACTION_EX_LIVE_REWARD => '直播打赏',
        self::ACTION_EX_THEATER     => '解锁剧集',
        self::ACTION_EX_THEATER_C   => '解锁剧集单集',
        self::ACTION_EX_SKIT        => '解锁短剧',
        self::ACTION_EX_SKIT_C      => '解锁短剧单集',
        self::ACTION_AI_CREATER     => 'AI女友创建',
        self::ACTION_AI_CHAT        => 'AI女友聊天',
        self::ACTION_EX_IM_BUY      => '购买私信',
        self::ACTION_EX_GROUP_CHAT      => '购买群组',
        self::ACTION_EX_GROUP_CHAT_MSG      => '购买群组聊天',
    ];
    const MEMBER_POST_INCOME_GROUP  = 'member:post:income:group';
    const MEMBER_POST_INCOME_UID  = 'member:post:income:uid:%s';
    public function getActionStrAttribute(): string
    {
        return self::ACTION[$this->attributes['action']] ?? '明细-';
    }
    public function getAddTimeStrAttribute($key)
    {
        return date('Y-m-d H:i', $this->attributes['addtime'] ?? 0);
    }
    public function getLogSnAttribute(){
        if($this->type == self::TYPE_INCOME){
            return "S7IN-".str_pad($this->id,10,'0',STR_PAD_LEFT);
        }
        return "S7ZF-".str_pad($this->id,10,'0',STR_PAD_LEFT);
    }

    public function withMember()
    {
        return $this->hasOne(MemberModel::class, 'uid', 'uid');
    }

    /**
     * @param array $data
     * @param $type
     * @param $action
     * @param $uid
     * @param string $desc
     * @param int $totalCoin
     * @param int $coin_type
     * @param int $relation_id
     * @param int $toUid
     * @param int $mark
     * @return self|Model
     */
    static function addCoinLog($data = []) {

        return self::create([
            "type"        => $data['type'],
            "action"      => $data['action'],
            "uid"         => $data['uid'],
            "touid"       => $data['touid']??0,//非必填
            "totalcoin"   => $data['totalcoin'],
            "reachcoin"   => $data['reachcoin'],
            "coin_type"   => $data['coin_type']??0,//非必填 默认钻石
            "addtime"     => time(),
            "mark"        => $data['mark']??0,//非必填
            "relation_id" =>  $data['relation_id']??0,//非必填
            'desc'        => $data['desc']??$data['action'],//非必填
        ]);
    }

    /**
     * //$action, $uid, $desc = '', $totalCoin = 0,$coin_type=0, $relation_id = 0, $toUid = 0, $mark = 0
     * @param $action
     * @param $uid
     * @param string $desc
     * @param int $totalCoin
     * @param int $coin_type
     * @param int $relation_id
     * @param int $toUid
     * @param int $mark
     * @param $data
     * @return MemberCoinrecordModel
     */
    public static function addIncome($data)
    {
        $data['type'] = self::TYPE_INCOME;
        return self::addCoinLog($data);
    }

    /**
     * @param $data
     * @return MemberCoinrecordModel
     */
    public static function addExpend($data)
    {

        $data['type'] = self::TYPE_EXPEND;

        return self::addCoinLog($data);
    }


    /**
     * @param $uid
     * @param $type  收入|支出类型
     * @param $page
     * @param $limit
     */
    public static function getMyCoinLog($uid, $type, $page, $limit){
        return self::query()
            ->when($type == 'all', function ($q)  use ($uid){
                return $q->whereRaw("uid = {$uid} or touid = {$uid}");
            })
            ->when($type == self::TYPE_EXPEND, function ($q) use ($uid){
                return $q->where('uid', $uid)
                    ->where('type', self::TYPE_EXPEND);
            })
            ->when($type == self::TYPE_INCOME, function ($q) use ($uid){
                return $q->where('touid', $uid);
            })
            ->orderByDesc('id')
            ->forPage($page, $limit)
            ->get()
            ->map(function ($item) use ($uid , $type){
                $item->addHidden(['id','uid','touid','addtime','mark','relation_id']);
                /** @var MemberCoinrecordModel $item */
                if ($item->uid == $uid && $item->type != self::TYPE_INCOME){
                   $item->type = self::TYPE_EXPEND;
                }elseif ($item->touid == $uid){
                   $item->type = self::TYPE_INCOME;
                }
                return $item;
            });
    }

    public static function getTotalPostIncome($uid){
        $key = sprintf(self::MEMBER_POST_INCOME_UID,$uid);
        return cached($key)->group(self::MEMBER_POST_INCOME_GROUP)->fetchPhp(function () use ($uid){
            return self::where('touid',$uid)->where('action',self::ACTION_EX_POST)->sum('reachcoin');
        },1800);
    }


}
