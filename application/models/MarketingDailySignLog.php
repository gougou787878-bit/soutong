<?php

class MarketingDailySignLogModel extends EloquentModel
{
    protected $table = 'marketing_daily_sign_log';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'activity_id', 'uid', 'sign_date', 'continuous_day',
        'daily_coins', 'bonus_vip_days', 'is_bonus', 'remark',
        'created_at', 'updated_at',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'activity_id' => 'integer',
        'uid' => 'integer',
        'continuous_day' => 'integer',
        'daily_coins' => 'integer',
        'bonus_vip_days' => 'integer',
        'is_bonus' => 'integer',
    ];
}
