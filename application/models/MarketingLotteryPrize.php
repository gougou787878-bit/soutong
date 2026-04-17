<?php

/**
 * 营销抽奖奖项
 *
 * @property int $id
 * @property int $activity_id
 * @property string $name
 * @property string|null $prize_desc
 * @property string|null $prize_image
 * @property string|null $prize_icon
 * @property string $prize_type
 * @property int $is_win 是否中奖 0否 1是
 * @property int $status
 * @property int $sort_order
 * @property int $win_probability 中奖概率 0-100
 * @property int $total_stock
 * @property int $issued_count
 * @property int $per_user_cap
 * @property int $coins_amount
 * @property int $coins_random_min 金币随机最小值
 * @property int $coins_random_max 金币随机最大值
 * @property int $vip_days
 * @property int|null $vip_product_id
 * @property array|null $vip_random_product_ids VIP随机产品ID数组（空/NULL=全部）
 * @property array|null $extra
 */
class MarketingLotteryPrizeModel extends EloquentModel
{
    protected $table = 'marketing_lottery_prize';

    protected $primaryKey = 'id';

    public $timestamps = true;

    /** @var bool|null */
    private static $hasWinProbabilityColumn = null;

    protected $fillable = [
        'activity_id', 'name', 'prize_desc', 'prize_image', 'prize_icon',
        'prize_type', 'is_win', 'status', 'sort_order',
        // 概率字段：新表用 win_probability；旧表用 weight。保存时会自动兼容（见 setWinProbabilityAttribute）
        'win_probability', 'weight',
        'total_stock', 'issued_count',
        'per_user_cap', 'coins_amount', 'coins_random_min', 'coins_random_max',
        'vip_days', 'vip_product_id', 'vip_random_product_ids', 'extra',
        'created_at', 'updated_at',
    ];

    protected $guarded = ['id'];

    public function setWinProbabilityAttribute($value): void
    {
        if ($value === '' || $value === null) {
            if (self::hasWinProbabilityColumn()) {
                $this->attributes['win_probability'] = 0;
            } else {
                $this->attributes['weight'] = 0;
                unset($this->attributes['win_probability']);
            }
            return;
        }
        $v = max(0, min(100, (int) $value));
        if (self::hasWinProbabilityColumn()) {
            $this->attributes['win_probability'] = $v;
        } else {
            $this->attributes['weight'] = $v;
            unset($this->attributes['win_probability']);
        }
    }

    /**
     * 兼容旧库：当没有 win_probability 列时，用 weight 作为“中奖概率(0-100)”。
     */
    public function getWinProbabilityAttribute($value): int
    {
        if ($value !== null && $value !== '') {
            return (int) $value;
        }
        $w = $this->attributes['weight'] ?? 0;
        return (int) $w;
    }

    public function setExtraAttribute($value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['extra'] = null;
            return;
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $this->attributes['extra'] = json_encode($decoded ?? new \stdClass(), JSON_UNESCAPED_UNICODE);
            return;
        }
        $this->attributes['extra'] = json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    protected $casts = [
        'extra' => 'array',
        'status' => 'integer',
        'sort_order' => 'integer',
        'win_probability' => 'integer',
        'weight' => 'integer',
        'total_stock' => 'integer',
        'issued_count' => 'integer',
        'per_user_cap' => 'integer',
        'coins_amount' => 'integer',
        'coins_random_min' => 'integer',
        'coins_random_max' => 'integer',
        'vip_days' => 'integer',
        'vip_product_id' => 'integer',
        'vip_random_product_ids' => 'array',
    ];

    private static function hasWinProbabilityColumn(): bool
    {
        if (self::$hasWinProbabilityColumn !== null) {
            return self::$hasWinProbabilityColumn;
        }
        try {
            self::$hasWinProbabilityColumn = \DB::schema()->hasColumn('marketing_lottery_prize', 'win_probability');
        } catch (\Throwable $e) {
            self::$hasWinProbabilityColumn = false;
        }
        return self::$hasWinProbabilityColumn;
    }

    const STATUS_OFF = 0;
    const STATUS_ON  = 1;
    const STATUS_TIPS = [
        self::STATUS_OFF => '停用',
        self::STATUS_ON  => '启用',
    ];

    const PRIZE_THANKS = 'thanks';
    const PRIZE_COINS  = 'coins';
    const PRIZE_VIP    = 'vip';
    const PRIZE_PHYSICAL = 'physical';
    const PRIZE_OTHER    = 'other';
    const PRIZE_TYPE_TIPS = [
        self::PRIZE_THANKS => '谢谢参与',
        self::PRIZE_COINS  => '金币',
        self::PRIZE_VIP    => 'VIP',
        self::PRIZE_PHYSICAL => '实物',
        self::PRIZE_OTHER  => '其它',
    ];

    const IS_WIN_NO  = 0;
    const IS_WIN_YES = 1;
    const IS_WIN_TIPS = [
        self::IS_WIN_NO  => '非中奖',
        self::IS_WIN_YES => '中奖',
    ];

    public function activity()
    {
        return $this->belongsTo(MarketingLotteryActivityModel::class, 'activity_id', 'id');
    }
}
