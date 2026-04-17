<?php

/**
 * 营销抽奖兑奖
 *
 * @property int $id
 * @property int $play_id
 * @property int $uid
 * @property int $activity_id
 * @property string $activity_name 冗余：活动名称
 * @property int|null $prize_id
 * @property string $prize_name 冗余：奖项名称
 * @property int $is_win 冗余：是否中奖 0否 1是
 * @property int $status
 * @property int|null $admin_uid
 * @property string|null $remark
 * @property array|null $grant_snapshot
 * @property array|null $prize_snapshot
 */
class MarketingLotteryRedemptionModel extends EloquentModel
{
    protected $table = 'marketing_lottery_redemption';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'play_id', 'uid',
        'activity_id', 'activity_name',
        'prize_id', 'prize_name','is_win',
        'status', 'admin_uid',
        'remark', 'grant_snapshot', 'prize_snapshot',
        'created_at', 'updated_at',
    ];

    protected $guarded = ['id'];

    public function setGrantSnapshotAttribute($value): void
    {
        $this->attributes['grant_snapshot'] = $this->encodeJsonField($value);
    }

    public function setPrizeSnapshotAttribute($value): void
    {
        $this->attributes['prize_snapshot'] = $this->encodeJsonField($value);
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
        'grant_snapshot' => 'array',
        'prize_snapshot' => 'array',
        'status' => 'integer',
        'is_win' => 'integer',
    ];

    const STATUS_PENDING    = 0;
    const STATUS_PROCESSING = 1;
    const STATUS_SUCCESS    = 2;
    const STATUS_FAIL       = -1;
    const STATUS_TIPS = [
        self::STATUS_PENDING    => '待处理',
        self::STATUS_PROCESSING => '处理中',
        self::STATUS_SUCCESS    => '成功',
        self::STATUS_FAIL       => '失败',
    ];

    public function play()
    {
        return $this->belongsTo(MarketingLotteryPlayModel::class, 'play_id', 'id');
    }
}
