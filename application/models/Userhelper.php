<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class UserhelperModel
 *
 * @property int $id
 * @property string $question 问题描述
 * @property string $answer 答案
 * @property int $status 0:关闭 1开启
 * @property int $type 1.热点问题/2加载缓存3个人账户4资源内容5分享推广6其他问题
 * @property string $created_at
 *
 * @author xiongba
 * @date 2020-03-16 17:57:30
 *
 * @mixin \Eloquent
 */
class UserhelperModel extends Model
{

    protected $table = "userhelper";

    protected $primaryKey = 'id';

    protected $fillable = ['question', 'answer', 'status', 'type', 'created_at'];

    protected $guarded = 'id';

    public $timestamps = false;

    const STATUS_ENABLE = 1;

    const STATUS = [
        0 => '禁用',
        1 => '启用',
    ];

    const REDIS_LIST_KEY = 'hlp';

    static function clearCache()
    {
//        redis()->del(self::REDIS_LIST_KEY);
        cached('')->clearGroup('helper:user:new');
    }


}
