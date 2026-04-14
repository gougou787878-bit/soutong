<?php
class UserInviteReceiveLogModel extends EloquentModel{

    protected $table = 'users_invite_receive_log';

    const INVITE_LOG = "invite_receive_log";

    const INVITE_TIME_LIMIT = "invite_time_limit";

    const START_TIME = "2020-02-22";

    protected $guarded = [];

    protected $fillable = [
        'uid',
        'invite_id',
        'created_at'
    ];
}