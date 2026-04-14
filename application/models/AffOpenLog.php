<?php


class AffOpenLogModel extends EloquentModel
{
    protected $table = 'aff_openlog';

    protected $fillable = [
        'aff',
        'type',
        'ua',
        'ip',
        'channel',
        'created_at'
    ];
}