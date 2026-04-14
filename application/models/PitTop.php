<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class PitTopModel
 *
 * @property int $id 
 * @property int $vid 
 * @property int $status 
 * @property string $comment 
 * @property int $created_at 
 *
 *
 * @property MvModel $mv
 *
 *
 * @author xiongba
 * @date 2020-11-10 17:54:34
 *
 * @mixin \Eloquent
 */
class PitTopModel extends EloquentModel
{

    protected $table = "pit_top";

    protected $primaryKey = 'id';

    protected $fillable = ['vid', 'status', 'comment', 'created_at'];

    protected $guarded = 'id';

    public $timestamps = false;

    const STAT_ENABLE = 1;
    const STAT_DISABLE = 0;

    const STAT = [
        self::STAT_ENABLE  => '启用',
        self::STAT_DISABLE => '禁用',
    ];


    public static function queryBase()
    {
        return self::where('status', self::STAT_ENABLE);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function mv(){
        return self::hasOne(MvModel::class,'id','vid');
    }





}
