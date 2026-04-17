<?php

/**
 * 营销抽奖参与机会
 *
 * @property int $id
 * @property int $activity_id
 * @property int $uid
 * @property int $status
 * @property array|null $extra
 * @property string|null $expire_at
 * @property string|null $remark
 * @property string|null $idempotency_key
 * @property string|null $source_order_id
 * @property string|null $created_day
 */
class MarketingLotteryPlayModel extends EloquentModel
{
    protected $table = 'marketing_lottery_play';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'activity_id', 'uid', 'status', 'extra',
        'expire_at', 'remark', 'idempotency_key', 'source_order_id',
        'created_day',
        'created_at', 'updated_at',
    ];

    protected $guarded = ['id'];

    public function setExtraAttribute($value): void
    {
        $this->attributes['extra'] = $this->encodeJsonField($value);
    }

    private function encodeJsonField($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return json_encode($decoded ?? new \stdClass(), JSON_UNESCAPED_UNICODE);
        }
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    protected $casts = [
        'extra' => 'array',
        'status' => 'integer',
    ];

    const STATUS_PENDING = 0;
    const STATUS_USED    = 1;
    const STATUS_TIPS = [
        self::STATUS_PENDING => '待抽奖',
        self::STATUS_USED    => '已抽奖',
    ];

    public function activity()
    {
        return $this->belongsTo(MarketingLotteryActivityModel::class, 'activity_id', 'id');
    }
}
