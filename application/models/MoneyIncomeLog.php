<?php

use Illuminate\Database\Eloquent\Model;

/**
 * class MoneyIncomeLogModel
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
 * @mixin Eloquent
 */
class MoneyIncomeLogModel extends Model
{
    const TYPE_ADD = 1;
    const TYPE_SUB = 2;
    const TYPE
        = [
            self::TYPE_ADD => '增加',
            self::TYPE_SUB => '减少',
        ];
    const SOURCE_TOPPED = 1;
    const SOURCE_PROXY = 2;
    const SOURCE_SIGN_UP = 3;
    const SOURCE_MANAGEMENT_INSPECTION = 4;
    const SOURCE_BUY_MV = 5;
    const SOURCE_EXCHANGE = 6;     //充值
    const SOURCE_BUY_GIRL = 7;      //邀请
    const SOURCE_BUY_CHAT = 8;    //签到
    const SOURCE_SPECIAL_COIN_CARD = 9;  // 管理巡查
    const SOURCE_BUY_VIP_GIFT = 10;  // 购买视频
    const SOURCE_BUY_PACKAGE = 11;  // 扣币购买会员
    const SOURCE_UNLOCK_CHAT = 12;  // 解锁约炮
    const SOURCE_UNLOCK_GIRL = 13;  // 购买裸聊
    const SOURCE_BUY_BOOK = 14;  // 每天扣币卡领取
    const SOURCE_BUY_PIC = 15;  // vip赠送
    const SOURCE_BUY_STORY = 16;  // 购买优惠包
    const SOURCE_BUY_PUACOURSE = 17;  // 解锁裸聊
    const SOURCE_SUB_WITHDRAW = 18;  // 解锁楼凤
    const SOURCE_ADD_WITHDRAW = 19;  // 购买漫画
    const SOURCE_KEFU = 20;  // 购买美图
    const SOURCE_CHECK = 21;  // 购买小说
    const SOURCE_POST = 22;
    const SOURCE_EXP = 23;  // 提现扣除
    const SOURCE_INIVTE = 24;  // 提现退回
    const SHOW_NAME
        = [
            self::SOURCE_TOPPED => '充值',
            self::SOURCE_SIGN_UP => '签到',
            self::SOURCE_PROXY => '邀请',
            self::SOURCE_MANAGEMENT_INSPECTION => '管理巡查',
            self::SOURCE_BUY_MV => '购买视频',
            self::SOURCE_EXCHANGE => '扣币购买会员',
            self::SOURCE_BUY_GIRL => '解锁约炮',
            self::SOURCE_BUY_CHAT => '购买裸聊',
            self::SOURCE_SPECIAL_COIN_CARD => '每天扣币卡领取',
            self::SOURCE_BUY_VIP_GIFT => 'vip赠送',
            self::SOURCE_BUY_PACKAGE => '购买优惠包',
            self::SOURCE_UNLOCK_CHAT => '解锁裸聊',
            self::SOURCE_UNLOCK_GIRL => '解锁楼凤',
            self::SOURCE_BUY_BOOK => '购买漫画',
            self::SOURCE_BUY_PIC => '购买美图',
            self::SOURCE_BUY_STORY => '购买小说',
            self::SOURCE_BUY_PUACOURSE => '购买把妹课程',
            self::SOURCE_SUB_WITHDRAW => '提现扣除',
            self::SOURCE_ADD_WITHDRAW => '提现退回',
            self::SOURCE_KEFU => '客服处理',
            self::SOURCE_CHECK => '手动核算',
            self::SOURCE_POST => '解锁帖子',
            self::SOURCE_EXP => '兑换积分',
            self::SOURCE_INIVTE => '邀请获得金币',
        ];  // 后台上下分
    public $timestamps = false;  // 手动核算
    protected $table = 'money_income_log';  // 帖子打赏
    protected $primaryKey = 'id';  // 兑换积分
    protected $fillable = ['id', 'aff', 'source', 'type', 'coinCnt', 'desc', 'source_aff', 'created_at', 'data_name', 'data_id'];  // 邀请获得金币
    protected $guarded = 'id';

}