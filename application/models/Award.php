<?php
/**
 * 靓号
 */
class AwardModel extends EloquentModel
{
    protected $table = 'award';
    protected $fillable = [
        'type',
        'uid',
        'description',
        'date'
    ];
}