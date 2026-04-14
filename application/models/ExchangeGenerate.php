<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class ExchangeGenerateModel
 *
 * @property int $admin_id 谁操作的
 * @property string $created_at
 * @property string $ext 扩展字段
 * @property int $id
 * @property string $memo 操作记录
 * @property int $num 生成的数量
 * @property string $number 值
 * @property int $type 兑换码类型
 * @property string $updated_at
 * @property int $validity 有效期
 * @property int $status
 *
 * @author xiongba
 * @date 2020-03-10 16:57:11
 *
 * @mixin \Eloquent
 */
class ExchangeGenerateModel extends Model
{

    protected $table = "exchange_generate";

    protected $primaryKey = 'id';

    protected $fillable = [
        'admin_id',
        'created_at',
        'ext',
        'memo',
        'num',
        'number',
        'type',
        'updated_at',
        'validity',
        'status'
    ];

    protected $guarded = 'id';

    //public $timestamps = false;
    const STATUS_FAIL = 0; // 失效
    const STATUS_SUCCESS = 1; // 正常
    const STATUS = [
        self::STATUS_FAIL => '召回',
        self::STATUS_SUCCESS => '正常',
    ];


}
