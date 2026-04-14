<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class ProductPrivilegeModel
 *
 * @property int $id 
 * @property int $product_id 产品
 * @property int $privilege_id 权限
 * @property int $value 值  暂无用
 * @property string $created_at 时间
 * @property ProductModel $product
 * @property PrivilegeModel $privilege
 *
 *
 * @date 2022-04-08 21:27:50
 *
 * @mixin \Eloquent
 */
class ProductPrivilegeModel extends Model
{

    protected $table = "product_privilege";

    protected $primaryKey = 'id';

    protected $fillable = ['product_id', 'privilege_id', 'value', 'created_at'];

    protected $guarded = 'id';

    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function product(){
        return $this->hasOne(ProductModel::class, 'id', 'product_id');
    }
    public function privilege(){
        return $this->hasOne(PrivilegeModel::class, 'id', 'privilege_id');
    }
}
