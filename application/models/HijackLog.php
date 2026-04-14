<?php

/**
 * @property int $id
 * @property string $type 类型
 * @property string $json json
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @mixin \Eloquent
 */
class HijackLogModel extends EloquentModel
{
    protected $table = 'hijack_log';

    protected $fillable = [
        'id',
        'type',
        'json',
        'created_at',
        'updated_at',
    ];

    const TYPE_INTERFACE = 1;
    const TYPE_ADK = 2;

    public static function create_record($type, $json)
    {
        return self::create([
            'type'       => $type,
            'json'       => $json,
            'create_at'  => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}