<?php

/**
 * 营销抽奖活动
 *
 * @property int $id
 * @property string $name
 * @property int $status
 * @property int|null $creator_uid
 * @property string|null $start_at
 * @property string|null $end_at
 * @property int $daily_limit 每人每日上限（获得抽奖次数），0 不限
 * @property int $daily_send_limit
 * @property int $per_user_limit
 * @property int $total_limit
 * @property string|null $activity_image
 * @property string|null $rule_text
 * @property array|null $config
 * @property array|null $extra_config 其他配置（JSON，扩展用）
 * @property string|null $icon
 * @property string|null $intro
 * @property string $activity_type
 * @property string $trigger_scenario
 * @property int $receive_valid_days
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class MarketingLotteryActivityModel extends EloquentModel
{
    protected $table = 'marketing_lottery_activity';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'name', 'status', 'creator_uid', 'start_at', 'end_at',
        'daily_limit', 'daily_send_limit', 'per_user_limit', 'total_limit',
        'receive_valid_days',
        'activity_image', 'rule_text', 'config', 'extra_config', 'icon', 'intro', 'activity_type', 'trigger_scenario',
        'created_at', 'updated_at',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'config' => 'array',
        'extra_config' => 'array',
        'status' => 'integer',
        'daily_limit' => 'integer',
        'daily_send_limit' => 'integer',
        'per_user_limit' => 'integer',
        'total_limit' => 'integer',
        'receive_valid_days' => 'integer',
    ];

    public function setConfigAttribute($value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['config'] = null;
            return;
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $this->attributes['config'] = json_encode($decoded ?? new \stdClass(), JSON_UNESCAPED_UNICODE);
            return;
        }
        $this->attributes['config'] = json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    public function setExtraConfigAttribute($value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['extra_config'] = null;
            return;
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $this->attributes['extra_config'] = json_encode($decoded ?? new \stdClass(), JSON_UNESCAPED_UNICODE);
            return;
        }
        $this->attributes['extra_config'] = json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    public function setStartAtAttribute($value): void
    {
        $this->attributes['start_at'] = ($value === '' || $value === null) ? null : $value;
    }

    public function setEndAtAttribute($value): void
    {
        $this->attributes['end_at'] = ($value === '' || $value === null) ? null : $value;
    }

    public function setActivityTypeAttribute($value): void
    {
        $this->attributes['activity_type'] = ($value === '' || $value === null)
            ? self::TYPE_LOTTERY
            : $value;
    }

    public function setDailyLimitAttribute($value): void
    {
        $this->attributes['daily_limit'] = ($value === '' || $value === null) ? 0 : (int) $value;
    }

    public function setDailySendLimitAttribute($value): void
    {
        $this->attributes['daily_send_limit'] = ($value === '' || $value === null) ? 0 : max(0, (int) $value);
    }

    public function setPerUserLimitAttribute($value): void
    {
        $this->attributes['per_user_limit'] = ($value === '' || $value === null) ? 0 : (int) $value;
    }

    public function setTotalLimitAttribute($value): void
    {
        $this->attributes['total_limit'] = ($value === '' || $value === null) ? 0 : (int) $value;
    }

    public function setReceiveValidDaysAttribute($value): void
    {
        $this->attributes['receive_valid_days'] = ($value === '' || $value === null) ? 0 : max(0, (int) $value);
    }

    const STATUS_OFF = 0;
    const STATUS_ON  = 1;
    const STATUS_TIPS = [
        self::STATUS_OFF => '下架',
        self::STATUS_ON  => '上架',
    ];

    const TYPE_LOTTERY   = 'lottery';
    const TYPE_EGG       = 'egg';
    const TYPE_BLIND_BOX = 'blind_box';
    const TYPE_OTHER     = 'other';
    const ACTIVITY_TYPE_TIPS = [
        self::TYPE_LOTTERY   => '抽奖',
        self::TYPE_EGG       => '砸蛋',
        self::TYPE_BLIND_BOX => '盲盒',
        self::TYPE_OTHER     => '其他',
    ];

    /** 空字符串：不参与自动触发（仅用户主动参与等） */
    const TRIGGER_SCENARIO_NONE = '';
    const TRIGGER_SCENARIO_PAY_SUCCESS = 'pay_success';
    const TRIGGER_SCENARIO_TIPS = [
        self::TRIGGER_SCENARIO_NONE => '无',
        self::TRIGGER_SCENARIO_PAY_SUCCESS => '支付成功后',
    ];

    public function setTriggerScenarioAttribute($value): void
    {
        $this->attributes['trigger_scenario'] = ($value === null || $value === '')
            ? self::TRIGGER_SCENARIO_NONE
            : (string) $value;
    }

    /**
     * 当前时间窗内、已上架、且匹配触发情景的活动列表（供队列/支付成功等调用）
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, static>
     */
    public static function queryActiveForTriggerScenario(string $scenario)
    {
        if ($scenario === self::TRIGGER_SCENARIO_NONE) {
            return static::query()->whereRaw('1 = 0')->get();
        }
        $now = date('Y-m-d H:i:s');
        return static::query()
            ->where('status', self::STATUS_ON)
            ->where('trigger_scenario', $scenario)
            ->where(function ($q) use ($now) {
                $q->whereNull('start_at')->orWhere('start_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_at')->orWhere('end_at', '>=', $now);
            })
            ->orderBy('id')
            ->get();
    }

    public function prizes()
    {
        return $this->hasMany(MarketingLotteryPrizeModel::class, 'activity_id', 'id');
    }
}
