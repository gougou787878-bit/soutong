<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class WeekRelationModel
 *
 * @property int $id 
 * @property int $week_id 
 * @property int $mv_id 
 * @property int $created_at
 * @property string $comment 评语
 *
 * @author xiongba
 * @date 2020-11-10 18:32:58
 *
 * @mixin \Eloquent
 */
class WeekRelationModel extends EloquentModel
{

    protected $table = "week_relation";

    protected $primaryKey = 'id';

    protected $fillable = ['week_id', 'mv_id', 'comment','created_at'];

    protected $guarded = 'id';

    public $timestamps = false;

    protected $appends = [ 'created_at_txt'];

    public function getCreatedAtTxtAttribute(){
        $date = date('Y-m-d',$this->created_at);
        return $date;
    }


    public function week(){
        return self::hasOne(WeekModel::class,'id','week_id');
    }
    public function mv(){
        return self::hasOne(MvModel::class,'id','mv_id');
    }

    /**
     * @param $data
     * @return bool
     */
    static function addWeekMv($data){
        $data['created_at'] = TIMESTAMP;
        return self::insert($data);
    }




}
