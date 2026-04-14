<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class MvBackUserModel
 *
 * @property int $id 
 * @property int $uid 
 * @property int $type 0 默认上传
 * @property string $note 
 * @property string $created_at 
 *
 * @author xiongba
 * @date 2021-01-02 18:13:02
 *
 * @mixin \Eloquent
 */
class MvBackUserModel extends Model
{

    protected $table = "mv_back_user";

    protected $primaryKey = 'id';

    protected $fillable = ['uid', 'type', 'note', 'created_at'];

    protected $guarded = 'id';

    public $timestamps = false;

    const TYPE_UPLOAD = 0;
    const TYPE = [
        self::TYPE_UPLOAD=>'上传'
    ];

    const CACHE_BACK_KEY = 'upload:black:user';
    static function getBackUserList(){
       return  cached(self::CACHE_BACK_KEY)->expired(4000)->serializerJSON()->fetch(function(){
           $data = self::select(['uid'])->get()->pluck('uid')->values();
           if(is_null($data)){
               return [];
           }
           return $data->toArray();
        });
    }
    static function clearCache(){
        return redis()->del(self::CACHE_BACK_KEY);

    }




}
