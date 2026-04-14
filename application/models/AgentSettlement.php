<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class AgentSettlementModel
 *
 * @property int $id 
 * @property int $agent_id 代理ID
 * @property string $channel 渠道
 * @property int $type 同订单类型
 * @property string $start 结算开始时间
 * @property string $end 结算截止时间
 * @property int $rate 比例
 * @property string $total_amount 结算总额
 * @property string $real_amount 应到
 * @property string $add_time 
 * @property string $description 备注
 * @property int $is_show 是否展示 1 展示
 * @property int $is_delete 是否删除
 *
 * @author xiongba
 * @date 2020-03-04 13:02:27
 *
 * @mixin \Eloquent
 */
class AgentSettlementModel extends Model
{

    protected $table = "agent_settlement";

    protected $primaryKey = 'id';

    protected $fillable = ['agent_id', 'channel', 'type', 'start', 'end', 'rate', 'total_amount', 'real_amount', 'add_time', 'description', 'is_show','is_delete'];

    protected $guarded = 'id';

    public $timestamps = false;
    const SHOW_OK = 1;
    const SHOW_NO = 0;
    const SHOW = [
        self::SHOW_OK => '展示',
        self::SHOW_NO => '隐藏',
    ];

    const TYPE_VIP = 1;
    const TYPE_COIN = 2;

    const TYPES = [
        self::TYPE_VIP=>'会员',
        self::TYPE_COIN=>'金币',

    ];




}
