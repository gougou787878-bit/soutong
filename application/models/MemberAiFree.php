<?php

/**
 * class MemberAiFree
 *
 * @property int $id
 * @property int $aff 用户aff
 * @property int $value 剩余次数
 * @property int $total 总次数
 * @property string $created_at
 * @property string $updated_at
 *
 *
 * @date 2024-11-25 21:40:55
 *
 * @mixin \Eloquent
 */
class MemberAiFreeModel extends EloquentModel
{
    protected $table = "member_ai_free";
    protected $primaryKey = 'id';
    protected $fillable = [
        'aff',
        'value',
        'total',
        'created_at',
        'updated_at'
    ];

    protected $guarded = 'id';
    public $timestamps = true;

    public static function setRecord($aff, $num){
        $model = self::where('aff', $aff)
            ->first();
        if ($model){
            //直接覆盖
            $isOk = $model->increment('value', $num, ['total' => DB::raw('total + ' . $num)]);
        }else{
            $model = self::make();
            $model->aff = $aff;
            $model->value = $num;
            $model->total = $num;
            $isOk = $model->save();
        }
        test_assert($isOk, '兑换失败');
        return true;
    }

    public static function decrValueByType($aff){
        return self::query()
            ->where('aff', $aff)
            ->where('value', '>', 0)
            ->decrement('value');
    }

    public static function getValueByType($aff){
        return (int)self::query()
            ->where('aff', $aff)
            ->value('value');
    }
}
