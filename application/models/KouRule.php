<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class KouRuleModel
 *
 * @property int $id
 * @property int $agent_id 渠道编号
 * @property string $type 渠道类型 cps cpa
 * @property int $status 0 不生效 1生效
 * @property string $base_number 扣量基数
 * @property int $point 扣量点位 比如10 即
 * @property string $admin_name 管理人员
 * @property string $created_at 时间
 * @property string $time_rule 时间规则
 *
 *
 * @date 2021-11-12 18:30:05
 *
 * @mixin \Eloquent
 */
class KouRuleModel extends Model
{

    protected $table = "kou_rule";

    protected $primaryKey = 'id';

    protected $fillable = ['agent_id', 'time_rule', 'type', 'status', 'base_number', 'point', 'admin_name', 'created_at'];

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

    const KEY_RULE_LIST = 'kou:rule';

    public function setTimeRuleAttribute($value)
    {
        $this->attributes['time_rule'] = json_encode($value);
    }

    public function getTimeRuleAttribute(): array
    {
        $default = [
            ['date' => '1970-01-01', 'point' => '0'],
            ['date' => '1970-01-01', 'point' => '0'],
            ['date' => '1970-01-01', 'point' => '0'],
        ];
        $rule = $this->attributes['time_rule'] ?? '';
        if (strlen($rule) > 10) {
            $rule = json_decode($rule, 1);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $rule = $default;
            }
        } else {
            $rule = $default;
        }
        return $rule;
    }


    /**
     * 扣量规则 全量缓存处理
     * @return mixed
     */
    static function getKouRuleDataList(){
        $data =  cached(self::KEY_RULE_LIST)->expired(2000)->serializerJSON()->fetch(function (){
           return self::query()->where('status','=',1)->get(['id','agent_id','type','base_number','point','time_rule'])->toArray();
        });
        if($data) {
            return array_column($data,null,'agent_id');
        }
        return [];
    }
    static function clearCache(){
        return redis()->del(self::KEY_RULE_LIST);
    }

}
