<?php

class MarketingDailySignActivityModel extends EloquentModel
{
    protected $table = 'marketing_daily_sign_activity';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'name', 'status', 'show_start_at', 'start_at', 'end_at',
        'daily_coins', 'cycle_days', 'bonus_vip_days', 'bonus_vip_level', 'rule_text',
        'created_at', 'updated_at',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'status' => 'integer',
        'daily_coins' => 'integer',
        'cycle_days' => 'integer',
        'bonus_vip_days' => 'integer',
        'bonus_vip_level' => 'integer',
    ];

    const STATUS_OFF = 0;
    const STATUS_ON = 1;
    const STATUS_TIPS = [
        self::STATUS_OFF => '下架',
        self::STATUS_ON => '上架',
    ];

    public function setStartAtAttribute($value): void
    {
        $this->attributes['start_at'] = ($value === '' || $value === null) ? null : $value;
    }

    public function setShowStartAtAttribute($value): void
    {
        $this->attributes['show_start_at'] = ($value === '' || $value === null) ? null : $value;
    }

    public function setEndAtAttribute($value): void
    {
        $this->attributes['end_at'] = ($value === '' || $value === null) ? null : $value;
    }

    public static function current()
    {
        $now = date('Y-m-d H:i:s');
        return static::query()
            ->where('status', self::STATUS_ON)
            ->where(function ($q) use ($now) {
                $q->where(function ($sub) use ($now) {
                    $sub->whereNotNull('show_start_at')->where('show_start_at', '<=', $now);
                })->orWhere(function ($sub) use ($now) {
                    $sub->whereNull('show_start_at')
                        ->where(function ($inner) use ($now) {
                            $inner->whereNull('start_at')->orWhere('start_at', '<=', $now);
                        });
                });
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_at')->orWhere('end_at', '>=', $now);
            })
            ->orderByDesc('id')
            ->first();
    }
}
