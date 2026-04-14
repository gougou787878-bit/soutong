<?php


/**
 * class CartoonPayModel
 *
 * @property int $id
 * @property int $uid 用户id
 * @property int $coins 购买时的价格
 * @property int $video_id 视频id
 * @property string $created_at 购买时间
 * @property int $cartoon_id
 *
 * @author xiongba
 * @date 2022-05-17 17:35:31
 *
 * @mixin \Eloquent
 */
class CartoonPayModel extends EloquentModel
{

    protected $table = "cartoon_pay";

    protected $primaryKey = 'id';

    protected $fillable = [
        'uid',
        'coins',
        'video_id',
        'created_at',
        'cartoon_id'
    ];

    protected $guarded = 'id';

    public $timestamps = false;

    /**
     * @param $uid
     * @param $mh_id
     * @return bool
     */
    static function hasBuy($uid, $video_id)
    {
        return self::where([
            'uid' => $uid,
            'video_id' => $video_id
        ])->exists();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function video()
    {
        return $this->hasOne(CartoonChaptersModel::class, 'id', 'video_id');
    }

    public function cartoon()
    {
        return $this->hasOne(CartoonModel::class, 'id', 'cartoon_id');
    }

    /**
     * @param $uid
     * @param $page
     * @param $limit
     * @return array
     */
    public static function getUserBuyData($uid, $page, $limit)
    {
        $items = self::query()
            ->where(['uid' => $uid])
            ->with([
                'video',
                'cartoon'
            ])
            ->forPage($page, $limit)
            ->orderByDesc('id')
            ->get()->toArray();
        $res = [];
        if ($items) {
            foreach ($items as $item) {
                $data = [];
                if ($item['cartoon']) {
                    $data['id'] = $item['cartoon']['id'];
                    $data['title'] = $item['cartoon']['title'];
                    if ($item['video']['type'] == 2) {
                        $data['title'] .= '第' . $item['video']['sort'] . '集';
                        $data['selected'] = $item['video']['sort'];
                    }
                    $data['created_at'] = $item['cartoon']['created_at'];
                    $data['is_series'] = $item['cartoon']['is_series'];
                    $data['cover_full'] = $item['cartoon']['cover_full'];
                    $data['play_count'] = $item['cartoon']['play_count'];
                    $res[] = $data;
                }
            }
        }
        return $res;
    }
}
