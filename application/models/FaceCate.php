<?php

/**
 * class FaceCateModel
 *
 * @property string $created_at
 * @property int $id
 * @property string $name 分类名
 * @property int $sort 排序
 * @property int $status
 * @property string $updated_at
 * @property int $type 类型
 *
 *
 * @date 2024-04-08 15:52:34
 *
 * @mixin \Eloquent
 */
class FaceCateModel extends EloquentModel
{
    protected $table = "face_cate";
    protected $primaryKey = 'id';
    protected $fillable = [
        'created_at',
        'name',
        'sort',
        'status',
        'updated_at',
        'type'
    ];

    protected $guarded = 'id';
    public $timestamps = true;

    const STATUS_NO = 0;
    const STATUS_ON = 1;
    const STATUS_TIPS = [
        self::STATUS_NO => '下架',
        self::STATUS_ON => '上架',
    ];

    const TYPE_COM = 0;
    const TYPE_NEW = 1;
    const TYPE_TIPS = [
        self::TYPE_COM => '普通',
        self::TYPE_NEW => '最新',
    ];

    const SE_FACE_CATE_LIST = ['id', 'name'];
    const CK_FACE_CATE_LIST = 'ck:face:cate:list';
    const GP_FACE_CATE_LIST = 'gp:face:cate:list';
    const CN_FACE_CATE_LIST = '换头图片分类列表';

    public static function list_cate()
    {
        return cached(self::CK_FACE_CATE_LIST)
            ->group(self::GP_FACE_CATE_LIST)
            ->chinese(self::CN_FACE_CATE_LIST)
            ->fetchPhp(function () {
                return self::select(self::SE_FACE_CATE_LIST)
                    ->where('status', self::STATUS_ON)
                    ->orderByDesc('sort')
                    ->orderByDesc('id')
                    ->get();
            });
    }
}
