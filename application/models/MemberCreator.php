<?php


use Illuminate\Database\Eloquent\Model;

/**
 *
 * 上传视频用户 统计   啥也不是
 *
 * class MemberCreatorModel
 *
 * @property int $id
 * @property int $type 1 个人类型 2 团队类型
 * @property int $uid
 * @property int $level_num 创作者等级
 * @property string $phone 手机
 * @property int $status 0 缺省 1待审核  2 未通过  3. 禁用 4 正常
 * @property string $refuse_reason 拒绝理由
 * @property int $mv_check 视频审核次数
 * @property int $mv_submit 视频提交数量
 * @property int $mv_refuse 视频拒绝次数
 * @property string $creator_tag
 * @property string $creator_desc
 * @property int $update_at
 * @property int $created_at
 * @property string $nickname 用户昵称，和用户表保持一致
 * @property int $mv_pass
 * @property double $refuse_rate
 * @property int $total_coins 赚的累计金币
 * @property string $output_rate 扣量指标，出量率
 * @property string $pay_rate 提现结算比例
 * @property int $rank 创作者等级
 *
 * @author xiongba
 * @date 2020-12-29 16:41:04
 *
 * @mixin \Eloquent
 */
class MemberCreatorModel extends EloquentModel
{
    //1 个人类型 2 团队类型 机构
    const TYPE_PERSONAL = 1;
    const TYPE_TEAM = 2;
    const TYPE = [
        self::TYPE_PERSONAL => '个人',
        self::TYPE_TEAM     => '团队',
    ];

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

    protected $table = "member_creator";

    protected $primaryKey = 'id';

    protected $fillable = ['type', 'uid', 'level_num', 'phone', 'status', 'refuse_reason', 'mv_check', 'mv_submit', 'mv_refuse', 'creator_tag', 'creator_desc', 'update_at', 'created_at', 'nickname', 'total_coins', 'output_rate', 'pay_rate', 'rank'];

    protected $guarded = 'id';

    public $timestamps = false;

    public static function init(MemberModel $member)
    {
        return self::create([
            'type'          => self::TYPE_PERSONAL,
            'uid'           => $member->uid,
            'level_num'     => 0,
            'phone'         => $member->phone?$member->phone:'',
            'status'        => self::CREATOR_STAT_DF,
            'refuse_reason' => '',
            'mv_check'      => 0,
            'mv_submit'     => 1,
            'mv_refuse'     => 0,
            'creator_tag'   => '',
            'creator_desc'  => '',
            'update_at'     => time(),
            'created_at'    => time(),
            'nickname'      => $member->nickname,
            'total_coins'   => $member->votes_total,
            'output_rate'   => 1,
            'pay_rate'      => 0.4,
            'rank'          => 0
        ]);
    }

    public function member()
    {
        return $this->hasOne(MemberModel::class, 'uid', 'uid');
    }

    protected $appends = ['mv_pass', 'refuse_rate'];

    public function getMvPassAttribute()
    {
        $mv_check = $this->attributes['mv_check'] ?? 0;
        $mv_refuse = $this->attributes['mv_refuse'] ?? 0;
        return $mv_check - $mv_refuse;
    }

    public function getRefuseRateAttribute()
    {
        $mv_check = $this->attributes['mv_check'] ?? 0;
        $mv_refuse = $this->attributes['mv_refuse'] ?? 0;
        if ($mv_refuse === 0 || $mv_check === 0) {
            return 1;
        }
        return $mv_refuse / $mv_check;
    }


}
