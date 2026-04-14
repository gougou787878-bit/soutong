<?php

/**
 * class MvTgModel
 *
 * @property string $cover
 * @property string $created_at
 * @property int $duration
 * @property int $height
 * @property int $id
 * @property string $local_path
 * @property string $m3u8
 * @property int $messae_id
 * @property int $status
 * @property string $title 影片标题
 * @property string $updated_at
 * @property int $width
 *
 * @author xiongba
 * @date 2025-01-28 16:25:00
 *
 * @mixin \Eloquent
 */
class MvTgModel extends EloquentModel
{

    protected $table = "mv_tg";

    protected $primaryKey = 'id';

    protected $fillable = [
        'cover',
        'created_at',
        'duration',
        'height',
        'local_path',
        'm3u8',
        'messae_id',
        'status',
        'title',
        'updated_at',
        'width',
        'type',
    ];

    protected $guarded = 'id';
    public $timestamps = true;

    const TYPE_0 = 0;
    const TYPE_1 = 1;
    const TYPE_2 = 2;
    const TYPE_3 = 3;
    const TYPE_4 = 4;
    const TYPE_5 = 5;
    const TYPE_6 = 6;
    const TYPE_7 = 7;
    const TYPE_8 = 9;

    const TYPE_TIPS = [
        self::TYPE_0 => 'boygv',
        self::TYPE_1 => 'saojigaymasturbation',
        self::TYPE_2 => 'sgqsxz',
        self::TYPE_3 => 'mi1069',
        self::TYPE_4 => 'boyxianjing',
        self::TYPE_5 => 'AsianGayP',
        self::TYPE_6 => 'MengNX',
        self::TYPE_7 => 'gay18cm',
        self::TYPE_8 => 'gtt0105',
    ];
}
