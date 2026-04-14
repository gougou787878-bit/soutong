<?php


/**
 * class ProductRightMapModel
 *
 * @property int $id
 * @property int $product_id
 * @property int $product_right_id
 * @property int $status
 * @property int $sort
 *
 *
 * @date 2022-03-29 20:58:54
 *
 * @mixin \Eloquent
 */
class ProductRightMapModel extends EloquentModel
{
    protected $table = "product_right_map";
    protected $primaryKey = 'id';
    protected $fillable = [
        'product_id',
        'product_right_id',
        'status',
        'sort'
    ];

    protected $guarded = 'id';
    public $timestamps = false;

    const STATUS_YES = '1';
    const STATUS_NO = '0';

    const STATUS = [
        self::STATUS_NO => '关闭',
        self::STATUS_YES => '启用',
    ];

    public function product()
    {
        return $this->hasOne(ProductModel::class, 'id', 'product_id');
    }

    public function right()
    {
        return $this->hasOne(ProductRightModel::class, 'id', 'product_right_id');
    }
}
