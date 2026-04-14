<?php


use Illuminate\Database\Eloquent\Model;

/**
 *
 *
 * 用户认证
 *
 * class MemberAuthModel
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
class MemberAuthModel extends Model
{

    protected $table = "member_auth";

    protected $primaryKey = 'id';

    protected $fillable = [
        'uuid',
        'type',
        'status',
        'refuse_reason',
        'nickname',
        'number_code',
        'video',
        'updated_at',
        'created_at',
    ];

    protected $guarded = 'id';

    const AUTH_STAT_DF = 0;
    const AUTH_STAT_ING = 1;
    const AUTH_STAT_NO = 2;
    const AUTH_STAT_BAN = 3;
    const AUTH_STAT_YES = 4;
    const AUTH_STATUS_OPT = [
        self::AUTH_STAT_DF  => '缺省',
        self::AUTH_STAT_ING => '审核中',
        self::AUTH_STAT_NO  => '未通过',
        self::AUTH_STAT_BAN => '禁用',
        self::AUTH_STAT_YES => '正常',
    ];

    const AUTH_type_OPT = [
        0=>'原创认证',
        1=>'约炮认证'
    ];
    public function member()
    {
        return $this->hasOne(MemberModel::class, 'uuid', 'uuid');
    }

}
