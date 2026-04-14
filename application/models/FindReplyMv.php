<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class FindReplyMvModel
 *
 * @property string $create_at 
 * @property int $id 
 * @property int $mv_id 推荐视频
 * @property int $reply_id 推荐编号
 * @property string $uuid 推荐人
 *
 * @property-read MvModel $mv
 *
 * @mixin \Eloquent
 */
class FindReplyMvModel extends Model
{
    use \traits\EventLog;
    protected $table = "find_reply_mv";

    protected $primaryKey = 'id';

    protected $fillable = ['create_at', 'mv_id', 'reply_id', 'uuid'];

    protected $guarded = 'id';

    public $timestamps = false;

    const FIND_REPLY_MV_LIST = 'find:reply:mv:list:%d';
    const FIND_REPLY_MV_LIST_GROUP = 'find:reply:mv:list:group';

    public function mv(){
        return self::hasOne(MvModel::class , 'id' , 'mv_id');
    }

    public static function getMvList($reply_id){
        $key = sprintf(self::FIND_REPLY_MV_LIST,$reply_id);
        return cached($key)
            ->group(self::FIND_REPLY_MV_LIST_GROUP)
            ->fetchPhp(function () use ($reply_id){
                return self::with('mv')
                    ->where('reply_id',$reply_id)
                    ->orderByDesc('id')
                    ->get()
                    ->map(function ($item){
                        if (empty($item->mv)){
                            return null;
                        }
                        return $item->mv;
                    })->filter()->values();
            });
    }

}
