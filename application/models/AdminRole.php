<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class AdminRoleModel
 *
 * @property int $id 
 * @property string $name 角色名称
 * @property string $desc 角色描述
 * @property string $rule 角色规则
 * @property string $created_at 
 * @property string $updated_at 
 *
 * @author xiongba
 * @date 2020-02-27 12:24:59
 *
 * @mixin \Eloquent
 */
class AdminRoleModel extends Model
{

    protected $table = "admin_role";

    protected $primaryKey = 'id';

    protected $fillable = ['name', 'desc', 'rule', 'created_at', 'updated_at'];

    protected $guarded = 'id';

    public $timestamps = false;



    public static function getRoleArray()
    {
        $data = self::get(['id','name']);
        $results = [];
        foreach ($data as $datum) {
            $results[$datum->id] = $datum->name;
        }
        return $results;
    }


}
