<?php


class UserProxyCashBackModel extends EloquentModel
{
    protected $table = 'user_proxy_cash_back';

    protected $fillable = [
        'aff',
        'level_1',
        'level_2',
        'level_3',
        'level_4',
        'withdraw_times',
        'amount',
        'created_at',
        'updated_at'
    ];
}