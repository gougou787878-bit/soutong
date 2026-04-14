<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class AgentChartModel
 *
 * @property int $id
 * @property string $channel
 * @property string $type
 * @property int $value
 * @property int $date
 * @property int $is_check
 *
 * @author xiongba
 * @date 2020-03-11 20:06:45
 *
 * @mixin \Eloquent
 */
class AgentChartModel extends Model
{

    protected $table = "agent_chart";

    protected $primaryKey = 'id';

    protected $fillable = ['channel', 'type', 'value', 'date', 'is_check'];

    protected $guarded = 'id';

    public $timestamps = false;

    const TYPE_REG = 'reg';
    const TYPE_VIP = 'vip';
    const TYPE_GOLD = 'gold';
    const TYPES = [
        self::TYPE_REG  => '注册人数',
        self::TYPE_VIP  => '会员总额',
        self::TYPE_GOLD => '金币总额'
    ];

    static function getChannelDataList($channel,$type,$dateData=[]){
        $w['type'] = $type;
        $channel && $w['channel'] = $channel;
        $data =  self::query()->where($w)->whereIn('date',$dateData)->orderBy('date','ASC')->get();
        return $data?$data->toArray():[];
    }

    static function setOrGetChannelDate($where, $data)
    {
        if (isset($where['channel']) && $where['channel']) {//防止空渠道输入
            self::updateOrCreate($where, $data);
        }
    }


}
