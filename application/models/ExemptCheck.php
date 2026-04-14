<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class ExemptCheckModel
 *
 * @property int $id
 * @property int $uid
 * @property int $vid
 * @property int $created_at 自动审核时间
 * @property int $is_check 是否抽查
 *
 * @author xiongba
 * @date 2020-12-29 18:22:29
 *
 * @mixin \Eloquent
 */
class ExemptCheckModel extends EloquentModel
{

    protected $table = "exempt_check";

    protected $primaryKey = 'id';

    protected $fillable = ['uid', 'vid', 'created_at' , 'is_check'];

    protected $guarded = 'id';

    public $timestamps = false;


}
