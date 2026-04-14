<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class TaskLogModel
 *
 * @property int $id
 * @property int $aff 用户aff
 * @property int $status 1待领取状态 2已领取状态
 * @property int $task_type 任务类型
 * @property string $title 标题
 * @property string $varname 简拼
 * @property int $add_diamond 金币
 * @property int $experience 经验
 * @property int $active_cnt 活跃值
 * @property int $gift_id 礼物
 * @property int $created_at 创建时间
 *
 * @author xiongba
 * @date 2020-04-02 17:35:17
 *
 * @mixin \Eloquent
 */
class TaskLogModel extends Model
{

    protected $table = "task_log";

    protected $primaryKey = 'id';

    protected $fillable = [
        'aff',
        'title',
        'varname',
        'add_diamond',
        'experience',
        'status',
        'task_type',
        'active_cnt',
        'gift_id',
        'created_at'
    ];

    protected $guarded = 'id';

    public $timestamps = false;


    /**
     * 创建一个带领区奖励的日志
     * @param $aff
     * @param TaskModel $task
     * @return Model|TaskLogModel
     * @author xiongba
     * @date 2020-04-03 16:49:33
     */
    public static function createTaskLog($aff, $task)
    {
        return self::create(
            [
                'aff'         => $aff,
                'title'       => $task['name'],
                'varname'     => $task['varname'],
                'add_diamond' => $task['add_diamond'],
                'experience'  => $task['experience'],
                'active_cnt'  => $task['active_cnt'],
                'gift_id'     => $task['gift_id'],
                'created_at'  => TIMESTAMP
            ]
        );
    }


    /**
     * @param $aff
     * @param $taskId
     * @return object|null|self
     * @author xiongba
     * @date 2020-04-03 17:11:16
     */
    public static function firstLog($aff, $taskName)
    {
        return cached('task:yj:' . $aff . ':' . $taskName)->serializerPHP()->expired(99999)->fetch(function () use (
            $aff,
            $taskName
        ) {
            return self::where('varname', $taskName)->select('id')->where('aff', $aff)->first();
        });


    }

    /**
     * @param $aff
     * @param $taskId
     * @return object|null|self
     * @author xiongba
     * @date 2020-04-03 17:11:16
     */
    public static function lastLog($aff, $taskName)
    {
        $key = 'task:nm:' . $aff . ':' . $taskName . ':' . date("Ymd");
        $time = strtotime(date('Y-m-d', TIMESTAMP));
        return cached($key)->serializerPHP()->expired(3600)->fetch(function () use ($aff, $taskName, $time) {
            return self::where([
                ['aff', '=', $aff],
                ['varname', '=', $taskName],
                ['created_at', '>=', $time],
            ])->select('id')->orderByDesc('id')->first();
        });
    }
    public static function lastSignLog($aff)
    {
        $key = 'task:sign:' . $aff  . ':' . date("Ymd");
        $time = strtotime(date('Y-m-d', TIMESTAMP));
        $exp = strtotime("+1 day")-TIMESTAMP;
        return cached($key)->serializerPHP()->expired($exp)->fetch(function () use ($aff, $time) {
            return self::where([
                ['aff', '=', $aff],
                ['created_at', '>=', $time],
            ])->select('id')->orderByDesc('id')->first();
        });
    }


}
