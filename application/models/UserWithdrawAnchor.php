<?php


class UserWithdrawAnchorModel extends EloquentModel
{
    protected $table= 'user_withdraw_anchor';
    protected $fillable = [
        'uid',
        'created_at',
        'updated_at',
        'votes',
        'votes_total',
        'withdraw_votes',
        'withdraw_votes_total'
    ];
}