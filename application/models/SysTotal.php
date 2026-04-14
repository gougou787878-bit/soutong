<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class SysTotalModel
 *
 * @property string $date 日期
 * @property int $id
 * @property string $name 减值
 * @property int $value 统计
 *
 * @author xiongba
 * @date 2021-06-05 00:33:15
 *
 * @mixin \Eloquent
 */
class SysTotalModel extends Model
{

    protected $table = "sys_total";

    protected $primaryKey = 'id';

    protected $fillable = ['date', 'name', 'value'];

    protected $guarded = 'id';

    public $timestamps = false;

    const RK_NAME = 'sys-total:%s';


    public static function incrBy($name, $value = 1, $date = null): int
    {
        if ($value == 0) {
            return 0;
        }
        if ($date === null) {
            $date = date('Y-m-d');
        }
        $key = sprintf(self::RK_NAME ,$date );
        $ret = redis()->hIncrBy($key, $name, $value);
        // 改为定时任务入库
//        if ($ret <= 2) {
//            \App\jobs\SysTotalJob::dispatch(date('Y-m-d' , strtotime('-1 days')));
//        }
        return $ret;
    }

    static function getValueBy($name, $date = null): int
    {
        if ($date === null) {
            $date = date('Y-m-d');
        }
        if ($date == date('Y-m-d', time())) {
            $key = sprintf(self::RK_NAME ,$date );
            return (int)redis()->hGet($key, $name);
        }
        $value = self::where('name', $name)
            ->where('date', $date)
            ->value('value');
        return (int)$value;
    }

    static function getBy($name, $date = null): int
    {
        return self::getValueBy($name, $date);
    }


}
