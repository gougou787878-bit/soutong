<?php

/**
 * class PornTagsModel
 *
 * @property string $created_at
 * @property int $id
 * @property string $name 标签
 * @property int $sort 排序
 * @property int $status 状态
 * @property string $updated_at
 *
 *
 * @date 2024-04-01 15:59:29
 *
 * @mixin \Eloquent
 */
class PornTagsModel extends EloquentModel
{
    protected $table = "porn_tags";
    protected $primaryKey = 'id';
    protected $fillable = [
        'created_at',
        'name',
        'sort',
        'status',
        'updated_at'
    ];
    protected $guarded = 'id';
    public $timestamps = true;

    const STATUS_NO = 0;
    const STATUS_OK = 1;
    const STATUS_TIPS = [
        self::STATUS_NO => '下架',
        self::STATUS_OK => '上架',
    ];
}
