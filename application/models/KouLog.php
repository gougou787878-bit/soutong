<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class KouLogModel
 *
 * @property int $id
 * @property int $kou_id
 * @property int $agent_id 渠道编号
 * @property string $type 渠道类型 cps cpa
 * @property int $day_time 日期时间
 * @property string $base_number 扣量基数
 * @property int $point 扣量点位 比如10 即按10%扣量 浮动处理
 * @property string $total_number 总量 cpa 是人数 cps 是订单
 * @property string $kou_number 扣量 cpa 是人数 cps 是订单
 *
 *
 * @date 2021-11-12 18:30:18
 *
 * @mixin \Eloquent
 */
class KouLogModel extends Model
{

    protected $table = "kou_log";

    protected $primaryKey = 'id';

    protected $fillable = [
        'kou_id',
        'agent_id',
        'type',
        'day_time',
        'base_number',
        'point',
        'total_number',
        'kou_number'
    ];

    protected $guarded = 'id';

    public $timestamps = false;

    const TYPE_CPS = 'cps';
    const TYPE_CPA = 'cpa';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function agent()
    {
        return self::hasOne(MemberModel::class, 'uid', 'agent_id');
    }

    /**
     * 是否存在扣量记录
     * @param $kou_id
     * @param $agent_id
     * @param $day_time
     * @return \Illuminate\Database\Eloquent\Builder|Model|object|null
     */
    static function hasLog($kou_id, $agent_id, $day_time)
    {
        return self::where([
            ['kou_id', '=', $kou_id],
            ['agent_id', '=', $agent_id],
            ['day_time', '=', $day_time],
        ])->first();
    }

    /**
     * 扣量规则生效统计入库
     * @param $videoModel
     * @param null $day_time
     * @return array
     */
    static function addLog(MvModel $videoModel, $day_time = null)
    {
        $is_kou = 0;
        $flag = true;
        $agentRule = null;
        $agent_id = $videoModel->uid;
        try {
            $agentRuleDataList = KouRuleModel::getKouRuleDataList();
            //print_r($agentRuleDataList);die;
            if ($agentRuleDataList && isset($agentRuleDataList[$agent_id])) {
                $agentRule = $agentRuleDataList[$agent_id];
            }
            if (!$agentRule) {//没有扣量规则的渠道 不执行扣量计算
                return [$is_kou, $flag];
            }
            $time_rule = $agentRule['time_rule'] ?? '';
            //print_r($agentRule);die;
            $day_time == null && $day_time = date('Ymd');
            $number = $videoModel->coins;

            /** @var KouLogModel $hasLog */
            if ($hasLog = self::hasLog($agentRule['id'], $agentRule['agent_id'], $day_time)) {
                $kou_point = $hasLog->point;
                if ($hasLog->base_number < $hasLog->total_number) {
                    if (is_array($time_rule)) {
                        // 时间域扣量
                        $time = $videoModel->getOriginal('created_at');
                        $ary = array_column($time_rule, 'point', 'date');
                        ksort($ary);
                        foreach ($ary as $date => $point) {
                            if ($point != 0 && $time < strtotime($date)) {
                                $kou_point = $point;
                                break;
                            }
                        }
                    }
                    //超过基数  如果计算超过阈值要执行扣量
                    $rate = ($hasLog->kou_number / ($hasLog->total_number-$hasLog->base_number)) * 100;
                    //echo $rate;
                    if ($rate < $kou_point) {
                        $is_kou = 1;//超过基数  如果计算超过阈值要执行扣量
                    }
                }
                $update = [];
                $update['total_number'] = DB::raw("total_number+{$number}");
                if ($is_kou) {
                    $update['kou_number'] = DB::raw("kou_number+{$number}");
                }
                $flag = self::where('id', $hasLog->id)->update($update);
            } else {
                $flag = self::insert([
                    'kou_id'       => $agentRule['id'],
                    'agent_id'     => $agentRule['agent_id'],
                    'type'         => $agentRule['type'],
                    'day_time'     => $day_time,
                    'base_number'  => $agentRule['base_number'],
                    'point'        => $agentRule['point'],
                    'total_number' => $number,
                    'kou_number'   => 0
                ]);
                $is_kou = 0;
            }
            return [$is_kou, $flag];
        } catch (Throwable $exception) {
            errLog("addLog" . $exception->getMessage());
            return [0, true];
        }
    }


    /**
     * 扣量面板统计
     * @param $type
     * @param bool $is_toady
     * @return mixed
     */
    static function kouAgentNumber($type,$is_toady =1)
    {
        $key = "kou:{$type}:{$is_toady}";
        return cached($key)->expired(320)->serializerPHP()->fetch(function () use ($type,$is_toady) {
            $w['type'] = $type;
            if($is_toady){
                $w['day_time']= date("Ymd");
            }
            return KouLogModel::where($w)->sum('kou_number');
        });
    }



}
