<?php


/**
 * class OriginalPayModel
 *
 * @property int $id 
 * @property int $uid 用户id
 * @property int $coins 购买时的价格
 * @property int $video_id 视频id
 * @property string $created_at 购买时间
 * @property MhModel $manhua
 *
 * @author xiongba
 * @date 2022-05-17 17:35:31
 *
 * @mixin \Eloquent
 */
class OriginalPayModel extends EloquentModel
{

    protected $table = "original_pay";

    protected $primaryKey = 'id';

    protected $fillable = ['uid', 'coins', 'video_id', 'created_at','original_id'];

    protected $guarded = 'id';

    public $timestamps = false;

    /**
     * @param $uid
     * @param $mh_id
     * @return bool
     */
    static function hasBuy($uid,$video_id){
        return self::where(['uid'=>$uid,'video_id'=>$video_id])->exists();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function video()
    {
        return $this->hasOne(OriginalVideoModel::class, 'id', 'video_id');
    }

    public function original()
    {
        return $this->hasOne(OriginalModel::class, 'id', 'original_id');
    }

    /**
     * @param $uid
     * @param $page
     * @param $limit
     * @return array
     */
    public static function getUserBuyData($uid, $page, $limit)
    {
        $items =  self::query()
            ->where(['uid' => $uid])
            ->with(['video','original'])
            ->forPage($page, $limit)
            ->orderByDesc('id')
            ->get()->toArray();
        $res = [];
        if($items){
            foreach ($items as $item){
                $data = [];
                if($item['original']){
                    $data['id'] = $item['original']['id'];
                    $data['title'] = $item['original']['title'];
                    if($item['video']['type'] == 2){
                        $data['title'] .='第'.$item['video']['sort'].'集';
                        $data['selected'] = $item['video']['sort'];
                    }
                    $data['created_at'] = $item['original']['created_at'];
                    $data['is_series'] = $item['original']['is_series'];
                    $data['cover_full'] = $item['original']['cover_full'];
                    $data['play_count'] = $item['original']['play_count'];
                    $res[] = $data;
                }
            }
        }
        return $res;

    }



}
