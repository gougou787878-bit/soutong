<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class AdminPermitModel
 *
 * @property int $id
 * @property int $admin_id 后台用户id
 * @property int $role_id 角色id
 * @property string $permission 权限
 *
 * @author xiongba
 * @date 2020-02-27 12:24:59
 *
 * @mixin \Eloquent
 */
class AdminPermitModel extends Model
{

    protected $table = "admin_permit";

    protected $primaryKey = 'id';

    protected $fillable = ['admin_id', 'role_id', 'permission'];

    protected $guarded = 'id';

    public $timestamps = false;


    public static function findByAdminId(int $id)
    {
        return self::where(['admin_id' => $id])->first();
    }

    /**
     * 创建或者更新
     * @param integer $adminId 管理员id
     * @param integer $roleId 管理员需要的角色，如果空，删除管理员
     * @return bool
     * @throws Exception
     */
    public static function createOrUpdate($adminId, $roleId)
    {
        $item = \AdminPermitModel::where(['admin_id' => $adminId])->first();
        if ($item) {
            if (empty($roleId)) {
                $item->delete();
            } else {
                $item->role_id = $roleId;
                $item->save();
            }
        } else {
            AdminPermitModel::create(['role_id' => $roleId, 'admin_id' => $adminId, 'permission' => '']);
        }
        return true;
    }


}
