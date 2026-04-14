<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class FindLookModel
 *
 * @property int $create_at 
 * @property int $find_id 想看的求片
 * @property int $id 
 * @property string $uuid 谁想看
 *
 * @author xiongba
 * @date 2020-07-21 16:02:41
 *
 * @mixin \Eloquent
 */
class FindLookModel extends Model
{
    use \traits\EventLog;
    protected $table = "find_look";

    protected $primaryKey = 'id';

    protected $fillable = ['create_at', 'find_id', 'uuid'];

    protected $guarded = 'id';

    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function myfind(){
        return $this->hasOne(FindModel::class,'id','find_id');
    }



}
