<?php

use Illuminate\Database\Eloquent\Model;

/**
 * class MoneyLogModel
 * 
 * 
 * @property int $id  
 * @property int $aff 用户aff 
 * @property int $source 日志来源 
 * @property int $type 1增 2减 
 * @property string $coinCnt 获币数量 
 * @property string $desc  
 * @property int $source_aff  
 * @property string $created_at  
 * @property string $data_name 数据名称 
 * @property int $data_id 数据id 
 * 
 * 
 *
 * @mixin \Eloquent
 */
class MoneyLogModel extends Model
{
    protected $table = 'money_log';
    protected $primaryKey = 'id';
    protected $fillable = ['id', 'aff', 'source', 'type', 'coinCnt', 'desc', 'source_aff', 'created_at', 'data_name', 'data_id'];
    protected $guarded = 'id';
    public $timestamps = false;


    const TYPE_ADD = 1;
    const TYPE_SUB = 2;
    const TYPE = [
        self::TYPE_ADD => '增加',
        self::TYPE_SUB => '减少',
    ];

    const SOURCE_TOPPED = 1;     //充值
    const SOURCE_PROXY = 2;      //邀请
    const SOURCE_SIGN_UP = 3;    //签到
    const SOURCE_MANAGEMENT_INSPECTION = 4;  // 管理巡查
    const SOURCE_BUY_MV = 5;  // 购买视频
    const SOURCE_EXCHANGE = 6;  // 扣币购买会员
    const SOURCE_BUY_GIRL = 7;  // 解锁约炮
    const SOURCE_BUY_CHAT = 8;  // 购买裸聊
    const SOURCE_SPECIAL_COIN_CARD = 9;  // 每天扣币卡领取
    const SOURCE_BUY_VIP_GIFT = 10;  // vip赠送
    const SOURCE_BUY_PACKAGE = 11;  // 购买优惠包
    const SOURCE_UNLOCK_CHAT = 12;  // 解锁裸聊
    const SOURCE_UNLOCK_GIRL = 13;  // 解锁楼凤
    const SOURCE_BUY_BOOK = 14;  // 购买漫画
    const SOURCE_BUY_PIC = 15;  // 购买美图
    const SOURCE_BUY_STORY = 16;  // 购买小说
    const SOURCE_BUY_PUACOURSE = 17;  // 购买pua课程
    const SOURCE_SALE_GIRLCHAT = 18;  // 用户购买裸聊获得分成
    const SOURCE_CHATORDER_FUND_ADD = 19;  // 退单增加用户金币
    const SOURCE_CHATORDER_FUND_SUB = 20;  // 退单减去商家收益
    const SOURCE_REWARD_POST = 21;  // 帖子打赏
    const SOURCE_TASK = 22;  // 任务获取

    const SHOW_NAME = [
        self::SOURCE_TOPPED  => '充值',
        self::SOURCE_SIGN_UP => '签到',
        self::SOURCE_PROXY   => '邀请',
        self::SOURCE_MANAGEMENT_INSPECTION   => '管理巡查',
        self::SOURCE_BUY_MV   => '购买视频',
        self::SOURCE_EXCHANGE   => '扣币购买会员',
        self::SOURCE_BUY_GIRL   => '解锁约炮',
        self::SOURCE_BUY_CHAT   => '购买裸聊',
        self::SOURCE_SPECIAL_COIN_CARD   => '每天扣币卡领取',
        self::SOURCE_BUY_VIP_GIFT   => 'vip赠送',
        self::SOURCE_BUY_PACKAGE   => '购买优惠包',
        self::SOURCE_UNLOCK_CHAT   => '解锁裸聊',
        self::SOURCE_UNLOCK_GIRL   => '解锁楼凤',
        self::SOURCE_BUY_BOOK => '购买漫画',
        self::SOURCE_BUY_PIC => '购买美图',
        self::SOURCE_BUY_STORY => '购买小说',
        self::SOURCE_BUY_PUACOURSE => '购买把妹课程',
        self::SOURCE_REWARD_POST => '帖子打赏',
        self::SOURCE_TASK => '任务获取',
    ];
}