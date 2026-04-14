<?php

/**
 * class ProductRightModel
 *
 * @property int $id
 * @property string $name
 * @property string $sub_name
 * @property string $img
 * @property string $desc
 * @property string $created_at
 * @property string $updated_at
 * @property int $sort
 *
 *
 * @date 2022-03-29 20:58:48
 *
 * @mixin \Eloquent
 */
class ProductRightModel extends EloquentModel
{
    protected $table = "product_right";
    protected $primaryKey = 'id';
    protected $fillable = [
        'name',
        'sub_name',
        'img',
        'desc',
        'created_at',
        'updated_at',
        'sort'
    ];
    protected $guarded = 'id';
    public $timestamps = false;

    protected $appends = [
        'img_url',
    ];

    /**
     * 替换图片地址
     * @param $value
     * @return string
     */
    public function getImgUrlAttribute()
    {
        return $this->img ? url_ads($this->img) : '';
    }

    /**
     * @return array
     */
    public static function getDataList()
    {
        return self::get([
            'id',
            'name'
        ])->mapWithKeys(function ($item) {
            return [$item->id => $item->id . '|' . $item->name];
        })->toArray();
    }

    public static function getDataListForProduct()
    {
        return self::get(['id', 'name', 'desc', 'img'])->toArray();
    }


}
