<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class FindAppendModel
 *
 * @property int $coins 
 * @property int $created_at 
 * @property int $find_id 
 * @property string $find_uuid 
 * @property string $from_uuid 
 * @property int $id 
 *
 * @author xiongba
 * @date 2020-07-09 23:38:48
 *
 * @mixin \Eloquent
 */
class FindAppendModel extends EloquentModel
{
    use \traits\EventLog;
    protected $table = "find_append";

    protected $primaryKey = 'id';

    protected $fillable = ['coins', 'created_at', 'find_id', 'find_uuid', 'from_uuid'];

    protected $guarded = 'id';

    public $timestamps = false;

    /**
     * 打赏列表关联
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function member()
    {
        return self::hasOne(MemberModel::class, 'uuid', 'from_uuid');
    }

    /**
     * 第一次打赏时间 计算
     * @param $find_id
     * @return self
     */
    public static function getFirstFindAppend($find_id)
    {
        $_key = 'append:newest:' . $find_id;
        return cached($_key)
            ->fetchPhp(function () use ($find_id) {
                return self::where('find_id', $find_id)->orderBy('id')->first();
            },5 * 3600);
    }

    /**
     * @return array
     */
    public function findExpiredInfo()
    {
        $created_at = $this->created_at;
        $expire_at = $created_at + 48 * 3600;
        return [
            'expire_at' => $expire_at,
            'now'       => TIMESTAMP,
        ];
    }


    /**
     * @param $find_id
     * @param $find_uuid
     * @param $from_uuid
     * @param $coins
     * @return FindAppendModel|Model
     */
    public static function addData($find_id, $find_uuid, $from_uuid, $coins)
    {
        return self::create([
            'coins'=>$coins,
            'created_at'=>TIMESTAMP,
            'find_id'=>$find_id,
            'find_uuid'=>$find_uuid,
            'from_uuid'=>$from_uuid
        ]);
    }


    public static function getAppendList($find_id,$page, $limit)
    {
        return self::where('find_id', $find_id)
            ->with(['member:uuid,nickname,thumb,followed_count,auth_status'])
            ->orderByDesc('id')
            ->forPage($page,$limit)
            ->get()
            ->map(function ($item){
                if ($item->member){
                    $item->nickname = $item->member->nickname;
                    $item->thumb = url_avatar($item->member->thumb);
                    $item->followed_count = $item->member->followed_count;
                    $item->auth_status = $item->member->auth_status;
                    $item->created_at = date('Y-m-d H:i', $item->created_at);
                    unset($item->member);
                }else{
                    $item->nickname = '用户已经注销';
                    $item->thumb = url_avatar('');
                    $item->followed_count = 0;
                    $item->auth_status = 0;
                    $item->created_at = date('Y-m-d H:i', time());
                }
                return $item;
            });
    }

    public static function getTotalInfo($find_id)
    {
        return self::selectRaw('count(id) as ct,sum(coins) as sum')
            ->where('find_id', $find_id)
            ->first()
            ->toArray();
    }
}
