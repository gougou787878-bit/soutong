<?php


use Illuminate\Database\Eloquent\Model;

/**
 *
 *
 *小兰  制片人 中心
 *
 * class MemberMakerModel
 *
 * @property int $id
 * @property string $uuid
 * @property int $level_num 创作者等级
 * @property string $phone 手机
 * @property int $status 0 缺省 1待审核  2 未通过  3. 禁用 4 正常
 * @property string $refuse_reason 拒绝理由
 * @property string $created_at
 * @property string $nickname 用户昵称，和用户表保持一致
 * @property string $contact 扩展联系方式
 * @property int $total_coins 累计汤币
 * @property string $pay_rate 提现结算比例
 * @property int $topic_count 合集数量
 *
 * @author xiongba
 * @date 2021-01-09 16:25:49
 *
 * @mixin \Eloquent
 */
class MemberMakerModel extends Model
{

    protected $table = "member_maker";

    protected $primaryKey = 'id';

    protected $fillable = [
        'uuid',
        'level_num',
        'phone',
        'status',
        'refuse_reason',
        'created_at',
        'nickname',
        'contact',
        'total_coins',
        'pay_rate',
        'topic_count'
    ];

    protected $guarded = 'id';

    public $timestamps = false;


    const CREATOR_STAT_DF = 0;
    const CREATOR_STAT_ING = 1;
    const CREATOR_STAT_NO = 2;
    const CREATOR_STAT_BAN = 3;
    const CREATOR_STAT_YES = 4;
    const CREATOR_STATUS_TEXT = [
        self::CREATOR_STAT_DF  => '缺省',
        self::CREATOR_STAT_ING => '审核中',
        self::CREATOR_STAT_NO  => '未通过',
        self::CREATOR_STAT_BAN => '禁用',
        self::CREATOR_STAT_YES => '正常',
    ];
    //1 个人类型 2 团队类型 机构
    const TYPE_PERSONAL = 1;
    const TYPE_TEAM = 2;
    const TYPE = [
        self::TYPE_PERSONAL => '个人',
        self::TYPE_TEAM     => '团队',
    ];

    public function member()
    {
        return $this->hasOne(MemberModel::class, 'uuid', 'uuid');
    }

    /**
     * @param $uuid
     * @return \Illuminate\Database\Eloquent\Builder|Model|object|null
     */
    static function getMakeRowInfo($uuid)
    {
        return self::where(['uuid' => $uuid])->first();
    }

    /**
     * @param $uuid
     * @return \Illuminate\Database\Eloquent\Builder|Model|object|null
     */
    static function getMakeInfo($uuid)
    {
        return self::where(['uuid' => $uuid, 'status' => self::CREATOR_STAT_YES])->first();
    }

    /**
     * 包含 支付通道费
     * @param $uuid
     * @return mixed|string
     */

    static function getMakerRate($uuid)
    {

        /** @var MemberMakerModel $row */
        $row = self::getMakeInfo($uuid);
        if (is_null($row)) {
            return (string)(UserWithdrawModel::USER_WITHDRAW_MONEY_RATE_MV - UserWithdrawModel::USER_WITHDRAW_MONEY_RATE_MV_CHANNEL);
        }
        if($row->pay_rate>=UserWithdrawModel::USER_WITHDRAW_MONEY_RATE_MV_CHANNEL){
            //$msg = $row->pay_rate;
            $p = round(($row->pay_rate*100-UserWithdrawModel::USER_WITHDRAW_MONEY_RATE_MV_CHANNEL*100)/100,2);
            //errLog("rate: {$msg} p:{$p}");
            return (string)$p;
        }
        return 0.00;
    }

    static function getMakerLevel($uuid)
    {

        /** @var MemberMakerModel $row */
        $row = self::getMakeInfo($uuid);
        if (is_null($row)) {
            return 0;
        }
        return $row->level_num;
    }

    static function getMakerRule()
    {
        return [
            [
                'level'      => 0,
                'name'       => '普通用户',
                'vip'        => '无',
                'vip_level'  => 0,
                'mv_coins'   => '0',
                'rate'       => '25%',
                'rate_value' => '0.25',
            ],
            [
                'level'      => 1,
                'name'       => '制片人LV1',
                'vip'        => '季卡',
                'vip_level'  => MemberModel::VIP_LEVEL_JIKA,
                'mv_coins'   => '0',
                'rate'       => '30%',
                'rate_value' => '0.30',
            ],
            [
                'level'      => 2,
                'name'       => '制片人LV2',
                'vip'        => '季卡',
                'vip_level'  => MemberModel::VIP_LEVEL_JIKA,
                'mv_coins'   => '200',
                'rate'       => '35%',
                'rate_value' => '0.35',
            ],
            [
                'level'      => 3,
                'name'       => '制片人LV3',
                'vip'        => '年卡',
                'vip_level'  => MemberModel::VIP_LEVEL_YEAR,
                'mv_coins'   => '1000',
                'rate'       => '40%',
                'rate_value' => '0.40',
            ],
            [
                'level'      => 4,
                'name'       => '制片人LV4',
                'vip'        => '年卡',
                'vip_level'  => MemberModel::VIP_LEVEL_YEAR,
                'mv_coins'   => '5000',
                'rate'       => '45%',
                'rate_value' => '0.45',
            ],
            [
                'level'      => 5,
                'name'       => '制片人LV5',
                'vip'        => '永久',
                'vip_level'  => MemberModel::VIP_LEVEL_LONG,
                'mv_coins'   => '20000',
                'rate'       => '50%',
                'rate_value' => '0.50',
            ],
        ];
    }
}
