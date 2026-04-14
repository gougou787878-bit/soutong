<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class AdminMenuModel
 *
 * @property int $id
 * @property string $name 菜单名称
 * @property string $icon 图标
 * @property string $controller 图标
 * @property string $action 图标
 * @property string $value 菜单值，
 * @property int $pid 父ID 默认0-一级菜单
 * @property int $level 级别
 * @property int $sort 排序
 * @property string $status 状态
 * @property string $created_at 创建时间
 *
 * @author xiongba
 * @date 2020-02-27 12:24:59
 *
 * @mixin \Eloquent
 */
class AdminMenuModel extends Model
{

    protected $table = "admin_menu";

    protected $primaryKey = 'id';

    protected $fillable = [
        'name',
        'icon',
        'value',
        'controller',
        'action',
        'pid',
        'level',
        'sort',
        'status',
        'created_at'
    ];

    protected $guarded = 'id';

    public $timestamps = false;

    const STATUS_YES = 'yes';
    const STATUS_NO = 'no';

    public static function getAll()
    {
        $list = self::get()->toArray();
        $list = arrayToTree($list);
        $data = [];
        foreach ($list as $k => $v) {
            array_push($data, $v);
            if (isset($list[$k]['children'])) {
                foreach ($list[$k]['children'] as $vv) {
                    array_push($data, $vv);
                }
            }
        }
        return $data;
    }

    public static function getTreeAll($status = null, $idArray = [])
    {
        $where = [];
        if ($status) {
            $where[] = ['status', '=', $status];
        }
        $query = self::query();
        if (!empty($idArray)) {
            $query->whereIn('id', $idArray);
        }
        $list = $query->where($where)->get()->toArray();
        return arrayToTree($list);
    }

    public static function getTreeArray($pid = -1, $prefix = '')
    {
        $list = self::where('pid', $pid)->get(['id', 'name', 'pid']);
        $res = [];
        /** @var AdminMenuModel $item */
        foreach ($list as $item) {
            $res[$item->id] = $prefix . $item->name;
            $nRes = self::getTreeArray($item->id, $prefix . "&nbsp;&nbsp;&nbsp;&nbsp;");
            if (!empty($nRes)) {
                $res = array_merge($res, $nRes);
            }
        }
        return $res;
    }

    public static function getTreeArrayData($pid = 0, $prefix = '')
    {
        $list = self::where('pid', $pid)->get(['id', 'name', 'pid']);
        $res[0] = '顶级(默认)';
        /** @var AdminMenuModel $item */
        foreach ($list as $item) {
            $res[$item->id] = $prefix . $item->name;
        }

        return $res;
    }

}
